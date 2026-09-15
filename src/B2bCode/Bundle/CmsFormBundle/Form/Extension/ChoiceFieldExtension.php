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
use B2bCode\Bundle\CmsFormBundle\Form\Type\ChoiceOptionCollectionType;
use B2bCode\Bundle\CmsFormBundle\Form\Type\ChoiceOptionType;
use B2bCode\Bundle\CmsFormBundle\Form\Type\FieldType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Adds and processes the choice options of choice-based CMS form fields.
 */
class ChoiceFieldExtension extends AbstractTypeExtension
{
    /** @var array|string[] */
    protected $supportedTypes = ['dropdown', 'radio'];

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [FieldType::class];
    }

    public function addSupportedType(string $type): void
    {
        $this->supportedTypes[] = $type;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->get('type')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event): void {
                $form = $event->getForm()->getParent();
                $formType = $event->getData();

                if ($formType && (in_array($formType, $this->getSupportedTypes()))) {
                    $this->addFieldsToForm($form);
                }
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event): void {
                $form = $event->getForm();
                /** @var CmsFormField|null $formField */
                $formField = $event->getData();

                if ($formField && (in_array($formField->getType(), $this->getSupportedTypes()))) {
                    $fieldChoices = $formField->getOption('choices');
                    $choices = [];
                    // @todo change to transformers/data mappers
                    if (is_array($fieldChoices) && count($fieldChoices) > 0) {
                        foreach ($fieldChoices as $choiceName => $choiceValue) {
                            $choices[] = ['name' => $choiceName, 'value' => $choiceValue];
                        }
                    }

                    $this->addFieldsToForm($form, $choices);
                }
            }
        );

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);
    }

    /**
     * @param array<int, array<string, mixed>> $choices
     */
    protected function addFieldsToForm(FormInterface $form, array $choices = []): void
    {
        $choicesOptions = [
            'required'   => false,
            'label'      => 'b2bcode.cmsform.cmsformfield.options.choice.choices.label',
            'tooltip'    => 'b2bcode.cmsform.cmsformfield.options.choice.choices.tooltip',
            'mapped'     => false,
            'allow_add'  => true,
            'prototype'  => true,
            'entry_type' => ChoiceOptionType::class,
        ];

        if (count($choices) > 0) {
            $choicesOptions = array_merge($choicesOptions, ['data' => $choices]);
        }

        $form
            ->add(
                'choices',
                ChoiceOptionCollectionType::class,
                $choicesOptions
            )
            ->add(
                'multiple',
                CheckboxType::class,
                [
                    'required'      => false,
                    'label'         => 'b2bcode.cmsform.cmsformfield.options.choice.multiple.label',
                    'tooltip'       => 'b2bcode.cmsform.cmsformfield.options.choice.multiple.tooltip',
                    'property_path' => 'options[multiple]'
                ]
            )
            ->add(
                'choice_placeholder',
                TextType::class,
                [
                    'required'      => false,
                    'label'         => 'b2bcode.cmsform.cmsformfield.options.choice.placeholder.label',
                    'tooltip'       => 'b2bcode.cmsform.cmsformfield.options.choice.placeholder.tooltip',
                    'property_path' => 'options[placeholder]'
                ]
            );
    }

    public function onSubmit(FormEvent $event): void
    {
        /** @var CmsFormField $cmsField */
        $cmsField = $event->getData();

        if (!in_array($cmsField->getType(), $this->getSupportedTypes())) {
            return;
        }

        $form = $event->getForm();

        $this->processChoices($cmsField, $form);
    }

    /**
     * @return array|string[]
     */
    protected function getSupportedTypes(): array
    {
        return $this->supportedTypes;
    }

    /**
     * @todo change to transformers/data mappers
     *
     */
    protected function processChoices(CmsFormField $cmsField, FormInterface $form): void
    {
        if (!$form->has('choices')) {
            return;
        }

        $data = $form->get('choices')->getData();
        if (is_array($data)) {
            $optionsData = [];
            foreach ($data as $choiceOption) {
                $optionsData[$choiceOption['name']] = $choiceOption['value'];
            }
            // @todo validation
            $cmsField->addOption('choices', $optionsData);
        }
    }
}
