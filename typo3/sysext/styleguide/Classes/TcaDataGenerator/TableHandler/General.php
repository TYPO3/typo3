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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordData;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;
use TYPO3\CMS\Styleguide\TcaDataGenerator\TableHandlerInterface;
use TYPO3\CMS\Styleguide\Service\TranslateCopyService;

/**
 * General table handler
 */
class General extends AbstractTableHandler implements TableHandlerInterface
{
    /**
     * Match always
     *
     * @param string $tableName
     * @return bool
     */
    public function match(string $tableName): bool
    {
        return true;
    }

    /**
     * Adds rows
     *
     * @param string $tableName
     * @return string
     */
    public function handle(string $tableName)
    {
        /** @var RecordFinder $recordFinder */
        $recordFinder = GeneralUtility::makeInstance(RecordFinder::class);
        /** @var RecordData $recordData */
        $recordData = GeneralUtility::makeInstance(RecordData::class);

        // First insert an empty row and get the uid of this row since
        // some fields need this uid for relations later.
        $fieldValues = [
            'pid' => $recordFinder->findPidOfMainTableRecord($tableName),
        ];
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);
        $connection->insert($tableName, $fieldValues);
        $fieldValues['uid'] = $connection->lastInsertId($tableName);
        $fieldValues = $recordData->generate($tableName, $fieldValues);
        $connection->update(
            $tableName,
            $fieldValues,
            [ 'uid' => $fieldValues['uid'] ]
        );

        /** @var TranslateCopyService $translateCopyService */
        $translateCopyService = GeneralUtility::makeInstance(TranslateCopyService::class);

        $demoLanguages = $recordFinder->findUidsOfDemoLanguages();
        $translatedRecord = -42;
        foreach ($demoLanguages as $demoLanguageIndex => $demoLanguageUid) {
            switch($demoLanguageIndex) {
                case 0:
                    $translateCopyService->copyRecordToLanguage($tableName, $fieldValues['uid'], $demoLanguageUid);
                    break;
                case 1:
                    $result = $translateCopyService->localizeRecord($tableName, $fieldValues['uid'], $demoLanguageUid);
                    $translatedRecord = $result[$tableName][$fieldValues['uid']];
                    break;
                case 2:
                    $result = $translateCopyService->localizeRecord($tableName, $translatedRecord, $demoLanguageUid);
                    $translatedRecord = $result[$tableName][$translatedRecord];
                    break;
                case 3:
                    $translateCopyService->copyRecordToLanguage($tableName, $translatedRecord, $demoLanguageUid);
                    break;
                default:
                    $translateCopyService->localizeRecord($tableName, $fieldValues['uid'], $demoLanguageUid);
                    break;
            }
        }
    }
}
