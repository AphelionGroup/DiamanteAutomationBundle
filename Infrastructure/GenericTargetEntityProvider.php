<?php
/*
 * Copyright (c) 2015 Eltrino LLC (http://eltrino.com)
 *
 * Licensed under the Open Software License (OSL 3.0).
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://opensource.org/licenses/osl-3.0.php
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eltrino.com so we can send you a copy immediately.
 */

namespace Diamante\AutomationBundle\Infrastructure;


use Diamante\AutomationBundle\Configuration\AutomationConfigurationProvider;
use Diamante\AutomationBundle\Entity\Group;
use Diamante\AutomationBundle\Model\Rule;
use Diamante\AutomationBundle\Rule\Condition\ConditionFactory;
use Diamante\AutomationBundle\Rule\Condition\ConditionInterface;
use Diamante\DeskBundle\Model\Ticket\Priority;
use Diamante\UserBundle\Model\User;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

class GenericTargetEntityProvider
{
    const TARGET_ALIAS = 't';

    protected static $conditionTimeMap
        = [
            'gt'  => 'lt',
            'gte' => 'lte',
            'lt'  => 'gt',
            'lte' => 'gte',
        ];

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ConditionFactory
     */
    protected $conditionFactory;

    /**
     * @var AutomationConfigurationProvider
     */
    protected $configurationProvider;

    /**
     * GenericTargetEntityProvider constructor.
     *
     * @param Registry                        $registry
     * @param ConditionFactory                $conditionFactory
     * @param AutomationConfigurationProvider $configurationProvider
     */
    public function __construct(
        Registry $registry,
        ConditionFactory $conditionFactory,
        AutomationConfigurationProvider $configurationProvider
    ) {
        $this->em = $registry->getManager();
        $this->conditionFactory = $conditionFactory;
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * @param Rule $rule
     * @param      $targetClass
     *
     * @return array|null
     */
    public function getTargets(Rule $rule, $targetClass)
    {
        try {
            $query = $this->buildQuery($rule->getGrouping(), $targetClass);
            $result = $query->getResult();
        } catch (\Exception $e) {
            $result = null;
        }

        return $result;
    }

    /**
     * @param Group $group
     * @param       $targetClass
     *
     * @return \Doctrine\ORM\Query
     */
    protected function buildQuery(Group $group, $targetClass)
    {
        $targetType = $this->configurationProvider->getTargetByClass($targetClass);
        $qb = $this->em->createQueryBuilder();

        $qb->select(self::TARGET_ALIAS)
            ->from($targetClass, self::TARGET_ALIAS);

        $where = $this->buildGroupCondition($qb, $group, $targetType);

        $qb->where($where);

        return $qb->getQuery();
    }

    /**
     * @param QueryBuilder $qb
     * @param Group        $group
     * @param string       $targetType
     *
     * @return Expr\Andx|Expr\Orx
     */
    protected function buildGroupCondition(QueryBuilder $qb, Group $group, $targetType)
    {
        $connector = $group->getConnector();

        switch ($connector) {
            case Group::CONNECTOR_INCLUSIVE:
                $condition = $qb->expr()->andX();
                break;
            case Group::CONNECTOR_EXCLUSIVE:
                $condition = $qb->expr()->orX();
                break;
            default:
                throw new \RuntimeException("Invalid configuration for rule.");
                break;
        }

        if ($group->hasChildren()) {
            foreach ($group->getChildren() as $childGroup) {
                $childGroupCondition = $this->buildGroupCondition($qb, $childGroup, $targetType);
                $condition->add($childGroupCondition);
            }

            return $condition;
        }

        foreach ($group->getConditions() as $conditionDefinition) {
            $conditionDefinition = $this->conditionFactory->getCondition(
                $conditionDefinition->getType(),
                $conditionDefinition->getParameters(),
                $targetType
            );
            /** @var ConditionInterface $conditionDefinition */
            list($property, $expr, $value) = $conditionDefinition->export();
            $compiledCondition = $this->buildCondition($qb, $property, $expr, $value, $targetType);
            $condition->add($compiledCondition);
        }

        return $condition;
    }

    /**
     * @param QueryBuilder $qb
     * @param              $property
     * @param              $expr
     * @param              $value
     * @param string       $targetType
     *
     * @return mixed
     */
    protected function buildCondition(QueryBuilder $qb, $property, $expr, $value, $targetType)
    {
        if (!method_exists($qb->expr(), $expr)) {
            throw new \RuntimeException(sprintf("Operator '%s' does not exist. Please verify export format", $expr));
        }

        if ($this->configurationProvider->isDatetimeProperty($targetType, $property)) {
            $value = new \DateTime(sprintf("-%s hours", $value), new \DateTimeZone("UTC"));
            $expr = static::$conditionTimeMap[$expr];
        }

        $targetClass = $this->configurationProvider->getEntityConfiguration($targetType)->get('class');
        $fieldName = $this->em->getClassMetadata($targetClass)->getFieldName($property);

        $propertyMethod = sprintf("get%sCondition", ucwords($fieldName));
        $propertyAndConditionMethod = sprintf("get%s%sCondition", ucwords($fieldName), ucwords($expr));

        if (method_exists($this, $propertyMethod)) {
            $condition = $this->$propertyMethod($qb, $expr, $fieldName, $value);
        } elseif (method_exists($this, $propertyAndConditionMethod)) {
            $condition = $this->$propertyAndConditionMethod($qb, $expr, $fieldName, $value);
        } else {
            $condition = call_user_func_array(
                [$qb->expr(), $expr],
                [sprintf("%s.%s", self::TARGET_ALIAS, $fieldName), sprintf(":%s", $fieldName)]
            );
            $qb->setParameter($fieldName, $value);
        }

        return $condition;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $expr
     * @param string       $fieldName
     * @param string       $value
     *
     * @return \Doctrine\ORM\Query\Expr\Comparison
     */
    protected function getBranchLikeCondition(QueryBuilder $qb, $expr, $fieldName, $value)
    {
        $qb->innerJoin(sprintf('t.%s', $fieldName), 'b');
        $condition = call_user_func_array(
            [$qb->expr(), $expr],
            ['b.name', sprintf(":%s", $fieldName)]
        );
        $qb->setParameter($fieldName, sprintf("%%%s%%", $value));

        return $condition;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $expr
     * @param string       $fieldName
     * @param string       $value
     *
     * @return \Doctrine\ORM\Query\Expr\Comparison
     */
    protected function getAssigneeCondition(QueryBuilder $qb, $expr, $fieldName, $value)
    {
        $condition = call_user_func_array(
            [$qb->expr(), $expr],
            [sprintf("%s.%s", self::TARGET_ALIAS, $fieldName), sprintf(":%s", $fieldName)]
        );
        $qb->setParameter($fieldName, User::fromString($value)->getId());

        return $condition;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $expr
     * @param string       $fieldName
     * @param string       $value
     *
     * @return \Doctrine\ORM\Query\Expr\Comparison
     */
    protected function getPriorityGteCondition(QueryBuilder $qb, $expr, $fieldName, $value)
    {
        $callback = function ($weight, $rulePropertyWeight) {
            if ($weight >= $rulePropertyWeight) {
                return true;
            }

            return false;
        };

        $condition = $this->getPriorityExpr($qb, $fieldName, $value, $callback);

        return $condition;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $expr
     * @param string       $fieldName
     * @param string       $value
     *
     * @return \Doctrine\ORM\Query\Expr\Comparison
     */
    protected function getPriorityGtCondition(QueryBuilder $qb, $expr, $fieldName, $value)
    {
        $callback = function ($weight, $rulePropertyWeight) {
            if ($weight > $rulePropertyWeight) {
                return true;
            }

            return false;
        };

        $condition = $this->getPriorityExpr($qb, $fieldName, $value, $callback);

        return $condition;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $expr
     * @param string       $fieldName
     * @param string       $value
     *
     * @return \Doctrine\ORM\Query\Expr\Comparison
     */
    protected function getPriorityLteCondition(QueryBuilder $qb, $expr, $fieldName, $value)
    {
        $callback = function ($weight, $rulePropertyWeight) {
            if ($weight <= $rulePropertyWeight) {
                return true;
            }

            return false;
        };

        $condition = $this->getPriorityExpr($qb, $fieldName, $value, $callback);

        return $condition;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $expr
     * @param string       $fieldName
     * @param string       $value
     *
     * @return \Doctrine\ORM\Query\Expr\Comparison
     */
    protected function getPriorityLtCondition(QueryBuilder $qb, $expr, $fieldName, $value)
    {
        $callback = function ($weight, $rulePropertyWeight) {
            if ($weight < $rulePropertyWeight) {
                return true;
            }

            return false;
        };

        $condition = $this->getPriorityExpr($qb, $fieldName, $value, $callback);

        return $condition;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $fieldName
     * @param string       $value
     * @param callable     $comparisonCallback
     *
     * @return \Doctrine\ORM\Query\Expr\Comparison
     */
    private function getPriorityExpr(QueryBuilder $qb, $fieldName, $value, $comparisonCallback)
    {
        $properties = [];
        $weightList = Priority::getWeightList();
        $rulePropertyWeight = Priority::getWeight($value);

        foreach ($weightList as $weight => $property) {
            if ($comparisonCallback($weight, $rulePropertyWeight)) {
                $properties[] = $property;
            }
        }

        $condition = $qb->expr()->in(sprintf("%s.%s", self::TARGET_ALIAS, $fieldName), ':priority');
        $qb->setParameter('priority', $properties);

        return $condition;
    }
}