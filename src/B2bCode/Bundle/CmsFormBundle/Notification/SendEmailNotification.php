<?php

declare(strict_types=1);

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\Notification;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormResponse;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Sends the configured email notifications for a submitted CMS form response.
 */
class SendEmailNotification implements NotificationInterface
{
    public const DEFAULT_EMAIL_TEMPLATE = 'B2bCodeCmsFormBundle:response_received';

    private MessageProducerInterface $messageProducer;

    private EmailRenderer $renderer;

    private ConfigManager $configManager;

    private ManagerRegistry $doctrine;

    public function __construct(
        MessageProducerInterface $messageProducer,
        EmailRenderer $renderer,
        ConfigManager $configManager,
        ManagerRegistry $doctrine
    ) {
        $this->messageProducer = $messageProducer;
        $this->renderer = $renderer;
        $this->configManager = $configManager;
        $this->doctrine = $doctrine;
    }

    #[\Override]
    public function process(CmsFormResponse $formResponse, array $context = []): void
    {
        $form = $formResponse->getForm();

        if (!$form->isNotificationsEnabled()) {
            return;
        }

        foreach ($form->getNotifications() as $notification) {
            if ($notification->getEmail() === null) {
                continue;
            }

            if ($notification->getTemplate() === null) {
                $emailTemplate = $this->doctrine->getRepository(EmailTemplate::class)
                    ->findOneBy(['name' => static::DEFAULT_EMAIL_TEMPLATE, 'entityName' => CmsFormResponse::class]);
            } else {
                $emailTemplate = $notification->getTemplate();
            }

            if ($emailTemplate === null) {
                // @todo log error
                continue;
            }

            try {
                [$subject, $body] = $this->renderer->compileMessage(
                    $emailTemplate,
                    ['entity' => $formResponse]
                );

                $this->messageProducer->send(SendEmailNotificationTopic::getName(), [
                    'from'        => $this->configManager->get('oro_notification.email_notification_sender_email'),
                    'toEmail'     => $notification->getEmail(),
                    'subject'     => $subject,
                    'body'        => $body,
                    'contentType' => $emailTemplate->getType() === 'txt' ? 'text/plain' : 'text/html'
                ]);
            } catch (\Exception $e) {
                // @todo error log
            }
        }
    }
}
