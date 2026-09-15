<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Validator\Config;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Validator\Config\FormConstraintCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class FormConstraintCollectionTest extends TestCase
{
    private CmsForm $form;
    private FormConstraintCollection $collection;

    #[\Override]
    protected function setUp(): void
    {
        $this->form = new CmsForm();
        $this->form->addField((new CmsFormField())->setName('email'));

        $this->collection = new FormConstraintCollection($this->form);
    }

    public function testAFreshCollectionHoldsNoConstraints(): void
    {
        self::assertSame([], $this->collection->getRawConstraintsForField('email'));
        self::assertSame([], $this->collection->getConstraintsForField('email'));
        self::assertFalse($this->collection->hasFieldAnyConstraints('email'));
    }

    public function testAConstraintIsStoredForAFieldTheFormActuallyHas(): void
    {
        self::assertSame(
            $this->collection,
            $this->collection->addConstraintForField('email', NotBlank::class)
        );

        self::assertSame([[NotBlank::class => null]], $this->collection->getRawConstraintsForField('email'));
        self::assertTrue($this->collection->hasFieldAnyConstraints('email'));
    }

    public function testConstraintsForAFieldTheFormDoesNotHaveAreDropped(): void
    {
        $this->collection->addConstraintForField('no-such-field', NotBlank::class);

        self::assertSame([], $this->collection->getRawConstraintsForField('no-such-field'));
        self::assertFalse($this->collection->hasFieldAnyConstraints('no-such-field'));
    }

    public function testSeveralConstraintsAccumulateForOneFieldInInsertionOrder(): void
    {
        $this->collection->addConstraintForField('email', NotBlank::class);
        $this->collection->addConstraintForField('email', Length::class, ['max' => 10]);

        self::assertSame(
            [[NotBlank::class => null], [Length::class => ['max' => 10]]],
            $this->collection->getRawConstraintsForField('email')
        );
    }

    public function testGetConstraintsForFieldInstantiatesEachConstraintWithItsOptions(): void
    {
        $this->collection->addConstraintForField('email', NotBlank::class);
        $this->collection->addConstraintForField('email', Length::class, ['max' => 10]);

        self::assertEquals(
            [new NotBlank(), new Length(['max' => 10])],
            $this->collection->getConstraintsForField('email')
        );
    }

    public function testAnUnknownConstraintClassIsSkippedInsteadOfFatalling(): void
    {
        $this->collection->addConstraintForField('email', 'Acme\\NoSuch\\Constraint');
        $this->collection->addConstraintForField('email', NotBlank::class);

        self::assertEquals([new NotBlank()], $this->collection->getConstraintsForField('email'));
    }

    public function testAnUnknownFieldYieldsNoConstraintsAtAll(): void
    {
        self::assertSame([], $this->collection->getRawConstraintsForField('unknown'));
        self::assertSame([], $this->collection->getConstraintsForField('unknown'));
        self::assertFalse($this->collection->hasFieldAnyConstraints('unknown'));
    }
}
