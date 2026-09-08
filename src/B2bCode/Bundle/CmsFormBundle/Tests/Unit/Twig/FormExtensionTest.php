<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Twig;

use B2bCode\Bundle\CmsFormBundle\Builder\FormBuilderInterface;
use B2bCode\Bundle\CmsFormBundle\Provider\GeneralFieldProvider;
use B2bCode\Bundle\CmsFormBundle\Twig\FormExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\Form\FormView;

class FormExtensionTest extends TestCase
{
    private const THEME = '@B2bCodeCmsForm/layouts/default/cms_form.html.twig';

    private FormBuilderInterface&MockObject $formBuilder;
    private FormRendererInterface&MockObject $formRenderer;
    private FormExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->formRenderer = $this->createMock(FormRendererInterface::class);

        $this->extension = new FormExtension(
            $this->formBuilder,
            $this->formRenderer,
            new GeneralFieldProvider()
        );
    }

    public function testRenderFormBuildsTheAliasedFormAppliesTheBundleThemeAndRendersItsWidget(): void
    {
        $formView = new FormView();
        $this->formBuilder->expects(self::once())
            ->method('getForm')
            ->with('contact-us', [])
            ->willReturn($this->formReturning($formView));

        $this->formRenderer->expects(self::once())->method('setTheme')->with($formView, self::THEME);
        $this->formRenderer->expects(self::once())
            ->method('renderBlock')
            ->with($formView, 'cms_form_widget')
            ->willReturn('<form></form>');

        self::assertSame('<form></form>', $this->extension->renderForm('contact-us'));
    }

    public function testAnActionUrlIsPassedThroughToTheFormBuilder(): void
    {
        $this->formBuilder->expects(self::once())
            ->method('getForm')
            ->with('contact-us', ['action' => '/custom-endpoint'])
            ->willReturn($this->formReturning(new FormView()));
        $this->formRenderer->method('renderBlock')->willReturn('');

        $this->extension->renderForm('contact-us', '/custom-endpoint');
    }

    public function testAnEmptyActionUrlIsNotTurnedIntoAnActionOption(): void
    {
        $this->formBuilder->expects(self::once())
            ->method('getForm')
            ->with('contact-us', [])
            ->willReturn($this->formReturning(new FormView()));
        $this->formRenderer->method('renderBlock')->willReturn('');

        $this->extension->renderForm('contact-us', '');
    }

    public function testTheUpdateableFieldsComeFromTheGeneralFieldProvider(): void
    {
        self::assertSame(
            ['label', 'name', 'type', 'size', 'placeholder', 'css_class', 'required'],
            $this->extension->getUpdateableFields()
        );
    }

    private function formReturning(FormView $formView): FormInterface&MockObject
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())->method('createView')->willReturn($formView);

        return $form;
    }
}
