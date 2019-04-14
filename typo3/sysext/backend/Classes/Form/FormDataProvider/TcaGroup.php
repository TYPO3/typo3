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

use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Resolve databaseRow field content to the real connected rows for type=group
 */
class TcaGroup implements FormDataProviderInterface
{
    /**
     * Initialize new row with default values from various sources
     *
     * @param array $result
     * @return array
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     */
    public function addData(array $result)
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['config']['type'])
                || $fieldConfig['config']['type'] !== 'group'
            ) {
                continue;
            }

            // Sanitize max items, set to 99999 if not defined
            $result['processedTca']['columns'][$fieldName]['config']['maxitems'] = MathUtility::forceIntegerInRange(
                $fieldConfig['config']['maxitems'] ?? 0,
                0,
                99999
            );
            if ($result['processedTca']['columns'][$fieldName]['config']['maxitems'] === 0) {
                $result['processedTca']['columns'][$fieldName]['config']['maxitems'] = 99999;
            }

            $databaseRowFieldContent = '';
            if (!empty($result['databaseRow'][$fieldName])) {
                $databaseRowFieldContent = (string)$result['databaseRow'][$fieldName];
            }

            $items = [];
            $sanitizedClipboardElements = [];
            $internalType = (string)($fieldConfig['config']['internal_type'] ?? 'db');
            if ($internalType === 'db') {
                if (empty($fieldConfig['config']['allowed'])) {
                    throw new \RuntimeException(
                        'Mandatory TCA config setting "allowed" missing in field "' . $fieldName . '" of table "' . $result['tableName'] . '"',
                        1482250512
                    );
                }

                // In case of vanilla uid, 0 is used to query relations by splitting $databaseRowFieldContent (possible defVals)
                $MMuid = MathUtility::canBeInterpretedAsInteger($result['databaseRow']['uid']) ? $result['databaseRow']['uid'] : 0;

                $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
                $relationHandler->start(
                    $databaseRowFieldContent,
                    $fieldConfig['config']['allowed'] ?? '',
                    $fieldConfig['config']['MM'] ?? '',
                    $MMuid,
                    $result['tableName'] ?? '',
                    $fieldConfig['config'] ?? []
                );
                $relationHandler->getFromDB();
                $relationHandler->processDeletePlaceholder();
                $relations = $relationHandler->getResolvedItemArray();
                foreach ($relations as $relation) {
                    $tableName = $relation['table'];
                    $record = $relation['record'];
                    BackendUtility::workspaceOL($tableName, $record);
                    $title = BackendUtility::getRecordTitle($tableName, $record, false, false);
                    $items[] = [
                        'table' => $tableName,
                        'uid' => $record['uid'] ?? null,
                        'title' => $title,
                        'row' => $record,
                    ];
                }

                // Register elements from clipboard
                $allowed = GeneralUtility::trimExplode(',', $fieldConfig['config']['allowed'], true);
                $clipboard = GeneralUtility::makeInstance(Clipboard::class);
                $clipboard->initializeClipboard();
                if ($allowed[0] !== '*') {
                    // Only some tables, filter them:
                    foreach ($allowed as $tablename) {
                        foreach ($clipboard->elFromTable($tablename) as $recordUid) {
                            $record = BackendUtility::getRecordWSOL($tablename, $recordUid);
                            $sanitizedClipboardElements[] = [
                                'title' => BackendUtility::getRecordTitle($tablename, $record),
                                'value' => $tablename . '_' . $recordUid,
                            ];
                        }
                    }
                } else {
                    // All tables allowed for relation:
                    $clipboardElements = array_keys($clipboard->elFromTable(''));
                    foreach ($clipboardElements as $elementValue) {
                        [$elementTable, $elementUid] = explode('|', $elementValue);
                        $record = BackendUtility::getRecordWSOL($elementTable, (int)$elementUid);
                        $sanitizedClipboardElements[] = [
                            'title' => BackendUtility::getRecordTitle($elementTable, $record),
                            'value' => $elementTable . '_' . $elementUid,
                        ];
                    }
                }
            } elseif ($internalType === 'folder') {
                // Simple list of folders
                $folderList = GeneralUtility::trimExplode(',', $databaseRowFieldContent, true);
                foreach ($folderList as $folder) {
                    if (empty($folder)) {
                        continue;
                    }
                    try {
                        $folderObject = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject($folder);
                        if ($folderObject instanceof Folder) {
                            $items[] = [
                                'folder' => $folder,
                            ];
                        }
                    } catch (Exception $exception) {
                        continue;
                    }
                }
            } else {
                throw new \UnexpectedValueException(
                    'Invalid TCA internal_type of field "' . $fieldName . '" in table ' . $result['tableName']
                    . ': Must not be set, or set to "folder".',
                    1438780511
                );
            }

            $result['databaseRow'][$fieldName] = $items;
            $result['processedTca']['columns'][$fieldName]['config']['clipboardElements'] = $sanitizedClipboardElements;
        }

        return $result;
    }
}
