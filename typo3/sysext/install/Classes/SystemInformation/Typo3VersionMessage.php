<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Install\SystemInformation;

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

use TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem;
use TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Install\Service\CoreVersionService;
use TYPO3\CMS\Install\Service\Exception\RemoteFetchException;

/**
 * Count newest exceptions for the system information menu
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class Typo3VersionMessage
{
    /**
     * Modifies the SystemInformation array
     *
     * @param SystemInformationToolbarItem $systemInformationToolbarItem
     */
    public function appendMessage(SystemInformationToolbarItem $systemInformationToolbarItem): void
    {
        $coreVersionService = GeneralUtility::makeInstance(CoreVersionService::class);

        try {
            if ($coreVersionService->isVersionActivelyMaintained()) {
                $isYoungerPatchReleaseAvailable = $coreVersionService->isYoungerPatchReleaseAvailable();

                if (true === $isYoungerPatchReleaseAvailable) {
                    $release = $coreVersionService->getYoungestPatchRelease();

                    if ($coreVersionService->isUpdateSecurityRelevant()) {
                        $severity = InformationStatus::STATUS_ERROR;
                        $message = LocalizationUtility::translate(
                            'LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_newVersionSecurityRelevant'
                        );
                    } else {
                        $severity = InformationStatus::STATUS_WARNING;
                        $message = LocalizationUtility::translate(
                            'LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_newVersion'
                        );
                    }

                    $message = sprintf($message, $release);
                } else {
                    $severity = InformationStatus::STATUS_OK;
                    $message = LocalizationUtility::translate(
                        'LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_uptodate'
                    );
                }
            } else {
                $severity = InformationStatus::STATUS_ERROR;
                $message = LocalizationUtility::translate(
                    'LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_versionOutdated'
                );
            }
        } catch (RemoteFetchException $exception) {
            $message = LocalizationUtility::translate(
                'LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_noAutomaticCheck'
            );
            $severity = InformationStatus::STATUS_WARNING;
        }

        $systemInformationToolbarItem->addSystemMessage(
            $message,
            $severity
        );
    }
}
