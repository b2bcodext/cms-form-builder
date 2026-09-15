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

namespace B2bCode\Bundle\CmsFormBundle\Form\Extension;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Form\Type\FieldType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Adds the general per-field options (label, required, sort order, ...) to the CMS field form.
 */
class FieldOptionsExtension extends AbstractTypeExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [FieldType::class];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addGeneralOptionsToForm($builder, $options);

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);
    }

    public function onSubmit(FormEvent $event): void
    {
        $this->incrementSortOrder($event);
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function addGeneralOptionsToForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'required',
                CheckboxType::class,
                [
                    'required'      => false,
                    'label'         => 'b2bcode.cmsform.cmsformfield.options.required.label',
                    'property_path' => 'options[required]'
                ]
            )
            // attr options below
            ->add(
                'placeholder',
                TextType::class,
                [
                    'required'      => false,
                    'label'         => 'b2bcode.cmsform.cmsformfield.options.placeholder.label',
                    'tooltip'       => 'b2bcode.cmsform.cmsformfield.options.placeholder.tooltip',
                    'property_path' => 'options[attr][placeholder]'
                ]
            )
            ->add(
                'css_class',
                TextType::class,
                [
                    'required'      => false,
                    'label'         => 'b2bcode.cmsform.cmsformfield.options.css_class.label',
                    'tooltip'       => 'b2bcode.cmsform.cmsformfield.options.css_class.tooltip',
                    'property_path' => 'options[attr][class]'
                ]
            )
            ->add(
                'size',
                ChoiceType::class,
                [
                    'required'      => true,
                    'label'         => 'b2bcode.cmsform.cmsformfield.options.size.label',
                    'tooltip'       => 'b2bcode.cmsform.cmsformfield.options.size.tooltip',
                    'choices'       => [
                        'b2bcode.cmsform.cmsformfield.options.size.choices.small'  => 'small',
                        'b2bcode.cmsform.cmsformfield.options.size.choices.medium' => 'medium',
                        'b2bcode.cmsform.cmsformfield.options.size.choices.large'  => 'large',
                    ],
                    'property_path' => 'options[attr][data-size]',
                    'constraints'   => [new NotBlank()],
                ]
            );
    }

    protected function incrementSortOrder(FormEvent $event): void
    {
        /** @var CmsFormField $cmsField */
        $cmsField = $event->getData();
        if ($cmsField->getSortOrder() === null) {
            $cmsField->incrementSortOrder();
        }
    }
}
