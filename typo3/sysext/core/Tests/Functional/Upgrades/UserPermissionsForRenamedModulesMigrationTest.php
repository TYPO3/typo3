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
}
