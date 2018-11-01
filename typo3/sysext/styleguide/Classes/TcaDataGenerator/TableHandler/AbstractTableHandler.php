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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
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
     * @param $recordFinder
     * @param $fieldValues
     */
    protected function generateTranslatedRecords(string $tableName, $fieldValues)
    {
        $recordFinder = GeneralUtility::makeInstance(RecordFinder::class);
        $demoLanguages = $recordFinder->findUidsOfDemoLanguages();

        $translatedRecord = -42;
        foreach ($demoLanguages as $demoLanguageIndex => $demoLanguageUid) {
            switch ($demoLanguageIndex) {
                case 0:
                    $this->copyRecordToLanguage($tableName, $fieldValues['uid'], $demoLanguageUid);
                    break;
                case 1:
                    $result = $this->localizeRecord($tableName, $fieldValues['uid'], $demoLanguageUid);
                    $translatedRecord = $result[$tableName][$fieldValues['uid']];
                    break;
                case 2:
                    $result = $this->localizeRecord($tableName, $translatedRecord, $demoLanguageUid);
                    $translatedRecord = $result[$tableName][$translatedRecord];
                    break;
                case 3:
                    $this->copyRecordToLanguage($tableName, $translatedRecord, $demoLanguageUid);
                    break;
                default:
                    $this->localizeRecord($tableName, $fieldValues['uid'], $demoLanguageUid);
                    break;
            }
        }
    }

    /**
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
        $dataHandler->start([], $commandMap);
        $dataHandler->process_cmdmap();
        return $dataHandler->copyMappingArray;
    }

    /**
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
        $dataHandler->start([], $commandMap);
        $dataHandler->process_cmdmap();
        return $dataHandler->copyMappingArray;
    }
}
