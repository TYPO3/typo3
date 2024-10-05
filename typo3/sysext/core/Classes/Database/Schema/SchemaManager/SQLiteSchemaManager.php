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

use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSQLitePlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\SQLiteSchemaManager as DoctrineSQLiteSchemaManager;
use Doctrine\DBAL\Types\Type;

/**
 * Extending the doctrine SQLiteSchemaManager to integrate additional processing stuff
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
class SQLiteSchemaManager extends DoctrineSQLiteSchemaManager
{
    use CustomDoctrineTypesColumnDefinitionTrait;
    use ColumnTypeCommentMethodsTrait;

    /**
     * Doctrine DBAL v4 dropped column comment based type api, which TYPO3 still needs. To mitigate this, this
     * method is overridden to reapply the type comment removal, adopted from:
     *
     * - https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Schema/SqliteSchemaManager.php#L338-L344
     *
     * by using {@see ColumnTypeCommentMethodsTrait::determineColumnType()} to reuse methods.
     */
    protected function _getPortableTableColumnList(string $table, string $database, array $tableColumns): array
    {
        $list = parent::_getPortableTableColumnList($table, $database, $tableColumns);
        foreach ($list as $columnName => $column) {
            $fakeTableColumn = [
                'type' => $column->getType(),
                'comment' => $column->getComment(),
            ];
            $type = $this->determineColumnType('', $fakeTableColumn);
            if ($type !== '') {
                $column->setType(Type::getType($type));
            }
            $column->setComment($fakeTableColumn['comment']);
        }

        return $list;
    }

    /**
     * Gets Table Column Definition.
     *
     * @param array<string, mixed> $tableColumn
     */
    protected function _getPortableTableColumnDefinition(array $tableColumn): Column
    {
        /** @var DoctrineSQLitePlatform $platform */
        $platform = $this->platform;
        return $this->processCustomDoctrineTypesColumnDefinition($tableColumn, $platform)
            ?? parent::_getPortableTableColumnDefinition($tableColumn);
    }
}
