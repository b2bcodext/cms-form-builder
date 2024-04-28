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
use Oro\Bundle\EmailBundle\DependencyInjection\Compiler\AbstractTwigSandboxConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TwigSandboxConfigurationPass extends AbstractTwigSandboxConfigurationPass
{
    public function process(ContainerBuilder $container)
    {
        $this->cmsTwigRenderer($container);
        parent::process($container);
    }

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

    #[\Override] protected function getFunctions(): array
    {
        return [
            'b2b_code_form_response_array'
        ];
    }

    #[\Override] protected function getFilters(): array
    {
        return [
            'merge'
        ];
    }

    #[\Override] protected function getTags(): array
    {
        return [];
    }

    #[\Override] protected function getExtensions(): array
    {
        return [
            EmailExtension::class,
        ];
    }
}
