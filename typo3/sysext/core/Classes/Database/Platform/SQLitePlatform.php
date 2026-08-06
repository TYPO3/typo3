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

namespace TYPO3\CMS\Core\Database\Platform;

use Doctrine\DBAL\Platforms\SQLitePlatform as DoctrineSQLitePlatform;

/**
 * doctrine/dbal 4+ removed the old doctrine event system. The new way is to extend the platform
 * class(es) and directly override the methods instead of consuming events. Therefore, we need to
 * extend the platform classes to provide some changes for TYPO3 database schema operations.
 *
 * SQLite is dynamically typed and stores the declared column type verbatim, which is what
 * `pragma_table_info` reports back. That allows declaring dedicated type names for Doctrine types
 * SQLite has no native counterpart for, and mapping them back on introspection - instead of writing
 * a `(DC2Type:<name>)` column comment as the only way to recover the type.
 *
 * Avoiding that comment is not cosmetic: SQLite cannot parse an inline comment in the simplified
 * `ALTER TABLE <table> ADD COLUMN <definition>` form doctrine emits, so adding such a column to an
 * existing table failed with "incomplete input".
 *
 * Existing installations are not affected. Columns created before this carry `CLOB`/`CHAR(36)` plus
 * the type comment and are still resolved to the same Doctrine type by
 * {@see \TYPO3\CMS\Core\Database\Schema\SchemaManager\SQLiteSchemaManager}. Schema comparison uses
 * the generated column declaration, not the stored database type, so no change is detected.
 *
 * @internal not part of Public Core API.
 */
class SQLitePlatform extends DoctrineSQLitePlatform
{
    /**
     * @param array<string, mixed> $column
     */
    public function getJsonTypeDeclarationSQL(array $column): string
    {
        return 'JSON';
    }

    /**
     * @param array<string, mixed> $column
     */
    public function getGuidTypeDeclarationSQL(array $column): string
    {
        return 'UUID';
    }

    protected function initializeDoctrineTypeMappings(): void
    {
        parent::initializeDoctrineTypeMappings();
        // Counterpart of the declarations above, mapping the stored type name back to the Doctrine type.
        $this->doctrineTypeMapping['json'] = 'json';
        $this->doctrineTypeMapping['uuid'] = 'guid';
    }
}
