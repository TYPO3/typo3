<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Hooks;

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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Mvc\Configuration\YamlSource;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;
use TYPO3\CMS\Form\Slot\FilePersistenceSlot;
use TYPO3\CMS\Install\Updates\AbstractUpdate;

/**
 * Update wizard to migrate all forms currently in use to new ending
 */
class FormFileExtensionUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Rename form definition file extension from .yaml to .form.yaml';

    /**
     * Checks whether updates are required.
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is required (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        $updateNeeded = false;

        $allStorageFormFiles = $this->getAllStorageFormFilesWithOldNaming();
        $referencedExtensionFormFiles = $this->groupReferencedExtensionFormFiles(
            $this->getReferencedFormFilesWithOldNaming()
        );

        $information = [];
        if (count($allStorageFormFiles) > 0) {
            $updateNeeded = true;
            $information[] = 'Form configuration files were found that should be migrated to be named .form.yaml.';
        }
        if (count($referencedExtensionFormFiles) > 0) {
            $updateNeeded = true;
            $information[] = 'Referenced extension form configuration files found that should be updated.';
        }
        $description = implode('<br>', $information);

        return $updateNeeded;
    }

    /**
     * Performs the accordant updates.
     *
     * @param array &$dbQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool Whether everything went smoothly or not
     */
    public function performUpdate(array &$dbQueries, &$customMessage): bool
    {
        $messages = [];

        $allStorageFormFiles = $this->getAllStorageFormFilesWithOldNaming();
        $referencedFormFiles = $this->getReferencedFormFilesWithOldNaming();
        $referencedExtensionFormFiles = $this->groupReferencedExtensionFormFiles($referencedFormFiles);
        $filePersistenceSlot = GeneralUtility::makeInstance(FilePersistenceSlot::class);
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable('tt_content');
        $referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);
        $persistenceManager = $this->getObjectManager()->get(FormPersistenceManager::class);

        $filePersistenceSlot->defineInvocation(
            FilePersistenceSlot::COMMAND_FILE_RENAME,
            true
        );

        // Processing all files in a regular file abstraction layer storage
        foreach ($allStorageFormFiles as $file) {
            $oldPersistenceIdentifier = $file->getCombinedIdentifier();

            $newPossiblePersistenceIdentifier = $persistenceManager->getUniquePersistenceIdentifier(
                $file->getNameWithoutExtension(),
                $file->getParentFolder()->getCombinedIdentifier()
            );
            $newFileName = PathUtility::pathinfo(
                $newPossiblePersistenceIdentifier,
                PATHINFO_BASENAME
            );

            try {
                $file->rename($newFileName, DuplicationBehavior::RENAME);
                $newPersistenceIdentifier = $file->getCombinedIdentifier();
            } catch (\Exception $e) {
                $messages[] = sprintf(
                    'Failed to rename identifier "%s" to "%s"',
                    $oldPersistenceIdentifier,
                    $newFileName
                );
                continue;
            }

            // Update referenced FlexForm in tt_content elements (if any)
            $dataItems = $this->filterReferencedFormFilesByIdentifier(
                $referencedFormFiles,
                $oldPersistenceIdentifier
            );
            if (count($dataItems) === 0) {
                continue;
            }

            foreach ($dataItems as $dataItem) {
                // No reference index update needed since file UID not changed
                $this->updateContentReference(
                    $connection,
                    $dataItem,
                    $oldPersistenceIdentifier,
                    $newPersistenceIdentifier
                );
            }
        }

        $filePersistenceSlot->defineInvocation(
            FilePersistenceSlot::COMMAND_FILE_RENAME,
            null
        );

        // Processing all referenced files being part of some extension
        foreach ($referencedExtensionFormFiles as $identifier => $dataItems) {
            $oldFilePath = GeneralUtility::getFileAbsFileName(
                ltrim($identifier, '/')
            );
            $newFilePath = $this->upgradeFilename($oldFilePath);

            if (!file_exists($newFilePath)) {
                $messages[] = sprintf(
                    'Failed to update content reference of identifier "0:%s"'
                    . ' (probably not renamed yet using ".form.yaml" suffix)',
                    $identifier
                );
                continue;
            }

            $oldExtensionIdentifier = preg_replace(
                '#^/typo3conf/ext/#',
                'EXT:',
                $identifier
            );
            $newExtensionIdentifier = $this->upgradeFilename(
                $oldExtensionIdentifier
            );

            foreach ($dataItems as $dataItem) {
                $result = $this->updateContentReference(
                    $connection,
                    $dataItem,
                    $oldExtensionIdentifier,
                    $newExtensionIdentifier
                );
                if (!$result) {
                    continue;
                }
                // Update reference index since extension file probably
                // has been renamed or duplicated without invoking FAL API
                $referenceIndex->updateRefIndexTable(
                    'tt_content',
                    (int)$dataItem['recuid']
                );
            }
        }

        if (count($messages) > 0) {
            $customMessage = 'The following issues occurred during performing updates:'
                . '<br><ul><li>' . implode('</li><li>', $messages) . '</li></ul>';
            return false;
        }

        return true;
    }

    /**
     * @param Connection $connection
     * @param array $dataItem
     * @param string $oldIdentifier
     * @param string $newIdentifier
     * @return bool
     */
    protected function updateContentReference(
        Connection $connection,
        array $dataItem,
        string $oldIdentifier,
        string $newIdentifier
    ): bool {
        if ($oldIdentifier === $newIdentifier) {
            return false;
        }

        $flexForm = str_replace(
            $oldIdentifier,
            $newIdentifier,
            $dataItem['pi_flexform']
        );

        $connection->update(
            'tt_content',
            ['pi_flexform' => $flexForm],
            ['uid' => (int)$dataItem['recuid']]
        );

        return true;
    }

    /**
     * Upgrades filename to end with ".form.yaml", e.g.
     * + "file.yaml"      -> "file.form.yaml"
     * + "file.form.yaml" -> "file.form.yaml" (unchanged)
     *
     * @param string $filename
     * @return string
     */
    protected function upgradeFilename(string $filename): string
    {
        return preg_replace(
            '#(?<!\.form).yaml$#',
            '.form.yaml',
            $filename
        );
    }

    /**
     * @return File[]
     */
    protected function getAllStorageFormFilesWithOldNaming(): array
    {
        $persistenceManager = $this->getObjectManager()
            ->get(FormPersistenceManager::class);
        $yamlSource = $this->getObjectManager()
            ->get(YamlSource::class);

        return array_filter(
            $persistenceManager->retrieveYamlFilesFromStorageFolders(),
            function (File $file) use ($yamlSource) {
                $isNewFormFile = StringUtility::endsWith(
                    $file->getName(),
                    FormPersistenceManager::FORM_DEFINITION_FILE_EXTENSION
                );
                if ($isNewFormFile) {
                    return false;
                }

                try {
                    $form = $yamlSource->load([$file]);
                    return !empty($form['identifier'])
                        && ($form['type'] ?? null) === 'Form';
                } catch (\Exception $exception) {
                }
                return false;
            }
        );
    }

    /**
     * @return array
     */
    protected function getReferencedFormFilesWithOldNaming(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_refindex');
        $queryBuilder->getRestrictions()->removeAll();
        $records = $queryBuilder
            ->select(
                'f.identifier AS identifier',
                'f.uid AS uid',
                'f.storage AS storage',
                'r.recuid AS recuid',
                't.pi_flexform AS pi_flexform'
            )
            ->from('sys_refindex', 'r')
            ->innerJoin('r', 'sys_file', 'f', 'r.ref_uid = f.uid')
            ->innerJoin('r', 'tt_content', 't', 'r.recuid = t.uid')
            ->where(
                $queryBuilder->expr()->eq(
                    'r.ref_table',
                    $queryBuilder->createNamedParameter('sys_file', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'r.softref_key',
                    $queryBuilder->createNamedParameter('formPersistenceIdentifier', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'r.deleted',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->notLike(
                    'f.identifier',
                    $queryBuilder->createNamedParameter('%.form.yaml', \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchAll();

        return $records;
    }

    /**
     * @param array $referencedFormFiles
     * @return array
     */
    protected function groupReferencedExtensionFormFiles(
        array $referencedFormFiles
    ): array {
        $referencedExtensionFormFiles = [];

        foreach ($referencedFormFiles as $referencedFormFile) {
            $identifier = $referencedFormFile['identifier'];
            if ((int)$referencedFormFile['storage'] !== 0
                || strpos($identifier, '/typo3conf/ext/') !== 0
            ) {
                continue;
            }
            $referencedExtensionFormFiles[$identifier][] = $referencedFormFile;
        }

        return $referencedExtensionFormFiles;
    }

    /**
     * @param array $referencedFormFiles
     * @param string $identifier
     * @return array
     */
    protected function filterReferencedFormFilesByIdentifier(
        array $referencedFormFiles,
        string $identifier
    ): array {
        return array_filter(
            $referencedFormFiles,
            function (array $referencedFormFile) use ($identifier) {
                $referencedFormFileIdentifier = sprintf(
                    '%d:%s',
                    $referencedFormFile['storage'],
                    $referencedFormFile['identifier']
                );
                return $referencedFormFileIdentifier === $identifier;
            }
        );
    }

    /**
     * @param FolderInterface $folder
     * @return string
     */
    protected function buildCombinedIdentifier(FolderInterface $folder): string
    {
        return sprintf(
            '%d:%s',
            $folder->getStorage()->getUid(),
            $folder->getIdentifier()
        );
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }
}
