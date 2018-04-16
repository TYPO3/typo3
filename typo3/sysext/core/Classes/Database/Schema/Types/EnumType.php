<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Database\Schema\Types;

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

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Type that maps an TYPE field.
 */
class EnumType extends Type
{
    const TYPE = 'enum';

    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param array $fieldDeclaration The field declaration.
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform The currently used database platform.
     *
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        $quotedValues = array_map([$platform, 'quoteStringLiteral'], $fieldDeclaration['unquotedValues']);

        return sprintf('ENUM(%s)', implode(', ', $quotedValues));
    }

    /**
     * Gets the name of this type.
     *
     * @return string
     */
    public function getName(): string
    {
        return static::TYPE;
    }
}
