<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Form\Tests\Functional\Domain\Model\Renderable;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface as ExtbaseConfigurationManagerInterface;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable;
use TYPO3\CMS\Form\Event\BeforeRenderableIsRemovedFromFormEvent;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as ExtFormConfigurationManagerInterface;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AbstractRenderableTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'form',
    ];

    #[Test]
    public function setOptionsResetsValidatorsIfDefined(): void
    {
        // $prototypeConfiguration is a monster array. Get it up front.
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $extbaseConfigurationManager = $this->get(ExtbaseConfigurationManagerInterface::class);
        $extbaseConfigurationManager->setRequest($request);
        $extFormConfigurationManager = $this->get(ExtFormConfigurationManagerInterface::class);
        $configurationService = new ConfigurationService(
            $extbaseConfigurationManager,
            $extFormConfigurationManager,
            $this->createMock(TranslationService::class),
            $this->createMock(FrontendInterface::class),
            $this->createMock(FrontendInterface::class),
        );
        $prototypeConfiguration = $configurationService->getPrototypeConfiguration('standard');

        $subject = new class () extends AbstractRenderable {};
        $subject->setIdentifier('Foo');
        $subject->setParentRenderable(new FormDefinition('foo', $prototypeConfiguration));

        $subject->setOptions([
            'validators' => [
                ['identifier' => 'NotEmpty'],
                ['identifier' => 'EmailAddress'],
            ],
        ]);
        self::assertCount(2, $subject->getValidators());
        $subject->setOptions(['validators' => []], true);
        self::assertCount(0, $subject->getValidators());
    }

    #[Test]
    public function beforeRenderableIsRemovedEventIsTriggered(): void
    {
        $subject = new class () extends AbstractRenderable {};
        $subject->setLabel('test');
        $container = $this->get('service_container');
        $state = [
            'before-renderable-is-removed-listener' => null,
            'before-renderable-is-removed-listener-not-called' => null,
        ];
        $container->set(
            'before-renderable-is-removed-listener',
            static function (BeforeRenderableIsRemovedFromFormEvent $event) use (&$state): void {
                $state['before-renderable-is-removed-listener'] = $event;
                $state['label'] = $event->renderable->getLabel();
                $event->preventRemoval = true;
            }
        );
        $container->set(
            'before-renderable-is-removed-listener-not-called',
            static function (BeforeRenderableIsRemovedFromFormEvent $event) use (&$state): void {
                $state['before-renderable-is-removed-listener-not-called'] = $event;
                $event->preventRemoval = false;
            }
        );
        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(BeforeRenderableIsRemovedFromFormEvent::class, 'before-renderable-is-removed-listener');
        $eventListener->addListener(BeforeRenderableIsRemovedFromFormEvent::class, 'before-renderable-is-removed-listener-not-called');

        $subject->onRemoveFromParentRenderable();
        self::assertInstanceOf(BeforeRenderableIsRemovedFromFormEvent::class, $state['before-renderable-is-removed-listener']);
        self::assertNull($state['before-renderable-is-removed-listener-not-called']);
        self::assertTrue($state['before-renderable-is-removed-listener']->isPropagationStopped());
        self::assertEquals('test', $state['label']);
    }
}
