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

namespace TYPO3\CMS\Core\Tests\Functional\Package;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests the package states and order of packages in functional tests.
 * Thus, this is a test case for functional tests.
 */
final class PackageStatesTest extends FunctionalTestCase
{
    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    // disables caching of package states
                    'core' => [
                        'backend' => NullBackend::class,
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        $this->coreExtensionsToLoad = require __DIR__ . '/../../../Resources/Private/Php/framework-packages.php';
        shuffle($this->coreExtensionsToLoad);
        parent::setUp();
    }

    public static function expectedSystemExtensionKeys(): \Generator
    {
        yield 'all system extensions' => [
            'expectedSystemExtensionKeys' => [
                'core',
                'scheduler',
                'extbase',
                'fluid',
                'install',
                'backend',
                'frontend',
                'dashboard',
                'filelist',
                'impexp',
                'lowlevel',
                'form',
                'fluid_styled_content',
                'seo',
                'indexed_search',
                'felogin',
                'styleguide',
                'adminpanel',
                'reports',
                'redirects',
                'linkvalidator',
                'reactions',
                'recycler',
                'sys_note',
                'webhooks',
                'belog',
                'beuser',
                'extensionmanager',
                'filemetadata',
                'info',
                'opendocs',
                'rte_ckeditor',
                'theme_camino',
                'tstemplate',
                'viewpage',
                'workspaces',
            ],
        ];
    }

    /**
     * Validate loaded `PackageStates.php` (classic mode) created by the `typo3/testing-framework`
     * using a custom implementation, which works before the functional test instance is created
     * and bootstrapped (loaded up).
     *
     * {@see self::activePackagesAreOrderedByPrioritizedPackageKeysOrPackageDependenciesOrAlphabeticallyAndSustainResorting}
     * counter-part triggering a resort using the core implementation for sorting for `PackageStates.php` extensions.
     */
    #[DataProvider('expectedSystemExtensionKeys')]
    #[Test]
    public function activePackagesAreOrderedByPrioritizedPackageKeysOrPackageDependenciesOrAlphabetically(array $expectedSystemExtensionKeys): void
    {
        $packageManager = $this->get(PackageManager::class);
        $activePackages = $packageManager->getActivePackages();
        self::assertSame(
            $expectedSystemExtensionKeys,
            // use the order of `$activePackages`, but only pass those values of `$expectedKeys`
            array_values(
                array_intersect(
                    array_keys($activePackages),
                    $expectedSystemExtensionKeys,
                )
            ),
        );
    }

    /**
     * Counter-part validation based on testing-framework created `PackageStates.php` (classic mode),
     * but intentionally resorted using the `EXT:core` implementation alone and verifies same result.
     *
     * {@see self::activePackagesAreOrderedByPrioritizedPackageKeysOrPackageDependenciesOrAlphabetically()}
     */
    #[DataProvider('expectedSystemExtensionKeys')]
    #[Test]
    public function activePackagesAreOrderedByPrioritizedPackageKeysOrPackageDependenciesOrAlphabeticallyAndSustainResorting(array $expectedSystemExtensionKeys): void
    {
        $packageManager = $this->get(PackageManager::class);
        (new \ReflectionMethod($packageManager, 'sortActivePackagesByDependencies'))->invoke($packageManager);
        $activePackages = $packageManager->getActivePackages();
        self::assertSame(
            $expectedSystemExtensionKeys,
            // use the order of `$activePackages`, but only pass those values of `$expectedKeys`
            array_values(
                array_intersect(
                    array_keys($activePackages),
                    $expectedSystemExtensionKeys,
                )
            ),
        );
    }
}
