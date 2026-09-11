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

namespace TYPO3\CMS\Core\Tests\Functional\Hooks;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Hooks\TcaItemsProcessorFunctions;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TcaItemsProcessorFunctionsFileStoragesTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Authentication/Fixtures/be_groups.csv');
        $this->importCSVDataSet(__DIR__ . '/../Authentication/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Authentication/Fixtures/sys_filemounts.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_file_storage');
        $storageRow = $connection->select(['*'], 'sys_file_storage', ['uid' => 1])->fetchAssociative();
        $connection->insert('sys_file_storage', array_merge($storageRow, ['uid' => 2, 'name' => 'second storage']));
        $connection->insert('sys_file_storage', array_merge($storageRow, ['uid' => 3, 'name' => 'unregistered driver', 'driver' => 'NotRegistered']));
        $this->get(StorageRepository::class)->flush();
    }

    public static function populateFileStoragesListsStoragesOfBackendUserDataProvider(): \Generator
    {
        yield 'admin gets all storages with a registered driver' => [1, [1, 2]];
        yield 'editor gets the storages of the file mounts' => [3, [1]];
    }

    #[DataProvider('populateFileStoragesListsStoragesOfBackendUserDataProvider')]
    #[Test]
    public function populateFileStoragesListsStoragesOfBackendUser(int $backendUserUid, array $expectedStorageUids): void
    {
        $this->setUpBackendUser($backendUserUid);

        $fieldDefinition = ['items' => []];
        $this->get(TcaItemsProcessorFunctions::class)->populateFileStorages($fieldDefinition);

        self::assertSame($expectedStorageUids, array_column($fieldDefinition['items'], 'value'));
    }
}
