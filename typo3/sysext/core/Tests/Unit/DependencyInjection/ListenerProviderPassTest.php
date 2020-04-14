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

namespace TYPO3\CMS\Core\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Core\DependencyInjection\ListenerProviderPass;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ListenerProviderPassTest extends UnitTestCase
{
    protected function getContainerWithListenerProvider(array $packages = [])
    {
        $container = new ContainerBuilder();

        foreach ($packages as $package) {
            $yamlFileLoader = new YamlFileLoader($container, new FileLocator($package . '/Configuration'));
            $yamlFileLoader->load('Services.yaml');
        }

        $listenerProvider = new Definition(ListenerProvider::class);
        $listenerProvider->setPublic(true);
        $listenerProvider->setArguments([
            new Reference('service_container')
        ]);

        $container->setDefinition(ListenerProvider::class, $listenerProvider);

        $container->addCompilerPass(new ListenerProviderPass('event.listener'));
        $container->compile();

        return $container;
    }

    public function testSimpleChainsAndDependencies()
    {
        $container = $this->getContainerWithListenerProvider([
            __DIR__ . '/Fixtures/Package1',
            __DIR__ . '/Fixtures/Package2',
            __DIR__ . '/Fixtures/Package3',
        ]);

        $listenerProvider = $container->get(ListenerProvider::class);
        $listeners = $listenerProvider->getAllListenerDefinitions();

        self::assertEquals(
            [
                'TYPO3\\CMS\\Core\\Mail\\Event\\AfterMailerInitializationEvent' => [
                    [
                        'service' => 'package2.listener',
                        'method' => 'onEvent',
                    ],
                    [
                        'service' => 'package1.listener1',
                        'method' => null,
                    ]
                ],
                'TYPO3\\CMS\\Core\\Foo\\Event\\TestEvent' => [
                    [
                        'service' => 'package3.listener',
                        'method' => null,
                    ]
                ],
            ],
            $listeners
        );
    }

    public function testCycleException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Your dependencies have cycles. That will not work out. Cycles found: legacy-hook->package4.listener, package4.listener->legacy-hook');

        $container = $this->getContainerWithListenerProvider([
            __DIR__ . '/Fixtures/Package1',
            __DIR__ . '/Fixtures/Package4Cycle',
        ]);
    }

    public function testWithoutConfiguration()
    {
        $container = $this->getContainerWithListenerProvider([]);

        $listenerProvider = $container->get(ListenerProvider::class);
        $listeners = $listenerProvider->getAllListenerDefinitions();

        self::assertEquals([], $listeners);
    }
}
