<?php
/*
 * Copyright (c) 2014 Eltrino LLC (http://eltrino.com)
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
namespace Diamante\DeskBundle\Entity;

use LogicException;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;
use Oro\Bundle\DataAuditBundle\Model\ExtendAuditField;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Diamante\DeskBundle\Model\Shared\Entity;

/**
 * @ORM\Entity
 * @ORM\Table(name="diamante_audit_field")
 * @Config(mode=Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager::MODE_HIDDEN)
 */
class AuditField extends ExtendAuditField implements Entity
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Audit
     *
     * @ORM\ManyToOne(targetEntity="Audit", inversedBy="fields", cascade={"persist"})
     * @ORM\JoinColumn(name="audit_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $audit;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     */
    protected $field;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default"=true})
     */
    protected $visible = true;

    /**
     * @var string
     *
     * @ORM\Column(name="data_type", type="string", nullable=false)
     */
    protected $dataType;

    /**
     * @var int
     *
     * @ORM\Column(name="old_integer", type="bigint", nullable=true)
     */
    protected $oldInteger;

    /**
     * @var float
     *
     * @ORM\Column(name="old_float", type="float", nullable=true)
     */
    protected $oldFloat;

    /**
     * @var boolean
     *
     * @ORM\Column(name="old_boolean", type="boolean", nullable=true)
     */
    protected $oldBoolean;

    /**
     * @var string
     *
     * @ORM\Column(name="old_text", type="text", nullable=true)
     */
    protected $oldText;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="old_date", type="date", nullable=true)
     */
    protected $oldDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="old_time", type="time", nullable=true)
     */
    protected $oldTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="old_datetime", type="datetime", nullable=true)
     */
    protected $oldDatetime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="old_datetimetz", type="datetimetz", nullable=true)
     */
    protected $oldDatetimetz;

    /**
     * @var object
     *
     * @ORM\Column(name="old_object", type="object", nullable=true)
     */
    protected $oldObject;

    /**
     * @var array
     *
     * @ORM\Column(name="old_array", type="array", nullable=true)
     */
    protected $oldArray;

    /**
     * @var array
     *
     * @ORM\Column(name="old_simplearray", type="simple_array", nullable=true)
     */
    protected $oldSimplearray;

    /**
     * @var array
     *
     * @ORM\Column(name="old_jsonarray", type="json_array", nullable=true)
     */
    protected $oldJsonarray;

    /**
     * @var int
     *
     * @ORM\Column(name="new_integer", type="bigint", nullable=true)
     */
    protected $newInteger;

    /**
     * @var int
     *
     * @ORM\Column(name="new_float", type="float", nullable=true)
     */
    protected $newFloat;

    /**
     * @var bool
     *
     * @ORM\Column(name="new_boolean", type="boolean", nullable=true)
     */
    protected $newBoolean;

    /**
     * @var string
     *
     * @ORM\Column(name="new_text", type="text", nullable=true)
     */
    protected $newText;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="new_date", type="date", nullable=true)
     */
    protected $newDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="new_time", type="time", nullable=true)
     */
    protected $newTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="new_datetime", type="datetime", nullable=true)
     */
    protected $newDatetime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="new_datetimetz", type="datetimetz", nullable=true)
     */
    protected $newDatetimetz;

    /**
     * @var object
     *
     * @ORM\Column(name="new_object", type="object", nullable=true)
     */
    protected $newObject;

    /**
     * @var array
     *
     * @ORM\Column(name="new_array", type="array", nullable=true)
     */
    protected $newArray;

    /**
     * @var array
     *
     * @ORM\Column(name="new_simplearray", type="simple_array", nullable=true)
     */
    protected $newSimplearray;

    /**
     * @var array
     *
     * @ORM\Column(name="new_jsonarray", type="json_array", nullable=true)
     */
    protected $newJsonarray;

    /**
     * @param Audit $audit
     * @param string $field
     * @param string $dataType
     * @param mixed $newValue
     * @param mixed $oldValue
     */
    public function __construct(Audit $audit, $field, $dataType, $newValue, $oldValue)
    {
        $this->audit = $audit;
        $this->field = $field;

        $this->dataType = AuditFieldTypeRegistry::getAuditType($dataType);

        $this->setOldValue($oldValue);
        $this->setNewValue($newValue);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * @return Audit
     */
    public function getAudit()
    {
        return $this->audit;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return mixed
     */
    public function getNewValue()
    {
        $propertyName = $this->getPropertyName('new');

        return $this->$propertyName;
    }

    /**
     * @return mixed
     */
    public function getOldValue()
    {
        $propertyName = $this->getPropertyName('old');

        return $this->$propertyName;
    }

    /**
     * @return string
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * @param $value
     *
     * @return $this
     */
    protected function setOldValue($value)
    {
        $propertyValue = $this->getPropertyName('old');
        $this->$propertyValue = $value;

        return $this;
    }

    /**
     * @param $value
     *
     * @return $this
     */
    protected function setNewValue($value)
    {
        $propertyValue = $this->getPropertyName('new');
        $this->$propertyValue = $value;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function getPropertyName($type)
    {
        $name = sprintf('%s%s', $type, ucfirst($this->dataType));
        if (property_exists(get_class($this), $name)) {
            return $name;
        }

        $customName = sprintf('%s_%s', $type, $this->dataType);
        if (property_exists(get_class($this), $customName)) {
            return $customName;
        }

        throw new LogicException(sprintf(
            'Neither property "%s" nor "%s" was found. Maybe you forget to add migration?',
            $name,
            $customName
        ));
    }

    public static function getClassName()
    {
        return __CLASS__;
    }
}
