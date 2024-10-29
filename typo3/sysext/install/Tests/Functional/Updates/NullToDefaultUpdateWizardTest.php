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

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Updates\NullToDefaultUpdateWizard;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class NullToDefaultUpdateWizardTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/install/Tests/Functional/Fixtures/Extensions/test_notnull_to_default',
    ];

    protected string $baseDataSet = __DIR__ . '/Fixtures/NullToDefaultUpdateWizardBase.csv';
    protected string $fullMigrationResultDataSet = __DIR__ . '/Fixtures/NullToDefaultUpdateWizardMigrated.csv';

    #[Test]
    public function nullValuesAreUpdated(): void
    {
        $subject = $this->get(NullToDefaultUpdateWizard::class);

        $connection = $this->get(ConnectionPool::class)->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);

        // Change tx_testnotnulltodefault_example_user.image to NULL(able)
        // in order for schema migrator to pick up migration to NOT NULL
        // as defined in test_notnull_to_default/ext_tables.sql
        if ($connection->getDatabasePlatform() instanceof SQLitePlatform) {
            $connection->executeStatement('DROP TABLE "tx_testnotnulltodefault_example_user"');
            $connection->executeStatement('CREATE TABLE "tx_testnotnulltodefault_example_user" ("uid" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, "image" VARCHAR(255) DEFAULT NULL, "files" INTEGER UNSIGNED DEFAULT NULL)');
        } elseif ($connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            $connection->executeStatement('ALTER TABLE tx_testnotnulltodefault_example_user ALTER image TYPE VARCHAR');
            $connection->executeStatement('ALTER TABLE tx_testnotnulltodefault_example_user ALTER image SET DEFAULT NULL');
            $connection->executeStatement('ALTER TABLE tx_testnotnulltodefault_example_user ALTER image DROP NOT NULL');
        } else {
            $connection->executeStatement('ALTER TABLE tx_testnotnulltodefault_example_user CHANGE image image TINYTEXT DEFAULT NULL');
        }

        $this->importCSVDataSet($this->baseDataSet);

        self::assertTrue($subject->updateNecessary());
        $subject->executeUpdate();
        self::assertFalse($subject->updateNecessary());

        // tx_testnotnulltodefault_example_user.image should have been updated
        // tx_testnotnulltodefault_example_user.files kept as is
        $this->assertCSVDataSet($this->fullMigrationResultDataSet);

        // Just ensure that running the upgrade again does not change anything
        $subject->executeUpdate();
        $this->assertCSVDataSet($this->fullMigrationResultDataSet);
    }
}
