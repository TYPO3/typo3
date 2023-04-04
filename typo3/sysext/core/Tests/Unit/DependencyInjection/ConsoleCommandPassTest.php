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

final class ConsoleCommandPassTest extends UnitTestCase
{
    private function buildContainer(string $uniqid, array $packages = []): ContainerInterface
    {
        $packageManagerMock = $this->createMock(PackageManager::class);
        $activePackages = [];
        foreach ($packages as $packageKey => $config) {
            $packageMock = $this->createMock(Package::class);
            $packageMock->method('getPackageKey')->willReturn($packageKey);
            $packageMock->method('getPackagePath')->willReturn($config['path']);
            $packageMock->method('isPartOfMinimalUsableSystem')->willReturn(false);
            $packageMock->method('getServiceProvider')->willReturn($config['serviceProvider'] ?? NullServiceProvider::class);
            $activePackages[$packageKey] = $packageMock;
        }

        $packageManagerMock->method('getCacheIdentifier')->willReturn('PackageManager.' . $uniqid);
        $packageManagerMock->method('getActivePackages')->willReturn($activePackages);

        $cacheMock = $this->createMock(PhpFrontend::class);
        $cacheMock->method('requireOnce')->with(self::isType('string'))->willReturn(false);
        $cacheMock->method('set')->willReturnCallback(function (string $entryIdentifier, string $sourceCode): void {
            eval($sourceCode);
        });

        return (new ContainerBuilder([]))->createDependencyInjectionContainer($packageManagerMock, $cacheMock);
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
