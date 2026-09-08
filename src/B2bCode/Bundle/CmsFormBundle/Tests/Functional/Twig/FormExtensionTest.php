<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Functional\Twig;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Twig\Environment;
use Twig\Error\RuntimeError;

/**
 * `b2b_code_form` is the function content editors put into a landing page. It only works if the
 * extension is registered on the application Twig environment AND the layout form renderer can find
 * the bundle's own form theme.
 */
class FormExtensionTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(['@B2bCodeCmsFormBundle/Tests/Functional/DataFixtures/cms_forms.yml']);
        // the generated form carries a CSRF token, which needs a session behind it
        $this->ensureSessionIsAvailable();
    }

    public function testRenderFormProducesTheStorefrontMarkupOfTheForm(): void
    {
        $cmsForm = $this->getReference('form_preview_enabled');
        self::assertInstanceOf(CmsForm::class, $cmsForm);

        $html = $this->render("{{ b2b_code_form('preview-enabled') }}");

        self::assertStringContainsString('class="cms-form"', $html);
        self::assertStringContainsString('name="cms_form[first-name]"', $html);
        self::assertStringContainsString('name="cms_form[email]"', $html);
        self::assertStringContainsString(
            sprintf('action="%s"', $this->getUrl('b2b_code_cms_frontend_ajax_respond', ['uuid' => $cmsForm->uuid()])),
            $html
        );
    }

    public function testRenderFormHonoursAnExplicitActionUrl(): void
    {
        $html = $this->render("{{ b2b_code_form('preview-enabled', '/custom/endpoint') }}");

        self::assertStringContainsString('action="/custom/endpoint"', $html);
    }

    public function testRenderFormFailsLoudlyForAnUnknownAlias(): void
    {
        // Twig wraps it, but the CmsFormNotFound message has to reach the caller rather than
        // the function rendering an empty string
        self::expectException(RuntimeError::class);
        self::expectExceptionMessage('CmsForm with alias no-such-form not found');

        $this->render("{{ b2b_code_form('no-such-form') }}");
    }

    public function testUpdateableFieldsFunctionExposesTheGeneralFieldProviderList(): void
    {
        $html = $this->render("{{ b2b_code_form_updateable_fields()|join(',') }}");

        self::assertSame('label,name,type,size,placeholder,css_class,required', $html);
    }

    private function render(string $template): string
    {
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        return $twig->createTemplate($template)->render();
    }
}
