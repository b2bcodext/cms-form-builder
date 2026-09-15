<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Functional\DataFixtures;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

/**
 * The form whose alias matches the `feedback-form` entry of Resources/config/form_validation.yml, so
 * that the config-driven validation rules have something to apply to. None of its fields is marked
 * `required`: every constraint on them can only come from that configuration file.
 */
class LoadFeedbackFormData extends AbstractFixture
{
    public const FORM_REFERENCE = 'feedback_form';

    private const FIELDS = [
        ['Are you satisfied', 'are-you-satisfied', 1, 'text'],
        ['Rating', 'rating', 2, 'text'],
        ['Tell us more', 'tell-us-more', 3, 'textarea'],
    ];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $cmsForm = new CmsForm();
        $cmsForm->setName('Feedback form')
            ->setAlias('feedback-form')
            ->setPreviewEnabled(true)
            ->setNotificationsEnabled(false);

        foreach (self::FIELDS as [$label, $name, $sortOrder, $type]) {
            $field = new CmsFormField();
            $field->setLabel($label)
                ->setName($name)
                ->setSortOrder($sortOrder)
                ->setType($type)
                ->setOptions(['required' => false]);

            $cmsForm->addField($field);
            $manager->persist($field);
        }

        $manager->persist($cmsForm);
        $manager->flush();

        $this->setReference(self::FORM_REFERENCE, $cmsForm);
    }
}
