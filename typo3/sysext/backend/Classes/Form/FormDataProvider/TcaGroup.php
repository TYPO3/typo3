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
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

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
     * @todo: Should not implode valid values with | again, container & elements should work
     * @todo: with the array as it was done for select items
     * @throws \UnexpectedValueException
     */
    public function addData(array $result)
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['config']['type'])
                || $fieldConfig['config']['type'] !== 'group'
                || empty($fieldConfig['config']['internal_type'])
            ) {
                continue;
            }

            $databaseRowFieldContent = '';
            if (!empty($result['databaseRow'][$fieldName])) {
                $databaseRowFieldContent = (string)$result['databaseRow'][$fieldName];
            }

            $internalType = $fieldConfig['config']['internal_type'];
            if ($internalType === 'file_reference' || $internalType === 'file') {
                $files = [];
                // Simple list of files
                $fileList = GeneralUtility::trimExplode(',', $databaseRowFieldContent, true);
                foreach ($fileList as $file) {
                    if ($file) {
                        $files[] = rawurlencode($file) . '|' . rawurlencode(PathUtility::basename($file));
                    }
                }
                $result['databaseRow'][$fieldName] = implode(',', $files);
            } elseif ($internalType === 'db') {
                /** @var $relationHandler RelationHandler */
                $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
                $relationHandler->start(
                    $databaseRowFieldContent,
                    $fieldConfig['config']['allowed'],
                    $fieldConfig['config']['MM'],
                    $result['databaseRow']['uid'],
                    $result['tableName'],
                    $fieldConfig['config']
                );
                $relationHandler->getFromDB();
                $result['databaseRow'][$fieldName] = $relationHandler->readyForInterface();
            } elseif ($internalType === 'folder') {
                $folders = [];
                // Simple list of folders
                $folderList = GeneralUtility::trimExplode(',', $databaseRowFieldContent, true);
                foreach ($folderList as $folder) {
                    if ($folder) {
                        $folderObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject($folder);
                        if ($folderObject instanceof Folder) {
                            $folderName = PathUtility::basename($folderObject->getIdentifier());
                            $folders[] = rawurlencode($folder) . '|' . rawurlencode($folderName);
                        }
                    }
                }
                $result['databaseRow'][$fieldName] = implode(',', $folders);
            } else {
                throw new \UnexpectedValueException(
                    'TCA internal_type of field "' . $fieldName . '" in table ' . $result['tableName']
                    . ' must be set to either "db", "file" or "file_reference"',
                    1438780511
                );
            }
        }

        return $result;
    }
}
