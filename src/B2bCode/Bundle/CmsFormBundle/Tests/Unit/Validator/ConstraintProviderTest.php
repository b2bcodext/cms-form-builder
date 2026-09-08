<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Validator;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Event\ConstraintBuild;
use B2bCode\Bundle\CmsFormBundle\Validator\ConstraintProvider;
use B2bCode\Bundle\CmsFormBundle\Validator\Loader\ValidationRuleLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ConstraintProviderTest extends TestCase
{
    private ValidationRuleLoader&MockObject $ruleLoader;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private ConstraintProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->ruleLoader = $this->createMock(ValidationRuleLoader::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->provider = new ConstraintProvider($this->ruleLoader, $this->eventDispatcher);
    }

    public function testTheRulesAreLookedUpByTheFormAlias(): void
    {
        $form = $this->form('contact-us', ['email' => []]);
        $this->ruleLoader->expects(self::once())
            ->method('getForForm')
            ->with('contact-us')
            ->willReturn([]);
        $this->dispatcherPassesTheEventThrough();

        self::assertSame([], $this->provider->getConstraintsForForm($form)->getRawConstraintsForField('email'));
    }

    public function testConfiguredRulesBecomeConstraintsOfTheirField(): void
    {
        $form = $this->form('contact-us', ['email' => [], 'message' => []]);
        $this->ruleLoader->method('getForForm')->willReturn([
            'fields' => [
                'email'   => [[NotBlank::class => null]],
                'message' => [[Length::class => ['max' => 500]]],
            ],
        ]);
        $this->dispatcherPassesTheEventThrough();

        $collection = $this->provider->getConstraintsForForm($form);

        self::assertSame([[NotBlank::class => null]], $collection->getRawConstraintsForField('email'));
        self::assertSame([[Length::class => ['max' => 500]]], $collection->getRawConstraintsForField('message'));
    }

    public function testARuleForAFieldTheFormDoesNotHaveIsIgnored(): void
    {
        $form = $this->form('contact-us', ['email' => []]);
        $this->ruleLoader->method('getForForm')->willReturn([
            'fields' => ['gone-away' => [[NotBlank::class => null]]],
        ]);
        $this->dispatcherPassesTheEventThrough();

        self::assertFalse($this->provider->getConstraintsForForm($form)->hasFieldAnyConstraints('gone-away'));
    }

    public function testARequiredFieldGetsANotBlankConstraintOnTopOfTheConfiguredOnes(): void
    {
        $form = $this->form('contact-us', [
            'email'   => ['required' => true],
            'message' => ['required' => false],
            'extra'   => [],
        ]);
        $this->ruleLoader->method('getForForm')->willReturn([
            'fields' => ['email' => [[Length::class => ['max' => 100]]]],
        ]);
        $this->dispatcherPassesTheEventThrough();

        $collection = $this->provider->getConstraintsForForm($form);

        self::assertSame(
            [[Length::class => ['max' => 100]], [NotBlank::class => null]],
            $collection->getRawConstraintsForField('email')
        );
        self::assertFalse($collection->hasFieldAnyConstraints('message'));
        self::assertFalse($collection->hasFieldAnyConstraints('extra'));
    }

    public function testTheBuiltCollectionIsHandedToListenersTogetherWithTheForm(): void
    {
        $form = $this->form('contact-us', ['email' => ['required' => true]]);
        $this->ruleLoader->method('getForForm')->willReturn([]);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ConstraintBuild::class))
            ->willReturnCallback(function (ConstraintBuild $event) use ($form) {
                self::assertSame($form, $event->getForm());
                self::assertTrue($event->getConstraintCollection()->hasFieldAnyConstraints('email'));

                return $event;
            });

        $this->provider->getConstraintsForForm($form);
    }

    public function testAListenerCanEnrichTheCollectionThroughTheEvent(): void
    {
        $form = $this->form('contact-us', ['email' => []]);
        $this->ruleLoader->method('getForForm')->willReturn([]);
        $this->eventDispatcher->method('dispatch')
            ->willReturnCallback(static function (ConstraintBuild $event): ConstraintBuild {
                $event->getConstraintCollection()->addConstraintForField('email', NotBlank::class);

                return $event;
            });

        $collection = $this->provider->getConstraintsForForm($form);

        self::assertSame([[NotBlank::class => null]], $collection->getRawConstraintsForField('email'));
    }

    private function dispatcherPassesTheEventThrough(): void
    {
        $this->eventDispatcher->method('dispatch')->willReturnArgument(0);
    }

    /**
     * @param array<string, array<string, mixed>> $fields field name => field options
     */
    private function form(string $alias, array $fields): CmsForm
    {
        $form = (new CmsForm())->setAlias($alias);
        foreach ($fields as $name => $options) {
            $form->addField((new CmsFormField())->setName($name)->setOptions($options));
        }

        return $form;
    }
}
