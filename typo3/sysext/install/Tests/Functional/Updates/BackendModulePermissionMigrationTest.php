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

namespace TYPO3\CMS\Install\Tests\Functional\Updates;

use TYPO3\CMS\Install\Updates\BackendModulePermissionMigration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BackendModulePermissionMigrationTest extends FunctionalTestCase
{
    protected string $baseDataSet = __DIR__ . '/Fixtures/BackendModulePermissionMigrationBefore.csv';
    protected string $resultDataSet = __DIR__ . '/Fixtures/BackendModulePermissionMigrationAfter.csv';

    /**
     * Require additional core extensions so routes of modules in the fixture are available.
     */
    protected array $coreExtensionsToLoad = ['workspaces'];

    /**
     * @test
     */
    public function recordsUpdated(): void
    {
        $subject = new BackendModulePermissionMigration();
        $this->importCSVDataSet($this->baseDataSet);
        self::assertTrue($subject->updateNecessary());
        $subject->executeUpdate();
        self::assertFalse($subject->updateNecessary());
        $this->assertCSVDataSet($this->resultDataSet);

        // Just ensure that running the upgrade again does not change anything
        $subject->executeUpdate();
        $this->assertCSVDataSet($this->resultDataSet);
    }
}
