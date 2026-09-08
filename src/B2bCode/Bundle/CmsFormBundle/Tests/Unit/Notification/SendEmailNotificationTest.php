<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Notification;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormNotification;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormResponse;
use B2bCode\Bundle\CmsFormBundle\Notification\SendEmailNotification;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SendEmailNotificationTest extends TestCase
{
    private const SENDER = 'noreply@example.org';

    private MessageProducerInterface&MockObject $messageProducer;
    private EmailRenderer&MockObject $renderer;
    private ConfigManager&MockObject $configManager;
    private ManagerRegistry&MockObject $doctrine;
    private ObjectRepository&MockObject $templateRepository;
    private SendEmailNotification $notifier;

    #[\Override]
    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->renderer = $this->createMock(EmailRenderer::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->templateRepository = $this->createMock(ObjectRepository::class);

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(EmailTemplate::class)
            ->willReturn($this->templateRepository);
        $this->configManager->expects(self::any())
            ->method('get')
            ->with('oro_notification.email_notification_sender_email')
            ->willReturn(self::SENDER);

        $this->notifier = new SendEmailNotification(
            $this->messageProducer,
            $this->renderer,
            $this->configManager,
            $this->doctrine
        );
    }

    public function testNothingIsSentWhenNotificationsAreDisabledOnTheForm(): void
    {
        $form = $this->form(false);
        $form->addNotification((new CmsFormNotification())->setEmail('sales@example.org'));

        $this->renderer->expects(self::never())->method('compileMessage');
        $this->messageProducer->expects(self::never())->method('send');

        $this->notifier->process($this->response($form));
    }

    public function testNothingIsSentWhenTheFormHasNoNotifications(): void
    {
        $this->messageProducer->expects(self::never())->method('send');

        $this->notifier->process($this->response($this->form(true)));
    }

    public function testANotificationWithoutARecipientIsSkipped(): void
    {
        $form = $this->form(true);
        $form->addNotification(new CmsFormNotification());

        $this->messageProducer->expects(self::never())->method('send');
        $this->templateRepository->expects(self::never())->method('findOneBy');

        $this->notifier->process($this->response($form));
    }

    public function testTheNotificationTemplateIsRenderedAndQueuedAsHtml(): void
    {
        $template = $this->template('html');
        $form = $this->form(true);
        $form->addNotification((new CmsFormNotification())->setEmail('sales@example.org')->setTemplate($template));
        $formResponse = $this->response($form);

        $this->renderer->expects(self::once())
            ->method('compileMessage')
            ->with($template, ['entity' => $formResponse])
            ->willReturn(['New response', '<b>body</b>']);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(SendEmailNotificationTopic::getName(), [
                'from'        => self::SENDER,
                'toEmail'     => 'sales@example.org',
                'subject'     => 'New response',
                'body'        => '<b>body</b>',
                'contentType' => 'text/html',
            ]);

        $this->notifier->process($formResponse);
    }

    public function testAPlainTextTemplateIsQueuedWithATextContentType(): void
    {
        $form = $this->form(true);
        $form->addNotification(
            (new CmsFormNotification())->setEmail('sales@example.org')->setTemplate($this->template('txt'))
        );

        $this->renderer->method('compileMessage')->willReturn(['New response', 'body']);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                SendEmailNotificationTopic::getName(),
                self::callback(static fn (array $message): bool => $message['contentType'] === 'text/plain')
            );

        $this->notifier->process($this->response($form));
    }

    public function testANotificationWithoutATemplateFallsBackToTheBundleDefaultTemplate(): void
    {
        $default = $this->template('html');
        $form = $this->form(true);
        $form->addNotification((new CmsFormNotification())->setEmail('sales@example.org'));

        $this->templateRepository->expects(self::once())
            ->method('findOneBy')
            ->with([
                'name'       => SendEmailNotification::DEFAULT_EMAIL_TEMPLATE,
                'entityName' => CmsFormResponse::class,
            ])
            ->willReturn($default);

        $this->renderer->expects(self::once())
            ->method('compileMessage')
            ->with($default, self::anything())
            ->willReturn(['New response', 'body']);
        $this->messageProducer->expects(self::once())->method('send');

        $this->notifier->process($this->response($form));
    }

    public function testNothingIsSentWhenEvenTheDefaultTemplateIsMissing(): void
    {
        $form = $this->form(true);
        $form->addNotification((new CmsFormNotification())->setEmail('sales@example.org'));

        $this->templateRepository->expects(self::once())->method('findOneBy')->willReturn(null);
        $this->renderer->expects(self::never())->method('compileMessage');
        $this->messageProducer->expects(self::never())->method('send');

        $this->notifier->process($this->response($form));
    }

    public function testARenderingFailureIsSwallowedSoTheRemainingRecipientsAreStillServed(): void
    {
        $failing = $this->template('html');
        $working = $this->template('html');
        $form = $this->form(true);
        $form->addNotification((new CmsFormNotification())->setEmail('broken@example.org')->setTemplate($failing));
        $form->addNotification((new CmsFormNotification())->setEmail('sales@example.org')->setTemplate($working));

        $this->renderer->expects(self::exactly(2))
            ->method('compileMessage')
            ->willReturnCallback(static function (EmailTemplate $template) use ($failing): array {
                if ($template === $failing) {
                    throw new \RuntimeException('template is broken');
                }

                return ['New response', 'body'];
            });

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                SendEmailNotificationTopic::getName(),
                self::callback(static fn (array $m): bool => $m['toEmail'] === 'sales@example.org')
            );

        $this->notifier->process($this->response($form));
    }

    public function testEveryConfiguredRecipientGetsItsOwnMessage(): void
    {
        $form = $this->form(true);
        $form->addNotification((new CmsFormNotification())->setEmail('a@example.org')->setTemplate($this->template()));
        $form->addNotification((new CmsFormNotification())->setEmail('b@example.org')->setTemplate($this->template()));

        $this->renderer->method('compileMessage')->willReturn(['New response', 'body']);

        $recipients = [];
        $this->messageProducer->expects(self::exactly(2))
            ->method('send')
            ->willReturnCallback(static function (string $topic, array $message) use (&$recipients): void {
                $recipients[] = $message['toEmail'];
            });

        $this->notifier->process($this->response($form));

        self::assertSame(['a@example.org', 'b@example.org'], $recipients);
    }

    private function template(string $type = 'html'): EmailTemplate
    {
        return (new EmailTemplate())->setType($type);
    }

    private function form(bool $notificationsEnabled): CmsForm
    {
        return (new CmsForm())->setName('Contact us')->setNotificationsEnabled($notificationsEnabled);
    }

    private function response(CmsForm $form): CmsFormResponse
    {
        return (new CmsFormResponse())->setForm($form);
    }
}
