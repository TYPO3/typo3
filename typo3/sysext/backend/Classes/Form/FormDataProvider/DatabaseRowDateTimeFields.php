<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Database\Query\QueryHelper;

/**
 * Migrate date and datetime db field values to timestamp
 */
class DatabaseRowDateTimeFields implements FormDataProviderInterface
{
    /**
     * Migrate date and datetime db field values to timestamp
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        $dateTimeFormats = QueryHelper::getDateTimeFormats();
        foreach ($result['processedTca']['columns'] as $column => $columnConfig) {
            if (isset($columnConfig['config']['dbType'])
                && ($columnConfig['config']['dbType'] === 'date' || $columnConfig['config']['dbType'] === 'datetime')
            ) {
                if (!empty($result['databaseRow'][$column])
                    &&  $result['databaseRow'][$column] !== $dateTimeFormats[$columnConfig['config']['dbType']]['empty']
                ) {
                    // Create an ISO-8601 date from current field data; the database always contains UTC
                    // The field value is something like "2016-01-01" or "2016-01-01 10:11:12", so appending "UTC"
                    // makes date() treat it as a UTC date (which is what we store in the database).
                    $result['databaseRow'][$column] = date('c', strtotime($result['databaseRow'][$column] . ' UTC'));
                } else {
                    $result['databaseRow'][$column] = null;
                }
            }
            // its a UNIX timestamp! We do not modify this here, as it will only be treated as a datetime because
                // of eval being set to "date" or "datetime". This is handled in InputTextElement then.
        }
        return $result;
    }
}
