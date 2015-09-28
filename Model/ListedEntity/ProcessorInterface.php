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

namespace Diamante\AutomationBundle\Model\ListedEntity;

use Diamante\AutomationBundle\Action\Strategy\EmailNotificationStrategy\EmailNotification;
use Diamante\DeskBundle\Model\Shared\Entity;
use Diamante\DeskBundle\Model\Ticket\Ticket;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use \Oro\Bundle\DataAuditBundle\Entity\Repository\AuditRepository;

/**
 * Interface ProcessorInterface
 * @package Diamante\AutomationBundle\Model\ListedEntity
 */
interface ProcessorInterface
{
    /**
     * @return array
     */
    public function getEntityEmailTemplates();

    /**
     * @param Entity           $entity
     * @param AbstractLogEntry $entityLog
     * @param AuditRepository  $repository
     *
     * @return mixed
     */
    public function getEntityChanges(Entity $entity, AbstractLogEntry $entityLog, AuditRepository $repository);

    /**
     * @return string
     */
    public function getEntityCreateText();

    /**
     * @return string
     */
    public function getEntityUpdateText();

    /**
     * @return string
     */
    public function getEntityDeleteText();

    /**
     * @param Entity $entity
     * @return string
     */
    public function formatEntityEmailSubject(Entity $entity);

    /**
     * @param EmailNotification $notification
     * @param string $recipientEmail
     * @return array
     */
    public function getEmailTemplateOptions(EmailNotification $notification, $recipientEmail);

    /**
     * @param Entity $entity
     * @return Ticket
     */
    public function getTicketEntity(Entity $entity);
}