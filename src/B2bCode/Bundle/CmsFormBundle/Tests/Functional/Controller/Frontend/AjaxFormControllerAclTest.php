<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Functional\Controller\Frontend;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The storefront submit endpoint is gated by the b2b_code_cms_frontend_form_respond action ACL.
 *
 * The bundle has always DECLARED that ACL (Resources/config/oro/acls.yml) and granted it to the
 * BUYER, ADMINISTRATOR and ANONYMOUS frontend roles (Migrations/Data/ORM/data/frontend_roles.yml),
 * but nothing enforced it: revoking the permission did not stop anyone submitting. These tests pin
 * both halves of the contract so the enforcement cannot be dropped again unnoticed.
 *
 * @dbIsolationPerTest
 */
class AjaxFormControllerAclTest extends FrontendWebTestCase
{
    use RolePermissionExtension;

    private const ANONYMOUS_ROLE = 'ROLE_FRONTEND_ANONYMOUS';
    private const ACTION = 'b2b_code_cms_frontend_form_respond';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(['@B2bCodeCmsFormBundle/Tests/Functional/DataFixtures/cms_forms.yml']);
    }

    public function testSubmitIsAllowedWhileTheRoleHoldsThePermission(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            $this->getUrl('b2b_code_cms_frontend_ajax_respond', ['uuid' => $this->cmsForm()->uuid()]),
            ['cms_form' => $this->validPayload()]
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    /**
     * The same request that returns 200 above is refused once the permission is gone — which is the
     * whole point of the enforcement: before it existed, revoking the permission changed nothing.
     *
     * The refusal is 401, not 403, because this visitor is ANONYMOUS: Symfony answers an
     * unauthenticated request to a denied resource with an authentication challenge. An
     * authenticated customer user whose role lacked the permission would get 403.
     */
    public function testSubmitIsRefusedOnceThePermissionIsRevoked(): void
    {
        $this->updateRolePermissionForAction(self::ANONYMOUS_ROLE, self::ACTION, false);

        $this->client->request(
            Request::METHOD_POST,
            $this->getUrl('b2b_code_cms_frontend_ajax_respond', ['uuid' => $this->cmsForm()->uuid()]),
            ['cms_form' => $this->validPayload()]
        );

        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
        self::assertNotEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    private function cmsForm(): CmsForm
    {
        /** @var EntityRepository<CmsForm> $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(CmsForm::class);
        $form = $repository->findOneBy(['alias' => 'preview-enabled']);
        self::assertInstanceOf(CmsForm::class, $form);

        return $form;
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'first-name' => 'John',
            'last-name' => 'Doe',
            'email' => 'john.doe@example.com',
        ];
    }
}
