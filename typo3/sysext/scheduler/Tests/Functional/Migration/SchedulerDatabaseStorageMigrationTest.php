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

namespace TYPO3\CMS\Scheduler\Tests\Functional\Migration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Scheduler\Migration\SchedulerDatabaseStorageMigration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SchedulerDatabaseStorageMigrationTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['scheduler'];

    #[Test]
    public function schedulerTasksAreMigrated(): void
    {
        $subject = new SchedulerDatabaseStorageMigration();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseStorageMigrationBase.csv');
        self::assertTrue($subject->updateNecessary());
        self::assertTrue($subject->executeUpdate());
        self::assertFalse($subject->updateNecessary());
        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/DatabaseStorageMigrationApplied.csv');

        // Just ensure that running the upgrade again does not change anything
        self::assertTrue($subject->executeUpdate());
        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/DatabaseStorageMigrationApplied.csv');
    }

    #[Test]
    public function schedulerTasksWithFailuresKeepWizardShowingUp(): void
    {
        $subject = new SchedulerDatabaseStorageMigration();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseStorageFailedMigrationBase.csv');
        self::assertTrue($subject->updateNecessary());
        self::assertFalse($subject->executeUpdate());
        self::assertTrue($subject->updateNecessary());
        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/DatabaseStorageFailedMigrationApplied.csv');

        // Just ensure that running the upgrade again does not change anything
        self::assertFalse($subject->executeUpdate());
        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/DatabaseStorageFailedMigrationApplied.csv');
    }
}
