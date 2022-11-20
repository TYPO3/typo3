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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Mocks;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class MockPlatform extends AbstractPlatform
{
    /**
     * Gets the SQL Snippet used to declare a BLOB column type.
     *
     * @return string|void
     * @throws \Doctrine\DBAL\Exception
     */
    public function getBlobTypeDeclarationSQL(array $field)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * Returns the SQL snippet that declares a boolean column.
     */
    public function getBooleanTypeDeclarationSQL(array $columnDef): ?string
    {
        return null;
    }

    /**
     * Returns the SQL snippet that declares a 4 byte integer column.
     */
    public function getIntegerTypeDeclarationSQL(array $columnDef): ?string
    {
        return null;
    }

    /**
     * Returns the SQL snippet that declares an 8 byte integer column.
     */
    public function getBigIntTypeDeclarationSQL(array $columnDef): ?string
    {
        return null;
    }

    /**
     * Returns the SQL snippet that declares a 2 byte integer column.
     */
    public function getSmallIntTypeDeclarationSQL(array $columnDef): ?string
    {
        return null;
    }

    /**
     * Returns the SQL snippet that declares common properties of an integer column.
     */
    public function _getCommonIntegerTypeDeclarationSQL(array $columnDef): ?string
    {
        return null;
    }

    /**
     * Returns the SQL snippet used to declare a VARCHAR column type.
     */
    public function getVarcharTypeDeclarationSQL(array $field): string
    {
        return 'DUMMYVARCHAR()';
    }

    /**
     * Returns the SQL snippet used to declare a CLOB column type.
     */
    public function getClobTypeDeclarationSQL(array $field): string
    {
        return 'DUMMYCLOB';
    }

    /**
     * Returns the SQL snippet to declare a JSON field.
     *
     * By default this maps directly to a CLOB and only maps to more
     * special datatypes when the underlying databases support this datatype.
     *
     * @param array $field
     */
    public function getJsonTypeDeclarationSQL(array $field): string
    {
        return 'DUMMYJSON';
    }

    /**
     * Returns the SQL snippet used to declare a BINARY/VARBINARY column type.
     *
     * @param array $field The column definition.
     */
    public function getBinaryTypeDeclarationSQL(array $field): string
    {
        return 'DUMMYBINARY';
    }

    /**
     * Gets the name of the platform.
     *
     * @return string
     */
    public function getName(): ?string
    {
        return 'mock';
    }

    /**
     * Lazy load Doctrine Type Mappings.
     */
    protected function initializeDoctrineTypeMappings(): void
    {
    }

    /**
     * @param int $length
     * @param bool $fixed
     *
     * @return string
     *
     * @throws \Doctrine\DBAL\Exception If not supported on this platform.
     */
    protected function getVarcharTypeDeclarationSQLSnippet($length, $fixed): ?string
    {
        return '';
    }

    /**
     * Returns the class name of the reserved keywords list.
     *
     *
     * @throws \Doctrine\DBAL\Exception If not supported on this platform.
     */
    protected function getReservedKeywordsClass(): string
    {
        return MockKeywordList::class;
    }

    public function getCurrentDatabaseExpression(): string
    {
        return "''";
    }
}
