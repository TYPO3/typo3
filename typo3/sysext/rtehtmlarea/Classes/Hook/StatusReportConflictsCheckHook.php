<?php
namespace TYPO3\CMS\Rtehtmlarea\Hook;

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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Hook into the backend module "Reports" checking whether there are extensions installed that conflicting with htmlArea RTE
 */
class StatusReportConflictsCheckHook implements StatusProviderInterface
{
    /**
     * Compiles a collection of system status checks as a status report.
     *
     * @return array List of statuses
     */
    public function getStatus()
    {
        $reports = [
            'noConflictingExtensionISInstalled' => $this->checkIfNoConflictingExtensionIsInstalled()
        ];
        return $reports;
    }

    /**
     * Check whether any conflicting extension has been installed
     *
     * @return Status
     */
    protected function checkIfNoConflictingExtensionIsInstalled()
    {
        $languageService = $this->getLanguageService();
        $title = $languageService->sL('LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_statusreport.xlf:title');
        $conflictingExtensions = [];
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['conflicts'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['conflicts'] as $extensionKey => $version) {
                if (ExtensionManagementUtility::isLoaded($extensionKey)) {
                    $conflictingExtensions[] = $extensionKey;
                }
            }
        }
        if (!empty($conflictingExtensions)) {
            $value = $languageService->sL('LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_statusreport.xlf:keys')
                . ' ' . implode(', ', $conflictingExtensions);
            $message = $languageService->sL('LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_statusreport.xlf:uninstall');
            $status = Status::ERROR;
        } else {
            $value = $languageService->sL('LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_statusreport.xlf:none');
            $message = '';
            $status = Status::OK;
        }
        return GeneralUtility::makeInstance(Status::class, $title, $value, $message, $status);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
