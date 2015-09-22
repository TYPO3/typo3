<?php
namespace TYPO3\CMS\Install\Controller\Action\Tool;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Install\Controller\Action;
use TYPO3\CMS\Install\Status\ErrorStatus;
use TYPO3\CMS\Install\Status\InfoStatus;
use TYPO3\CMS\Install\Status\OkStatus;

/**
 * Clean up page
 */
class CleanUp extends Action\AbstractAction
{
    /**
     * Status messages of submitted actions
     *
     * @var array
     */
    protected $actionMessages = [];

    /**
     * Executes the tool
     *
     * @return string Rendered content
     */
    protected function executeAction()
    {
        if (isset($this->postValues['set']['clearTables'])) {
            $this->actionMessages[] = $this->clearSelectedTables();
            $this->view->assign('postAction', 'clearTables');
        }
        if (isset($this->postValues['set']['resetBackendUserUc'])) {
            $this->actionMessages[] = $this->resetBackendUserUc();
            $this->view->assign('postAction', 'resetBackendUserUc');
        }
        if (isset($this->postValues['set']['clearProcessedFiles'])) {
            $this->actionMessages[] = $this->clearProcessedFiles();
            $this->view->assign('postAction', 'clearProcessedFiles');
        }
        if (isset($this->postValues['set']['deleteTypo3TempFiles'])) {
            $this->view->assign('postAction', 'deleteTypo3TempFiles');
        }

        $this->view->assign('cleanableTables', $this->getCleanableTableList());

        $typo3TempData = $this->getTypo3TempStatistics();
        $this->view->assign('typo3TempData', $typo3TempData);

        $this->view->assign('actionMessages', $this->actionMessages);
        return $this->view->render();
    }

    /**
     * Get list of existing tables that could be truncated.
     *
     * @return array List of cleanable tables with name, description and number of rows
     */
    protected function getCleanableTableList()
    {
        $tableCandidates = [
            [
                'name' => 'be_sessions',
                'description' => 'Backend user sessions'
            ],
            [
                'name' => 'cache_md5params',
                'description' => 'Frontend redirects',
            ],
            [
                'name' => 'fe_sessions',
                'description' => 'Frontend user sessions',
            ],
            [
                'name' => 'sys_history',
                'description' => 'Tracking of database record changes through TYPO3 backend forms',
            ],
            [
                'name' => 'sys_lockedrecords',
                'description' => 'Record locking of backend user editing',
            ],
            [
                'name' => 'sys_log',
                'description' => 'General log table',
            ],
            [
                'name' => 'sys_preview',
                'description' => 'Workspace preview links',
            ],
            [
                'name' => 'tx_extensionmanager_domain_model_extension',
                'description' => 'List of TER extensions',
            ],
            [
                'name' => 'tx_rsaauth_keys',
                'description' => 'Login process key storage'
            ],
        ];

        $tables = [];
        foreach ($tableCandidates as $candidate) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
               ->getConnectionForTable($candidate['name']);
            if ($connection->getSchemaManager()->tablesExist([$candidate['name']])) {
                $candidate['rows'] = $connection->count(
                    '*',
                    $candidate['name'],
                    []
                );
                $tables[] = $candidate;
            }
        }
        return $tables;
    }

    /**
     * Truncate selected tables
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function clearSelectedTables()
    {
        $clearedTables = [];
        if (isset($this->postValues['values']) && is_array($this->postValues['values'])) {
            foreach ($this->postValues['values'] as $tableName => $selected) {
                if ($selected == 1) {
                    GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getConnectionForTable($tableName)
                        ->truncate($tableName);
                    $clearedTables[] = $tableName;
                }
            }
        }
        if (!empty($clearedTables)) {
            /** @var OkStatus $message */
            $message = GeneralUtility::makeInstance(OkStatus::class);
            $message->setTitle('Cleared tables');
            $message->setMessage('List of cleared tables: ' . implode(', ', $clearedTables));
        } else {
            /** @var InfoStatus $message */
            $message = GeneralUtility::makeInstance(InfoStatus::class);
            $message->setTitle('No tables selected to clear');
        }
        return $message;
    }

    /**
     * Reset uc field of all be_users to empty string
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function resetBackendUserUc()
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('be_users')
            ->update('be_users')
            ->set('uc', '')
            ->execute();
        /** @var OkStatus $message */
        $message = GeneralUtility::makeInstance(OkStatus::class);
        $message->setTitle('Reset all backend users preferences');
        return $message;
    }

    /**
     * Data for the typo3temp/ deletion view
     *
     * @return array Data array
     */
    protected function getTypo3TempStatistics()
    {
        $data = [];
        $pathTypo3Temp = PATH_site . 'typo3temp/';
        $postValues = $this->postValues['values'];

        $condition = '0';
        if (isset($postValues['condition'])) {
            $condition = $postValues['condition'];
        }
        $numberOfFilesToDelete = 0;
        if (isset($postValues['numberOfFiles'])) {
            $numberOfFilesToDelete = $postValues['numberOfFiles'];
        }
        $subDirectory = '';
        if (isset($postValues['subDirectory'])) {
            $subDirectory = $postValues['subDirectory'];
        }

        // Run through files
        $fileCounter = 0;
        $deleteCounter = 0;
        $criteriaMatch = 0;
        $timeMap = ['day' => 1, 'week' => 7, 'month' => 30];
        $directory = @dir($pathTypo3Temp . $subDirectory);
        if (is_object($directory)) {
            while ($entry = $directory->read()) {
                $absoluteFile = $pathTypo3Temp . $subDirectory . '/' . $entry;
                if (@is_file($absoluteFile)) {
                    $ok = false;
                    $fileCounter++;
                    if ($condition) {
                        if (MathUtility::canBeInterpretedAsInteger($condition)) {
                            if (filesize($absoluteFile) > $condition * 1024) {
                                $ok = true;
                            }
                        } else {
                            if (fileatime($absoluteFile) < $GLOBALS['EXEC_TIME'] - (int)$timeMap[$condition] * 60 * 60 * 24) {
                                $ok = true;
                            }
                        }
                    } else {
                        $ok = true;
                    }
                    if ($ok) {
                        $hashPart = substr(basename($absoluteFile), -14, 10);
                        // This is a kind of check that the file being deleted has a 10 char hash in it
                        if (
                            !preg_match('/[^a-f0-9]/', $hashPart)
                            || substr($absoluteFile, -6) === '.cache'
                            || substr($absoluteFile, -4) === '.tbl'
                            || substr($absoluteFile, -4) === '.css'
                            || substr($absoluteFile, -3) === '.js'
                            || substr($absoluteFile, -5) === '.gzip'
                            || substr(basename($absoluteFile), 0, 8) === 'installTool'
                        ) {
                            if ($numberOfFilesToDelete && $deleteCounter < $numberOfFilesToDelete) {
                                $deleteCounter++;
                                unlink($absoluteFile);
                            } else {
                                $criteriaMatch++;
                            }
                        }
                    }
                }
            }
            $directory->close();
        }
        $data['numberOfFilesMatchingCriteria'] = $criteriaMatch;
        $data['numberOfDeletedFiles'] = $deleteCounter;

        if ($deleteCounter > 0) {
            $message = GeneralUtility::makeInstance(OkStatus::class);
            $message->setTitle('Deleted ' . $deleteCounter . ' files from typo3temp/' . $subDirectory . '/');
            $this->actionMessages[] = $message;
        }

        $data['selectedCondition'] = $condition;
        $data['numberOfFiles'] = $numberOfFilesToDelete;
        $data['selectedSubDirectory'] = $subDirectory;

        // Set up sub directory data
        $data['subDirectories'] = [
            '' => [
                'name' => '',
                'filesNumber' => count(GeneralUtility::getFilesInDir($pathTypo3Temp)),
            ],
        ];
        $directories = dir($pathTypo3Temp);
        if (is_object($directories)) {
            while ($entry = $directories->read()) {
                if (is_dir($pathTypo3Temp . $entry) && $entry !== '..' && $entry !== '.') {
                    $data['subDirectories'][$entry]['name'] = $entry;
                    $data['subDirectories'][$entry]['filesNumber'] = count(GeneralUtility::getFilesInDir($pathTypo3Temp . $entry));
                    $data['subDirectories'][$entry]['selected'] = false;
                    if ($entry === $data['selectedSubDirectory']) {
                        $data['subDirectories'][$entry]['selected'] = true;
                    }
                }
            }
        }
        $data['numberOfFilesInSelectedDirectory'] = $data['subDirectories'][$data['selectedSubDirectory']]['filesNumber'];

        return $data;
    }

    /**
     * Clear processed files
     *
     * The sys_file_processedfile table is truncated and the physical files of local storages are deleted.
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function clearProcessedFiles()
    {
        $repository = GeneralUtility::makeInstance(ProcessedFileRepository::class);
        $failedDeletions = $repository->removeAll();
        if ($failedDeletions) {
            /** @var ErrorStatus $message */
            $message = GeneralUtility::makeInstance(ErrorStatus::class);
            $message->setTitle('Failed to delete ' . $failedDeletions . ' processed files. See TYPO3 log (by default typo3temp/var/logs/typo3_*.log)');
        } else {
            /** @var OkStatus $message */
            $message = GeneralUtility::makeInstance(OkStatus::class);
            $message->setTitle('Cleared processed files');
        }

        return $message;
    }
}
