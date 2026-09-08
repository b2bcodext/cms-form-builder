<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Functional\Controller\Frontend;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The storefront preview page: a layout-rendered page that embeds the generated form through the
 * b2b_code_form Twig function, and hides it when the form has the preview switched off.
 */
class FormControllerTest extends FrontendWebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(['@B2bCodeCmsFormBundle/Tests/Functional/DataFixtures/cms_forms.yml']);
    }

    public function testPreviewPageRendersTheGeneratedForm(): void
    {
        $cmsForm = $this->getCmsForm('preview-enabled');

        $this->client->request(
            Request::METHOD_GET,
            $this->getUrl('b2b_code_cms_form_frontend_form_preview', ['uuid' => $cmsForm->uuid()])
        );

        $response = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($response, Response::HTTP_OK);

        $content = $response->getContent();
        self::assertStringContainsString('contact-us-form', $content);
        self::assertStringContainsString('name="cms_form[first-name]"', $content);
        self::assertStringContainsString('name="cms_form[last-name]"', $content);
        self::assertStringContainsString('name="cms_form[email]"', $content);
        self::assertStringContainsString('name="cms_form[organization]"', $content);
        self::assertStringContainsString(
            $this->getUrl('b2b_code_cms_frontend_ajax_respond', ['uuid' => $cmsForm->uuid()]),
            $content,
            'the generated form posts back to the respond endpoint of its own form'
        );
    }

    public function testPreviewPageHidesTheFormWhenPreviewIsDisabled(): void
    {
        $cmsForm = $this->getCmsForm('preview-disabled');

        $this->client->request(
            Request::METHOD_GET,
            $this->getUrl('b2b_code_cms_form_frontend_form_preview', ['uuid' => $cmsForm->uuid()])
        );

        $response = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertStringNotContainsString('contact-us-form', $response->getContent());
    }

    private function getCmsForm(string $alias): CmsForm
    {
        $cmsForm = self::getContainer()->get('doctrine')->getRepository(CmsForm::class)
            ->findOneBy(['alias' => $alias]);
        self::assertInstanceOf(CmsForm::class, $cmsForm);

        return $cmsForm;
    }
}
