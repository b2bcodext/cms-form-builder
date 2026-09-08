<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Entity;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormNotification;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class CmsFormTest extends TestCase
{
    private CmsForm $form;

    #[\Override]
    protected function setUp(): void
    {
        $this->form = new CmsForm();
    }

    public function testANewFormStartsWithEmptyCollectionsAndDisabledFlags(): void
    {
        self::assertInstanceOf(ArrayCollection::class, $this->form->getFields());
        self::assertCount(0, $this->form->getFields());
        self::assertInstanceOf(ArrayCollection::class, $this->form->getNotifications());
        self::assertCount(0, $this->form->getNotifications());
        self::assertFalse($this->form->isPreviewEnabled());
        self::assertFalse($this->form->isNotificationsEnabled());
        self::assertNull($this->form->getId());
        self::assertNull($this->form->getRedirectUrl());
        self::assertNull($this->form->uuid());
    }

    public function testScalarAccessors(): void
    {
        self::assertSame($this->form, $this->form->setName('Contact us'));
        self::assertSame($this->form, $this->form->setAlias('contact-us'));
        self::assertSame($this->form, $this->form->setPreviewEnabled(true));
        self::assertSame($this->form, $this->form->setNotificationsEnabled(true));
        $this->form->setRedirectUrl('https://example.org/thanks');

        self::assertSame('Contact us', $this->form->getName());
        self::assertSame('contact-us', $this->form->getAlias());
        self::assertTrue($this->form->isPreviewEnabled());
        self::assertTrue($this->form->isNotificationsEnabled());
        self::assertSame('https://example.org/thanks', $this->form->getRedirectUrl());
    }

    public function testAddFieldStoresTheFieldAndSetsTheBackReference(): void
    {
        $field = (new CmsFormField())->setName('email');

        self::assertSame($this->form, $this->form->addField($field));

        self::assertCount(1, $this->form->getFields());
        self::assertSame($field, $this->form->getFields()->first());
        self::assertSame($this->form, $field->getForm());
    }

    public function testAddFieldIsIdempotentForTheSameInstance(): void
    {
        $field = (new CmsFormField())->setName('email');

        $this->form->addField($field);
        $this->form->addField($field);

        self::assertCount(1, $this->form->getFields());
    }

    public function testSetFieldsAddsEveryFieldOfTheIterable(): void
    {
        $first = (new CmsFormField())->setName('email');
        $second = (new CmsFormField())->setName('message');

        $this->form->setFields(new ArrayCollection([$first, $second]));

        self::assertSame([$first, $second], $this->form->getFields()->toArray());
        self::assertSame($this->form, $second->getForm());
    }

    public function testGetFieldAndHasFieldLookUpByName(): void
    {
        $email = (new CmsFormField())->setName('email');
        $this->form->addField($email);

        self::assertSame($email, $this->form->getField('email'));
        self::assertTrue($this->form->hasField('email'));

        self::assertNull($this->form->getField('message'));
        self::assertFalse($this->form->hasField('message'));
    }

    public function testNotificationsAreAddedWithABackReferenceAndCanBeRemoved(): void
    {
        $notification = (new CmsFormNotification())->setEmail('sales@example.org');

        self::assertSame($this->form, $this->form->addNotification($notification));
        self::assertCount(1, $this->form->getNotifications());
        self::assertSame($this->form, $notification->getForm());

        $this->form->addNotification($notification);
        self::assertCount(1, $this->form->getNotifications());

        self::assertSame($this->form, $this->form->removeNotification($notification));
        self::assertCount(0, $this->form->getNotifications());
    }

    public function testSetNotificationsAddsEveryNotificationOfTheIterable(): void
    {
        $first = (new CmsFormNotification())->setEmail('a@example.org');
        $second = (new CmsFormNotification())->setEmail('b@example.org');

        self::assertSame($this->form, $this->form->setNotifications([$first, $second]));

        self::assertSame([$first, $second], $this->form->getNotifications()->toArray());
    }

    public function testPrePersistDerivesTheAliasFromTheNameAndGeneratesAUuid(): void
    {
        $this->form->setName('Contact Us Today');

        $this->form->prePersist();

        self::assertSame('contact-us-today', $this->form->getAlias());
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $this->form->uuid()
        );
        self::assertNotNull($this->form->getCreatedAt());
        self::assertNotNull($this->form->getUpdatedAt());
        self::assertSame('UTC', $this->form->getCreatedAt()->getTimezone()->getName());
    }

    public function testPrePersistKeepsAnExplicitAliasAndCreatedAt(): void
    {
        $createdAt = new \DateTime('2020-01-02 03:04:05', new \DateTimeZone('UTC'));
        $this->form->setName('Contact Us')->setAlias('my-alias');
        $this->form->setCreatedAt($createdAt);

        $this->form->prePersist();

        self::assertSame('my-alias', $this->form->getAlias());
        self::assertSame($createdAt, $this->form->getCreatedAt());
    }

    public function testPrePersistDoesNotOverwriteAnExistingUuid(): void
    {
        $this->form->setName('Contact Us')->setAlias('contact-us');
        $this->form->prePersist();
        $uuid = $this->form->uuid();

        $this->form->prePersist();

        self::assertSame($uuid, $this->form->uuid());
    }

    public function testPreUpdateRefreshesUpdatedAtOnly(): void
    {
        $this->form->setUpdatedAt(new \DateTime('2020-01-02 03:04:05', new \DateTimeZone('UTC')));

        $this->form->preUpdate();

        self::assertGreaterThan(
            new \DateTime('2020-01-02 03:04:05', new \DateTimeZone('UTC')),
            $this->form->getUpdatedAt()
        );
        self::assertNull($this->form->getCreatedAt());
    }

    public function testToArrayExposesTheNameAndEveryFieldAsAnArray(): void
    {
        $this->form->setName('Contact us');
        $this->form->addField(
            (new CmsFormField())->setName('email')->setLabel('E-mail')->setOptions(['required' => true])
        );

        self::assertSame(
            [
                'name'   => 'Contact us',
                'fields' => [
                    ['name' => 'email', 'label' => 'E-mail', 'options' => ['required' => true]],
                ],
            ],
            $this->form->toArray()
        );
    }
}
