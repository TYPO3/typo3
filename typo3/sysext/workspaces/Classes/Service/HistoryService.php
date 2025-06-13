<?php

declare(strict_types=1);

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
use TYPO3\CMS\Backend\View\ValueFormatter\FlexFormValueFormatter;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\DiffGranularity;
use TYPO3\CMS\Core\Utility\DiffUtility;

/**
 * @internal
 */
readonly class HistoryService implements SingletonInterface
{
    public function __construct(
        private Avatar $avatar,
        private DiffUtility $diffUtility,
        private FlexFormValueFormatter $flexFormValueFormatter,
        private RecordHistory $recordHistory,
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * Gets the editing history of a record.
     *
     * @param string $table Name of the table
     * @param int $id Uid of the record
     * @return array Record history entries
     */
    public function getHistory(string $table, int $id): array
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
     * @see getHistory
     */
    protected function getHistoryEntry(array $entry): array
    {
        if (!empty($entry['action'])) {
            $differences = $entry['action'];
        } else {
            $differences = $this->getDifferences($entry);
        }

        $beUserRecord = BackendUtility::getRecord('be_users', $entry['userid']);

        return [
            'datetime' => htmlspecialchars(BackendUtility::datetime($entry['tstamp'])),
            'user' => htmlspecialchars($beUserRecord['username'] ?? 'unknown'),
            'user_avatar' => $this->avatar->render($beUserRecord),
            'differences' => $differences,
        ];
    }

    /**
     * Gets the differences between two record versions out
     * of one record history entry.
     *
     * @param array $entry Record history entry
     */
    protected function getDifferences(array $entry): array
    {
        $differences = [];
        $tableName = $entry['tablename'];
        if (is_array($entry['newRecord'] ?? false)) {
            $schema = $this->tcaSchemaFactory->get($tableName);
            $fields = array_keys($entry['newRecord']);

            /** @var array<int, string> $fields */
            foreach ($fields as $field) {
                if (!$schema->hasField($field)) {
                    continue;
                }
                $fieldInformation = $schema->getField($field);
                if ($fieldInformation->isType(TableColumnType::PASSTHROUGH)) {
                    continue;
                }
                // Create diff-result:
                if ($fieldInformation->isType(TableColumnType::FLEX)) {
                    $old = $this->flexFormValueFormatter->format($tableName, $field, $entry['oldRecord'][$field], $entry['recuid'], $fieldInformation->getConfiguration());
                    $new = $this->flexFormValueFormatter->format($tableName, $field, $entry['newRecord'][$field], $entry['recuid'], $fieldInformation->getConfiguration());
                    $fieldDifferences = $this->diffUtility->diff(strip_tags($old), strip_tags($new), DiffGranularity::CHARACTER);
                } else {
                    $old = (string)BackendUtility::getProcessedValue($tableName, $field, $entry['oldRecord'][$field], 0, true);
                    $new = (string)BackendUtility::getProcessedValue($tableName, $field, $entry['newRecord'][$field], 0, true);
                    $fieldDifferences = $this->diffUtility->diff(strip_tags($old), strip_tags($new));
                }
                if (!empty($fieldDifferences)) {
                    $differences[] = [
                        'label' => $this->getLanguageService()->sL((string)BackendUtility::getItemLabel($tableName, (string)$field)),
                        'html' => trim($fieldDifferences),
                    ];
                }
            }
        }
        return $differences;
    }

    /**
     * Gets an instance of the record history of a record.
     *
     * @param string $table Name of the table
     * @param int $id Uid of the record
     */
    protected function getHistoryEntries(string $table, int $id): array
    {
        return $this->recordHistory->getHistoryDataForRecord($table, $id);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
