<?php

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

class FormExtension extends AbstractExtension
{
    /** @var FormBuilderInterface */
    protected $formBuilder;

    /** @var FormRendererInterface */
    protected $formRenderer;

    /** @var GeneralFieldProvider */
    protected $generalFieldProvider;

    /**
     * @param FormBuilderInterface  $formBuilder
     * @param FormRendererInterface $formRenderer
     * @param GeneralFieldProvider  $generalFieldProvider
     */
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
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('b2b_code_form', [$this, 'renderForm'], ['is_safe' => ['html']]),
            new TwigFunction('b2b_code_form_updateable_fields', [$this, 'getUpdateableFields']),
        ];
    }

    /**
     * @param string      $alias
     * @param string|null $actionUrl
     * @return string
     */
    public function renderForm(string $alias, ?string $actionUrl = null)
    {
        $options = [];
        if ($actionUrl) {
            $options['action'] = $actionUrl;
        }

        $formView = $this->formBuilder->getForm($alias, $options)->createView();
        // @todo evaluate this approach
        $this->formRenderer->setTheme($formView, '@B2bCodeCmsForm/layouts/blank/cms_form.html.twig');

        // @todo evaluate this approach
        return $this->formRenderer->renderBlock($formView, 'cms_form_widget');
    }

    /**
     * @return array
     */
    public function getUpdateableFields(): array
    {
        return $this->generalFieldProvider->getUpdateableFields();
    }
}
