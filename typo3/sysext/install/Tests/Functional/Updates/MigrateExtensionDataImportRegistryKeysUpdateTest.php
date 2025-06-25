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
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Install\Updates\MigrateExtensionDataImportRegistryKeysUpdate;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class MigrateExtensionDataImportRegistryKeysUpdateTest extends FunctionalTestCase
{
    protected string $baseDataSet = __DIR__ . '/Fixtures/MigrateExtensionDataImportRegistryKeysBase.csv';
    protected string $fullMigrationResultDataSet = __DIR__ . '/Fixtures/MigrateExtensionDataImportRegistryKeysMigrated.csv';

    #[Test]
    public function registryKeysAreUpdated(): void
    {
        $this->importCSVDataSet($this->baseDataSet);
        $subject = new MigrateExtensionDataImportRegistryKeysUpdate();
        self::assertTrue($subject->updateNecessary());
        $subject->executeUpdate();
        self::assertFalse($subject->updateNecessary());
        $this->assertCSVDataSet($this->fullMigrationResultDataSet);

        // Just ensure that running the upgrade again does not change anything
        $subject->executeUpdate();
        $this->assertCSVDataSet($this->fullMigrationResultDataSet);
    }

    #[Test]
    public function updateNotNecessaryWhenNoOldKeysExist(): void
    {
        // Import only the new format entries
        $registry = $this->get(Registry::class);
        $registry->set('extensionDataImport', 'my_extension:ext_tables_static+adt.sql', 'hash123');
        $registry->set('extensionDataImport', 'another_ext:Initialisation/Files', 1);
        $subject = new MigrateExtensionDataImportRegistryKeysUpdate();
        self::assertFalse($subject->updateNecessary());
    }

    #[Test]
    public function pathBasedKeysAreConvertedCorrectly(): void
    {
        $registry = $this->get(Registry::class);

        // Set up old format entries
        $registry->set('extensionDataImport', 'typo3conf/ext/my_extension/ext_tables_static+adt.sql', 'hash123');
        $registry->set('extensionDataImport', 'typo3/sysext/core/ext_tables_static+adt.sql', 'hash456');
        $registry->set('extensionDataImport', 'EXT:another_ext/Initialisation/Files', 1);

        $subject = new MigrateExtensionDataImportRegistryKeysUpdate();
        self::assertTrue($subject->updateNecessary());
        $subject->executeUpdate();
        self::assertFalse($subject->updateNecessary());

        // Check that new format entries exist
        self::assertEquals('hash123', $registry->get('extensionDataImport', 'my_extension:ext_tables_static+adt.sql'));
        self::assertEquals('hash456', $registry->get('extensionDataImport', 'core:ext_tables_static+adt.sql'));
        self::assertEquals(1, $registry->get('extensionDataImport', 'another_ext:Initialisation/Files'));

        // Check that old format entries are removed
        self::assertNull($registry->get('extensionDataImport', 'typo3conf/ext/my_extension/ext_tables_static+adt.sql'));
        self::assertNull($registry->get('extensionDataImport', 'typo3/sysext/core/ext_tables_static+adt.sql'));
        self::assertNull($registry->get('extensionDataImport', 'EXT:another_ext/Initialisation/Files'));
    }

    #[Test]
    public function newFormatEntriesAreNotModified(): void
    {
        $registry = $this->get(Registry::class);

        // Set up mixed format entries
        $registry->set('extensionDataImport', 'typo3conf/ext/old_ext/ext_tables_static+adt.sql', 'old_hash');
        $registry->set('extensionDataImport', 'new_ext:ext_tables_static+adt.sql', 'new_hash');

        $subject = new MigrateExtensionDataImportRegistryKeysUpdate();
        self::assertTrue($subject->updateNecessary());
        $subject->executeUpdate();

        // Check that new format entry is unchanged
        self::assertEquals('new_hash', $registry->get('extensionDataImport', 'new_ext:ext_tables_static+adt.sql'));
        // Check that old format entry is converted
        self::assertEquals('old_hash', $registry->get('extensionDataImport', 'old_ext:ext_tables_static+adt.sql'));
        self::assertNull($registry->get('extensionDataImport', 'typo3conf/ext/old_ext/ext_tables_static+adt.sql'));
    }
}
