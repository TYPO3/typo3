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
use TYPO3\CMS\Core\Database\DatabaseConnection;

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
        $dateTimeFormats = $this->getDatabase()->getDateTimeFormats($result['tableName']);
        foreach ($result['processedTca']['columns'] as $column => $columnConfig) {
            if (isset($columnConfig['config']['dbType'])
                && ($columnConfig['config']['dbType'] === 'date' || $columnConfig['config']['dbType'] === 'datetime')
            ) {
                if (!empty($result['databaseRow'][$column])
                    &&  $result['databaseRow'][$column] !== $dateTimeFormats[$columnConfig['config']['dbType']]['empty']
                ) {
                    // Create a timestamp from current field data
                    $result['databaseRow'][$column] = strtotime($result['databaseRow'][$column]);
                } else {
                    // Set to 0 timestamp
                    $result['databaseRow'][$column] = 0;
                }
            }
        }
        return $result;
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabase()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
