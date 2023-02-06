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

namespace TYPO3\CMS\Core\Database\Schema\SchemaManager;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform as DoctrineAbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSQLitePlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\SchemaManagerFactory;

/**
 * Custom SchemaManager factory to ensure that the extended SchemaManager
 * classes are used for supported platforms. Without this, custom schema
 * handling would be cut off.
 *
 * Note:    This is the transition to mitigate the dropped doctrine event manager
 *          since `doctrine/dbal ^4`.
 *
 * @see https://github.com/doctrine/dbal/blob/3.7.x/UPGRADE.md#deprecated-not-setting-a-schema-manager-factory
 * @see https://github.com/doctrine/dbal/blob/3.7.x/UPGRADE.md#deprecated-extension-via-schema-definition-events
 *
 * @internal for core internal usage and not part of public core API.
 */
final class CoreSchemaManagerFactory implements SchemaManagerFactory
{
    public function createSchemaManager(Connection $connection): AbstractSchemaManager
    {
        $platform = $connection->getDatabasePlatform();
        // Platform specific SchemaManager are extended to manipulate the schema handling. TYPO3 needs to
        // do that to provide additional doctrine type handling and other workarounds or alignments. Long
        // time this have been done by using the `doctrine EventManager` to hook into several places, which
        // no longer exists.
        //
        // @link https://github.com/doctrine/dbal/blob/3.7.x/UPGRADE.md#deprecated-not-setting-a-schema-manager-factory
        // @link https://github.com/doctrine/dbal/blob/3.7.x/UPGRADE.md#deprecated-extension-via-doctrine-event-manager
        // @todo Consider make check on SchemaManager instance retrieved from $platform->createSchemaManager()
        return match (true) {
            $platform instanceof DoctrineSQLitePlatform => new SQLiteSchemaManager($connection, $platform),
            $platform instanceof DoctrinePostgreSQLPlatform => new PostgreSQLSchemaManager($connection, $platform),
            $platform instanceof DoctrineMariaDBPlatform,
            $platform instanceof DoctrineMySQLPlatform,
            $platform instanceof DoctrineAbstractMySQLPlatform => new MySQLSchemaManager($connection, $platform),
            default => $platform->createSchemaManager($connection),
        };
    }
}
