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

use TYPO3\CMS\Backend\Form\Exception\DatabaseRecordException;
use TYPO3\CMS\Backend\Form\Exception\DatabaseRecordWorkspaceDeletePlaceholderException;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

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
     * @throws DatabaseRecordWorkspaceDeletePlaceholderException
     * @throws DatabaseRecordException
     */
    public function addData(array $result)
    {
        if ($result['command'] !== 'edit' || !empty($result['databaseRow'])) {
            return $result;
        }

        $databaseRow = $this->getRecordFromDatabase($result['tableName'], $result['vanillaUid']);
        if (!array_key_exists('pid', $databaseRow)) {
            throw new \UnexpectedValueException(
                'Parent record does not have a pid field',
                1437663061
            );
        }
        if (BackendUtility::isTableWorkspaceEnabled($result['tableName'])
            && isset($databaseRow['t3ver_state'])
            && VersionState::cast($databaseRow['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)
        ) {
            // Workspace delete placeholder records (t3ver_state = 2) should never be edited. This is a fallback
            // to suppress editing in case something still links to FormEngine edit of such a record.
            throw new DatabaseRecordWorkspaceDeletePlaceholderException(
                'Record with uid "' . $databaseRow['uid'] . '" from table "' . $result['tableName'] . '" is'
                    . ' a workspace delete placeholder record which can not be edited.',
                1608658396,
                $result['tableName'],
                (int)$databaseRow['uid']
            );
        }

        $result['databaseRow'] = $databaseRow;

        return $result;
    }
}
