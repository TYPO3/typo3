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

namespace TYPO3\CMS\Core\Database\Schema\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Type that maps a TYPE field.
 *
 * @internal not part of public core API.
 *
 * @todo EnumType does not work for SQLite and PostgresSQL. SQLite supports it with a slightly other syntax and
 *       PostgreSQL needs to create a custom type with a human-readable name, which is not reasonable either. Consider
 *       to deprecate and drop ENUM support due not having compatibility for all supported database systems.
 *         * @see https://www.postgresql.org/docs/current/datatype-enum.html#DATATYPE-ENUM (PostgreSQL)
 *         * @see https://www.sqlite.org/lang_createtable.html#ckconst (SQlite)
 *         * @see https://stackoverflow.com/questions/5299267/how-to-create-enum-type-in-sqlite
 */
class EnumType extends Type
{
    public const TYPE = 'enum';

    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param array $fieldDeclaration The field declaration.
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform The currently used database platform.
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        $quotedValues = array_map($platform->quoteStringLiteral(...), $fieldDeclaration['unquotedValues']);

        return sprintf('ENUM(%s)', implode(', ', $quotedValues));
    }

    /**
     * Gets the name of this type.
     * @todo Remove this method with doctrine/dbal 4.0 upgrade.
     * @see https://github.com/doctrine/dbal/blob/3.8.x/UPGRADE.md#deprecated-typegetname
     */
    public function getName(): string
    {
        return static::TYPE;
    }
}
