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

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Tests\Unit\DependencyInjection\Fixtures\CommandRegistryPackage\CommandRegistryServiceProvider;
use TYPO3\CMS\Core\Tests\Unit\DependencyInjection\Fixtures\NullServiceProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ConsoleCommandPassTest extends UnitTestCase
{
    use ProphecyTrait;

    protected function buildContainer(string $uniqid, array $packages = []): ContainerInterface
    {
        $packageManagerProphecy = $this->prophesize(PackageManager::class);
        $activePackages = [];
        foreach ($packages as $packageKey => $config) {
            $packageProphecy = $this->prophesize(Package::class);
            $packageProphecy->getPackageKey()->willReturn($packageKey);
            $packageProphecy->getPackagePath()->willReturn($config['path']);
            $packageProphecy->isPartOfMinimalUsableSystem()->willReturn(false);
            $packageProphecy->getServiceProvider()->willReturn($config['serviceProvider'] ?? NullServiceProvider::class);
            $activePackages[$packageKey] = $packageProphecy->reveal();

            $packageManagerProphecy->getPackage($packageKey)->willReturn($packageProphecy->reveal());
            $packageManagerProphecy->isPackageActive($packageKey)->willReturn(true);
        }
        $packageManagerProphecy->getCacheIdentifier()->willReturn('PackageManager.' . $uniqid);
        $packageManagerProphecy->getActivePackages()->willReturn($activePackages);

        $cache = $this->prophesize(PhpFrontend::class);
        $cache->requireOnce(Argument::type('string'))->willReturn(false);
        $cache->set(Argument::type('string'), Argument::type('string'))->will(function ($args) {
            $sourceCode = $args[1];
            eval($sourceCode);
        });

        $containerBuilder = new ContainerBuilder([]);
        return $containerBuilder->createDependencyInjectionContainer($packageManagerProphecy->reveal(), $cache->reveal());
    }

    /**
     * @test
     */
    public function commandRegistrationViaTags(): void
    {
        $container = $this->buildContainer(__METHOD__, [
            'command-registry-package' => [
                'path' => __DIR__ . '/Fixtures/CommandRegistryPackage/',
                'serviceProvider' => CommandRegistryServiceProvider::class,
            ],
            'package1' => [
                'path' =>__DIR__ . '/Fixtures/Package1/',
            ],
        ]);

        $commandRegistry = $container->get(CommandRegistry::class);

        self::assertTrue($commandRegistry->has('test:cmd'));
        self::assertEquals(['test:cmd'], $commandRegistry->getNames());
        self::assertEquals('Dummy description including new as word', $commandRegistry->filter()['test:cmd']['description'] ?? '');
        self::assertInstanceOf(Command::class, $commandRegistry->getCommandByIdentifier('test:cmd'));
    }

    /**
     * @test
     */
    public function withoutConfiguration(): void
    {
        $container = $this->buildContainer(__METHOD__, [
            'command-registry-package' => [
                'path' => __DIR__ . '/Fixtures/CommandRegistryPackage/',
                'serviceProvider' => CommandRegistryServiceProvider::class,
            ],
        ]);

        $commandRegistry = $container->get(CommandRegistry::class);

        self::assertEquals([], $commandRegistry->getNames());
        self::assertFalse($commandRegistry->has('unknown:command'));
    }
}
