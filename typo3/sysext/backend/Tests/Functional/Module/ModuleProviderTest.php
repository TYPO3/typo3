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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Module\ModuleFactory;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Module\ModuleRegistry;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ModuleProviderTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    #[Test]
    public function workspaceAccessIsInherited(): void
    {
        $parentModule = $this->get(ModuleFactory::class)->createModule(
            'parent_module',
            [
                'workspaces' => 'live',
            ]
        );

        $parentModuleAll = $this->get(ModuleFactory::class)->createModule(
            'parent_module_all',
            [
                'workspaces' => '*',
            ]
        );

        $subModule = $this->get(ModuleFactory::class)->createModule(
            'sub_module',
            [
                'parent' => 'parent_module',
            ]
        );

        $subModuleAll = $this->get(ModuleFactory::class)->createModule(
            'sub_module_all',
            [
                'parent' => 'parent_module_all',
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

        self::assertFalse($moduleProvider->accessGranted('parent_module_all', $user)); // Default -99 is allowed

        self::assertFalse($moduleProvider->accessGranted('sub_module', $user)); // Default -99 is disallowed

        self::assertFalse($moduleProvider->accessGranted('sub_module_all', $user)); // Default -99 is allowed

        self::assertFalse($moduleProvider->accessGranted('another_sub_module', $user)); // Default -99 is disallowed

        self::assertTrue($moduleProvider->accessGranted('all_workspaces', $user)); // Default -99 is allowed

        $user->workspace = 0;

        self::assertTrue($moduleProvider->accessGranted('parent_module', $user)); // 0=live is allowed

        self::assertFalse($moduleProvider->accessGranted('parent_module_all', $user)); // 0=live is allowed

        self::assertTrue($moduleProvider->accessGranted('sub_module', $user)); // 0=live is allowed

        self::assertFalse($moduleProvider->accessGranted('sub_module_all', $user)); // 0=live is allowed

        self::assertFalse($moduleProvider->accessGranted('offline_workspace', $user)); // 0=live is explicitly disallowed

        self::assertTrue($moduleProvider->accessGranted('all_workspaces', $user)); // 0=live is allowed

        $user->workspace = 1;

        self::assertFalse($moduleProvider->accessGranted('parent_module', $user)); // 1=workspace is disallowed

        self::assertFalse($moduleProvider->accessGranted('parent_module_all', $user)); // 1=workspace is allowed

        self::assertFalse($moduleProvider->accessGranted('sub_module', $user)); // 1=workspace is disallowed

        self::assertFalse($moduleProvider->accessGranted('sub_module_all', $user)); // 1=workspace is allowed

        self::assertTrue($moduleProvider->accessGranted('offline_workspace', $user)); // 1=workspace is explicitly allowed

        self::assertTrue($moduleProvider->accessGranted('all_workspaces', $user)); // 1=workspace is allowed
    }

    #[Test]
    public function moduleAccessOfUserIsChecked(): void
    {
        $parentModule = $this->get(ModuleFactory::class)->createModule(
            'parent_module',
            [
                'access' => 'admin',
            ]
        );

        $subModule = $this->get(ModuleFactory::class)->createModule(
            'sub_module',
            [
                'parent' => 'parent_module',
                'access' => 'user',
            ]
        );

        $anotherSubModule = $this->get(ModuleFactory::class)->createModule(
            'another_sub_module',
            [
                'parent' => 'parent_module',
                'access' => 'user',
            ]
        );

        $subModuleWithAlias = $this->get(ModuleFactory::class)->createModule(
            'sub_module_with_alias',
            [
                'parent' => 'parent_module',
                'aliases' => ['sub_module_alias'],
                'access' => 'user',
            ]
        );

        $moduleRegistry = new ModuleRegistry([$parentModule, $subModule, $anotherSubModule, $subModuleWithAlias]);
        $moduleProvider = new ModuleProvider($moduleRegistry);

        $user = new BackendUserAuthentication();
        $user->workspace = 0;
        $user->groupData['modules'] = 'another_sub_module,sub_module_alias';

        self::assertFalse($moduleProvider->accessGranted('parent_module', $user));
        self::assertFalse($moduleProvider->accessGranted('sub_module', $user));
        self::assertTrue($moduleProvider->accessGranted('another_sub_module', $user));
        self::assertTrue($moduleProvider->accessGranted('sub_module_with_alias', $user));
        self::assertTrue($moduleProvider->accessGranted('sub_module_alias', $user));
    }

    #[Test]
    public function modulesWithDependsOnSubmodulesAreFilteredCorrectly(): void
    {
        // Create a main module
        $mainModule = $this->get(ModuleFactory::class)->createModule(
            'main_module',
            []
        );

        // Create a second-level module that depends on having submodules
        $secondLevelModule = $this->get(ModuleFactory::class)->createModule(
            'second_level_module',
            [
                'parent' => 'main_module',
                'appearance' => [
                    'dependsOnSubmodules' => true,
                ],
            ]
        );

        // Create third-level modules
        $thirdLevelModule1 = $this->get(ModuleFactory::class)->createModule(
            'third_level_module_1',
            [
                'parent' => 'second_level_module',
                'access' => 'user',
            ]
        );

        $thirdLevelModule2 = $this->get(ModuleFactory::class)->createModule(
            'third_level_module_2',
            [
                'parent' => 'second_level_module',
                'access' => 'user',
            ]
        );

        $moduleRegistry = new ModuleRegistry([$mainModule, $secondLevelModule, $thirdLevelModule1, $thirdLevelModule2]);
        $moduleProvider = new ModuleProvider($moduleRegistry);

        $user = new BackendUserAuthentication();
        $user->workspace = 0;
        $user->groupData['modules'] = 'third_level_module_1,third_level_module_2';

        // Test getModuleForMenu - with submodules, the second level module should be included
        $menuModule = $moduleProvider->getModuleForMenu('main_module', $user);
        self::assertNotNull($menuModule);
        self::assertTrue($menuModule->hasSubModule('second_level_module'));
        $secondLevelMenuItem = $moduleProvider->getModuleForMenu('second_level_module', $user);
        self::assertNotNull($secondLevelMenuItem);
        self::assertTrue($secondLevelMenuItem->hasSubModule('third_level_module_1'));
        self::assertTrue($secondLevelMenuItem->hasSubModule('third_level_module_2'));

        // Test getModulesForModuleMenu
        $moduleMenuItems = $moduleProvider->getModulesForModuleMenu($user);
        self::assertArrayHasKey('main_module', $moduleMenuItems);
        $mainMenuItem = $moduleMenuItems['main_module'];
        self::assertTrue($mainMenuItem->hasSubModule('second_level_module'));
    }

    #[Test]
    public function modulesWithDependsOnSubmodulesAreHiddenWhenNoSubmodulesExist(): void
    {
        // Create a main module
        $mainModule = $this->get(ModuleFactory::class)->createModule(
            'main_module',
            []
        );

        // Create a second-level module that depends on having submodules (but has none)
        $secondLevelModule = $this->get(ModuleFactory::class)->createModule(
            'second_level_module',
            [
                'parent' => 'main_module',
                'appearance' => [
                    'dependsOnSubmodules' => true,
                ],
            ]
        );

        // Create another second-level module (without dependsOnSubmodules)
        $anotherSecondLevelModule = $this->get(ModuleFactory::class)->createModule(
            'another_second_level_module',
            [
                'parent' => 'main_module',
                'access' => 'user',
            ]
        );

        $moduleRegistry = new ModuleRegistry([$mainModule, $secondLevelModule, $anotherSecondLevelModule]);
        $moduleProvider = new ModuleProvider($moduleRegistry);

        $user = new BackendUserAuthentication();
        $user->workspace = 0;
        $user->groupData['modules'] = 'another_second_level_module';

        // Test getModuleForMenu - without submodules, the second level module should NOT be included
        $menuModule = $moduleProvider->getModuleForMenu('main_module', $user);
        self::assertNotNull($menuModule);
        self::assertFalse($menuModule->hasSubModule('second_level_module'), 'Module with dependsOnSubmodules=true should be hidden when it has no submodules');
        self::assertTrue($menuModule->hasSubModule('another_second_level_module'));

        // Test getModulesForModuleMenu
        $moduleMenuItems = $moduleProvider->getModulesForModuleMenu($user);
        self::assertArrayHasKey('main_module', $moduleMenuItems);
        $mainMenuItem = $moduleMenuItems['main_module'];
        self::assertFalse($mainMenuItem->hasSubModule('second_level_module'), 'Module with dependsOnSubmodules=true should be hidden when it has no submodules');
        self::assertTrue($mainMenuItem->hasSubModule('another_second_level_module'));
    }

    #[Test]
    public function modulesWithDependsOnSubmodulesAreHiddenWhenAllSubmodulesAreDenied(): void
    {
        // Create a main module
        $mainModule = $this->get(ModuleFactory::class)->createModule(
            'main_module',
            []
        );

        // Create a second-level module that depends on having submodules
        $secondLevelModule = $this->get(ModuleFactory::class)->createModule(
            'second_level_module',
            [
                'parent' => 'main_module',
                'appearance' => [
                    'dependsOnSubmodules' => true,
                ],
            ]
        );

        // Create third-level modules that the user doesn't have access to
        $thirdLevelModule1 = $this->get(ModuleFactory::class)->createModule(
            'third_level_module_1',
            [
                'parent' => 'second_level_module',
                'access' => 'user',
            ]
        );

        $thirdLevelModule2 = $this->get(ModuleFactory::class)->createModule(
            'third_level_module_2',
            [
                'parent' => 'second_level_module',
                'access' => 'user',
            ]
        );

        $moduleRegistry = new ModuleRegistry([$mainModule, $secondLevelModule, $thirdLevelModule1, $thirdLevelModule2]);
        $moduleProvider = new ModuleProvider($moduleRegistry);

        $user = new BackendUserAuthentication();
        $user->workspace = 0;
        // User has no access to any third-level modules
        $user->groupData['modules'] = '';

        // When all submodules are denied access, the second level module should not appear
        $menuModule = $moduleProvider->getModuleForMenu('main_module', $user);
        // The main module itself should not be returned if it has no accessible submodules
        self::assertNull($menuModule);

        $moduleMenuItems = $moduleProvider->getModulesForModuleMenu($user);
        self::assertArrayNotHasKey('main_module', $moduleMenuItems);
    }

    #[Test]
    public function deepNestedModulesAreFilteredRecursivelyInGetModule(): void
    {
        // Test recursive access filtering at 5 levels deep
        $level1 = $this->get(ModuleFactory::class)->createModule('level1', []);
        $level2 = $this->get(ModuleFactory::class)->createModule('level2', ['parent' => 'level1']);
        $level3Accessible = $this->get(ModuleFactory::class)->createModule('level3_accessible', ['parent' => 'level2']);
        $level3Denied = $this->get(ModuleFactory::class)->createModule('level3_denied', ['parent' => 'level2', 'access' => 'user']);
        $level4Accessible = $this->get(ModuleFactory::class)->createModule('level4_accessible', ['parent' => 'level3_accessible']);
        $level4Denied = $this->get(ModuleFactory::class)->createModule('level4_denied', ['parent' => 'level3_accessible', 'access' => 'admin']);
        $level5Accessible = $this->get(ModuleFactory::class)->createModule('level5_accessible', ['parent' => 'level4_accessible', 'access' => 'user']);
        $level5Denied = $this->get(ModuleFactory::class)->createModule('level5_denied', ['parent' => 'level4_accessible', 'access' => 'user']);

        $moduleRegistry = new ModuleRegistry([
            $level1, $level2, $level3Accessible, $level3Denied,
            $level4Accessible, $level4Denied, $level5Accessible, $level5Denied,
        ]);
        $moduleProvider = new ModuleProvider($moduleRegistry);

        $user = new BackendUserAuthentication();
        $user->workspace = 0;
        $user->groupData['modules'] = 'level5_accessible'; // Only access to level5_accessible

        // Get the top-level module with user context - should recursively filter all levels
        $module = $moduleProvider->getModule('level1', $user);
        self::assertNotNull($module);

        // Level 2 should be present
        self::assertTrue($module->hasSubModule('level2'));
        $level2Module = $module->getSubModule('level2');

        // Level 3: accessible should be present, denied should be removed
        self::assertTrue($level2Module->hasSubModule('level3_accessible'));
        self::assertFalse($level2Module->hasSubModule('level3_denied'), 'level3_denied should be removed due to user access restrictions');

        $level3Module = $level2Module->getSubModule('level3_accessible');

        // Level 4: accessible should be present, denied (admin only) should be removed
        self::assertTrue($level3Module->hasSubModule('level4_accessible'));
        self::assertFalse($level3Module->hasSubModule('level4_denied'), 'level4_denied should be removed due to admin access requirement');

        $level4Module = $level3Module->getSubModule('level4_accessible');

        // Level 5: accessible should be present, denied should be removed
        self::assertTrue($level4Module->hasSubModule('level5_accessible'));
        self::assertFalse($level4Module->hasSubModule('level5_denied'), 'level5_denied should be removed due to user access restrictions');
    }

    #[Test]
    public function deepNestedModulesAreFilteredRecursivelyInGetModules(): void
    {
        // Test recursive filtering in getModules() with grouped=true
        $main1 = $this->get(ModuleFactory::class)->createModule('main1', []);
        $main2 = $this->get(ModuleFactory::class)->createModule('main2', []);
        $sub1 = $this->get(ModuleFactory::class)->createModule('sub1', ['parent' => 'main1']);
        $sub2 = $this->get(ModuleFactory::class)->createModule('sub2', ['parent' => 'main2', 'access' => 'admin']);
        $subsub1 = $this->get(ModuleFactory::class)->createModule('subsub1', ['parent' => 'sub1']);
        $subsub2 = $this->get(ModuleFactory::class)->createModule('subsub2', ['parent' => 'sub1', 'access' => 'user']);
        $subsubsub1 = $this->get(ModuleFactory::class)->createModule('subsubsub1', ['parent' => 'subsub1', 'access' => 'user']);
        $subsubsub2 = $this->get(ModuleFactory::class)->createModule('subsubsub2', ['parent' => 'subsub1', 'access' => 'user']);

        $moduleRegistry = new ModuleRegistry([
            $main1, $main2, $sub1, $sub2, $subsub1, $subsub2, $subsubsub1, $subsubsub2,
        ]);
        $moduleProvider = new ModuleProvider($moduleRegistry);

        $user = new BackendUserAuthentication();
        $user->workspace = 0;
        $user->groupData['modules'] = 'subsubsub1'; // Only access to subsubsub1

        $modules = $moduleProvider->getModules($user, true, true);

        // main1 should be present
        self::assertArrayHasKey('main1', $modules);
        $main1Module = $modules['main1'];

        // main2 should be present (even though sub2 is admin-only)
        self::assertArrayHasKey('main2', $modules);
        $main2Module = $modules['main2'];

        // sub1 should be present, but sub2 (admin) should be removed from main2
        self::assertTrue($main1Module->hasSubModule('sub1'));
        self::assertFalse($main2Module->hasSubModule('sub2'), 'sub2 should be removed due to admin access requirement');

        $sub1Module = $main1Module->getSubModule('sub1');

        // subsub1 should be present, subsub2 (user without access) should be removed
        self::assertTrue($sub1Module->hasSubModule('subsub1'));
        self::assertFalse($sub1Module->hasSubModule('subsub2'), 'subsub2 should be removed due to user access restrictions');

        $subsub1Module = $sub1Module->getSubModule('subsub1');

        // subsubsub1 should be present (user has access), subsubsub2 should be removed
        self::assertTrue($subsub1Module->hasSubModule('subsubsub1'));
        self::assertFalse($subsub1Module->hasSubModule('subsubsub2'), 'subsubsub2 should be removed due to user access restrictions');
    }

    #[Test]
    public function deepNestedModulesWithHideModulesInTSConfigAreFilteredInMenuMethods(): void
    {
        // Test that TSConfig hideModules works recursively at all levels
        $main = $this->get(ModuleFactory::class)->createModule('main', []);
        $level2 = $this->get(ModuleFactory::class)->createModule('level2', ['parent' => 'main']);
        $level3a = $this->get(ModuleFactory::class)->createModule('level3a', ['parent' => 'level2']);
        $level3b = $this->get(ModuleFactory::class)->createModule('level3b', ['parent' => 'level2']);
        $level4a = $this->get(ModuleFactory::class)->createModule('level4a', ['parent' => 'level3a']);
        $level4b = $this->get(ModuleFactory::class)->createModule('level4b', ['parent' => 'level3b']);

        $moduleRegistry = new ModuleRegistry([$main, $level2, $level3a, $level3b, $level4a, $level4b]);
        $moduleProvider = new ModuleProvider($moduleRegistry);

        // Set TSConfig via database
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->get(ConnectionPool::class)
            ->getConnectionForTable('be_users')
            ->update(
                'be_users',
                ['TSconfig' => 'options.hideModules = level3b,level4a'],
                ['uid' => 1]
            );
        $user = $this->setUpBackendUser(1);
        $user->workspace = 0;

        // Test getModuleForMenu
        $menuModule = $moduleProvider->getModuleForMenu('main', $user);
        self::assertNotNull($menuModule);
        self::assertTrue($menuModule->hasSubModule('level2'));

        $level2Menu = $menuModule->getSubModule('level2');
        // level3a should be present, level3b should be hidden by TSConfig
        self::assertTrue($level2Menu->hasSubModule('level3a'));
        self::assertFalse($level2Menu->hasSubModule('level3b'), 'level3b should be hidden by TSConfig');

        $level3aMenu = $level2Menu->getSubModule('level3a');
        // level4a should be hidden by TSConfig
        self::assertFalse($level3aMenu->hasSubModule('level4a'), 'level4a should be hidden by TSConfig');

        // Test getModulesForModuleMenu
        $moduleMenuItems = $moduleProvider->getModulesForModuleMenu($user);
        self::assertArrayHasKey('main', $moduleMenuItems);
        $mainMenuItem = $moduleMenuItems['main'];
        self::assertTrue($mainMenuItem->hasSubModule('level2'));
        $level2MenuItem = $mainMenuItem->getSubModule('level2');
        self::assertTrue($level2MenuItem->hasSubModule('level3a'));
        self::assertFalse($level2MenuItem->hasSubModule('level3b'), 'level3b should be hidden by TSConfig in module menu');
    }

    #[Test]
    public function deepNestedModulesWithDependsOnSubmodulesAtMultipleLevels(): void
    {
        // Test dependsOnSubmodules at multiple nesting levels
        $main = $this->get(ModuleFactory::class)->createModule('main', []);
        $level2 = $this->get(ModuleFactory::class)->createModule('level2', [
            'parent' => 'main',
            'appearance' => [
                'dependsOnSubmodules' => true,
            ],
        ]);
        $level3 = $this->get(ModuleFactory::class)->createModule('level3', [
            'parent' => 'level2',
            'appearance' => [
                'dependsOnSubmodules' => true,
            ],
        ]);
        $level4accessible = $this->get(ModuleFactory::class)->createModule('level4accessible', [
            'parent' => 'level3',
            'access' => 'user',
        ]);
        $level4denied = $this->get(ModuleFactory::class)->createModule('level4denied', [
            'parent' => 'level3',
            'access' => 'user',
        ]);

        $moduleRegistry = new ModuleRegistry([$main, $level2, $level3, $level4accessible, $level4denied]);
        $moduleProvider = new ModuleProvider($moduleRegistry);

        $user = new BackendUserAuthentication();
        $user->workspace = 0;
        $user->groupData['modules'] = 'level4accessible'; // Only access to level4accessible

        // With access to level4accessible, the entire chain should be visible
        $menuModule = $moduleProvider->getModuleForMenu('main', $user);
        self::assertNotNull($menuModule);
        self::assertTrue($menuModule->hasSubModule('level2'), 'level2 with dependsOnSubmodules should be visible when nested accessible modules exist');

        $level2Menu = $menuModule->getSubModule('level2');
        self::assertTrue($level2Menu->hasSubModule('level3'), 'level3 with dependsOnSubmodules should be visible when nested accessible modules exist');

        // Now test with no access - the entire chain should be hidden
        $user->groupData['modules'] = '';
        $menuModuleNoAccess = $moduleProvider->getModuleForMenu('main', $user);
        self::assertNull($menuModuleNoAccess, 'main module should be null when nested dependsOnSubmodules modules have no accessible children');
    }
}
