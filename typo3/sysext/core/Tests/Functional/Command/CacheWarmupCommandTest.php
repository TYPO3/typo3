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

namespace TYPO3\CMS\Core\Tests\Functional\Command;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CacheWarmupCommandTest extends AbstractCommandTest
{
    /**
     * @test
     */
    public function cachesCanBeWarmed(): void
    {
        $containerBuilder = $this->get(ContainerBuilder::class);
        $packageManager = $this->get(PackageManager::class);
        $diCacheIdentifier = $containerBuilder->getCacheIdentifier($packageManager);

        GeneralUtility::rmdir(Environment::getVarPath() . '/cache/', true);
        $result = $this->executeConsoleCommand('cache:warmup');

        self::assertEquals(0, $result['status']);
        self::assertFileExists(Environment::getVarPath() . '/cache/code/di/' . $diCacheIdentifier . '.php');
        self::assertFileExists(Environment::getVarPath() . '/cache/code/core/sites-configuration.php');
    }

    /**
     * @test
     */
    public function systemCachesCanBeWarmed(): void
    {
        $containerBuilder = $this->get(ContainerBuilder::class);
        $packageManager = $this->get(PackageManager::class);
        $diCacheIdentifier = $containerBuilder->getCacheIdentifier($packageManager);

        GeneralUtility::rmdir(Environment::getVarPath() . '/cache/', true);
        $result = $this->executeConsoleCommand('cache:warmup --group %s', 'system');

        self::assertEquals(0, $result['status']);
        self::assertFileExists(Environment::getVarPath() . '/cache/code/di/' . $diCacheIdentifier . '.php');
        self::assertFileExists(Environment::getVarPath() . '/cache/code/core/sites-configuration.php');
    }

    /**
     * @test
     */
    public function diCachesDoesNotWarmSystemCaches(): void
    {
        $containerBuilder = $this->get(ContainerBuilder::class);
        $packageManager = $this->get(PackageManager::class);
        $diCacheIdentifier = $containerBuilder->getCacheIdentifier($packageManager);

        GeneralUtility::rmdir(Environment::getVarPath() . '/cache/', true);
        $result = $this->executeConsoleCommand('cache:warmup -g %s', 'di');

        self::assertEquals(0, $result['status']);
        self::assertFileExists(Environment::getVarPath() . '/cache/code/di/' . $diCacheIdentifier . '.php');
        self::assertFileDoesNotExist(Environment::getVarPath() . '/cache/code/core/sites-configuration.php');
    }

    /**
     * @test
     */
    public function systemCachesCanBeWarmedIfCacheIsBroken(): void
    {
        $containerBuilder = $this->get(ContainerBuilder::class);
        $packageManager = $this->get(PackageManager::class);
        $diCacheIdentifier = $containerBuilder->getCacheIdentifier($packageManager);

        GeneralUtility::mkdir_deep(Environment::getVarPath() . '/cache/code/di');
        file_put_contents(
            Environment::getVarPath() . '/cache/code/di/' . $diCacheIdentifier . '.php',
            'invalid php code'
        );

        $result = $this->executeConsoleCommand('cache:warmup --group %s', 'system');

        self::assertEquals(0, $result['status']);
        self::assertFileExists(Environment::getVarPath() . '/cache/code/di/' . $diCacheIdentifier . '.php');
    }
}
