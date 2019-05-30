<?php

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\DependencyInjection\Compiler;

use B2bCode\Bundle\CmsFormBundle\Twig\EmailExtension;
use B2bCode\Bundle\CmsFormBundle\Twig\FormExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TwigSandboxConfigurationPass implements CompilerPassInterface
{
    public const EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY = 'oro_email.twig.email_security_policy';
    public const EMAIL_TEMPLATE_RENDERER_SERVICE_KEY = 'oro_email.email_renderer';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->cmsTwigRenderer($container);
        $this->emailTwigRenderer($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function cmsTwigRenderer(ContainerBuilder $container)
    {
        if ($container->hasDefinition('oro_cms.twig.content_security_policy')) {
            $securityPolicyDef = $container->getDefinition('oro_cms.twig.content_security_policy');

            $functions = array_merge($securityPolicyDef->getArgument(4), ['b2b_code_form']);
            $securityPolicyDef->replaceArgument(4, $functions);
        }

        if ($container->hasDefinition('oro_cms.twig.renderer')) {
            $twigRenderer = $container->getDefinition('oro_cms.twig.renderer');
            $twigRenderer->addMethodCall('addExtension', [new Reference(FormExtension::class)]);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function emailTwigRenderer(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY)
            && $container->hasDefinition(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY)
        ) {
            $securityPolicyDef = $container->getDefinition(static::EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY);

            $filters = array_merge($securityPolicyDef->getArgument(1), ['merge']);
            $functions = array_merge($securityPolicyDef->getArgument(4), ['b2b_code_form_response_array']);
            $securityPolicyDef->replaceArgument(1, $filters);
            $securityPolicyDef->replaceArgument(4, $functions);

            $rendererDef = $container->getDefinition(self::EMAIL_TEMPLATE_RENDERER_SERVICE_KEY);
            $rendererDef->addMethodCall('addExtension', [new Reference(EmailExtension::class)]);
        }
    }
}
