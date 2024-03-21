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

namespace TYPO3\CMS\Core\Database\Platform\Traits;

use Doctrine\DBAL\Types;

/**
 * @internal not part of Public Core API.
 */
trait MySQLDefaultValueDeclarationSQLOverrideTrait
{
    /**
     * Obtains DBMS specific SQL code portion needed to set a default value
     * declaration to be used in statements like CREATE TABLE.
     *
     * Oracle MySQL does not support default values on TEXT/BLOB columns until 8.0.13. Doctrine DBAL 4.x supports
     * earlier version of MySQL and decided to unset the column default value for TextType and BlobType generally
     * in the MySQL platform variants. This trait reintroduces the AbstractPlatform implementation to be used in
     * the TYPO3 platform overrides for MySQL to remove this limitation and allow the use of default value as
     * expressions.
     *
     * @see \Doctrine\DBAL\Platforms\MySQLPlatform::getDefaultValueDeclarationSQL()
     *
     * @param mixed[] $column The column definition array.
     *
     * @return string DBMS specific SQL code portion needed to set a default value.
     */
    public function getDefaultValueDeclarationSQL(array $column): string
    {
        $type = $column['type'] ?? null;

        // MySQL 8.0.13+ supports default for TEXT and BLOB fields only as expression, so we need to handle this
        // here properly for valid default value types.
        if ($type instanceof Types\TextType || $type instanceof Types\JsonType || $type instanceof Types\BlobType) {
            if (! isset($column['default'])) {
                return empty($column['notnull']) ? ' DEFAULT (NULL)' : '';
            }
            $default = $column['default'];
            if (is_int($default) || is_float($default)) {
                return ' DEFAULT (' . $default . ')';
            }
            return ' DEFAULT (' . $this->quoteStringLiteral($default) . ')';
        }

        if (! isset($column['default'])) {
            return empty($column['notnull']) ? ' DEFAULT NULL' : '';
        }

        $default = $column['default'];

        if (! isset($column['type'])) {
            return " DEFAULT '" . $default . "'";
        }

        if ($type instanceof Types\PhpIntegerMappingType) {
            return ' DEFAULT ' . $default;
        }

        if ($type instanceof Types\PhpDateTimeMappingType && $default === $this->getCurrentTimestampSQL()) {
            return ' DEFAULT ' . $this->getCurrentTimestampSQL();
        }

        if ($type instanceof Types\PhpTimeMappingType && $default === $this->getCurrentTimeSQL()) {
            return ' DEFAULT ' . $this->getCurrentTimeSQL();
        }

        if ($type instanceof Types\PhpDateMappingType && $default === $this->getCurrentDateSQL()) {
            return ' DEFAULT ' . $this->getCurrentDateSQL();
        }

        if ($type instanceof Types\BooleanType) {
            return ' DEFAULT ' . $this->convertBooleans($default);
        }

        if (is_int($default) || is_float($default)) {
            return ' DEFAULT ' . $default;
        }

        return ' DEFAULT ' . $this->quoteStringLiteral($default);
    }
}
