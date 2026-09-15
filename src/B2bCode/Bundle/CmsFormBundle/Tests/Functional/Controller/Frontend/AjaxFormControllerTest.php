<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Functional\Controller\Frontend;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFieldResponse;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormResponse;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\NotificationBundle\Async\Topic\SendEmailNotificationTopic;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The storefront submit flow end to end: the dynamically built form is validated, every answer is
 * persisted as a CmsFieldResponse and the notification is queued.
 *
 * @dbIsolationPerTest
 */
class AjaxFormControllerTest extends FrontendWebTestCase
{
    use MessageQueueExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(['@B2bCodeCmsFormBundle/Tests/Functional/DataFixtures/cms_forms.yml']);
    }

    public function testValidSubmitPersistsEveryAnswerAndQueuesTheNotification(): void
    {
        $cmsForm = $this->getCmsForm('preview-enabled');
        $responsesBefore = $this->countResponses($cmsForm);

        $this->client->request(
            Request::METHOD_POST,
            $this->getUrl('b2b_code_cms_frontend_ajax_respond', ['uuid' => $cmsForm->uuid()]),
            [
                'cms_form' => [
                    'first-name' => '  John  ',
                    'last-name' => 'Doe',
                    'email' => 'john.doe@example.com',
                    'organization' => 'Acme Inc.',
                    '_token' => $this->getCsrfToken('cms_form')->getValue(),
                ],
            ]
        );

        $response = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertEquals(
            ['success' => true, 'message' => '@todo', 'redirectUrl' => null],
            self::jsonToArray($response->getContent())
        );

        $formResponse = $this->getLastResponse($cmsForm);
        self::assertSame($responsesBefore + 1, $this->countResponses($cmsForm));
        self::assertEquals(
            [
                'first-name' => 'John',
                'last-name' => 'Doe',
                'email' => 'john.doe@example.com',
                'organization' => 'Acme Inc.',
            ],
            $this->getAnswers($formResponse),
            'scalar answers are trimmed and stored per field'
        );

        self::assertMessageSent(SendEmailNotificationTopic::getName());
        $sent = self::getSentMessagesByTopic(SendEmailNotificationTopic::getName());
        self::assertCount(1, $sent);
        self::assertSame('daniel@b2bcodext.com', $sent[0]['toEmail']);
    }

    public function testValidSubmitReturnsTheConfiguredRedirectUrl(): void
    {
        $cmsForm = $this->getCmsForm('preview-enabled');
        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(CmsForm::class);
        $cmsForm->setRedirectUrl('/thank-you');
        $entityManager->flush();

        $this->client->request(
            Request::METHOD_POST,
            $this->getUrl('b2b_code_cms_frontend_ajax_respond', ['uuid' => $cmsForm->uuid()]),
            [
                'cms_form' => [
                    'first-name' => 'Jane',
                    'last-name' => 'Roe',
                    'email' => 'jane.roe@example.com',
                    'organization' => '',
                    '_token' => $this->getCsrfToken('cms_form')->getValue(),
                ],
            ]
        );

        $response = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($response, Response::HTTP_OK);

        $content = self::jsonToArray($response->getContent());
        self::assertTrue($content['success']);
        self::assertSame('/thank-you', $content['redirectUrl']);

        self::assertEquals(
            [
                'first-name' => 'Jane',
                'last-name' => 'Roe',
                'email' => 'jane.roe@example.com',
                'organization' => null,
            ],
            $this->getAnswers($this->getLastResponse($cmsForm)),
            'an empty optional answer is still recorded, with a null value'
        );
    }

    public function testInvalidSubmitIsRejectedWithPerFieldErrorsAndPersistsNothing(): void
    {
        $cmsForm = $this->getCmsForm('preview-enabled');
        $responsesBefore = $this->countResponses($cmsForm);

        $this->client->request(
            Request::METHOD_POST,
            $this->getUrl('b2b_code_cms_frontend_ajax_respond', ['uuid' => $cmsForm->uuid()]),
            [
                'cms_form' => [
                    'first-name' => '',
                    'last-name' => 'Doe',
                    'email' => 'not-an-email',
                    'organization' => '',
                    '_token' => $this->getCsrfToken('cms_form')->getValue(),
                ],
            ]
        );

        $response = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($response, Response::HTTP_OK);

        $content = self::jsonToArray($response->getContent());
        self::assertFalse($content['success']);
        self::assertArrayHasKey('first-name', $content['errors']);
        self::assertArrayHasKey('email', $content['errors']);
        self::assertArrayNotHasKey('last-name', $content['errors']);

        self::assertSame($responsesBefore, $this->countResponses($cmsForm));
        self::assertMessagesEmpty(SendEmailNotificationTopic::getName());
    }

    private function getCmsForm(string $alias): CmsForm
    {
        $cmsForm = self::getContainer()->get('doctrine')->getRepository(CmsForm::class)
            ->findOneBy(['alias' => $alias]);
        self::assertInstanceOf(CmsForm::class, $cmsForm);

        return $cmsForm;
    }

    private function countResponses(CmsForm $cmsForm): int
    {
        return count(
            self::getContainer()->get('doctrine')->getRepository(CmsFormResponse::class)
                ->findBy(['form' => $cmsForm])
        );
    }

    private function getLastResponse(CmsForm $cmsForm): CmsFormResponse
    {
        $formResponse = self::getContainer()->get('doctrine')->getRepository(CmsFormResponse::class)
            ->findOneBy(['form' => $cmsForm], ['id' => 'DESC']);
        self::assertInstanceOf(CmsFormResponse::class, $formResponse);

        return $formResponse;
    }

    /**
     * @return array<string, string|null> field name => stored raw value
     */
    private function getAnswers(CmsFormResponse $formResponse): array
    {
        $answers = [];
        /** @var CmsFieldResponse $fieldResponse */
        foreach ($formResponse->getFieldResponses() as $fieldResponse) {
            $answers[$fieldResponse->getField()->getName()] = $fieldResponse->getRawValue();
        }

        return $answers;
    }
}
