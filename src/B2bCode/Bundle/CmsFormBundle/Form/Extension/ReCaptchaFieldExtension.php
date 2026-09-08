<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Form\Extension;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Form\Type\FieldType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Adds the reCAPTCHA field to a CMS form when the form has reCAPTCHA protection enabled.
 */
class ReCaptchaFieldExtension extends AbstractTypeExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [FieldType::class];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // works only with ORO recaptcha extension
        if (!class_exists('OroLab\Bundle\ReCaptchaBundle\Form\Type\ReCaptchaType')) {
            return;
        }

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event): void {
                $form = $event->getForm();
                /** @var CmsFormField|null $formField */
                $formField = $event->getData();

                if ($formField && $formField->getType() === 'oro-recaptcha-v3') {
                    $this->addField($form);
                }
            }
        );

        $builder->get('type')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event): void {
                $form = $event->getForm()->getParent();
                $formType = $event->getData();

                if ($formType && $formType === 'oro-recaptcha-v3') {
                    $this->addField($form);
                }
            }
        );
    }

    protected function addField(FormInterface $form): void
    {
        $form
            ->add(
                'data-re-captcha-action',
                TextType::class,
                [
                    'required'      => false,
                    'label'         => 'b2bcode.cmsform.cmsformfield.options.oro-recaptcha.recaptcha-action.label',
                    'tooltip'       => 'b2bcode.cmsform.cmsformfield.options.oro-recaptcha.recaptcha-action.tooltip',
                    'property_path' => 'options[attr][data-re-captcha-action]',
                ]
            );
    }
}
