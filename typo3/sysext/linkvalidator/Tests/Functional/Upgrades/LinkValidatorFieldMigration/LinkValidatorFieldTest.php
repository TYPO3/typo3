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

namespace TYPO3\CMS\Linkvalidator\Tests\Functional\Upgrades\LinkValidatorFieldMigration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Linkvalidator\Upgrades\LinkValidatorFieldMigration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LinkValidatorFieldTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['install', 'linkvalidator'];

    #[Test]
    public function fieldReferenceMigrated(): void
    {
        $subject = $this->get(LinkValidatorFieldMigration::class);
        // Test migratable records are recognized and execution leads to expected database state
        $this->importCSVDataSet(__DIR__ . '/Fixtures/LinkValidatorField/dataset_import.csv');
        self::assertTrue($subject->updateNecessary());
        $subject->executeUpdate();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/LinkValidatorField/dataset_migrated.csv');
        // Ensure that running the upgrade again does not change anything
        // and no updates are necessary anymore.
        self::assertFalse($subject->updateNecessary());
        self::assertTrue($subject->executeUpdate());
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/LinkValidatorField/dataset_migrated.csv');
    }
}
