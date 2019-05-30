<?php

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChoiceOptionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'b2bcode.cmsform.cmsformfield.options.choice.choices.name.label',
                    'attr' => [
                        'placeholder'    => 'b2bcode.cmsform.cmsformfield.options.choice.choices.name.label',
                    ],
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'value',
                TextType::class,
                [
                    'required' => true,
                    'label'    => 'b2bcode.cmsform.cmsformfield.options.choice.choices.value.label',
                    'attr' => [
                        'placeholder'    => 'b2bcode.cmsform.cmsformfield.options.choice.choices.value.label',
                    ],
                    'constraints' => [new NotBlank()],
                ]
            );
    }
}
