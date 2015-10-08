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
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Fetch existing database row on edit
 */
class DatabaseEditRow extends AbstractDatabaseRecordProvider implements FormDataProviderInterface
{
    /**
     * Fetch existing record from database
     *
     * @param array $result
     * @return array
     * @throws \UnexpectedValueException
     */
    public function addData(array $result)
    {
        if ($result['command'] !== 'edit') {
            return $result;
        }

        $databaseRow = $this->getRecordFromDatabase($result['tableName'], $result['vanillaUid']);
        if (!array_key_exists('pid', $databaseRow)) {
            throw new \UnexpectedValueException(
                'Parent record does not have a pid field',
                1437663061
            );
        }
        // Warning: By reference! In case the record is in a workspace, the -1 in pid will be
        // changed to the real pid of the life record here.
        BackendUtility::fixVersioningPid($result['tableName'], $databaseRow);
        $result['databaseRow'] = $databaseRow;

        return $result;
    }
}
