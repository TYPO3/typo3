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

namespace TYPO3\CMS\Lowlevel\Tests\Functional\ConfigurationModuleProvider;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\GlobalVariableProvider;
use TYPO3\CMS\Lowlevel\Event\ModifyBlindedConfigurationOptionsEvent;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class GlobalVariableProviderTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function modifyBlindedConfigurationOptionsEventIsTriggered(): void
    {
        $modifyBlindedConfigurationOptionsEvent = null;

        $identifier = 'foo-bar';
        $globalVariableKey = 'FOOBAR';
        $GLOBALS[$globalVariableKey] = ['someoption' => 'password123'];
        $blindedConfiguration = ['someoption' => '***blinded***'];
        $modifiedBlindedConfigurationOptions = [$globalVariableKey => $blindedConfiguration];

        /** @var Container $container */
        $container = $this->getContainer();
        $container->set(
            'modify-blinded-configuration-options',
            static function (ModifyBlindedConfigurationOptionsEvent $event) use (
                &$modifyBlindedConfigurationOptionsEvent,
                $modifiedBlindedConfigurationOptions
            ) {
                $event->setBlindedConfigurationOptions($modifiedBlindedConfigurationOptions);
                $modifyBlindedConfigurationOptionsEvent = $event;
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(ModifyBlindedConfigurationOptionsEvent::class, 'modify-blinded-configuration-options');

        $subject = new GlobalVariableProvider($container->get(EventDispatcherInterface::class));
        $subject([
            'identifier' => $identifier,
            'label' => 'bar',
            'globalVariableKey' => $globalVariableKey,
        ]);
        $configuration = $subject->getConfiguration();

        self::assertInstanceOf(ModifyBlindedConfigurationOptionsEvent::class, $modifyBlindedConfigurationOptionsEvent);
        self::assertEquals($modifiedBlindedConfigurationOptions, $modifyBlindedConfigurationOptionsEvent->getBlindedConfigurationOptions());
        self::assertEquals($identifier, $modifyBlindedConfigurationOptionsEvent->getProviderIdentifier());
        self::assertEquals($blindedConfiguration, $configuration);
    }
}
