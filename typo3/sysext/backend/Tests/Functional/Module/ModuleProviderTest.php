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

namespace TYPO3\CMS\Backend\Tests\Functional\Module;

use TYPO3\CMS\Backend\Module\ModuleFactory;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Module\ModuleRegistry;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ModuleProviderTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    /**
     * @test
     */
    public function workspaceAccessIsInherited(): void
    {
        $parentModule = $this->get(ModuleFactory::class)->createModule(
            'parent_module',
            [
                'workspaces' => 'live',
            ]
        );

        $subModule = $this->get(ModuleFactory::class)->createModule(
            'sub_module',
            [
                'parent' => 'parent_module',
            ]
        );

        $offlineWorkspace = $this->get(ModuleFactory::class)->createModule(
            'offline_workspace',
            [
                'parent' => 'parent_module',
                'workspaces' => 'offline',
            ]
        );

        $allWorkspaces = $this->get(ModuleFactory::class)->createModule(
            'all_workspaces',
            [
                'parent' => 'parent_module',
                'workspaces' => '*',
            ]
        );

        $moduleRegistry = new ModuleRegistry([$parentModule, $subModule, $offlineWorkspace, $allWorkspaces]);
        $moduleProvider = new ModuleProvider($moduleRegistry);

        $user = new BackendUserAuthentication();

        self::assertFalse($moduleProvider->accessGranted('parent_module', $user)); // Default -99 is disallowed

        self::assertFalse($moduleProvider->accessGranted('sub_module', $user)); // Default -99 is disallowed

        self::assertFalse($moduleProvider->accessGranted('another_sub_module', $user)); // Default -99 is disallowed

        self::assertFalse($moduleProvider->accessGranted('all_workspaces', $user)); // Default -99 is disallowed

        $user->workspace = 0;

        self::assertTrue($moduleProvider->accessGranted('parent_module', $user)); // 0=live is allowed

        self::assertTrue($moduleProvider->accessGranted('sub_module', $user)); // 0=live is allowed

        self::assertFalse($moduleProvider->accessGranted('offline_workspace', $user)); // 0=live is explicitly disallowed

        self::assertTrue($moduleProvider->accessGranted('all_workspaces', $user)); // 0=live is allowed

        $user->workspace = 1;

        self::assertFalse($moduleProvider->accessGranted('parent_module', $user)); // 1=workspace is disallowed

        self::assertFalse($moduleProvider->accessGranted('sub_module', $user)); // 1=workspace is disallowed

        self::assertTrue($moduleProvider->accessGranted('offline_workspace', $user)); // 1=workspace is explicitly allowed

        self::assertTrue($moduleProvider->accessGranted('all_workspaces', $user)); // 1=workspace is allowed
    }
}
