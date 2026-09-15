<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Form\Extension;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Form\Extension\ReCaptchaFieldExtension;
use B2bCode\Bundle\CmsFormBundle\Form\Type\FieldType;
use B2bCode\Bundle\CmsFormBundle\Tests\Unit\Stub\OroReCaptchaStubs;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * The extension is gated on the optional ORO reCAPTCHA extension being installed, which it is not in this
 * package's own dependency set; the tests that need it present install a stand-in for it
 * ({@see OroReCaptchaStubs}), and the one test that needs it ABSENT runs first and asserts that it did.
 *
 * PHPUnit's `@runInSeparateProcess` would make that ordering irrelevant, but it cannot be used here: on this
 * stack (PHPUnit 9.5.28 on PHP 8.4) the isolated child emits implicit-nullable deprecations from PHPUnit's own
 * classes to stderr, which the isolation reader turns into a `PHPUnit\Framework\Exception` — every isolated
 * test errors regardless of its body.
 */
class ReCaptchaFieldExtensionTest extends TestCase
{
    /** @var array<string, callable> */
    private array $rootListeners = [];

    /** @var array<string, callable> */
    private array $typeListeners = [];

    /** @var array<int, array{string, string, array<string, mixed>}> */
    private array $added = [];

    private FormInterface&MockObject $form;

    #[\Override]
    protected function setUp(): void
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->form->expects(self::any())
            ->method('add')
            ->willReturnCallback(function (string $child, string $type, array $options): FormInterface {
                $this->added[] = [$child, $type, $options];

                return $this->form;
            });
    }

    public function testTheExtensionAppliesToTheFieldType(): void
    {
        self::assertSame([FieldType::class], [...ReCaptchaFieldExtension::getExtendedTypes()]);
    }

    /**
     * The only cover for the "extension absent" branch of `ReCaptchaFieldExtension::buildForm()`. `class_alias()`
     * is process-wide and irreversible, so this must run before any test that installs the stand-in — which the
     * default file order guarantees. PHPUnit's process isolation, which would remove the ordering constraint,
     * is unusable on this stack (see the class docblock), so the constraint is asserted LOUDLY instead: a run
     * that reaches this test with the stand-in already installed FAILS rather than skipping, because a silent
     * skip would leave the branch uncovered while the suite still reported green.
     */
    public function testNoListenerIsRegisteredWhileTheOroReCaptchaExtensionIsAbsent(): void
    {
        self::assertFalse(
            OroReCaptchaStubs::isInstalled(),
            'The reCAPTCHA stand-in is already installed in this process, so the "extension absent" branch of '
            . 'ReCaptchaFieldExtension::buildForm() can no longer be reached. Run the suite in its default '
            . 'order (this test must precede every test that calls OroReCaptchaStubs::install()).'
        );

        (new ReCaptchaFieldExtension())->buildForm($this->builder(), []);

        self::assertSame([], $this->rootListeners);
        self::assertSame([], $this->typeListeners);
    }

    public function testAStoredRecaptchaFieldGetsItsActionInput(): void
    {
        $this->withOroReCaptchaInstalled();

        ($this->rootListeners[FormEvents::PRE_SET_DATA])(
            new FormEvent($this->form, (new CmsFormField())->setType('oro-recaptcha-v3'))
        );

        self::assertSame([['data-re-captcha-action', TextType::class, [
            'required'      => false,
            'label'         => 'b2bcode.cmsform.cmsformfield.options.oro-recaptcha.recaptcha-action.label',
            'tooltip'       => 'b2bcode.cmsform.cmsformfield.options.oro-recaptcha.recaptcha-action.tooltip',
            'property_path' => 'options[attr][data-re-captcha-action]',
        ]]], $this->added);
    }

    public function testAStoredFieldOfAnotherTypeGetsNoActionInput(): void
    {
        $this->withOroReCaptchaInstalled();

        ($this->rootListeners[FormEvents::PRE_SET_DATA])(
            new FormEvent($this->form, (new CmsFormField())->setType('text'))
        );
        ($this->rootListeners[FormEvents::PRE_SET_DATA])(new FormEvent($this->form, null));

        self::assertSame([], $this->added);
    }

    public function testPickingTheRecaptchaTypeAddsTheActionInputToTheParentForm(): void
    {
        $this->withOroReCaptchaInstalled();

        ($this->typeListeners[FormEvents::POST_SUBMIT])(new FormEvent($this->typeChild(), 'oro-recaptcha-v3'));

        self::assertSame(['data-re-captcha-action'], array_column($this->added, 0));
    }

    public function testPickingAnotherTypeAddsNothing(): void
    {
        $this->withOroReCaptchaInstalled();

        ($this->typeListeners[FormEvents::POST_SUBMIT])(new FormEvent($this->typeChild(), 'text'));
        ($this->typeListeners[FormEvents::POST_SUBMIT])(new FormEvent($this->typeChild(), null));

        self::assertSame([], $this->added);
    }

    private function withOroReCaptchaInstalled(): void
    {
        OroReCaptchaStubs::install();

        (new ReCaptchaFieldExtension())->buildForm($this->builder(), []);
    }

    private function typeChild(): FormInterface&MockObject
    {
        $typeChild = $this->createMock(FormInterface::class);
        $typeChild->expects(self::any())->method('getParent')->willReturn($this->form);

        return $typeChild;
    }

    private function builder(): FormBuilderInterface&MockObject
    {
        $typeBuilder = $this->createMock(FormBuilderInterface::class);
        $typeBuilder->expects(self::any())
            ->method('addEventListener')
            ->willReturnCallback(
                function (string $event, callable $listener) use ($typeBuilder): FormBuilderInterface {
                    $this->typeListeners[$event] = $listener;

                    return $typeBuilder;
                }
            );

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::any())->method('get')->with('type')->willReturn($typeBuilder);
        $builder->expects(self::any())
            ->method('addEventListener')
            ->willReturnCallback(function (string $event, callable $listener) use ($builder): FormBuilderInterface {
                $this->rootListeners[$event] = $listener;

                return $builder;
            });

        return $builder;
    }
}
