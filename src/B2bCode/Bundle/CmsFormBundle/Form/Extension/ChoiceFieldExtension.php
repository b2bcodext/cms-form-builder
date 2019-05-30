<?php

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

class ChoiceFieldExtension extends AbstractTypeExtension
{
    /** @var array|string[] */
    protected $supportedTypes = ['dropdown', 'radio'];

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FieldType::class;
    }

    /**
     * @param string $type
     */
    public function addSupportedType(string $type): void
    {
        $this->supportedTypes[] = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->get('type')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                /** @var CmsFormField $formField */
                $form = $event->getForm()->getParent();
                $formType = $event->getData();

                if ($formType && (in_array($formType, $this->getSupportedTypes()))) {
                    $this->addFieldsToForm($form);
                }
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                /** @var CmsFormField $formField */
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
     * @param FormInterface $form
     * @param array         $choices
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

    /**
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
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
     * @param CmsFormField  $cmsField
     * @param FormInterface $form
     */
    protected function processChoices(CmsFormField $cmsField, FormInterface $form)
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
