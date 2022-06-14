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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Middleware\VerifyHostHeader;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\RequestAwareStatusProviderInterface;
use TYPO3\CMS\Reports\Status as ReportStatus;

/**
 * Performs several checks about the system's health
 */
class SecurityStatus implements RequestAwareStatusProviderInterface
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * Determines the security of this TYPO3 installation
     *
     * @param ServerRequestInterface|null $request
     * @return ReportStatus[] List of statuses
     */
    public function getStatus(ServerRequestInterface $request = null)
    {
        $statuses = [
            'trustedHostsPattern' => $this->getTrustedHostsPatternStatus(),
            'adminUserAccount' => $this->getAdminAccountStatus(),
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

    /**
     * Checks if the current connection is encrypted (HTTPS)
     *
     * @param ServerRequestInterface $request
     * @return ReportStatus
     */
    protected function getEncryptedConnectionStatus(ServerRequestInterface $request): ReportStatus
    {
        $value = $this->getLanguageService()->getLL('status_ok');
        $message = '';
        $severity = ReportStatus::OK;

        $normalizedParams = $request->getAttribute('normalizedParams');

        if (!$normalizedParams->isHttps()) {
            $value = $this->getLanguageService()->getLL('status_insecure');
            $severity = ReportStatus::WARNING;
            $message = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_encryptedConnectionStatus_insecure');
        }

        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_encryptedConnectionStatus'), $value, $message, $severity);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ReportStatus
     */
    protected function getLockSslStatus(ServerRequestInterface $request): ?ReportStatus
    {
        $normalizedParams = $request->getAttribute('normalizedParams');

        if ($normalizedParams->isHttps()) {
            $value = $this->getLanguageService()->getLL('status_ok');
            $message = '';
            $severity = ReportStatus::OK;

            if (!$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL']) {
                $value = $this->getLanguageService()->getLL('status_insecure');
                $message = $this->getLanguageService()->getLL('status_lockSslStatus_insecure');
                $severity = ReportStatus::WARNING;
            }

            return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_lockSslStatus'), $value, $message, $severity);
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
        $value = $this->getLanguageService()->getLL('status_ok');
        $message = '';
        $severity = ReportStatus::OK;

        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] === VerifyHostHeader::ENV_TRUSTED_HOSTS_PATTERN_ALLOW_ALL) {
            $value = $this->getLanguageService()->getLL('status_insecure');
            $severity = ReportStatus::ERROR;
            $message = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.install_trustedhosts');
        }

        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_trustedHostsPattern'), $value, $message, $severity);
    }

    /**
     * Checks whether a BE user account named admin with default password exists.
     *
     * @return ReportStatus An object representing whether a default admin account exists
     */
    protected function getAdminAccountStatus(): ReportStatus
    {
        $value = $this->getLanguageService()->getLL('status_ok');
        $message = '';
        $severity = ReportStatus::OK;

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $row = $queryBuilder
            ->select('uid', 'username', 'password')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->eq(
                    'username',
                    $queryBuilder->createNamedParameter('admin', \PDO::PARAM_STR)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        if (!empty($row)) {
            try {
                $hashInstance = GeneralUtility::makeInstance(PasswordHashFactory::class)->get($row['password'], 'BE');
                if ($hashInstance->checkPassword('password', $row['password'])) {
                    // If the password for 'admin' user is 'password': bad idea!
                    // We're checking since the (very) old installer created instances like this in dark old times.
                    $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                    $value = $this->getLanguageService()->getLL('status_insecure');
                    $severity = ReportStatus::ERROR;
                    $editUserAccountUrl = (string)$uriBuilder->buildUriFromRoute(
                        'record_edit',
                        [
                            'edit[be_users][' . $row['uid'] . ']' => 'edit',
                            'returnUrl' => (string)$uriBuilder->buildUriFromRoute('system_reports'),
                        ]
                    );
                    $message = sprintf(
                        $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.backend_admin'),
                        '<a href="' . htmlspecialchars($editUserAccountUrl) . '">',
                        '</a>'
                    );
                }
            } catch (InvalidPasswordHashException $e) {
                // No hash class handling for current hash could be found. Not good, but ok in this case.
            }
        }

        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_adminUserAccount'), $value, $message, $severity);
    }

    /**
     * Checks if fileDenyPattern was changed which is dangerous on Apache
     *
     * @return ReportStatus An object representing whether the file deny pattern has changed
     */
    protected function getFileDenyPatternStatus(): ReportStatus
    {
        $value = $this->getLanguageService()->getLL('status_ok');
        $message = '';
        $severity = ReportStatus::OK;

        $fileAccessCheck = GeneralUtility::makeInstance(FileNameValidator::class);
        if ($fileAccessCheck->missingImportantPatterns()) {
            $value = $this->getLanguageService()->getLL('status_insecure');
            $severity = ReportStatus::ERROR;
            $message = sprintf(
                $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_deny_pattern_partsNotPresent'),
                '<br /><pre>' . htmlspecialchars($fileAccessCheck::DEFAULT_FILE_DENY_PATTERN) . '</pre><br />'
            );
        }

        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_fileDenyPattern'), $value, $message, $severity);
    }

    /**
     * Checks if fileDenyPattern allows to upload .htaccess files which is
     * dangerous on Apache.
     *
     * @return ReportStatus An object representing whether it's possible to upload .htaccess files
     */
    protected function getHtaccessUploadStatus(): ReportStatus
    {
        $value = $this->getLanguageService()->getLL('status_ok');
        $message = '';
        $severity = ReportStatus::OK;

        $fileNameAccess = GeneralUtility::makeInstance(FileNameValidator::class);
        if ($fileNameAccess->customFileDenyPatternConfigured()
            && $fileNameAccess->isValid('.htaccess')) {
            $value = $this->getLanguageService()->getLL('status_insecure');
            $severity = ReportStatus::ERROR;
            $message = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_deny_htaccess');
        }

        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_htaccessUploadProtection'), $value, $message, $severity);
    }

    protected function getExceptionHandlerStatus(): ReportStatus
    {
        $value = $this->getLanguageService()->getLL('status_ok');
        $message = '';
        $severity = ReportStatus::OK;
        if (
            str_contains($GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'], 'Debug') ||
            (Environment::getContext()->isProduction() && (int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] === 1)
        ) {
            $value = $this->getLanguageService()->getLL('status_insecure');
            $severity = ReportStatus::ERROR;
            $message = $this->getLanguageService()->getLL('status_exceptionHandler_errorMessage');
        } elseif ((int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] === 1) {
            $severity = ReportStatus::WARNING;
            $message = $this->getLanguageService()->getLL('status_exceptionHandler_warningMessage');
        }
        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_exceptionHandler'), $value, $message, $severity);
    }

    protected function getExportedFilesStatus(): ReportStatus
    {
        $value = $this->getLanguageService()->getLL('status_ok');
        $message = '';
        $severity = ReportStatus::OK;

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

            $value = $this->getLanguageService()->getLL('status_insecure');
            $severity = ReportStatus::WARNING;
            $message = $this->getLanguageService()->getLL('status_exportedFiles_warningMessage');
            $message .= '<ul>' . implode(PHP_EOL, $files) . '</ul>';
            $message .= $this->getLanguageService()->getLL('status_exportedFiles_warningRecommendation');
        }

        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_exportedFiles'), $value, $message, $severity);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
