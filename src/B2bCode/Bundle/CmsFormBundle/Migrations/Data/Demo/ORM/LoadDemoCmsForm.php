<?php

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\Migrations\Data\Demo\ORM;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Provider\FieldTypeRegistry;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadDemoCmsForm extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        // @todo extract to factory/builder
        $fieldProvider = $this->container->get(FieldTypeRegistry::class);
        $dropdownField = $fieldProvider->getByKey('dropdown');
        $radioField = $fieldProvider->getByKey('radio');

        $form = new CmsForm();
        $form
            ->setName('Feedback form')
            ->setAlias('feedback-form')
            ->setPreviewEnabled(true);

        $choiceType = new CmsFormField();
        $choiceType
            ->setLabel('Are you satisfied?')
            ->setSortOrder(1)
            ->setType($dropdownField->getName())
            ->setOptions([
                'choices'  => ['Yes' => 1, 'No' => 0],
                'required' => true,
                'attr'     => [
                    'data-size'   => 'small',
                ]
            ]);

        $textType = new CmsFormField();
        $textType
            ->setLabel('Rating')
            ->setSortOrder(5)
            ->setType($radioField->getName())
            ->setOptions([
                'choices'  => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5],
                'required' => true,
                'attr'     => [
                    'data-size' => 'medium'
                ]
            ]);

        $textArea = new CmsFormField();
        $textArea
            ->setLabel('Tell us more')
            ->setSortOrder(10)
            ->setType('textarea')
            ->setOptions([
                'required' => true,
                'attr'     => [
                    'placeholder' => 'Help us improve our services.',
                    'data-size'   => 'large',
                ]
            ]);

        $form
            ->addField($choiceType)
            ->addField($textType)
            ->addField($textArea);

        $manager->persist($form);
        $manager->flush();
    }
}
