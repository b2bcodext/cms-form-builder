<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Twig;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFieldResponse;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormResponse;
use B2bCode\Bundle\CmsFormBundle\Twig\EmailExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\TestCase;

class EmailExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private EmailExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->extension = new EmailExtension();
    }

    public function testTheResponseIsFlattenedForTheEmailTemplate(): void
    {
        $field = (new CmsFormField())->setName('country')->setLabel('Country')
            ->setOptions(['choices' => ['Poland' => 'pl']]);
        $form = (new CmsForm())->setName('Contact us');
        $form->addField($field);

        $formResponse = (new CmsFormResponse())->setForm($form);
        $formResponse->addFieldResponse((new CmsFieldResponse())->setField($field)->setValue('pl'));

        self::assertSame(
            [
                'form' => [
                    'name'   => 'Contact us',
                    'fields' => [
                        ['name' => 'country', 'label' => 'Country', 'options' => ['choices' => ['Poland' => 'pl']]],
                    ],
                ],
                'fieldResponses' => [
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
                ],
            ],
            $this->extension->getResponse($formResponse)
        );
    }

    public function testTheTwigFunctionResolvesToTheFlatteningCallable(): void
    {
        $formResponse = (new CmsFormResponse())->setForm((new CmsForm())->setName('Contact us'));

        self::assertSame(
            ['form' => ['name' => 'Contact us', 'fields' => []], 'fieldResponses' => []],
            self::callTwigFunction($this->extension, 'b2b_code_form_response_array', [$formResponse])
        );
    }
}
