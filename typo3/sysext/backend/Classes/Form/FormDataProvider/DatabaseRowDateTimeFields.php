<?php

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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Database\Query\QueryHelper;

/**
 * Migrate date and datetime db field values to timestamp
 */
class DatabaseRowDateTimeFields implements FormDataProviderInterface
{
    /**
     * Migrate native type=datetime dbType=datetime|date|time field values to ISO8601 dates
     *
     * @return array
     */
    public function addData(array $result)
    {
        $dateTimeTypes = QueryHelper::getDateTimeTypes();
        $dateTimeFormats = QueryHelper::getDateTimeFormats();

        foreach ($result['processedTca']['columns'] as $column => $columnConfig) {
            $dbType = $columnConfig['config']['dbType'] ?? '';
            if (($columnConfig['config']['type'] ?? '') !== 'datetime'
                || !in_array($dbType, $dateTimeTypes, true)
            ) {
                // it's a UNIX timestamp! We do not modify this here, as it will only be treated as a datetime because
                // of eval being set to "date" or "datetime". This is handled in InputTextElement then.
                continue;
            }
            // ensure the column's value is set
            $result['databaseRow'][$column] = $result['databaseRow'][$column] ?? null;

            // Nullable fields do not need treatment
            $isNullable = $columnConfig['config']['nullable'] ?? false;
            if ($isNullable && $result['databaseRow'][$column] === null) {
                continue;
            }

            $format = $dateTimeFormats[$dbType] ?? [];
            $emptyValueFormat = $format['empty'] ?? null;
            // Only the empty value (00:00:00) of dbType=time is a value that is also a valid value,
            // DATE and DATETIME empty-values like 0000-00-00 are *not* valid dates and therefore should
            // be represented as `null`.
            $emptyValueIsInvalidDateString = $dbType === 'date' || $dbType === 'datetime';
            $emptyValueIsValidDateString = $dbType === 'time';

            if (
                empty($result['databaseRow'][$column]) ||
                (
                    $result['databaseRow'][$column] === $emptyValueFormat && (
                        // treat 0000-00-00 for DATE/DATETIME fields as NULL,
                        // even for NULLable fields which should not have this value
                        // in theory, but may have not been migrated to NULL yet.
                        $emptyValueIsInvalidDateString ||
                        // Treat 00:00:00 for TIME fields as NULL if field is *not* nullable, skip
                        // for nullable fields as 00:00:00 is to be considered a valid midnight time.
                        ($emptyValueIsValidDateString && !$isNullable)
                    )
                )
            ) {
                $result['databaseRow'][$column] = null;
                continue;
            }

            // Create an ISO-8601 date from current field data; the database always contains UTC
            // The field value is something like "2016-01-01" or "2016-01-01 10:11:12", so appending "UTC"
            // makes date() treat it as a UTC date (which is what we store in the database).
            $result['databaseRow'][$column] = date('c', (int)strtotime($result['databaseRow'][$column] . ' UTC'));
        }
        return $result;
    }
}
