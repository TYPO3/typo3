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

/**
 * Remove fields from columns not in showitem or palette list or needed otherwise
 * This is a relatively effective performance improvement preventing other
 * providers from resolving stuff of fields that are not shown later.
 * Especially effective for fal related tables.
 */
class TcaColumnsRemoveUnused implements FormDataProviderInterface
{
    /**
     * Remove unused column fields to speed up further processing.
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        $columnsToRemove = array_diff(array_keys($result['processedTca']['columns']), $result['columnsToProcess']);
        foreach ($columnsToRemove as $column) {
            unset($result['processedTca']['columns'][$column]);
        }

        return $result;
    }
}
