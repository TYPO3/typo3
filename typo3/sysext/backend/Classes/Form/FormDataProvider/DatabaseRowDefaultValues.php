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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handle TCA default values on row. This affects existing rows as well as new rows.
 *
 * Hint: Even after this class it is NOT safe no rely that *all* fields from
 * columns are set in databaseRow.
 */
class DatabaseRowDefaultValues implements FormDataProviderInterface
{
    /**
     * Initialize new row with default values from various sources
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        $databaseRow = $result['databaseRow'];

        $newRow = $databaseRow;
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            // Keep current value if it can be resolved to "the is something" directly
            if (isset($databaseRow[$fieldName])) {
                $newRow[$fieldName] = $databaseRow[$fieldName];
                continue;
            }

            // Special handling for eval null
            if (!empty($fieldConfig['config']['eval']) && GeneralUtility::inList($fieldConfig['config']['eval'], 'null')) {
                if (// Field exists and is set to NULL
                    array_key_exists($fieldName, $databaseRow)
                    // Default NULL is set, and this is a new record!
                    || (array_key_exists('default', $fieldConfig['config']) && $fieldConfig['config']['default'] === null)
                ) {
                    $newRow[$fieldName] = null;
                } else {
                    $newRow[$fieldName] = (string)$fieldConfig['config']['default'];
                }
            } else {
                // Fun part: This forces empty string for any field even if no default is set. Unsure if that is a good idea.
                $newRow[$fieldName] = (string)$fieldConfig['config']['default'];
            }
        }

        $result['databaseRow'] = $newRow;

        return $result;
    }
}
