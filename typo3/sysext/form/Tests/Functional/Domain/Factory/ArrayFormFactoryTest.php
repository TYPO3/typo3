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

namespace TYPO3\CMS\Form\Tests\Functional\Domain\Factory;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface as ExtbaseConfigurationManagerInterface;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable;
use TYPO3\CMS\Form\Event\BeforeRenderableIsAddedToFormEvent;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as ExtFormConfigurationManagerInterface;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ArrayFormFactoryTest extends FunctionalTestCase
{
    public const BEFORE_RENDERABLE_IS_ADDED_TO_FORM_LISTENER_KEY = 'before-renderable-is-added-to-form-listener';

    protected array $coreExtensionsToLoad = [
        'form',
    ];

    #[Test]
    public function beforeRenderableIsAddedToFormEventIsTriggered(): void
    {
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

        $container = $this->get('service_container');
        $state = [
            self::BEFORE_RENDERABLE_IS_ADDED_TO_FORM_LISTENER_KEY => null,
        ];
        $container->set(
            self::BEFORE_RENDERABLE_IS_ADDED_TO_FORM_LISTENER_KEY,
            static function (BeforeRenderableIsAddedToFormEvent $event) use (&$state): void {
                $state[self::BEFORE_RENDERABLE_IS_ADDED_TO_FORM_LISTENER_KEY] = $event;
                if ($event->renderable instanceof AbstractRenderable) {
                    $event->renderable->setLabel('foo');
                }
            }
        );
        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(BeforeRenderableIsAddedToFormEvent::class, self::BEFORE_RENDERABLE_IS_ADDED_TO_FORM_LISTENER_KEY);

        $arrayFormFactory = $this->getAccessibleMock(ArrayFormFactory::class, null, [$this->get(EventDispatcherInterface::class)]);
        $configuration = [
            'identifier' => 'page-1',
            'type' => 'Page',
        ];
        $section = new FormDefinition('form-1', $prototypeConfiguration);
        $arrayFormFactory->_call('addNestedRenderable', $configuration, $section, $request);

        self::assertInstanceOf(BeforeRenderableIsAddedToFormEvent::class, $state[self::BEFORE_RENDERABLE_IS_ADDED_TO_FORM_LISTENER_KEY]);
        if (!($state[self::BEFORE_RENDERABLE_IS_ADDED_TO_FORM_LISTENER_KEY]->renderable instanceof AbstractRenderable)) {
            self::fail('Renderable is not an instance of AbstractRenderable');
        }
        self::assertEquals('foo', $state[self::BEFORE_RENDERABLE_IS_ADDED_TO_FORM_LISTENER_KEY]->renderable->getLabel());
    }
}
