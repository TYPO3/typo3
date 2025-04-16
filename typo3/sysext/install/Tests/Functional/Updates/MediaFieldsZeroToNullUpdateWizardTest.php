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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Updates\MediaFieldsZeroToNullUpdateWizard;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MediaFieldsZeroToNullUpdateWizardTest extends FunctionalTestCase
{
    protected string $baseDataSet = __DIR__ . '/Fixtures/MediaFieldsZeroToNullUpdateWizardBase.csv';
    protected string $fullMigrationResultDataSet = __DIR__ . '/Fixtures/MediaFieldsZeroToNullUpdateWizardMigrated.csv';

    protected MediaFieldsZeroToNullUpdateWizard $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $connectionPool = $this->get(ConnectionPool::class);
        $this->subject = new MediaFieldsZeroToNullUpdateWizard($connectionPool);
    }

    #[Test]
    public function mediaFieldsAreUpdated(): void
    {
        $this->importCSVDataSet($this->baseDataSet);
        self::assertTrue($this->subject->updateNecessary());
        $this->subject->executeUpdate();
        self::assertFalse($this->subject->updateNecessary());
        $this->assertCSVDataSet($this->fullMigrationResultDataSet);

        // Just ensure that running the upgrade again does not change anything
        $this->subject->executeUpdate();
        $this->assertCSVDataSet($this->fullMigrationResultDataSet);
    }
}
