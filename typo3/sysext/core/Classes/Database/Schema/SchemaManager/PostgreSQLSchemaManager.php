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

use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Doctrine\DBAL\Schema\Column;

/**
 * Extending the doctrine PostgreSQLSchemaManager to integrate additional processing stuff
 * due to the dropped event system with `doctrine/dbal 4.x`.
 *
 * For example, this is used to process custom doctrine types.
 *
 * Platform specific SchemaManager are extended to manipulate the schema handling. TYPO3 needs to
 *  do that to provide additional doctrine type handling and other workarounds or alignments. Long
 *  time this have been done by using the `doctrine EventManager` to hook into several places, which
 *  no longer exists.
 *
 * @link https://github.com/doctrine/dbal/blob/3.7.x/UPGRADE.md#deprecated-not-setting-a-schema-manager-factory
 * @link https://github.com/doctrine/dbal/blob/3.7.x/UPGRADE.md#deprecated-extension-via-doctrine-event-manager
 *
 * @internal not part of the public Core API.
 */
class PostgreSQLSchemaManager extends \Doctrine\DBAL\Schema\PostgreSQLSchemaManager
{
    use CustomDoctrineTypesColumnDefinitionTrait;

    /**
     * Gets Table Column Definition.
     *
     * @param array<string, mixed> $tableColumn
     */
    protected function _getPortableTableColumnDefinition(array $tableColumn): Column
    {
        /** @var DoctrinePostgreSQLPlatform $platform */
        $platform = $this->platform;
        return $this->processCustomDoctrineTypesColumnDefinition($tableColumn, $platform)
            ?? parent::_getPortableTableColumnDefinition($tableColumn);
    }
}
