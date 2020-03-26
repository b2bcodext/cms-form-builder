<?php

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\Provider;

use B2bCode\Bundle\CmsFormBundle\ValueObject\CmsFieldType;
use OroLab\Bundle\ReCaptchaBundle\Form\Type\ReCaptchaType;
use OroLab\Bundle\ReCaptchaBundle\Validator\Constraints\IsVerified;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Email;

class FieldTypeProvider implements FieldTypeProviderInterface
{
    /**
     * @return CmsFieldType[]
     */
    public function getAvailableTypes(): array
    {
        $fields = [
            new CmsFieldType('text', TextType::class),
            new CmsFieldType('textarea', TextareaType::class),
            new CmsFieldType('email', EmailType::class, ['constraints' => [new Email()]]),
            new CmsFieldType('dropdown', ChoiceType::class, ['expanded' => false]),
            new CmsFieldType('radio', ChoiceType::class, ['expanded' => true]),
            new CmsFieldType('hidden', HiddenType::class),
        ];

        // works only with ORO recaptcha extension
        if (class_exists('OroLab\Bundle\ReCaptchaBundle\Form\Type\ReCaptchaType')) {
            $fields[] = new CmsFieldType('oro-recaptcha-v3', ReCaptchaType::class, [
                'constraints' => [
                    new IsVerified()
                ],
            ]);
        }

        return $fields;
    }
}
