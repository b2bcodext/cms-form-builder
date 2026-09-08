<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Functional\Controller;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Covers the back-office AJAX endpoints: the drag & drop field reorder (which writes to the DB)
 * and the two field-form endpoints that render a live FieldType form.
 *
 * @dbIsolationPerTest
 */
class AjaxFormControllerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures(['@B2bCodeCmsFormBundle/Tests/Functional/DataFixtures/cms_forms.yml']);
    }

    public function testReorderPersistsTheSubmittedSortOrder(): void
    {
        $cmsForm = $this->getCmsForm('preview-enabled');

        self::assertSame(
            ['first-name' => 1, 'last-name' => 2, 'email' => 3, 'organization' => 4],
            $this->getSortOrders('preview-enabled'),
            'fixture pre-condition'
        );

        $this->ajaxRequest(
            Request::METHOD_POST,
            $this->getUrl('b2b_code_cms_form_ajax_reorder', ['id' => $cmsForm->getId()]),
            [
                'cms_form_reorder' => [
                    'fields' => [
                        'first-name' => ['sortOrder' => 4],
                        'last-name' => ['sortOrder' => 3],
                        'email' => ['sortOrder' => 2],
                        'organization' => ['sortOrder' => 1],
                    ],
                ],
            ]
        );

        $response = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertEquals(['success' => true], self::jsonToArray($response->getContent()));

        self::assertSame(
            ['organization' => 1, 'email' => 2, 'last-name' => 3, 'first-name' => 4],
            $this->getSortOrders('preview-enabled')
        );
    }

    public function testReorderWithoutFieldsKeyIsRejectedAndChangesNothing(): void
    {
        $cmsForm = $this->getCmsForm('preview-enabled');

        $this->ajaxRequest(
            Request::METHOD_POST,
            $this->getUrl('b2b_code_cms_form_ajax_reorder', ['id' => $cmsForm->getId()]),
            ['cms_form_reorder' => ['unexpected' => ['first-name' => ['sortOrder' => 9]]]]
        );

        $response = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertEquals(['success' => false], self::jsonToArray($response->getContent()));

        self::assertSame(
            ['first-name' => 1, 'last-name' => 2, 'email' => 3, 'organization' => 4],
            $this->getSortOrders('preview-enabled')
        );
    }

    public function testReorderSkipsUnknownFieldsAndEntriesWithoutSortOrder(): void
    {
        $cmsForm = $this->getCmsForm('preview-enabled');

        $this->ajaxRequest(
            Request::METHOD_POST,
            $this->getUrl('b2b_code_cms_form_ajax_reorder', ['id' => $cmsForm->getId()]),
            [
                'cms_form_reorder' => [
                    'fields' => [
                        'not-a-field-of-this-form' => ['sortOrder' => 7],
                        'contact-reason' => ['sortOrder' => 8],
                        'last-name' => ['label' => 'no sort order here'],
                        'email' => ['sortOrder' => 9],
                    ],
                ],
            ]
        );

        $response = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertEquals(['success' => true], self::jsonToArray($response->getContent()));

        self::assertSame(
            ['first-name' => 1, 'last-name' => 2, 'organization' => 4, 'email' => 9],
            $this->getSortOrders('preview-enabled')
        );
        // `contact-reason` exists as a row but belongs to no form: resolving fields globally instead of
        // through CmsForm::getField() would have reordered it
        self::assertSame(5, $this->getFieldByName('contact-reason')->getSortOrder());
    }

    public function testFormViewRendersOnlyTheTypeSpecificFieldsOfAChoiceField(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            $this->getUrl('b2b_code_cms_form_frontend_ajax_form_view'),
            [
                'field' => [
                    'label' => 'Contact reason',
                    'name' => 'contact-reason-new',
                    'type' => 'dropdown',
                    'size' => 'medium',
                    '_token' => $this->getCsrfToken('field')->getValue(),
                ],
            ]
        );

        $response = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($response, Response::HTTP_OK);

        $content = $response->getContent();
        // the choice-specific children the ChoiceFieldExtension adds are rendered ...
        self::assertStringContainsString('field[choices]', $content);
        self::assertStringContainsString('field[multiple]', $content);
        self::assertStringContainsString('field[choice_placeholder]', $content);
        // ... while every general field is stripped from the view by the GeneralFieldProvider
        self::assertStringNotContainsString('field[label]', $content);
        self::assertStringNotContainsString('field[name]', $content);
        self::assertStringNotContainsString('field[type]', $content);
        self::assertStringNotContainsString('field[required]', $content);
        self::assertStringNotContainsString('field[placeholder]', $content);
        self::assertStringNotContainsString('field[css_class]', $content);
        self::assertStringNotContainsString('field[size]', $content);
    }

    public function testFormViewRendersTheHiddenFieldDefaultValueInput(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            $this->getUrl('b2b_code_cms_form_frontend_ajax_form_view'),
            [
                'field' => [
                    'label' => 'Source',
                    'name' => 'source',
                    'type' => 'hidden',
                    'size' => 'medium',
                    '_token' => $this->getCsrfToken('field')->getValue(),
                ],
            ]
        );

        $response = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertStringContainsString('field[data]', $response->getContent());
        self::assertStringNotContainsString('field[choices]', $response->getContent());
    }

    public function testFieldPreviewRendersTheStorefrontWidgetOfTheSubmittedField(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            $this->getUrl('b2b_code_cms_form_frontend_ajax_field_preview'),
            [
                'field' => [
                    'label' => 'Your e-mail',
                    'name' => 'your-email',
                    'type' => 'email',
                    'size' => 'medium',
                    'placeholder' => 'you@example.com',
                    '_token' => $this->getCsrfToken('field')->getValue(),
                ],
            ]
        );

        $response = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($response, Response::HTTP_OK);

        $content = $response->getContent();
        self::assertStringContainsString('cms-field__medium', $content);
        self::assertStringContainsString('name="cms_form[your-email]"', $content);
        self::assertStringContainsString('type="email"', $content);
        self::assertStringContainsString('placeholder="you@example.com"', $content);
    }

    private function getFieldByName(string $name): CmsFormField
    {
        $field = self::getContainer()->get('doctrine')->getRepository(CmsFormField::class)
            ->findOneBy(['name' => $name]);
        self::assertInstanceOf(CmsFormField::class, $field);

        return $field;
    }

    private function getCmsForm(string $alias): CmsForm
    {
        $cmsForm = self::getContainer()->get('doctrine')->getRepository(CmsForm::class)
            ->findOneBy(['alias' => $alias]);
        self::assertInstanceOf(CmsForm::class, $cmsForm);

        return $cmsForm;
    }

    /**
     * @return array<string, int> field name => sort order, in the entity's own (sortOrder ASC) order
     */
    private function getSortOrders(string $alias): array
    {
        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(CmsForm::class);
        $entityManager->clear();

        $sortOrders = [];
        foreach ($this->getCmsForm($alias)->getFields() as $field) {
            $sortOrders[$field->getName()] = $field->getSortOrder();
        }

        return $sortOrders;
    }
}
