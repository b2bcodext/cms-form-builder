<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Controller\Api\Rest;

use B2bCode\Bundle\CmsFormBundle\Controller\Api\Rest\FormFieldController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\TestCase;

/**
 * The REST controller exposes deletion only; `handleDeleteRequest()` needs the whole SoapBundle stack and is
 * therefore covered by the functional suite.
 */
class FormFieldControllerTest extends TestCase
{
    private ApiEntityManager $apiManager;
    private FormFieldController $controller;

    #[\Override]
    protected function setUp(): void
    {
        $this->apiManager = $this->createMock(ApiEntityManager::class);

        $this->controller = new FormFieldController();
        $this->controller->setContainer(
            TestContainerBuilder::create()
                ->add('b2b_code_cms_form.field_manager.api', $this->apiManager)
                ->getContainer($this)
        );
    }

    public function testTheControllerWorksOnTheFormFieldApiManager(): void
    {
        self::assertSame($this->apiManager, $this->controller->getManager());
    }

    public function testTheDeleteOnlyEndpointOffersNoForm(): void
    {
        self::expectException(\BadMethodCallException::class);
        self::expectExceptionMessage('Form is not available.');

        $this->controller->getForm();
    }

    public function testTheDeleteOnlyEndpointOffersNoFormHandler(): void
    {
        self::expectException(\BadMethodCallException::class);
        self::expectExceptionMessage('FormHandler is not available.');

        $this->controller->getFormHandler();
    }
}
