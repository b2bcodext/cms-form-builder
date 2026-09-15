<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Controller\Frontend;

use B2bCode\Bundle\CmsFormBundle\Builder\FormBuilderInterface;
use B2bCode\Bundle\CmsFormBundle\Controller\Frontend\AjaxFormController;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFieldResponse;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormResponse;
use B2bCode\Bundle\CmsFormBundle\Notification\NotificationInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxFormControllerTest extends TestCase
{
    private FormBuilderInterface&MockObject $formBuilder;
    private ManagerRegistry&MockObject $registry;
    private ObjectManager&MockObject $manager;
    private NotificationInterface&MockObject $notification;
    private AjaxFormController $controller;

    #[\Override]
    protected function setUp(): void
    {
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->notification = $this->createMock(NotificationInterface::class);
        $this->manager = $this->createMock(ObjectManager::class);

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->with(CmsFormResponse::class)
            ->willReturn($this->manager);

        $this->controller = new AjaxFormController();
    }

    public function testAValidSubmissionIsStoredNotifiedAndAcknowledgedWithTheRedirectUrl(): void
    {
        $cmsForm = $this->cmsForm(['email' => 'text', 'message' => 'textarea']);
        $cmsForm->setRedirectUrl('https://example.org/thanks');

        $this->formIs($cmsForm, true, true, ['email' => ' john@example.org ', 'message' => 'hello']);

        $persisted = null;
        $this->manager->expects(self::once())
            ->method('persist')
            ->willReturnCallback(function (CmsFormResponse $response) use (&$persisted): void {
                $persisted = $response;
            });
        $this->manager->expects(self::once())->method('flush');
        $this->notification->expects(self::once())
            ->method('process')
            ->willReturnCallback(static function (CmsFormResponse $response) use (&$persisted): void {
                self::assertSame($persisted, $response, 'the notification must see the persisted response');
            });

        $response = $this->respond($cmsForm);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(
            ['success' => true, 'message' => '@todo', 'redirectUrl' => 'https://example.org/thanks'],
            json_decode($response->getContent(), true)
        );
        self::assertSame($cmsForm, $persisted->getForm());
        self::assertSame(
            ['email' => 'john@example.org', 'message' => 'hello'],
            $this->submittedValues($persisted)
        );
    }

    public function testAMultiValueAnswerIsStoredAsJson(): void
    {
        $cmsForm = $this->cmsForm(['countries' => 'dropdown']);
        $this->formIs($cmsForm, true, true, ['countries' => ['pl', 'de']]);

        $persisted = null;
        $this->manager->expects(self::once())
            ->method('persist')
            ->willReturnCallback(function (CmsFormResponse $response) use (&$persisted): void {
                $persisted = $response;
            });

        $this->respond($cmsForm);

        self::assertSame(['countries' => '["pl","de"]'], $this->submittedValues($persisted));
    }

    public function testAnAnswerForAFieldTheFormDoesNotHaveIsDropped(): void
    {
        $cmsForm = $this->cmsForm(['email' => 'text']);
        $this->formIs($cmsForm, true, true, ['email' => 'john@example.org', 'stray' => 'ignored']);

        $persisted = null;
        $this->manager->expects(self::once())
            ->method('persist')
            ->willReturnCallback(function (CmsFormResponse $response) use (&$persisted): void {
                $persisted = $response;
            });

        $this->respond($cmsForm);

        self::assertSame(['email' => 'john@example.org'], $this->submittedValues($persisted));
    }

    public function testAnUnsupportedAnswerTypeIsStoredAsNull(): void
    {
        $cmsForm = $this->cmsForm(['when' => 'text']);
        $this->formIs($cmsForm, true, true, ['when' => new \DateTime('2020-01-02 03:04:05')]);

        $persisted = null;
        $this->manager->expects(self::once())
            ->method('persist')
            ->willReturnCallback(function (CmsFormResponse $response) use (&$persisted): void {
                $persisted = $response;
            });

        $this->respond($cmsForm);

        self::assertSame(['when' => null], $this->submittedValues($persisted));
    }

    public function testAnInvalidSubmissionIsRejectedWithThePerFieldErrors(): void
    {
        $cmsForm = $this->cmsForm(['email' => 'text']);
        $form = $this->formIs($cmsForm, true, false, []);

        $origin = $this->createMock(FormInterface::class);
        $origin->expects(self::any())->method('getName')->willReturn('email');
        $error = new FormError('This value should not be blank.');
        $error->setOrigin($origin);
        $form->expects(self::once())
            ->method('getErrors')
            ->with(true, true)
            ->willReturn(new FormErrorIterator($form, [$error]));

        $this->manager->expects(self::never())->method('persist');
        $this->notification->expects(self::never())->method('process');

        $response = $this->respond($cmsForm);

        self::assertSame(
            ['success' => false, 'errors' => ['email' => 'This value should not be blank.']],
            json_decode($response->getContent(), true)
        );
    }

    public function testAFormThatWasNotSubmittedIsRejectedWithoutErrors(): void
    {
        $cmsForm = $this->cmsForm(['email' => 'text']);
        $form = $this->formIs($cmsForm, false, false, []);
        $form->expects(self::once())->method('getErrors')->willReturn(new FormErrorIterator($form, []));

        $this->manager->expects(self::never())->method('flush');

        $response = $this->respond($cmsForm);

        self::assertSame(['success' => false, 'errors' => []], json_decode($response->getContent(), true));
    }

    private function respond(CmsForm $cmsForm): JsonResponse
    {
        return $this->controller->respondAction(
            new Request(),
            $cmsForm,
            $this->formBuilder,
            $this->registry,
            $this->notification
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function formIs(CmsForm $cmsForm, bool $submitted, bool $valid, array $data): FormInterface&MockObject
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())->method('handleRequest')->willReturnSelf();
        $form->expects(self::any())->method('isSubmitted')->willReturn($submitted);
        $form->expects(self::any())->method('isValid')->willReturn($valid);
        $form->expects(self::any())->method('getData')->willReturn($data);

        $this->formBuilder->expects(self::once())
            ->method('getForm')
            ->with($cmsForm->getAlias())
            ->willReturn($form);

        return $form;
    }

    /**
     * @return array<string, string|null>
     */
    private function submittedValues(CmsFormResponse $response): array
    {
        $values = [];
        /** @var CmsFieldResponse $fieldResponse */
        foreach ($response->getFieldResponses() as $fieldResponse) {
            $values[$fieldResponse->getField()->getName()] = $fieldResponse->getRawValue();
        }

        return $values;
    }

    /**
     * @param array<string, string> $fields field name => field type
     */
    private function cmsForm(array $fields): CmsForm
    {
        $cmsForm = (new CmsForm())->setAlias('contact-us');
        foreach ($fields as $name => $type) {
            $cmsForm->addField((new CmsFormField())->setName($name)->setType($type));
        }

        return $cmsForm;
    }
}
