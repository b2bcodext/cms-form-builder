<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Functional\DependencyInjection\Compiler;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormResponse;
use B2bCode\Bundle\CmsFormBundle\Twig\EmailExtension;
use B2bCode\Bundle\CmsFormBundle\Twig\FormExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Twig\Environment;
use Twig\Sandbox\SecurityPolicyInterface;

/**
 * The compiler pass widens two sandboxes that live in sibling bundles. Only a compiled container can
 * show whether it did: the CMS content sandbox has to allow and resolve `b2b_code_form`, and the email
 * template sandbox has to allow `b2b_code_form_response_array` plus the `merge` filter.
 */
class TwigSandboxConfigurationPassTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(['@B2bCodeCmsFormBundle/Tests/Functional/DataFixtures/cms_forms.yml']);
        // the generated form carries a CSRF token, which needs a session behind it
        $this->ensureSessionIsAvailable();
    }

    public function testTheCmsContentSandboxRendersTheFormFunction(): void
    {
        $renderer = self::getContainer()->get('oro_cms.twig.renderer');
        self::assertInstanceOf(Environment::class, $renderer);
        self::assertTrue(
            $renderer->hasExtension(FormExtension::class),
            'the pass adds the FormExtension to the CMS content renderer'
        );

        // the CMS renderer sandboxes every template globally, so this also proves `b2b_code_form`
        // was added to oro_cms.twig.content_security_policy
        $html = $renderer->createTemplate("{{ b2b_code_form('preview-enabled') }}")->render();

        self::assertStringContainsString('name="cms_form[first-name]"', $html);
    }

    public function testTheEmailTemplateSandboxAllowsTheBundlesFunctionAndFilter(): void
    {
        $securityPolicy = self::getContainer()->get('oro_email.twig.email_security_policy');
        self::assertInstanceOf(SecurityPolicyInterface::class, $securityPolicy);

        // checkSecurity() throws for anything not allow-listed; reaching the assertion is the result
        $securityPolicy->checkSecurity([], ['merge'], ['b2b_code_form_response_array']);
        self::addToAssertionCount(1);
    }

    public function testTheEmailTemplateEnvironmentResolvesTheBundlesFunction(): void
    {
        $emailEnvironment = self::getContainer()->get('oro_email.twig.email_environment');
        self::assertInstanceOf(Environment::class, $emailEnvironment);
        self::assertTrue($emailEnvironment->hasExtension(EmailExtension::class));

        $formResponse = $this->getReference('form1_response');
        self::assertInstanceOf(CmsFormResponse::class, $formResponse);

        // rendering it is the effect: the function has to be BOTH registered on this environment
        // and allow-listed by its sandbox policy
        $rendered = $emailEnvironment
            ->createTemplate('{{ b2b_code_form_response_array(entity).fieldResponses|length }}')
            ->render(['entity' => $formResponse]);

        self::assertSame('3', $rendered);
    }
}
