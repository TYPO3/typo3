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

namespace TYPO3\CMS\Core\Tests\Functional\Upgrades;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Upgrades\UserPermissionsForRenamedModulesMigration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class UserPermissionsForRenamedModulesMigrationTest extends FunctionalTestCase
{
    private string $baseDataSet = __DIR__ . '/Fixtures/UserPermissionsForRenamedModulesBase.csv';
    private string $resultDataSet = __DIR__ . '/Fixtures/UserPermissionsForRenamedModulesMigrated.csv';

    #[Test]
    public function modulePermissionsAreMigratedForUsersAndGroups(): void
    {
        $subject = new UserPermissionsForRenamedModulesMigration();
        $ref = new \ReflectionClass($subject);
        $moduleRenamingProp = $ref->getProperty('moduleRenaming');
        $moduleRenamingProp->setValue($subject, [
            'old_module_a' => 'new_module_a',
            'old_module_b' => 'new_module_b',
            'old_module_c' => 'new_module_c',
        ]);

        $requiredParentModulesProp = $ref->getProperty('requiredParentModules');
        $requiredParentModulesProp->setValue($subject, [
            'new_module_c' => 'parent_module',
        ]);

        $this->importCSVDataSet($this->baseDataSet);
        self::assertTrue($subject->updateNecessary(), 'updateNecessary before update');

        $subject->executeUpdate();

        $this->assertCSVDataSet($this->resultDataSet);
        self::assertFalse($subject->updateNecessary(), 'updateNecessary after update');

        // Running again must not change anything
        $subject->executeUpdate();
        $this->assertCSVDataSet($this->resultDataSet);
    }

    #[Test]
    public function parentModulesAreAddedForAlreadyMigratedModules(): void
    {
        $alreadyMigratedDataSet = __DIR__ . '/Fixtures/UserPermissionsForRenamedModulesAlreadyMigrated.csv';
        $alreadyMigratedResultDataSet = __DIR__ . '/Fixtures/UserPermissionsForRenamedModulesAlreadyMigratedResult.csv';

        $subject = new UserPermissionsForRenamedModulesMigration();
        $ref = new \ReflectionClass($subject);

        // Simulate v14.1 where modules were already renamed in v14.0 but parent modules didn't exist yet
        $moduleRenamingProp = $ref->getProperty('moduleRenaming');
        $moduleRenamingProp->setValue($subject, [
            'old_module_a' => 'new_module_a',
            'old_module_c' => 'new_module_c',
        ]);

        // Now in v14.1, we have parent module requirements
        $requiredParentModulesProp = $ref->getProperty('requiredParentModules');
        $requiredParentModulesProp->setValue($subject, [
            'new_module_c' => 'parent_module',
        ]);

        $this->importCSVDataSet($alreadyMigratedDataSet);
        self::assertTrue($subject->updateNecessary(), 'updateNecessary before update - parent modules missing');

        $subject->executeUpdate();

        $this->assertCSVDataSet($alreadyMigratedResultDataSet);
        self::assertFalse($subject->updateNecessary(), 'updateNecessary after update - parent modules added');

        // Running again must not change anything
        $subject->executeUpdate();
        $this->assertCSVDataSet($alreadyMigratedResultDataSet);
    }
}
