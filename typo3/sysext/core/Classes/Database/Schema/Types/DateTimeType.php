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

use Doctrine\DBAL\Platforms\AbstractPlatform as DoctrineAbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;

/**
 * This custom type extends doctrine native DateTimeType to allow a
 * formatted string (in "Y-m-d H:i:s") directly, in addition to a DateTimeInterface.
 *
 * @internal not part of public core API.
 */
class DateTimeType extends \Doctrine\DBAL\Types\DateTimeType
{
    public function convertToDatabaseValue($value, DoctrineAbstractPlatform $platform): ?string
    {
        if ($value === null || (is_string($value) && $value !== '')) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format($platform->getDateTimeFormatString());
        }

        throw InvalidType::new($value, self::getTypeRegistry()->lookupName($this), ['null', 'string', 'DateTime']);
    }
}
