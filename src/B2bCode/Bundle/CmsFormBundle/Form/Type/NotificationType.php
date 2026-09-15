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

namespace B2bCode\Bundle\CmsFormBundle\Form\Type;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormNotification;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for a CMS form email notification (recipient email and email template).
 */
class NotificationType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'email',
                TextType::class,
                [
                    'required' => false,
                    'label'    => 'b2bcode.cmsform.notifications.email.label',
                    'attr' => [
                        'placeholder' => 'b2bcode.cmsform.notifications.email.label'
                    ]
                ]
            )
            ->add(
                'template',
                Select2TranslatableEntityType::class,
                [
                    'label'        => 'oro.notification.emailnotification.template.label',
                    'class'        => EmailTemplate::class,
                    'choice_label' => 'name',
                    'configs'      => [
                        'allowClear'  => true,
                        'placeholder' => 'oro.email.form.choose_template',
                    ],
                    'placeholder'  => '',
                    'required'     => false,
                ]
            );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => CmsFormNotification::class
            ]
        );
    }
}
