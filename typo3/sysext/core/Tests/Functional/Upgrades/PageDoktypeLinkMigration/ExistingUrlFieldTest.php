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

namespace TYPO3\CMS\Core\Tests\Functional\Upgrades\PageDoktypeLinkMigration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Upgrades\PageDoktypeLinkMigration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ExistingUrlFieldTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['install'];
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_pagelinkmigration_existingurlfield',
    ];

    #[Test]
    public function linkFieldUpdated(): void
    {
        $subject = $this->get(PageDoktypeLinkMigration::class);
        // Test migratable records are recognized and execution leads to expected database state
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ExistingUrlField/dataset_import.csv');
        self::assertTrue($subject->updateNecessary());
        $subject->executeUpdate();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExistingUrlField/dataset_migrated.csv');
        // Just ensure that running the upgrade again does not change anything
        // while still assuming to have possible necessary updates available.
        // Could happen if upgrade wizard is marked undone.
        self::assertTrue($subject->updateNecessary());
        self::assertTrue($subject->executeUpdate());
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ExistingUrlField/dataset_migrated.csv');
    }
}
