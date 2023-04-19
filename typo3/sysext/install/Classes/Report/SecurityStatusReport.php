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

namespace TYPO3\CMS\Install\Report;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\EnableFileService;
use TYPO3\CMS\Install\SystemEnvironment\ServerResponse\ServerResponseCheck;
use TYPO3\CMS\Reports\RequestAwareStatusProviderInterface;
use TYPO3\CMS\Reports\Status;

/**
 * Provides a status report of the security of the install tool
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
final class SecurityStatusReport implements RequestAwareStatusProviderInterface
{
    /**
     * Compiles a collection of system status checks as a status report.
     *
     * @return Status[]
     */
    public function getStatus(ServerRequestInterface $request = null): array
    {
        if ($request !== null) {
            $this->removeInstallToolEnableFilesIfRequested($request);
        }
        return [
            'installToolProtection' => $this->getInstallToolProtectionStatus(),
            'serverResponseStatus' => GeneralUtility::makeInstance(ServerResponseCheck::class)->asStatus(),
        ];
    }

    public function getLabel(): string
    {
        return 'security';
    }

    /**
     * Checks for the existence of the ENABLE_INSTALL_TOOL file.
     *
     * @return Status An object representing whether ENABLE_INSTALL_TOOL exists
     */
    private function getInstallToolProtectionStatus(): Status
    {
        $enableInstallToolFile = EnableFileService::getBestLocationForInstallToolEnableFile();
        // @todo: Note $this->getLanguageService() is declared to allow null. Calling ->sL() may fatal?!
        $value = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_disabled');
        $message = '';
        $severity = ContextualFeedbackSeverity::OK;
        if (EnableFileService::installToolEnableFileExists()) {
            if (EnableFileService::isInstallToolEnableFilePermanent()) {
                $severity = ContextualFeedbackSeverity::WARNING;
                // @todo: See todo on removeInstallToolEnableFilesIfRequested() when this GU::getIndpEnv() is about to be removed.
                $disableInstallToolUrl = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL') . '&adminCmd=remove_ENABLE_INSTALL_TOOL';
                $value = $this->getLanguageService()->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_enabledPermanently');
                $message = sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.install_enabled'),
                    '<code style="white-space: nowrap;">' . $enableInstallToolFile . '</code>'
                );
                $message .= ' <a href="' . htmlspecialchars($disableInstallToolUrl) . '">' .
                    $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.install_enabled_cmd') . '</a>';
            } else {
                if (EnableFileService::installToolEnableFileLifetimeExpired()) {
                    EnableFileService::removeInstallToolEnableFile();
                } else {
                    $severity = ContextualFeedbackSeverity::NOTICE;
                    // @todo: See todo on removeInstallToolEnableFilesIfRequested() when this GU::getIndpEnv() is about to be removed.
                    $disableInstallToolUrl = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL') . '&adminCmd=remove_ENABLE_INSTALL_TOOL';
                    $value = $this->getLanguageService()->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_enabledTemporarily');
                    $message = sprintf(
                        $this->getLanguageService()->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_installEnabledTemporarily'),
                        '<code style="white-space: nowrap;">' . $enableInstallToolFile . '</code>',
                        floor((@filemtime($enableInstallToolFile) + EnableFileService::INSTALL_TOOL_ENABLE_FILE_LIFETIME - time()) / 60)
                    );
                    $message .= ' <a href="' . htmlspecialchars($disableInstallToolUrl) . '">' .
                        $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.install_enabled_cmd') . '</a>';
                }
            }
        }
        return GeneralUtility::makeInstance(
            Status::class,
            $this->getLanguageService()->sL('LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf:status_installTool'),
            $value,
            $message,
            $severity
        );
    }

    private function removeInstallToolEnableFilesIfRequested(ServerRequestInterface $request): void
    {
        // @todo: This should of course be a POST-only call! No idea how, but it should be.
        //        Also, the EnableFileService is pretty ugly nowadays, since it can handle
        //        multiple file locations, but does not reflect this in its methods properly.
        //        Thankfully, EnableFileService is @internal, so all this could be cleaned up
        //        without being breaking ...
        if (($request->getQueryParams()['adminCmd'] ?? '') === 'remove_ENABLE_INSTALL_TOOL') {
            EnableFileService::removeInstallToolEnableFile();
        }
    }

    private function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
