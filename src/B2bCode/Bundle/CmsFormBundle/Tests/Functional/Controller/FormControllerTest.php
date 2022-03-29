<?php

namespace B2bCode\Bundle\CmsFormBundle\Tests\Functional\Controller;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FormControllerTest extends WebTestCase
{
    protected const GRID = 'b2bcode-cms-forms-grid';
    protected const FIELDS_GRID = 'b2bcode-cms-form-fields-grid';
    protected const RESPONSES_GRID = 'b2bcode-cms-form-responses-grid';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(['@B2bCodeCmsFormBundle/Tests/Functional/DataFixtures/cms_forms.yml']);
    }

    public function testIndex()
    {
        $crawler = $this->client->request(Request::METHOD_GET, $this->getUrl('b2b_code_cms_form_index'));
        $response = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertStringContainsString(static::GRID, $crawler->html());
        self::assertStringContainsString('Create Cms Form', $response->getContent());

        $response = $this->client->requestGrid(static::GRID);
        $gridRecords = self::getJsonResponseContent($response, Response::HTTP_OK);
        self::assertArrayHasKey('data', $gridRecords);
        self::assertCount(2, $gridRecords['data']);
    }

    public function testViewWithPreview()
    {
        /** @var CmsForm $cmsForm */
        $cmsForm = $this->getEntityBy(CmsForm::class, ['alias' => 'preview-enabled']);
        self::assertNotNull($cmsForm);

        $this->client->request(
            Request::METHOD_GET,
            $this->getUrl('b2b_code_cms_form_view', ['id' => $cmsForm->getId()])
        );

        $response = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertStringContainsString('General', $response->getContent());
        self::assertStringContainsString('Fields', $response->getContent());
        self::assertStringContainsString('Reorder fields', $response->getContent());

        self::assertStringContainsString('preview/4f7554f2-4442-4baf-8d86-84cb33e1a125', $response->getContent());
        // Notifications are enabled and has at least one email set
        self::assertStringContainsString('daniel@b2bcodext.com', $response->getContent());
        self::assertStringContainsString('Generated code', $response->getContent());
        self::assertStringContainsString('{{ b2b_code_form(&#039;preview-enabled&#039;) }}', $response->getContent());

        // Fields grid
        $response = $this->client->requestGrid([
            'gridName' => static::FIELDS_GRID,
            static::FIELDS_GRID . '[cmsFormId]' => $cmsForm->getId(),
        ]);
        $gridRecords = self::getJsonResponseContent($response, Response::HTTP_OK);
        self::assertArrayHasKey('data', $gridRecords);
        self::assertCount(4, $gridRecords['data']);
    }

    public function testViewWithoutPreviewAndNotifications()
    {
        /** @var CmsForm $cmsForm */
        $cmsForm = $this->getEntityBy(CmsForm::class, ['alias' => 'preview-disabled']);
        self::assertNotNull($cmsForm);

        $this->client->request(
            Request::METHOD_GET,
            $this->getUrl('b2b_code_cms_form_view', ['id' => $cmsForm->getId()])
        );

        $response = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertStringContainsString('Preview is not enabled. To enable it', $response->getContent());
        self::assertStringContainsString('Notifications are disabled or empty. To enable them', $response->getContent());
        self::assertStringContainsString('Generated code', $response->getContent());
        self::assertStringContainsString('{{ b2b_code_form(&#039;preview-disabled&#039;) }}', $response->getContent());
    }

    public function testCreateForm()
    {
        $crawler = $this->client->request(Request::METHOD_GET, $this->getUrl('b2b_code_cms_form_create'));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['form[name]'] = 'Contact Us';
        $form['form[alias]'] = 'contact-us';
        $form['form[previewEnabled]'] = true;
        $form['form[notificationsEnabled]'] = true;
        $form['form[notifications][0][email]'] = 'john@example.com';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $response = $this->client->getResponse();
        /** @var CmsForm $cmsForm */
        $cmsForm = $this->getEntityBy(CmsForm::class, ['alias' => 'contact-us']);

        self::assertHtmlResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertStringContainsString('Form saved', $crawler->html());
        self::assertNotNull($cmsForm);
        self::assertTrue($cmsForm->isPreviewEnabled());
        self::assertTrue($cmsForm->isNotificationsEnabled());
        self::assertCount(1, $cmsForm->getNotifications());
        $this->removeEntity($cmsForm);
    }

    public function testUpdateForm()
    {
        /** @var CmsForm $cmsForm */
        $cmsForm = $this->getEntityBy(CmsForm::class, ['alias' => 'preview-disabled']);
        $crawler = $this->client->request(
            Request::METHOD_GET,
            $this->getUrl('b2b_code_cms_form_update', ['id' => $cmsForm->getId()])
        );

        $form = $crawler->selectButton('Save and Close')->form();
        $form['form[name]'] = 'Preview now enabled';
        $form['form[alias]'] = 'preview-now-enabled';
        $form['form[previewEnabled]'] = true;
        $form['form[notificationsEnabled]'] = true;
        $form['form[notifications][0][email]'] = 'john.doe@example.com';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $response = $this->client->getResponse();
        $cmsForm = $this->getEntityBy(CmsForm::class, ['alias' => 'preview-now-enabled']);

        self::assertHtmlResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertStringContainsString('Form saved', $crawler->html());
        self::assertNotNull($cmsForm);
        self::assertTrue($cmsForm->isPreviewEnabled());
        self::assertTrue($cmsForm->isNotificationsEnabled());
        self::assertCount(1, $cmsForm->getNotifications());
    }

    public function testCreateField()
    {
        /** @var CmsForm $cmsForm */
        $cmsForm = $this->getEntityBy(CmsForm::class, ['alias' => 'preview-enabled']);
        $crawler = $this->client->request(
            Request::METHOD_GET,
            $this->getUrl('b2b_code_cms_form_field_create', ['id' => $cmsForm->getId()])
        );

        $form = $crawler->selectButton('Save and Close')->form();
        $form['field[label]'] = 'Simple text field';
        $form['field[name]'] = 'simple-text-field';
        $form['field[type]'] = 'text';
        $form['field[placeholder]'] = 'This is a simple text field...';
        $form['field[css_class]'] = 'custom-css__container alert-box';
        $form['field[required]'] = true;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $response = $this->client->getResponse();
        /** @var CmsFormField $field */
        $field = $this->getEntityBy(CmsFormField::class, ['name' => 'simple-text-field']);

        self::assertHtmlResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertStringContainsString('Field saved', $crawler->html());
        self::assertNotNull($field);
        self::assertEquals('Simple text field', $field->getLabel());
        self::assertEquals('text', $field->getType());
        self::assertTrue($field->getOption('required'));
        $attrOptions = $field->getOption('attr');
        self::assertEquals('This is a simple text field...', $attrOptions['placeholder']);
        self::assertEquals('custom-css__container alert-box', $attrOptions['class']);
        $this->removeEntity($field);
    }

    public function testUpdateField()
    {
        /** @var CmsFormField $field */
        $field = $this->getEntityBy(CmsFormField::class, ['name' => 'first-name']);
        $crawler = $this->client->request(
            Request::METHOD_GET,
            $this->getUrl('b2b_code_cms_form_field_update', ['id' => $field->getId()])
        );

        $form = $crawler->selectButton('Save and Close')->form();
        $form['field[label]'] = 'First name edited';
        $form['field[name]'] = 'first-name-edited';
        $form['field[type]'] = 'textarea';
        $form['field[placeholder]'] = 'New placeholder...';
        $form['field[css_class]'] = '';
        $form['field[required]'] = false;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $response = $this->client->getResponse();
        /** @var CmsFormField $field */
        $field = $this->getEntityBy(CmsFormField::class, ['name' => 'first-name-edited']);

        self::assertHtmlResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertStringContainsString('Field saved', $crawler->html());
        self::assertNotNull($field);
        self::assertEquals('First name edited', $field->getLabel());
        self::assertEquals('textarea', $field->getType());
        self::assertFalse($field->getOption('required'));
        $attrOptions = $field->getOption('attr');
        self::assertEquals('New placeholder...', $attrOptions['placeholder']);
        self::assertEquals('', $attrOptions['class']);
    }

    public function testResponses()
    {
        /** @var CmsForm $cmsForm */
        $cmsForm = $this->getEntityBy(CmsForm::class, ['alias' => 'preview-enabled']);
        $crawler = $this->client->request(
            Request::METHOD_GET,
            $this->getUrl('b2b_code_cms_form_responses', ['id' => $cmsForm->getId()])
        );
        $response = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertStringContainsString(static::RESPONSES_GRID, $crawler->html());
        self::assertStringContainsString('View Form', $response->getContent());
        self::assertStringContainsString('Export', $response->getContent());

        $response = $this->client->requestGrid([
            'gridName' => static::RESPONSES_GRID,
            static::RESPONSES_GRID . '[cmsFormId]' => $cmsForm->getId(),
        ]);
        $gridRecords = self::getJsonResponseContent($response, Response::HTTP_OK);
        self::assertArrayHasKey('data', $gridRecords);
        self::assertCount(1, $gridRecords['data']);
        $firstRow = reset($gridRecords['data']);
        self::assertArrayHasKey('fieldResponses', $firstRow);
        self::assertStringContainsString('Last name', $firstRow['fieldResponses']);
        self::assertStringContainsString('NameDoe', $firstRow['fieldResponses']);
        self::assertStringContainsString('Email', $firstRow['fieldResponses']);
        self::assertStringContainsString('doe.xx@example.com', $firstRow['fieldResponses']);
        self::assertStringContainsString('Contact reason', $firstRow['fieldResponses']);
        self::assertStringContainsString('Have a complaint', $firstRow['fieldResponses']);
    }

    /**
     * @param object $entity
     */
    private function removeEntity($entity)
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $entityManager->remove($entity);
        $entityManager->flush();
        $entityManager->clear();
    }

    /**
     * @param string $class
     * @param array  $criteria
     *
     * @return null|object
     */
    private function getEntityBy(string $class, array $criteria)
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        return $entityManager->getRepository($class)->findOneBy($criteria);
    }
}
