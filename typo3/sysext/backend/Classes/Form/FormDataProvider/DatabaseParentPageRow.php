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
 * Fetch parent page row from database if possible
 */
class DatabaseParentPageRow extends AbstractDatabaseRecordProvider implements FormDataProviderInterface
{
    /**
     * Add parent page row of existing row to result
     * parentPageRow will stay NULL in result if a record is added or edited below root node
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        // $parentPageRow end up NULL if a record added or edited on root node
        $parentPageRow = null;
        if ($result['command'] === 'new') {
            if ($result['vanillaUid'] < 0) {
                // vanillaUid points to a neighbor record in same table - get its record and its pid from there to find parent record
                $neighborRow = $this->getRecordFromDatabase($result['tableName'], abs($result['vanillaUid']));
                $result['neighborRow'] = $neighborRow;
                // uid of page the record is located in
                $neighborRowPid = (int)$neighborRow['pid'];
                if ($neighborRowPid !== 0) {
                    // Fetch the parent page record only if it is not the '0' root
                    $parentPageRow = $this->getRecordFromDatabase('pages', $neighborRowPid);
                }
            } elseif ($result['vanillaUid'] > 0) {
                // vanillaUid points to a page uid directly
                $parentPageRow = $this->getRecordFromDatabase('pages', $result['vanillaUid']);
            }
        } else {
            // On "edit", the row itself has been fetched already
            if ($result['databaseRow']['pid'] > 0) {
                $parentPageRow = $this->getRecordFromDatabase('pages', $result['databaseRow']['pid']);
            }
        }
        $result['parentPageRow'] = $parentPageRow;

        return $result;
    }
}
