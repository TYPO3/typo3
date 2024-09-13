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
    // @todo how to automatically fetch all available TYPO3 system extensions?
    private const CORE_EXTENSION_TO_LOAD = [
        'adminpanel',
        'backend',
        'belog',
        'beuser',
        'dashboard',
        'extbase',
        'extensionmanager',
        'felogin',
        'filelist',
        'filemetadata',
        'fluid',
        'fluid_styled_content',
        'form',
        'frontend',
        'impexp',
        'indexed_search',
        'info',
        'install',
        'linkvalidator',
        'lowlevel',
        'opendocs',
        'reactions',
        'recycler',
        'redirects',
        'reports',
        'rte_ckeditor',
        'scheduler',
        'seo',
        'setup',
        'sys_note',
        'tstemplate',
        'viewpage',
        'webhooks',
        'workspaces',
    ];

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
        $this->coreExtensionsToLoad = self::CORE_EXTENSION_TO_LOAD;
        shuffle($this->coreExtensionsToLoad);
        parent::setUp();
    }

    /**
     * This test cannot test the complete scenario, since the dependency
     * ordering service can only adjust order base on available information.
     *
     * The "sorting constraints" are a combination of static prioritized packages, the
     * corresponding dependencies from `ext_emconf.php` and finally as a fall-back,
     * an alphabetic order - which just ensures that the sequence stays the same.
     */
    #[Test]
    public function activePackagesAreOrderedByPrioritizedPackageKeysOrPackageDependenciesOrAlphabetically(): void
    {
        $packageManager = $this->get(PackageManager::class);
        $activePackages = $packageManager->getActivePackages();
        // @todo this list is still incorrect and requires consolidated `ext_emconf.php` constraints
        $expectedKeys = [
            'core',
            'filelist',
            'frontend',
            'impexp',
            'lowlevel',
            'form',
            'scheduler',
            'extbase',
            'fluid',
            'fluid_styled_content',
            'install',
            'info',
            'linkvalidator',
            'reports',
            'redirects',
            'indexed_search',
            'recycler',
            'setup',
            'rte_ckeditor',
            'dashboard',
            'seo',
            'sys_note',
            'adminpanel',
            'backend',
            'belog',
            'beuser',
            'extensionmanager',
            'felogin',
            'filemetadata',
            'opendocs',
            'reactions',
            'tstemplate',
            'viewpage',
            'webhooks',
            'workspaces',
        ];

        self::assertSame(
            $expectedKeys,
            // use the order of `$activePackages`, but only pass those values of `$expectedKeys`
            array_values(
                array_intersect(
                    array_keys($activePackages),
                    $expectedKeys
                )
            ),
        );
    }
}
