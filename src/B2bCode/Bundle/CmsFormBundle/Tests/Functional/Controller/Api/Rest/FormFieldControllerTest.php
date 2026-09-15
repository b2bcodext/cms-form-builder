<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Functional\Controller\Api\Rest;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The legacy REST endpoint behind the "delete" action of the form-fields grid. It only exists as a
 * whole SoapBundle stack (RestController + ApiEntityManager + the ACL layer), so it is covered here
 * rather than at unit level.
 *
 * @dbIsolationPerTest
 */
class FormFieldControllerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateApiAuthHeader());
        $this->loadFixtures(['@B2bCodeCmsFormBundle/Tests/Functional/DataFixtures/cms_forms.yml']);
    }

    public function testDeleteRemovesTheFieldAndLeavesItsFormIntact(): void
    {
        $field = $this->getFieldByName('organization');
        $fieldId = $field->getId();

        $this->client->jsonRequest(
            Request::METHOD_DELETE,
            $this->getUrl('b2b_code_cms_form_api_formfield_delete', ['id' => $fieldId])
        );

        self::assertEmptyResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NO_CONTENT);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(CmsFormField::class);
        $entityManager->clear();

        self::assertNull($entityManager->getRepository(CmsFormField::class)->find($fieldId));

        $cmsForm = self::getContainer()->get('doctrine')->getRepository(CmsForm::class)
            ->findOneBy(['alias' => 'preview-enabled']);
        self::assertInstanceOf(CmsForm::class, $cmsForm);
        self::assertSame(
            ['first-name', 'last-name', 'email'],
            array_map(
                static fn (CmsFormField $remaining): string => $remaining->getName(),
                $cmsForm->getFields()->toArray()
            )
        );
    }

    public function testDeleteOfAnUnknownFieldReturnsNotFound(): void
    {
        $this->client->jsonRequest(
            Request::METHOD_DELETE,
            $this->getUrl('b2b_code_cms_form_api_formfield_delete', ['id' => self::getUnusedFieldId()])
        );

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NOT_FOUND);
    }

    private function getFieldByName(string $name): CmsFormField
    {
        $field = self::getContainer()->get('doctrine')->getRepository(CmsFormField::class)
            ->findOneBy(['name' => $name]);
        self::assertInstanceOf(CmsFormField::class, $field);

        return $field;
    }

    private static function getUnusedFieldId(): int
    {
        $maxId = self::getContainer()->get('doctrine')->getManagerForClass(CmsFormField::class)
            ->createQueryBuilder()
            ->select('MAX(field.id)')
            ->from(CmsFormField::class, 'field')
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$maxId + 1000;
    }
}
