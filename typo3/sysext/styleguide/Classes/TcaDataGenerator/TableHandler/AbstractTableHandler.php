<?php

declare(strict_types=1);
namespace TYPO3\CMS\Styleguide\TcaDataGenerator\TableHandler;

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
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\Exception;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;

/**
 * Abstract table handler implements general methods
 */
class AbstractTableHandler
{
    /**
     * @var string Table name to match
     */
    protected $tableName;

    /**
     * Match if given table name is registered table name
     *
     * @param string $tableName
     * @return bool
     */
    public function match(string $tableName): bool
    {
        return $tableName === $this->tableName;
    }

    /**
     * @param string $tableName
     * @param array $fieldValues
     */
    protected function generateTranslatedRecords(string $tableName, $fieldValues): void
    {
        if (!BackendUtility::isTableLocalizable($tableName)) {
            return;
        }

        $recordFinder = GeneralUtility::makeInstance(RecordFinder::class);
        $demoLanguages = $recordFinder->findUidsOfDemoLanguages();

        $translatedRecord = -42;
        foreach ($demoLanguages as $demoLanguageIndex => $demoLanguageUid) {
            switch ($demoLanguageIndex) {
                case 0:
                    // danish: 'copy / free mode', l10n_parent = 0, l10n_source = default lang record
                    $this->copyRecordToLanguage($tableName, $fieldValues['uid'], $demoLanguageUid);
                    break;
                case 1:
                    // german: 'translate / connected mode', l10n_parent = default lang record, l10n_source = default lang record
                    $result = $this->localizeRecord($tableName, $fieldValues['uid'], $demoLanguageUid);
                    $translatedRecord = $result[$tableName][$fieldValues['uid']];
                    break;
                case 2:
                    // french: 'translate / connected mode' german as source, l10n_parent = default lang record, source = german record
                    $result = $this->localizeRecord($tableName, $translatedRecord, $demoLanguageUid);
                    $translatedRecord = $result[$tableName][$translatedRecord];
                    break;
                case 3:
                    // spanish: 'copy mode / free mode', french as source, l10n_parent = default lang record, source = french record
                    $this->copyRecordToLanguage($tableName, $translatedRecord, $demoLanguageUid);
                    break;
                default:
                    throw new Exception(
                        'Unknown language. No idea what to do with sys_language record ' . (int)$demoLanguageUid . ' for table ' . $tableName,
                        1597437985
                    );
            }
        }
    }

    /**
     * Create a 'translate / connected mode' localization
     *
     * @param string $tableName
     * @param int $uid
     * @param int $languageId
     * @return array
     */
    protected function localizeRecord($tableName, $uid, $languageId)
    {
        $commandMap = [
            $tableName => [
                $uid => [
                    'localize' => $languageId,
                ],
            ],
        ];
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->enableLogging = false;
        $dataHandler->start([], $commandMap);
        $dataHandler->process_cmdmap();
        return $dataHandler->copyMappingArray;
    }

    /**
     * Create a 'copy / free mode' localization
     *
     * @param string $tableName
     * @param int $uid
     * @param int $languageId
     * @return array
     */
    protected function copyRecordToLanguage($tableName, $uid, $languageId)
    {
        $commandMap = [
            $tableName => [
                $uid => [
                    'copyToLanguage' => $languageId,
                ],
            ],
        ];
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->enableLogging = false;
        $dataHandler->start([], $commandMap);
        $dataHandler->process_cmdmap();
        return $dataHandler->copyMappingArray;
    }
}
