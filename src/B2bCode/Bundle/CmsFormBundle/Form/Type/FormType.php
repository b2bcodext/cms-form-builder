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

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use Oro\Bundle\RedirectBundle\Form\Type\SlugType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This one is used for form instance creation
 */
class FormType extends AbstractType
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
                    'label'    => 'b2bcode.cmsform.name.label'
                ]
            )
            ->add(
                'alias',
                SlugType::class,
                [
                    'required'     => true,
                    'label'        => 'b2bcode.cmsform.alias.label',
                    'source_field' => 'name',
                    'tooltip'      => 'b2bcode.cmsform.alias.tooltip'
                ]
            )
            ->add(
                'previewEnabled',
                CheckboxType::class,
                [
                    'required' => false,
                    'label'    => 'b2bcode.cmsform.preview_enabled.label',
                    'tooltip'  => 'b2bcode.cmsform.preview_enabled.tooltip',
                ]
            )
            ->add(
                'notificationsEnabled',
                CheckboxType::class,
                [
                    'required' => false,
                    'label'    => 'b2bcode.cmsform.notifications_enabled.label',
                    'tooltip'  => 'b2bcode.cmsform.notifications_enabled.tooltip',
                ]
            )
            ->add(
                'notifications',
                CollectionType::class,
                [
                    'required'     => false,
                    'label'        => 'b2bcode.cmsform.notifications.label',
                    'tooltip'      => 'b2bcode.cmsform.notifications.tooltip',
                    'allow_add'    => true,
                    'allow_delete' => true,
                    'delete_empty' => true,
                    'prototype'    => true,
                    'entry_type'   => NotificationType::class,
                    'by_reference' => false,
                ]
            )
            ->add(
                'redirectUrl',
                UrlType::class,
                [
                    'label'    => 'b2bcode.cmsform.redirect_url.label',
                    'tooltip'  => 'b2bcode.cmsform.redirect_url.tooltip',
                    'required' => false
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => CmsForm::class
            ]
        );
    }
}
