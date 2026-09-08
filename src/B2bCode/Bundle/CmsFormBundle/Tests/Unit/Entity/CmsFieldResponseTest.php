<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Entity;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFieldResponse;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormResponse;
use PHPUnit\Framework\TestCase;

class CmsFieldResponseTest extends TestCase
{
    private CmsFieldResponse $fieldResponse;

    #[\Override]
    protected function setUp(): void
    {
        $this->fieldResponse = new CmsFieldResponse();
    }

    public function testANewFieldResponseIsEmpty(): void
    {
        self::assertNull($this->fieldResponse->getId());
        self::assertNull($this->fieldResponse->getField());
        self::assertNull($this->fieldResponse->getFormResponse());
        self::assertNull($this->fieldResponse->getRawValue());
    }

    public function testAccessorsAreFluent(): void
    {
        $field = new CmsFormField();
        $formResponse = new CmsFormResponse();

        self::assertSame($this->fieldResponse, $this->fieldResponse->setField($field));
        self::assertSame($this->fieldResponse, $this->fieldResponse->setFormResponse($formResponse));
        self::assertSame($this->fieldResponse, $this->fieldResponse->setValue('pl'));

        self::assertSame($field, $this->fieldResponse->getField());
        self::assertSame($formResponse, $this->fieldResponse->getFormResponse());
        self::assertSame('pl', $this->fieldResponse->getRawValue());
    }

    public function testGetValueReturnsTheRawValueForAPlainField(): void
    {
        $this->fieldResponse->setField($this->field([]))->setValue('john@example.org');

        self::assertSame('john@example.org', $this->fieldResponse->getValue());
        self::assertSame('john@example.org', $this->fieldResponse->getValue(true));
    }

    public function testGetValueIgnoresTheChoiceLabelsUnlessAsLabelIsRequested(): void
    {
        $this->fieldResponse->setField($this->field(['choices' => ['Poland' => 'pl']]))->setValue('pl');

        self::assertSame('pl', $this->fieldResponse->getValue());
    }

    public function testGetValueMapsASingleValueBackToItsChoiceLabel(): void
    {
        $this->fieldResponse->setField($this->field(['choices' => ['Poland' => 'pl', 'Germany' => 'de']]))
            ->setValue('de');

        self::assertSame('Germany', $this->fieldResponse->getValue(true));
    }

    public function testGetValueFallsBackToTheRawValueWhenItMatchesNoChoice(): void
    {
        $this->fieldResponse->setField($this->field(['choices' => ['Poland' => 'pl']]))->setValue('fr');

        self::assertSame('fr', $this->fieldResponse->getValue(true));
    }

    public function testGetValueDecodesAMultipleFieldIntoAList(): void
    {
        $this->fieldResponse->setField($this->field(['multiple' => true]))->setValue('["pl","de"]');

        self::assertSame(['pl', 'de'], $this->fieldResponse->getValue());
    }

    public function testGetValueMapsEveryEntryOfAMultipleFieldBackToItsLabel(): void
    {
        $field = $this->field(['multiple' => true, 'choices' => ['Poland' => 'pl', 'Germany' => 'de']]);
        $this->fieldResponse->setField($field)->setValue('["pl","de"]');

        self::assertSame(['Poland', 'Germany'], $this->fieldResponse->getValue(true));
    }

    public function testGetValueKeepsUnmappedEntriesOfAMultipleFieldAsIs(): void
    {
        $field = $this->field(['multiple' => true, 'choices' => ['Poland' => 'pl']]);
        $this->fieldResponse->setField($field)->setValue('["pl","fr"]');

        self::assertSame(['Poland', 'fr'], $this->fieldResponse->getValue(true));
    }

    public function testGetValueReturnsAnEmptyListForAnEmptyMultipleSelection(): void
    {
        $this->fieldResponse->setField($this->field(['multiple' => true]))->setValue('[]');

        self::assertSame([], $this->fieldResponse->getValue());
    }

    public function testANullValueIsPreserved(): void
    {
        $this->fieldResponse->setField($this->field([]))->setValue(null);

        self::assertNull($this->fieldResponse->getRawValue());
        self::assertNull($this->fieldResponse->getValue());
    }

    public function testToArrayExposesTheFieldAndBothValueRenderings(): void
    {
        $field = $this->field(['choices' => ['Poland' => 'pl']]);
        $field->setName('country')->setLabel('Country');
        $this->fieldResponse->setField($field)->setValue('pl');

        self::assertSame(
            [
                'field' => [
                    'name'    => 'country',
                    'label'   => 'Country',
                    'options' => ['choices' => ['Poland' => 'pl']],
                ],
                'rawValue'     => 'pl',
                'value'        => 'pl',
                'valueAsLabel' => 'Poland',
            ],
            $this->fieldResponse->toArray()
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    private function field(array $options): CmsFormField
    {
        return (new CmsFormField())->setOptions($options);
    }
}
