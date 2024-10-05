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

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

/**
 * Provides a couple of methods use-full to restore Doctrine DBAL 3 and earlier behaviour to
 * respect doctrine type override within column comment.
 *
 * Used within:
 *
 * - {@see MySQLSchemaManager::parentGetPortableTableColumnDefinition()}
 * - {@see PostgreSQLSchemaManager::parentGetPortableTableColumnDefinition()}
 * - {@see SQLiteSchemaManager::parentGetPortableTableColumnDefinition()}
 *
 * This trait contains code cloned or adopted from Doctrine DBAL 3 removed from {@see AbstractSchemaManager} hierarchy:
 *
 * - https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Schema/AbstractSchemaManager.php#L1730-L1752
 * - https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Schema/AbstractSchemaManager.php#L1730-L1752
 * - https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Schema/SqliteSchemaManager.php#L338-L344
 *
 * @internal for use in extended {@see AbstractSchemaManager} hierarchy and not part of public Core API.
 */
trait ColumnTypeCommentMethodsTrait
{
    /**
     * Determine Doctrine Type based on column database type with respecting
     * comment definition as overrule type. That reflects the old behaviour
     * of Doctrine DBAL 3 and older.
     *
     * Code has been adopted from Doctrine DBAL v3 to be used and integrated within extended {@see AbstractSchemaManager}
     * hierarchy to restore removed behaviour, see:
     * - https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Schema/MySQLSchemaManager.php#L186-L192
     * - https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Schema/PostgreSQLSchemaManager.php#L427-L429
     * - https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Schema/SqliteSchemaManager.php#L338-L344
     */
    private function determineColumnType(string $dbType, array &$tableColumn): string
    {
        $platform = ($this instanceof AbstractPlatform) ? $this : $this->platform;
        $type = $dbType !== '' ? $platform->getDoctrineTypeMapping($dbType) : '';
        if (isset($tableColumn['comment'])) {
            $type = $this->extractDoctrineTypeFromComment($tableColumn['comment'], $type);
            $tableColumn['comment'] = $this->removeDoctrineTypeFromComment($tableColumn['comment'], $type);
        }
        return $type;
    }

    /**
     * Doctrine DBAL 4 removed this from the {@see AbstractSchemaManager} hierarchy, and is here cloned, see:
     * https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Schema/AbstractSchemaManager.php#L1730-L1752
     *
     * Given a table comment this method tries to extract a typehint for Doctrine Type, or returns
     * the type given as default.
     *
     * @param string|null $comment
     * @param string      $currentType
     *
     * @return string
     *@internal This method should be only used from within the extended AbstractSchemaManager class hierarchy.
     */
    private function extractDoctrineTypeFromComment(?string $comment, string $currentType): string
    {
        if ($comment !== null && preg_match('(\(DC2Type:(((?!\)).)+)\))', $comment, $match) === 1) {
            return $match[1];
        }

        return $currentType;
    }

    /**
     * Doctrine DBAL 4 removed this from the {@see AbstractSchemaManager} hierarchy, and is here cloned, see:
     * https://github.com/doctrine/dbal/blob/61446f07fcb522414d6cfd8b1c3e5f9e18c579ba/src/Schema/AbstractSchemaManager.php#L1754-L1773
     *
     * @param string|null $comment
     * @param string|null $type
     *
     * @return string|null
     *@internal This method should be only used from within the extended AbstractSchemaManager class hierarchy.
     */
    private function removeDoctrineTypeFromComment(?string $comment = null, ?string $type = null): ?string
    {
        if ($comment === null) {
            return null;
        }

        return str_replace('(DC2Type:' . $type . ')', '', $comment);
    }
}
