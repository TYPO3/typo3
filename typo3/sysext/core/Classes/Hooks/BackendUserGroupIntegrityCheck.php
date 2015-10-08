<?php
namespace TYPO3\CMS\Core\Hooks;

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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * DataHandler hook class to check the integrity of submitted be_groups data
 */
class BackendUserGroupIntegrityCheck
{
    /**
     * @param string $status
     * @param string $table
     * @param int $id
     * @param array $fieldArray
     * @param DataHandler $parentObject
     */
    public function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $parentObject)
    {
        if ($table !== 'be_groups' || $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'] !== 'explicitAllow') {
            return;
        }

        $backendUserGroup = BackendUtility::getRecord($table, $id, 'explicit_allowdeny');
        $explicitAllowDenyFields = GeneralUtility::trimExplode(',', $backendUserGroup['explicit_allowdeny']);
        foreach ($explicitAllowDenyFields as $value) {
            if (StringUtility::beginsWith($value, 'tt_content:list_type:')) {
                if (!in_array('tt_content:CType:list:ALLOW', $explicitAllowDenyFields, true)) {
                    /** @var $flashMessage FlashMessage */
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        $this->getLanguageService()->sl('LLL:EXT:lang/locallang_core.xlf:error.backendUserGroupListTypeError.message'),
                        $this->getLanguageService()->sl('LLL:EXT:lang/locallang_core.xlf:error.backendUserGroupListTypeError.header'),
                        FlashMessage::WARNING,
                        true
                    );
                    /** @var $flashMessageService FlashMessageService */
                    $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                    /** @var $defaultFlashMessageQueue FlashMessageQueue */
                    $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                    $defaultFlashMessageQueue->enqueue($flashMessage);
                }
                return;
            }
        }
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
