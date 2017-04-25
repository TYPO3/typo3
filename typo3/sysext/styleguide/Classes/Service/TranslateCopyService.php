<?php
namespace TYPO3\CMS\Styleguide\Service;

/**
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

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class TranslateCopyService
{

    /**
     * @var DataHandler
     */
    protected $dataHandler;

    /**
     * @return DataHandler
     */
    public function getDataHandler()
    {
        return $this->dataHandler;
    }

    /**
     * @param string $tableName
     * @param int $uid
     * @param int $languageId
     * @return array
     */
    public function localizeRecord($tableName, $uid, $languageId)
    {
        $commandMap = [
            $tableName => [
                $uid => [
                    'localize' => $languageId,
                ],
            ],
        ];
        $this->createDataHandler();
        $this->dataHandler->start([], $commandMap);
        $this->dataHandler->process_cmdmap();
        return $this->dataHandler->copyMappingArray;
    }

    /**
     * @param string $tableName
     * @param int $uid
     * @param int $languageId
     * @return array
     */
    public function copyRecordToLanguage($tableName, $uid, $languageId)
    {
        $commandMap = [
            $tableName => [
                $uid => [
                    'copyToLanguage' => $languageId,
                ],
            ],
        ];
        $this->createDataHandler();
        $this->dataHandler->start([], $commandMap);
        $this->dataHandler->process_cmdmap();
        return $this->dataHandler->copyMappingArray;
    }

    /**
     * @return DataHandler
     */
    protected function createDataHandler()
    {
        $this->dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $backendUser = $this->getBackendUser();
        if (isset($backendUser->uc['copyLevels'])) {
            $this->dataHandler->copyTree = $backendUser->uc['copyLevels'];
        }
        return $this->dataHandler;
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
