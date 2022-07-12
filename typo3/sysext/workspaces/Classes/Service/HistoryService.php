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

namespace TYPO3\CMS\Workspaces\Service;

use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\DiffUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service for history
 */
class HistoryService implements SingletonInterface
{
    /**
     * @var array
     */
    protected $backendUserNames;

    /**
     * @var array
     */
    protected $historyEntries = [];

    /**
     * @var DiffUtility|null
     */
    protected $differencesObject;

    /**
     * Creates this object.
     */
    public function __construct()
    {
        $this->backendUserNames = BackendUtility::getUserNames();
    }

    /**
     * Gets the editing history of a record.
     *
     * @param string $table Name of the table
     * @param int $id Uid of the record
     * @return array Record history entries
     */
    public function getHistory($table, $id)
    {
        $history = [];
        $i = 0;
        foreach ($this->getHistoryEntries($table, $id) as $entry) {
            if ((int)($entry['actiontype'] ?? 0) === RecordHistoryStore::ACTION_STAGECHANGE) {
                continue;
            }
            if ($i++ > 20) {
                break;
            }
            $history[] = $this->getHistoryEntry($entry);
        }
        return $history;
    }

    public function getStageChanges(string $table, int $id): array
    {
        $stageChanges = [];
        foreach ($this->getHistoryEntries($table, $id) as $entry) {
            if ((int)($entry['actiontype'] ?? 0) !== RecordHistoryStore::ACTION_STAGECHANGE) {
                continue;
            }
            $stageChanges[] = $entry;
        }

        return $stageChanges;
    }

    /**
     * Gets the human readable representation of one
     * record history entry.
     *
     * @param array $entry Record history entry
     * @return array
     * @see getHistory
     */
    protected function getHistoryEntry(array $entry)
    {
        if (!empty($entry['action'])) {
            $differences = $entry['action'];
        } else {
            $differences = $this->getDifferences($entry);
        }

        $avatar = GeneralUtility::makeInstance(Avatar::class);
        $beUserRecord = BackendUtility::getRecord('be_users', $entry['userid']);

        return [
            'datetime' => htmlspecialchars(BackendUtility::datetime($entry['tstamp'])),
            'user' => htmlspecialchars($this->getUserName($entry['userid'])),
            'user_avatar' => $avatar->render($beUserRecord),
            'differences' => $differences,
        ];
    }

    /**
     * Gets the differences between two record versions out
     * of one record history entry.
     *
     * @param array $entry Record history entry
     * @return array
     */
    protected function getDifferences(array $entry)
    {
        $differences = [];
        $tableName = $entry['tablename'];
        if (is_array($entry['newRecord'] ?? false)) {
            $fields = array_keys($entry['newRecord']);

            /** @var array<int, string> $fields */
            foreach ($fields as $field) {
                if (!empty($GLOBALS['TCA'][$tableName]['columns'][$field]['config']['type']) && $GLOBALS['TCA'][$tableName]['columns'][$field]['config']['type'] !== 'passthrough') {
                    // Create diff-result:
                    $fieldDifferences = $this->getDifferencesObject()->makeDiffDisplay(
                        BackendUtility::getProcessedValue($tableName, $field, $entry['oldRecord'][$field], 0, true),
                        BackendUtility::getProcessedValue($tableName, $field, $entry['newRecord'][$field], 0, true)
                    );
                    if (!empty($fieldDifferences)) {
                        $differences[] = [
                            'label' => $this->getLanguageService()->sL((string)BackendUtility::getItemLabel($tableName, (string)$field)),
                            'html' => nl2br(trim($fieldDifferences)),
                        ];
                    }
                }
            }
        }
        return $differences;
    }

    /**
     * Gets the username of a backend user.
     *
     * @param string $user
     * @return string
     */
    protected function getUserName($user)
    {
        $userName = 'unknown';
        if (!empty($this->backendUserNames[$user]['username'])) {
            $userName = $this->backendUserNames[$user]['username'];
        }
        return $userName;
    }

    /**
     * Gets an instance of the record history of a record.
     *
     * @param string $table Name of the table
     * @param int $id Uid of the record
     * @return array
     */
    protected function getHistoryEntries($table, $id)
    {
        if (!isset($this->historyEntries[$table][$id])) {
            $this->historyEntries[$table][$id] = GeneralUtility::makeInstance(RecordHistory::class)
                ->getHistoryDataForRecord($table, $id);
        }
        return $this->historyEntries[$table][$id];
    }

    /**
     * Gets an instance of the record differences utility.
     *
     * @return DiffUtility
     */
    protected function getDifferencesObject()
    {
        if (!isset($this->differencesObject)) {
            $this->differencesObject = GeneralUtility::makeInstance(DiffUtility::class);
            $this->differencesObject->stripTags = false;
        }
        return $this->differencesObject;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
