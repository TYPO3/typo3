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

namespace TYPO3\CMS\Reports\Report\Status;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Middleware\VerifyHostHeader;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\RequestAwareStatusProviderInterface;
use TYPO3\CMS\Reports\Status as ReportStatus;

/**
 * Performs several checks about the system's health
 */
class SecurityStatus implements RequestAwareStatusProviderInterface
{
    /**
     * Determines the security of this TYPO3 installation
     *
     * @param ServerRequestInterface|null $request
     * @return ReportStatus[] List of statuses
     */
    public function getStatus(?ServerRequestInterface $request = null): array
    {
        $statuses = [
            'trustedHostsPattern' => $this->getTrustedHostsPatternStatus(),
            'fileDenyPattern' => $this->getFileDenyPatternStatus(),
            'htaccessUpload' => $this->getHtaccessUploadStatus(),
            'exceptionHandler' => $this->getExceptionHandlerStatus(),
            'exportedFiles' => $this->getExportedFilesStatus(),
        ];

        if ($request !== null) {
            $statuses['encryptedConnectionStatus'] = $this->getEncryptedConnectionStatus($request);
            $lockSslStatus = $this->getLockSslStatus($request);
            if ($lockSslStatus) {
                $statuses['getLockSslStatus'] = $lockSslStatus;
            }
        }

        return $statuses;
    }

    public function getLabel(): string
    {
        return 'security';
    }

    /**
     * Checks if the current connection is encrypted (HTTPS)
     */
    protected function getEncryptedConnectionStatus(ServerRequestInterface $request): ReportStatus
    {
        $value = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_ok');
        $message = '';
        $severity = ContextualFeedbackSeverity::OK;

        $normalizedParams = $request->getAttribute('normalizedParams');

        if (!$normalizedParams->isHttps()) {
            $value = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_insecure');
            $severity = ContextualFeedbackSeverity::WARNING;
            $message = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_encryptedConnectionStatus_insecure');
        }

        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_encryptedConnectionStatus'), $value, $message, $severity);
    }

    /**
     * @return ReportStatus
     */
    protected function getLockSslStatus(ServerRequestInterface $request): ?ReportStatus
    {
        $normalizedParams = $request->getAttribute('normalizedParams');

        if ($normalizedParams->isHttps()) {
            $value = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_ok');
            $message = '';
            $severity = ContextualFeedbackSeverity::OK;

            if (!$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL']) {
                $value = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_insecure');
                $message = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_lockSslStatus_insecure');
                $severity = ContextualFeedbackSeverity::WARNING;
            }

            return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_lockSslStatus'), $value, $message, $severity);
        }

        return null;
    }

    /**
     * Checks if the trusted hosts pattern check is disabled.
     *
     * @return ReportStatus An object representing whether the check is disabled
     */
    protected function getTrustedHostsPatternStatus(): ReportStatus
    {
        $value = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_ok');
        $message = '';
        $severity = ContextualFeedbackSeverity::OK;

        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] === VerifyHostHeader::ENV_TRUSTED_HOSTS_PATTERN_ALLOW_ALL) {
            $value = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_insecure');
            $severity = ContextualFeedbackSeverity::ERROR;
            $message = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.install_trustedhosts');
        }

        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_trustedHostsPattern'), $value, $message, $severity);
    }

    /**
     * Checks if fileDenyPattern was changed which is dangerous on Apache
     *
     * @return ReportStatus An object representing whether the file deny pattern has changed
     */
    protected function getFileDenyPatternStatus(): ReportStatus
    {
        $value = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_ok');
        $message = '';
        $severity = ContextualFeedbackSeverity::OK;

        $fileAccessCheck = GeneralUtility::makeInstance(FileNameValidator::class);
        if ($fileAccessCheck->missingImportantPatterns()) {
            $value = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_insecure');
            $severity = ContextualFeedbackSeverity::ERROR;
            $message = sprintf(
                $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_deny_pattern_partsNotPresent'),
                '<br /><pre>' . htmlspecialchars($fileAccessCheck::DEFAULT_FILE_DENY_PATTERN) . '</pre><br />'
            );
        }

        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_fileDenyPattern'), $value, $message, $severity);
    }

    /**
     * Checks if fileDenyPattern allows to upload .htaccess files which is
     * dangerous on Apache.
     *
     * @return ReportStatus An object representing whether it's possible to upload .htaccess files
     */
    protected function getHtaccessUploadStatus(): ReportStatus
    {
        $value = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_ok');
        $message = '';
        $severity = ContextualFeedbackSeverity::OK;

        $fileNameAccess = GeneralUtility::makeInstance(FileNameValidator::class);
        if ($fileNameAccess->customFileDenyPatternConfigured()
            && $fileNameAccess->isValid('.htaccess')) {
            $value = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_insecure');
            $severity = ContextualFeedbackSeverity::ERROR;
            $message = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_deny_htaccess');
        }

        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_htaccessUploadProtection'), $value, $message, $severity);
    }

    protected function getExceptionHandlerStatus(): ReportStatus
    {
        $value = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_ok');
        $message = '';
        $severity = ContextualFeedbackSeverity::OK;
        if (
            str_contains($GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'], 'Debug') ||
            (Environment::getContext()->isProduction() && (int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] === 1)
        ) {
            $value = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_insecure');
            $severity = ContextualFeedbackSeverity::ERROR;
            $message = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_exceptionHandler_errorMessage');
        } elseif ((int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] === 1) {
            $severity = ContextualFeedbackSeverity::WARNING;
            $message = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_exceptionHandler_warningMessage');
        }
        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_exceptionHandler'), $value, $message, $severity);
    }

    protected function getExportedFilesStatus(): ReportStatus
    {
        $value = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_ok');
        $message = '';
        $severity = ContextualFeedbackSeverity::OK;

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        $exportedFiles = $queryBuilder
            ->select('storage', 'identifier')
            ->from('sys_file')
            ->where(
                $queryBuilder->expr()->like(
                    'identifier',
                    $queryBuilder->createNamedParameter('%/_temp_/importexport/%')
                ),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->like(
                        'identifier',
                        $queryBuilder->createNamedParameter('%.xml')
                    ),
                    $queryBuilder->expr()->like(
                        'identifier',
                        $queryBuilder->createNamedParameter('%.t3d')
                    )
                ),
            )
            ->executeQuery()
            ->fetchAllAssociative();

        if (count($exportedFiles) > 0) {
            $files = [];
            foreach ($exportedFiles as $exportedFile) {
                $files[] = '<li>' . htmlspecialchars($exportedFile['storage'] . ':' . $exportedFile['identifier']) . '</li>';
            }

            $value = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_insecure');
            $severity = ContextualFeedbackSeverity::WARNING;
            $message = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_exportedFiles_warningMessage');
            $message .= '<ul>' . implode(PHP_EOL, $files) . '</ul>';
            $message .= $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_exportedFiles_warningRecommendation');
        }

        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_exportedFiles'), $value, $message, $severity);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
