<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Functional\Notification;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormResponse;
use B2bCode\Bundle\CmsFormBundle\Notification\NotificationInterface;
use B2bCode\Bundle\CmsFormBundle\Notification\SendEmailNotification;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTopic;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * The notification path relies on three things the unit suite has to fake: the email template the
 * bundle's data migration installs, the sandboxed email renderer (which must know
 * b2b_code_form_response_array) and the message producer.
 *
 * @dbIsolationPerTest
 */
class SendEmailNotificationTest extends WebTestCase
{
    use MessageQueueExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(['@B2bCodeCmsFormBundle/Tests/Functional/DataFixtures/cms_forms.yml']);
    }

    public function testTheDefaultEmailTemplateIsInstalledByTheDataMigration(): void
    {
        $template = $this->findDefaultTemplate();

        self::assertInstanceOf(EmailTemplate::class, $template);
        self::assertSame('Your form has new responses', $template->getSubject());
        self::assertSame('html', $template->getType());
    }

    public function testTheRenderedDefaultTemplateIsQueuedForEveryNotificationEmail(): void
    {
        $formResponse = $this->getReference('form1_response');
        self::assertInstanceOf(CmsFormResponse::class, $formResponse);

        $this->getNotification()->process($formResponse);

        $messages = self::getSentMessagesByTopic(SendEmailNotificationTopic::getName());
        self::assertCount(1, $messages);

        $message = $messages[0];
        self::assertSame('daniel@b2bcodext.com', $message['toEmail']);
        self::assertSame('Your form has new responses', $message['subject']);
        self::assertSame('text/html', $message['contentType']);
        self::assertSame(
            self::getConfigManager()->get('oro_notification.email_notification_sender_email'),
            $message['from']
        );

        // rendered through the sandboxed email environment, i.e. b2b_code_form_response_array resolved
        self::assertStringContainsString('Your form Form preview enabled has new responses.', $message['body']);
        self::assertStringContainsString('<span>Last name:</span><span>NameDoe</span>', $message['body']);
        self::assertStringContainsString('<span>Email:</span><span>doe.xx@example.com</span>', $message['body']);
        // the dropdown answer is rendered as its label, not as the stored value
        self::assertStringContainsString('<span>Contact reason:</span><span>Have a complaint</span>', $message['body']);
    }

    public function testNothingIsQueuedWhenNotificationsAreDisabledOnTheForm(): void
    {
        $formResponse = $this->getReference('form1_response');
        self::assertInstanceOf(CmsFormResponse::class, $formResponse);
        $formResponse->getForm()->setNotificationsEnabled(false);

        $this->getNotification()->process($formResponse);

        self::assertMessagesEmpty(SendEmailNotificationTopic::getName());
    }

    public function testANotificationWithoutAnEmailIsSkipped(): void
    {
        $formResponse = $this->getReference('form1_response');
        self::assertInstanceOf(CmsFormResponse::class, $formResponse);

        foreach ($formResponse->getForm()->getNotifications() as $notification) {
            $notification->setEmail(null);
        }
        $this->getEntityManager()->flush();

        $this->getNotification()->process($formResponse);

        self::assertMessagesEmpty(SendEmailNotificationTopic::getName());
    }

    private function findDefaultTemplate(): ?EmailTemplate
    {
        return $this->getEntityManager()->getRepository(EmailTemplate::class)->findOneBy([
            'name' => SendEmailNotification::DEFAULT_EMAIL_TEMPLATE,
            'entityName' => CmsFormResponse::class,
        ]);
    }

    private function getNotification(): NotificationInterface
    {
        return self::getContainer()->get(NotificationInterface::class);
    }

    private static function getConfigManager(): ConfigManager
    {
        return self::getContainer()->get('oro_config.global');
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(CmsFormResponse::class);
    }
}
