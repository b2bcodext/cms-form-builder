<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Entity;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFieldResponse;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormResponse;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use PHPUnit\Framework\TestCase;

class CmsFormResponseTest extends TestCase
{
    private CmsFormResponse $response;

    #[\Override]
    protected function setUp(): void
    {
        $this->response = new CmsFormResponse();
    }

    public function testANewResponseIsUnresolvedWithoutFieldResponses(): void
    {
        self::assertNull($this->response->getId());
        self::assertNull($this->response->getForm());
        self::assertNull($this->response->getVisitor());
        self::assertInstanceOf(ArrayCollection::class, $this->response->getFieldResponses());
        self::assertCount(0, $this->response->getFieldResponses());
        self::assertFalse($this->response->isResolved());
    }

    public function testFormAndVisitorAccessorsAreFluent(): void
    {
        $form = new CmsForm();
        $visitor = new CustomerVisitor();

        self::assertSame($this->response, $this->response->setForm($form));
        self::assertSame($this->response, $this->response->setVisitor($visitor));

        self::assertSame($form, $this->response->getForm());
        self::assertSame($visitor, $this->response->getVisitor());

        $this->response->setVisitor(null);
        self::assertNull($this->response->getVisitor());
    }

    /**
     * @dataProvider resolvedDataProvider
     */
    public function testSetResolvedCastsToBool(?bool $given, bool $expected): void
    {
        self::assertSame($this->response, $this->response->setResolved($given));

        self::assertSame($expected, $this->response->isResolved());
    }

    /**
     * @return array<string, array{bool|null, bool}>
     */
    public function resolvedDataProvider(): array
    {
        return [
            'true'  => [true, true],
            'false' => [false, false],
            'null becomes false' => [null, false],
        ];
    }

    public function testAddFieldResponseStoresItAndSetsTheBackReference(): void
    {
        $fieldResponse = new CmsFieldResponse();

        self::assertSame($this->response, $this->response->addFieldResponse($fieldResponse));

        self::assertCount(1, $this->response->getFieldResponses());
        self::assertSame($this->response, $fieldResponse->getFormResponse());
    }

    public function testAddFieldResponseIsIdempotentForTheSameInstance(): void
    {
        $fieldResponse = new CmsFieldResponse();

        $this->response->addFieldResponse($fieldResponse);
        $this->response->addFieldResponse($fieldResponse);

        self::assertCount(1, $this->response->getFieldResponses());
    }

    public function testToArrayNestsTheFormAndEveryFieldResponse(): void
    {
        $field = (new CmsFormField())->setName('email')->setLabel('E-mail');
        $form = (new CmsForm())->setName('Contact us');
        $form->addField($field);

        $this->response->setForm($form);
        $this->response->addFieldResponse(
            (new CmsFieldResponse())->setField($field)->setValue('john@example.org')
        );

        self::assertSame(
            [
                'form' => [
                    'name'   => 'Contact us',
                    'fields' => [['name' => 'email', 'label' => 'E-mail', 'options' => []]],
                ],
                'fieldResponses' => [
                    [
                        'field'        => ['name' => 'email', 'label' => 'E-mail', 'options' => []],
                        'rawValue'     => 'john@example.org',
                        'value'        => 'john@example.org',
                        'valueAsLabel' => 'john@example.org',
                    ],
                ],
            ],
            $this->response->toArray()
        );
    }
}
