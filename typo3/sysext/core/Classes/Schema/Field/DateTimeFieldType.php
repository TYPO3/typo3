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

namespace TYPO3\CMS\Core\Schema\Field;

use TYPO3\CMS\Core\Database\Query\QueryHelper;

/**
 * @internal This is an experimental implementation and might change until TYPO3 v13 LTS
 */
final readonly class DateTimeFieldType extends AbstractFieldType
{
    public function getType(): string
    {
        return 'datetime';
    }

    public function getFormat(): string
    {
        $format = $this->configuration['format'] ?? null;
        $persistenceType = $this->getPersistenceType();
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

    public function isSearchable(): bool
    {
        return $this->getPersistenceType() === null;
    }

    public function getPersistenceType(): ?string
    {
        return in_array($this->configuration['dbType'] ?? null, QueryHelper::getDateTimeTypes(), true) ? $this->configuration['dbType'] : null;
    }

    public function getDefaultValue(): mixed
    {
        $defaultValue = parent::getDefaultValue();
        $isIntegerBased = $this->getPersistenceType() === null;
        if (($defaultValue === 0 || $defaultValue === '0') && $isIntegerBased && !$this->isNullable()) {
            // Map the database-oriented default value `0` to the logical value to `null` if the field is not nullable.
            // (logical `null` is transformed to database value `0` by the persistence layer as this field is not nullable)
            return null;
        }
        return $defaultValue;
    }
}
