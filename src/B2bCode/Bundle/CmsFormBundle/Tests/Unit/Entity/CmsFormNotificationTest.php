<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Entity;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormNotification;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use PHPUnit\Framework\TestCase;

class CmsFormNotificationTest extends TestCase
{
    private CmsFormNotification $notification;

    #[\Override]
    protected function setUp(): void
    {
        $this->notification = new CmsFormNotification();
    }

    public function testANewNotificationHasNoRecipientAndNoTemplate(): void
    {
        self::assertNull($this->notification->getId());
        self::assertNull($this->notification->getForm());
        self::assertNull($this->notification->getEmail());
        self::assertNull($this->notification->getTemplate());
    }

    public function testAccessorsAreFluent(): void
    {
        $form = new CmsForm();
        $template = new EmailTemplate();

        self::assertSame($this->notification, $this->notification->setForm($form));
        self::assertSame($this->notification, $this->notification->setEmail('sales@example.org'));
        self::assertSame($this->notification, $this->notification->setTemplate($template));

        self::assertSame($form, $this->notification->getForm());
        self::assertSame('sales@example.org', $this->notification->getEmail());
        self::assertSame($template, $this->notification->getTemplate());
    }

    public function testTheTemplateAndTheEmailCanBeClearedBackToNull(): void
    {
        $this->notification->setEmail('sales@example.org')->setTemplate(new EmailTemplate());

        $this->notification->setEmail(null);
        $this->notification->setTemplate(null);

        self::assertNull($this->notification->getEmail());
        self::assertNull($this->notification->getTemplate());
    }
}
