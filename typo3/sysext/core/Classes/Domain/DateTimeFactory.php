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

namespace TYPO3\CMS\Core\Domain;

use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Schema\Field\DateTimeFieldType;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @internal
 */
final readonly class DateTimeFactory
{
    public static function createFromDatabaseValue(int|string|null $value, DateTimeFieldType $fieldInformation): ?\DateTimeImmutable
    {
        return self::fromDatabase(
            $value,
            $fieldInformation->isNullable(),
            $fieldInformation->getFormat(),
            $fieldInformation->getPersistenceType(),
        );
    }

    public static function createFomDatabaseValueAndTCAConfig(int|string|null $value, array $fieldConfig): ?\DateTimeImmutable
    {
        $persistenceType = in_array($fieldConfig['dbType'] ?? null, QueryHelper::getDateTimeTypes(), true) ? $fieldConfig['dbType'] : null;
        $isNative = $persistenceType !== null;
        $isNullable = $fieldConfig['nullable'] ?? $isNative;
        $format = self::getFormatFromTCAConfig($fieldConfig);
        return self::fromDatabase(
            $value,
            $isNullable,
            $format,
            $persistenceType
        );
    }

    public static function getFormatFromTCAConfig(array $fieldConfig): string
    {
        $format = $fieldConfig['format'] ?? null;
        $persistenceType = in_array($fieldConfig['dbType'] ?? null, QueryHelper::getDateTimeTypes(), true) ? $fieldConfig['dbType'] : null;
        // A native time field must not be formatted as date
        if (($format === 'datetime' || $format === 'date') && $persistenceType === 'time') {
            return 'timesec';
        }
        // A native date field must not be formatted as time
        if (($format === 'time' || $format === 'timesec') && $persistenceType === 'date') {
            return 'date';
        }
        if (in_array($format, ['datetime', 'date', 'time', 'timesec'], true)) {
            return $format;
        }
        if ($persistenceType !== null) {
            return $persistenceType === 'time' ? 'timesec' : $persistenceType;
        }
        return 'datetime';
    }

    /**
     * Create a DateTimeImmutable object from a unix timestamp in server localtime
     *
     * Alternative to \DateTimeImmutable('@…') which forces UTC timezone
     */
    public static function createFromTimestamp(int $timestamp): \DateTimeImmutable
    {
        // Create a new DateTime object in current timezone
        //
        // Note: As documented by PHP, `\DateTime` or `\DateTimeImmutable`
        // objects created from timestamps (e.g., '@12345678') as the
        // first constructor argument will use UTC as timezone instead of localtime,
        // therefore we must not initialize with a timestamp directly.
        $datetime = new \DateTimeImmutable();

        // Apply timestamp (which will not change the objects timezone)
        return $datetime->setTimestamp($timestamp);
    }

    private static function fromDatabase(
        int|string|null $value,
        bool $isNullable,
        string $format,
        ?string $persistenceType
    ): ?\DateTimeImmutable {
        if ($value === null || $value === '') {
            return null;
        }

        $emptyFormat = QueryHelper::getDateTimeFormats()[$persistenceType]['empty'] ?? null;
        // A regular empty value is null for nullable fields
        $emptyValue = $isNullable ? null : ($emptyFormat ?? 0);
        // A legacy empty value is "0000-00-00" or "0000-00-00 00:00:00" stored
        // in a nullable native DATE or DATETIME field (which should already use
        // a proper `null` value, but still has a legacy empty value set).
        $legacyEmptyValue = $persistenceType === 'date' || $persistenceType === 'datetime' ? $emptyFormat : null;

        if (MathUtility::canBeInterpretedAsInteger($value)) {
            $value = (int)$value;
        }
        if ($value === $emptyValue || $value === $legacyEmptyValue) {
            return null;
        }

        try {
            $datetime = match (true) {
                is_int($value) && ($format === 'time' || $format === 'timesec') => new \DateTimeImmutable(
                    // time(sec) is stored as elapsed seconds in DB and has no defined date associated.
                    // Per convention we map to 1970-01-01 for the sake of a reliable date.
                    // We still want a PHP localtime timezone in the DateTime object set,
                    // therefore we interpret the second as UTC time on 1970-01-01T00:00:00
                    // and map the resulting value to PHP localtime
                    gmdate(DateTimeFormat::ISO8601_LOCALTIME, $value)
                ),
                // Unix timestamp
                is_int($value) => self::createFromTimestamp($value),
                // The database always contains server localtime in native fields.
                // The field value is something like "2016-01-01" or "2016-01-01 10:11:12.
                default => new \DateTimeImmutable($value),
            };
        } catch (\Exception|\DateMalformedStringException $e) { // @todo drop catch(\Exception) once php 8.3 is minimum
            throw new \InvalidArgumentException('Invalid date provided', 1743159490, $e);
        }

        return match ($format) {
            // time(sec) is stored as elapsed seconds in DB, hence we normalize it as time on 1970-01-01 for consistency
            'time' => $datetime->setDate(1970, 1, 1)->setTime((int)$datetime->format('H'), (int)$datetime->format('i'), 0),
            'timesec' => $datetime->setDate(1970, 1, 1),
            'date' => $datetime->setTime(0, 0, 0),
            default => $datetime,
        };
    }
}
