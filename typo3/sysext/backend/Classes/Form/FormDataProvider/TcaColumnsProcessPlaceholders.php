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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Mark columns that are used by input placeholders for further processing
 */
class TcaColumnsProcessPlaceholders implements FormDataProviderInterface
{
    /**
     * Determine which fields are required to render the placeholders and
     * add those to the list of columns that must be processed by the next
     * data providers.
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            // Placeholders are only valid for input and text type fields
            if (!isset($fieldConfig['config']['placeholder'], $fieldConfig['config']['type'])
                || ($fieldConfig['config']['type'] !== 'input' && $fieldConfig['config']['type'] !== 'text')
            ) {
                continue;
            }

            // Process __row|field type placeholders
            if (strpos($fieldConfig['config']['placeholder'], '__row|') === 0) {
                // split field names into array and remove the __row indicator
                $fieldNameArray = array_slice(
                    GeneralUtility::trimExplode('|', $fieldConfig['config']['placeholder'], true),
                    1
                );

                // only the first field is required to be processed as it's the one referring to
                // the current record. All other columns will be resolved in a later pass through
                // the related records.
                if (!empty($fieldNameArray[0])) {
                    $result['columnsToProcess'][] = $fieldNameArray[0];
                }
            }
        }

        return $result;
    }
}
