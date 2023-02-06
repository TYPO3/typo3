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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Schema\SchemaManager\Fixtures\SchemaManager;

use Doctrine\DBAL\Schema\Column;
use TYPO3\CMS\Core\Database\Schema\SchemaManager\MySQLSchemaManager;

/**
 * @internal for testing purpose only, not part of public API.
 */
class FixtureMySQLSchemaManager extends MySQLSchemaManager
{
    /**
     * @param array<string, mixed> $tableColumn
     */
    public function callProtectedGetPortableTableColumnDefinition(array $tableColumn): Column
    {
        return $this->_getPortableTableColumnDefinition(
            tableColumn: $tableColumn,
        );
    }

    /**
     * @param array<string, mixed> $tableColumn
     */
    public function callProcessCustomDoctrineTypesColumnDefinitionFromTraitDirectly(array $tableColumn): Column|null
    {
        return $this->processCustomDoctrineTypesColumnDefinition(
            tableColumn: $tableColumn,
            platform: $this->platform,
        );
    }
}
