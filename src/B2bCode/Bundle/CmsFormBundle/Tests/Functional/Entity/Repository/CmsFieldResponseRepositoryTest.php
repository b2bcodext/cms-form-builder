<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Functional\Entity\Repository;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFieldResponse;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormResponse;
use B2bCode\Bundle\CmsFormBundle\Entity\Repository\CmsFieldResponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * The finder behind the "Responses" grid column: it has to group by form response and drop the
 * captcha answers, over real rows.
 *
 * @dbIsolationPerTest
 */
class CmsFieldResponseRepositoryTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(['@B2bCodeCmsFormBundle/Tests/Functional/DataFixtures/cms_forms.yml']);
    }

    public function testResponsesAreGroupedByTheirFormResponse(): void
    {
        $first = $this->getReference('form1_response');
        self::assertInstanceOf(CmsFormResponse::class, $first);
        $second = $this->createFormResponse($first->getForm(), ['first-name' => 'Ann']);

        $grouped = $this->getRepository()->findGroupedByFormResponses([$first->getId(), $second->getId()]);

        self::assertCount(2, $grouped);
        self::assertArrayHasKey($first->getId(), $grouped);
        self::assertArrayHasKey($second->getId(), $grouped);
        self::assertEquals(
            ['last-name' => 'NameDoe', 'email' => 'doe.xx@example.com', 'contact-reason' => 'complaint'],
            $this->toMap($grouped[$first->getId()])
        );
        self::assertEquals(['first-name' => 'Ann'], $this->toMap($grouped[$second->getId()]));
    }

    public function testOnlyTheRequestedFormResponsesAreReturned(): void
    {
        $first = $this->getReference('form1_response');
        self::assertInstanceOf(CmsFormResponse::class, $first);
        $second = $this->createFormResponse($first->getForm(), ['first-name' => 'Ann']);

        $grouped = $this->getRepository()->findGroupedByFormResponses([$second->getId()]);

        self::assertSame([$second->getId()], array_keys($grouped));
        self::assertCount(1, $grouped);
    }

    public function testCaptchaAnswersAreLeftOutOfTheGrouping(): void
    {
        $formResponse = $this->getReference('form1_response');
        self::assertInstanceOf(CmsFormResponse::class, $formResponse);

        $entityManager = $this->getEntityManager();
        $captchaField = (new CmsFormField())
            ->setName('captcha')
            ->setLabel('Captcha')
            ->setType('oro-recaptcha-v3')
            ->setSortOrder(99);
        $formResponse->getForm()->addField($captchaField);
        $entityManager->persist($captchaField);

        $captchaResponse = (new CmsFieldResponse())
            ->setField($captchaField)
            ->setFormResponse($formResponse)
            ->setValue('0.9');
        $entityManager->persist($captchaResponse);
        $entityManager->flush();

        $grouped = $this->getRepository()->findGroupedByFormResponses([$formResponse->getId()]);

        self::assertArrayNotHasKey('captcha', $this->toMap($grouped[$formResponse->getId()]));
        self::assertCount(3, $grouped[$formResponse->getId()]);
    }

    public function testNoRequestedIdsYieldsNoGroups(): void
    {
        self::assertSame([], $this->getRepository()->findGroupedByFormResponses([]));
    }

    /**
     * @param array<string, string> $answers field name => value
     */
    private function createFormResponse(CmsForm $cmsForm, array $answers): CmsFormResponse
    {
        $entityManager = $this->getEntityManager();
        $formResponse = new CmsFormResponse();
        $formResponse->setForm($cmsForm);

        foreach ($answers as $fieldName => $value) {
            $field = $cmsForm->getField($fieldName);
            self::assertInstanceOf(CmsFormField::class, $field, sprintf('field "%s" is on the form', $fieldName));

            $fieldResponse = (new CmsFieldResponse())->setField($field)->setValue($value);
            $formResponse->addFieldResponse($fieldResponse);
        }

        $entityManager->persist($formResponse);
        $entityManager->flush();

        return $formResponse;
    }

    /**
     * @param CmsFieldResponse[] $fieldResponses
     *
     * @return array<string, string|null> field name => raw value
     */
    private function toMap(array $fieldResponses): array
    {
        $map = [];
        foreach ($fieldResponses as $fieldResponse) {
            $map[$fieldResponse->getField()->getName()] = $fieldResponse->getRawValue();
        }

        return $map;
    }

    private function getRepository(): CmsFieldResponseRepository
    {
        return $this->getEntityManager()->getRepository(CmsFieldResponse::class);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(CmsFieldResponse::class);
    }
}
