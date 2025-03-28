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
use TYPO3\CMS\Core\Domain\DateTimeFactory;
use TYPO3\CMS\Core\Domain\DateTimeFormat;

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

        foreach ($result['processedTca']['columns'] as $column => $columnConfig) {
            $dbType = $columnConfig['config']['dbType'] ?? '';
            if (($columnConfig['config']['type'] ?? '') !== 'datetime'
                || !in_array($dbType, $dateTimeTypes, true)
            ) {
                // it's a UNIX timestamp! We do not modify this here, as it will only be treated as a datetime because
                // of eval being set to "date" or "datetime". This is handled in InputTextElement then.
                continue;
            }
            try {
                // Create an unqualified ISO-8601 date from current field data or null
                $result['databaseRow'][$column] = DateTimeFactory::createFomDatabaseValueAndTCAConfig(
                    $result['databaseRow'][$column] ?? null,
                    $columnConfig['config'] ?? [],
                )?->format(DateTimeFormat::ISO8601_LOCALTIME);
            } catch (\InvalidArgumentException) {
                $result['databaseRow'][$column] = null;
            }
        }
        return $result;
    }
}
