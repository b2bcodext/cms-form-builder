<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Entity;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use PHPUnit\Framework\TestCase;

class CmsFormFieldTest extends TestCase
{
    private CmsFormField $field;

    #[\Override]
    protected function setUp(): void
    {
        $this->field = new CmsFormField();
    }

    public function testANewFieldHasNoIdentityAndNoOptions(): void
    {
        self::assertNull($this->field->getId());
        self::assertNull($this->field->getName());
        self::assertNull($this->field->getLabel());
        self::assertNull($this->field->getType());
        self::assertNull($this->field->getSortOrder());
        self::assertNull($this->field->getForm());
        self::assertSame([], $this->field->getOptions());
    }

    public function testScalarAccessorsAreFluent(): void
    {
        $form = new CmsForm();

        self::assertSame($this->field, $this->field->setName('email'));
        self::assertSame($this->field, $this->field->setLabel('E-mail'));
        self::assertSame($this->field, $this->field->setType('email'));
        self::assertSame($this->field, $this->field->setSortOrder(3));
        self::assertSame($this->field, $this->field->setForm($form));

        self::assertSame('email', $this->field->getName());
        self::assertSame('E-mail', $this->field->getLabel());
        self::assertSame('email', $this->field->getType());
        self::assertSame(3, $this->field->getSortOrder());
        self::assertSame($form, $this->field->getForm());
    }

    /**
     * @dataProvider allowedOptionValueDataProvider
     */
    public function testAddOptionAcceptsScalarsArraysAndNull(mixed $value): void
    {
        self::assertSame($this->field, $this->field->addOption('anything', $value));

        self::assertSame($value, $this->field->getOption('anything'));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public function allowedOptionValueDataProvider(): array
    {
        return [
            'string' => ['a string'],
            'int'    => [42],
            'float'  => [1.5],
            'bool'   => [true],
            'array'  => [['a' => 'b']],
            'null'   => [null],
        ];
    }

    public function testAddOptionRejectsAnObject(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Only scalar and arrays are allowed. stdClass given');

        $this->field->addOption('anything', new \stdClass());
    }

    public function testAddOptionRejectsAResourceNamingItsType(): void
    {
        $resource = fopen('php://memory', 'rb');
        try {
            self::expectException(\InvalidArgumentException::class);
            self::expectExceptionMessage('Only scalar and arrays are allowed. resource given');

            $this->field->addOption('anything', $resource);
        } finally {
            fclose($resource);
        }
    }

    public function testSetOptionsMergesIntoTheExistingOptionsRatherThanReplacingThem(): void
    {
        $this->field->addOption('required', true);

        self::assertSame($this->field, $this->field->setOptions(['placeholder' => 'Your e-mail']));

        self::assertSame(['required' => true, 'placeholder' => 'Your e-mail'], $this->field->getOptions());
    }

    public function testSetOptionsOverwritesAnOptionOfTheSameName(): void
    {
        $this->field->setOptions(['required' => true]);
        $this->field->setOptions(['required' => false]);

        self::assertSame(['required' => false], $this->field->getOptions());
    }

    public function testGetOptionReturnsNullForAnUnknownName(): void
    {
        self::assertNull($this->field->getOption('no-such-option'));
    }

    public function testIncrementSortOrderIsANoOpWithoutAForm(): void
    {
        $this->field->incrementSortOrder();

        self::assertNull($this->field->getSortOrder());
    }

    public function testIncrementSortOrderStartsAtOneOnAnEmptyForm(): void
    {
        (new CmsForm())->addField($this->field);

        $this->field->incrementSortOrder();

        self::assertSame(1, $this->field->getSortOrder());
    }

    public function testIncrementSortOrderPlacesTheFieldAfterTheHighestExistingOne(): void
    {
        $form = new CmsForm();
        $form->addField((new CmsFormField())->setName('first')->setSortOrder(1));
        $form->addField((new CmsFormField())->setName('second')->setSortOrder(7));
        $form->addField($this->field->setName('third'));

        $this->field->incrementSortOrder();

        self::assertSame(8, $this->field->getSortOrder());
    }

    public function testPrePersistAssignsSortOrderNameAndTimestamps(): void
    {
        $form = new CmsForm();
        $form->addField((new CmsFormField())->setName('first')->setSortOrder(2));
        $form->addField($this->field);
        $this->field->setLabel('Your E-mail');

        $this->field->prePersist();

        self::assertSame(3, $this->field->getSortOrder());
        self::assertSame('your-e-mail', $this->field->getName());
        self::assertNotNull($this->field->getCreatedAt());
        self::assertNotNull($this->field->getUpdatedAt());
    }

    public function testPrePersistKeepsAnExplicitSortOrderNameAndCreatedAt(): void
    {
        $createdAt = new \DateTime('2020-01-02 03:04:05', new \DateTimeZone('UTC'));
        $this->field->setLabel('Your E-mail')->setName('email')->setSortOrder(9);
        $this->field->setCreatedAt($createdAt);

        $this->field->prePersist();

        self::assertSame(9, $this->field->getSortOrder());
        self::assertSame('email', $this->field->getName());
        self::assertSame($createdAt, $this->field->getCreatedAt());
    }

    public function testPrePersistLeavesTheNameNullWhenThereIsNoLabelToSlugify(): void
    {
        $this->field->prePersist();

        self::assertNull($this->field->getName());
    }

    public function testPreUpdateRefreshesUpdatedAt(): void
    {
        $this->field->preUpdate();

        self::assertNotNull($this->field->getUpdatedAt());
        self::assertSame('UTC', $this->field->getUpdatedAt()->getTimezone()->getName());
    }

    public function testToArrayExposesNameLabelAndOptions(): void
    {
        $this->field->setName('email')->setLabel('E-mail')->setOptions(['required' => true]);

        self::assertSame(
            ['name' => 'email', 'label' => 'E-mail', 'options' => ['required' => true]],
            $this->field->toArray()
        );
    }
}
