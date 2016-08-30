<?php
namespace TYPO3\CMS\Workspaces\Service;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Service for history
 */
class HistoryService implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var array
     */
    protected $backendUserNames;

    /**
     * @var array
     */
    protected $historyObjects = [];

    /**
     * @var \TYPO3\CMS\Core\Utility\DiffUtility
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
        foreach ((array)$this->getHistoryObject($table, $id)->changeLog as $entry) {
            if ($i++ > 20) {
                break;
            }
            $history[] = $this->getHistoryEntry($entry);
        }
        return $history;
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
        return [
            'datetime' => htmlspecialchars(BackendUtility::datetime($entry['tstamp'])),
            'user' => htmlspecialchars($this->getUserName($entry['user'])),
            'differences' => $differences
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
        if (is_array($entry['newRecord'])) {
            $fields = array_keys($entry['newRecord']);
            foreach ($fields as $field) {
                if (!empty($GLOBALS['TCA'][$tableName]['columns'][$field]['config']['type']) && $GLOBALS['TCA'][$tableName]['columns'][$field]['config']['type'] !== 'passthrough') {
                    // Create diff-result:
                    $fieldDifferences = $this->getDifferencesObject()->makeDiffDisplay(
                        BackendUtility::getProcessedValue($tableName, $field, $entry['oldRecord'][$field], 0, true),
                        BackendUtility::getProcessedValue($tableName, $field, $entry['newRecord'][$field], 0, true)
                    );
                    if (!empty($fieldDifferences)) {
                        $differences[] = [
                            'label' => $this->getLanguageService()->sl((string)BackendUtility::getItemLabel($tableName, $field)),
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
     * Gets an instance of the record history service.
     *
     * @param string $table Name of the table
     * @param int $id Uid of the record
     * @return \TYPO3\CMS\Backend\History\RecordHistory
     */
    protected function getHistoryObject($table, $id)
    {
        if (!isset($this->historyObjects[$table][$id])) {
            /** @var $historyObject \TYPO3\CMS\Backend\History\RecordHistory */
            $historyObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\History\RecordHistory::class);
            $historyObject->element = $table . ':' . $id;
            $historyObject->createChangeLog();
            $this->historyObjects[$table][$id] = $historyObject;
        }
        return $this->historyObjects[$table][$id];
    }

    /**
     * Gets an instance of the record differences utility.
     *
     * @return \TYPO3\CMS\Core\Utility\DiffUtility
     */
    protected function getDifferencesObject()
    {
        if (!isset($this->differencesObject)) {
            $this->differencesObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Utility\DiffUtility::class);
        }
        return $this->differencesObject;
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
