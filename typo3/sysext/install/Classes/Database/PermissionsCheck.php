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

namespace TYPO3\CMS\Install\Database;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Configuration\Exception;

/**
 * Check all required permissions within the install process.
 * @internal This is NOT an API class, it is for internal use in the install tool only.
 */
class PermissionsCheck
{
    private $testTableName = 't3install_test_table';
    private $messages = [];

    public function checkCreateAndDrop(): self
    {
        $tableCreated = $this->checkCreateTable($this->testTableName);
        if (!$tableCreated) {
            $this->messages[] = 'The database user needs CREATE permissions.';
        }
        $tableDropped = $this->checkDropTable($this->testTableName);
        if (!$tableDropped) {
            $this->messages[] = 'The database user needs DROP permissions.';
        }
        if ($tableCreated && !$tableDropped) {
            $this->messages[] = sprintf('Attention: A test table with name "%s" was created but could not be deleted, please remove the table manually!', $this->testTableName);
        }
        if (!$tableCreated || !$tableDropped) {
            throw new Exception('A test table could not be created or dropped, skipping all further checks now', 1590850369);
        }
        return $this;
    }

    public function checkAlter(): self
    {
        $this->checkCreateTable($this->testTableName);
        $connection = $this->getConnection();
        $schemaCurrent = $this->getSchemaManager()->createSchema();
        $schemaNew = $this->getSchemaManager()->createSchema();
        $schemaCurrent
            ->getTable($this->testTableName)
            ->addColumn('index_test', 'integer', ['unsigned' => true]);
        $platform = $connection->getDatabasePlatform();
        try {
            foreach ($schemaNew->getMigrateToSql($schemaCurrent, $platform) as $query) {
                $connection->executeQuery($query);
            }
        } catch (\Exception $e) {
            $this->messages[] = 'The database user needs ALTER permission';
        }
        $this->checkDropTable($this->testTableName);
        return $this;
    }

    public function checkIndex(): self
    {
        if ($this->checkCreateTable($this->testTableName)) {
            $connection = $this->getConnection();
            $schemaCurrent = $this->getSchemaManager()->createSchema();
            $schemaNew = $this->getSchemaManager()->createSchema();
            $testTable = $schemaCurrent->getTable($this->testTableName);
            $testTable->addColumn('index_test', 'integer', ['unsigned' => true]);
            $testTable->addIndex(['index_test'], 'test_index');
            $platform = $connection->getDatabasePlatform();
            try {
                foreach ($schemaNew->getMigrateToSql($schemaCurrent, $platform) as $query) {
                    $connection->executeQuery($query);
                }
            } catch (\Exception $e) {
                $this->checkDropTable($this->testTableName);
                $this->messages[] = 'The database user needs INDEX permission';
            }
            $this->checkDropTable($this->testTableName);
        }
        return $this;
    }

    public function checkCreateTemporaryTable(): self
    {
        $this->checkCreateTable($this->testTableName);
        $connection = $this->getConnection();
        try {
            $sql = 'CREATE TEMPORARY TABLE %s AS (SELECT id FROM %s )';
            $connection->exec(sprintf($sql, $this->testTableName . '_tmp', $this->testTableName));
        } catch (\Exception $e) {
            $this->messages[] = 'The database user needs CREATE TEMPORARY TABLE permission';
        }
        $this->checkDropTable($this->testTableName);
        return $this;
    }

    public function checkSelect(): self
    {
        $this->checkCreateTable($this->testTableName);
        $connection = $this->getConnection();
        try {
            $connection->insert($this->testTableName, ['id' => 1]);
            $connection->select(['id'], $this->testTableName);
        } catch (\Exception $e) {
            $this->messages[] = 'The database user needs SELECT permission';
        }
        $this->checkDropTable($this->testTableName);
        return $this;
    }

    public function checkInsert(): self
    {
        $this->checkCreateTable($this->testTableName);
        $connection = $this->getConnection();
        try {
            $connection->insert($this->testTableName, ['id' => 1]);
        } catch (\Exception $e) {
            $this->messages[] = 'The database user needs INSERT permission';
        }
        $this->checkDropTable($this->testTableName);
        return $this;
    }

    public function checkUpdate(): self
    {
        $this->checkCreateTable($this->testTableName);
        $connection = $this->getConnection();
        try {
            $connection->insert($this->testTableName, ['id' => 1]);
            $connection->update($this->testTableName, ['id' => 2], ['id' => 1]);
        } catch (\Exception $e) {
            $this->messages[] = 'The database user needs UPDATE permission';
        }
        $this->checkDropTable($this->testTableName);
        return $this;
    }

    public function checkDelete(): self
    {
        $this->checkCreateTable($this->testTableName);
        $connection = $this->getConnection();
        try {
            $connection->insert($this->testTableName, ['id' => 1]);
            $connection->delete($this->testTableName, ['id' => 1]);
        } catch (\Exception $e) {
            $this->messages[] = 'The database user needs DELETE permission';
        }
        $this->checkDropTable($this->testTableName);
        return $this;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    private function checkCreateTable(string $tablename): bool
    {
        $connection = $this->getConnection();
        $schema = $connection->getSchemaManager()->createSchema();
        $testTable = $schema->createTable($tablename);
        $testTable->addColumn('id', 'integer', ['unsigned' => true]);
        $testTable->setPrimaryKey(['id']);
        $platform = $connection->getDatabasePlatform();
        try {
            foreach ($schema->toSql($platform) as $query) {
                $connection->executeQuery($query);
            }
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    private function checkDropTable(string $tablename): bool
    {
        $connection = $this->getConnection();
        try {
            $schemaCurrent = $connection->getSchemaManager()->createSchema();
            $schemaNew = $connection->getSchemaManager()->createSchema();

            $schemaNew->dropTable($tablename);
            $platform = $connection->getDatabasePlatform();
            foreach ($schemaCurrent->getMigrateToSql($schemaNew, $platform) as $query) {
                $connection->executeQuery($query);
            }
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    private function getConnection(): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
    }

    private function getSchemaManager(): AbstractSchemaManager
    {
        return $this->getConnection()->createSchemaManager();
    }
}
