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

namespace TYPO3\CMS\Install\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\DependencyInjection\FailsafeContainer;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Install\Command\UpgradeScanCommand;
use TYPO3\CMS\Install\ServiceProvider;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Proves that "upgrade:scan" is actually reachable: the service provider must be able to
 * build the command together with the scanner service it depends on, and the command registry
 * must know the command under its name. Neither is exercised by the unit tests, which
 * construct the command directly.
 */
final class UpgradeScanCommandTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = ['install'];

    #[Test]
    public function theServiceProviderCanBuildTheCommandWithItsScannerService(): void
    {
        $container = new FailsafeContainer(
            [new ServiceProvider()],
            [PackageManager::class => $this->get(PackageManager::class)]
        );

        self::assertInstanceOf(UpgradeScanCommand::class, $container->get(UpgradeScanCommand::class));
    }

    #[Test]
    public function theCommandIsRegisteredUnderItsName(): void
    {
        $commandRegistry = $this->get(CommandRegistry::class);

        self::assertTrue($commandRegistry->has('upgrade:scan'));
        self::assertInstanceOf(UpgradeScanCommand::class, $commandRegistry->get('upgrade:scan'));
    }
}
