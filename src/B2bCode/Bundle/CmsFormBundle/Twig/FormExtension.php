<?php

declare(strict_types=1);

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\Twig;

use B2bCode\Bundle\CmsFormBundle\Builder\FormBuilderInterface;
use B2bCode\Bundle\CmsFormBundle\Provider\GeneralFieldProvider;
use Symfony\Component\Form\FormRendererInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig functions rendering a CMS form and listing its updateable fields.
 */
class FormExtension extends AbstractExtension
{
    protected FormBuilderInterface $formBuilder;

    protected FormRendererInterface $formRenderer;

    protected GeneralFieldProvider $generalFieldProvider;

    public function __construct(
        FormBuilderInterface $formBuilder,
        FormRendererInterface $formRenderer,
        GeneralFieldProvider $generalFieldProvider
    ) {
        $this->formBuilder = $formBuilder;
        $this->formRenderer = $formRenderer;
        $this->generalFieldProvider = $generalFieldProvider;
    }

    /**
     * @return TwigFunction[]
     */
    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('b2b_code_form', $this->renderForm(...), ['is_safe' => ['html']]),
            new TwigFunction('b2b_code_form_updateable_fields', $this->getUpdateableFields(...)),
        ];
    }

    public function renderForm(string $alias, ?string $actionUrl = null): string
    {
        $options = [];
        if ($actionUrl) {
            $options['action'] = $actionUrl;
        }

        $formView = $this->formBuilder->getForm($alias, $options)->createView();
        // @todo evaluate this approach
        $this->formRenderer->setTheme($formView, '@B2bCodeCmsForm/layouts/default/cms_form.html.twig');

        // @todo evaluate this approach
        return $this->formRenderer->renderBlock($formView, 'cms_form_widget');
    }

    /**
     * @return string[]
     */
    public function getUpdateableFields(): array
    {
        return $this->generalFieldProvider->getUpdateableFields();
    }
}
