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
 * Mark columns that are used to generate the record title for
 * further processing
 */
class TcaColumnsProcessRecordTitle implements FormDataProviderInterface
{
    /**
     * Determine which fields are required to render the record title and
     * add those to the list of columns that must be processed by the next
     * data providers.
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        // If a field name is given for the label we need to process the field
        if (!empty($result['processedTca']['ctrl']['label'])) {
            $result['columnsToProcess'][] = $result['processedTca']['ctrl']['label'];
        }

        // Add alternative fields that might be used to render the label
        if (!empty($result['processedTca']['ctrl']['label_alt'])) {
            $labelColumns = GeneralUtility::trimExplode(',', $result['processedTca']['ctrl']['label_alt'], true);
            $result['columnsToProcess'] = array_merge($result['columnsToProcess'], array_filter($labelColumns));
        }

        // Add foreign_label to process list if exists and the record is an inline child
        if ($result['isInlineChild'] && isset($result['inlineParentConfig']['foreign_label'])) {
            $result['columnsToProcess'][] = $result['inlineParentConfig']['foreign_label'];
        }

        // Add symmetric_label to process list if exists and the record is an inline child
        if ($result['isInlineChild'] && isset($result['inlineParentConfig']['symmetric_label'])) {
            $result['columnsToProcess'][] = $result['inlineParentConfig']['symmetric_label'];
        }

        return $result;
    }
}
