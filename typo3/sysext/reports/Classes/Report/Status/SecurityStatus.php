<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Reports\Report\Status;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\RequestAwareStatusProviderInterface;
use TYPO3\CMS\Reports\Status as ReportStatus;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;
use TYPO3\CMS\Saltedpasswords\Utility\ExtensionManagerConfigurationUtility;
use TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility;

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
            'saltedpasswords' => $this->getSaltedPasswordsStatus(),
        ];

        if ($request !== null) {
            $statuses['encryptedConnectionStatus'] = $this->getEncryptedConnectionStatus($request);
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

        /** @var \TYPO3\CMS\Core\Http\NormalizedParams $normalizedParams */
        $normalizedParams = $request->getAttribute('normalizedParams');

        if (!$normalizedParams->isHttps()) {
            $value = $this->getLanguageService()->getLL('status_insecure');
            $severity = ReportStatus::WARNING;
            $message = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_encryptedConnectionStatus_insecure');
        }

        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_encryptedConnectionStatus'), $value, $message, $severity);
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

        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] === GeneralUtility::ENV_TRUSTED_HOSTS_PATTERN_ALLOW_ALL) {
            $value = $this->getLanguageService()->getLL('status_insecure');
            $severity = ReportStatus::ERROR;
            $message = $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:warning.install_trustedhosts');
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
            ->execute()
            ->fetch();

        if (!empty($row)) {
            $secure = true;
            /** @var \TYPO3\CMS\Saltedpasswords\Salt\SaltInterface $saltingObject */
            $saltingObject = SaltFactory::getSaltingInstance($row['password']);
            if (is_object($saltingObject)) {
                if ($saltingObject->checkPassword('password', $row['password'])) {
                    $secure = false;
                }
            }
            // Check against plain MD5
            if ($row['password'] === '5f4dcc3b5aa765d61d8327deb882cf99') {
                $secure = false;
            }
            if (!$secure) {
                /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
                $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
                $value = $this->getLanguageService()->getLL('status_insecure');
                $severity = ReportStatus::ERROR;
                $editUserAccountUrl = (string)$uriBuilder->buildUriFromRoute(
                    'record_edit',
                    [
                        'edit[be_users][' . $row['uid'] . ']' => 'edit',
                        'returnUrl' => (string)$uriBuilder->buildUriFromRoute('system_reports')
                    ]
                );
                $message = sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:warning.backend_admin'),
                    '<a href="' . htmlspecialchars($editUserAccountUrl) . '">',
                    '</a>'
                );
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
        $defaultParts = GeneralUtility::trimExplode('|', FILE_DENY_PATTERN_DEFAULT, true);
        $givenParts = GeneralUtility::trimExplode('|', $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'], true);
        $result = array_intersect($defaultParts, $givenParts);

        if ($defaultParts !== $result) {
            $value = $this->getLanguageService()->getLL('status_insecure');
            $severity = ReportStatus::ERROR;
            $message = sprintf(
                $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:warning.file_deny_pattern_partsNotPresent'),
                '<br /><pre>' . htmlspecialchars(FILE_DENY_PATTERN_DEFAULT) . '</pre><br />'
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

        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] != FILE_DENY_PATTERN_DEFAULT
            && GeneralUtility::verifyFilenameAgainstDenyPattern('.htaccess')) {
            $value = $this->getLanguageService()->getLL('status_insecure');
            $severity = ReportStatus::ERROR;
            $message = $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:warning.file_deny_htaccess');
        }

        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_htaccessUploadProtection'), $value, $message, $severity);
    }

    /**
     * Checks whether salted Passwords are configured or not.
     *
     * @return ReportStatus An object representing the security of the saltedpassswords extension
     */
    protected function getSaltedPasswordsStatus(): ReportStatus
    {
        $value = $this->getLanguageService()->getLL('status_ok');
        $severity = ReportStatus::OK;
        /** @var ExtensionManagerConfigurationUtility $configCheck */
        $configCheck = GeneralUtility::makeInstance(ExtensionManagerConfigurationUtility::class);
        $message = '<p>' . $this->getLanguageService()->getLL('status_saltedPasswords_infoText') . '</p>';
        $messageDetail = '';
        $resultCheck = $configCheck->checkConfigurationBackend([]);

        switch ($resultCheck['errorType']) {
            case FlashMessage::INFO:
                $messageDetail .= $resultCheck['html'];
                break;
            case FlashMessage::WARNING:
                $severity = ReportStatus::WARNING;
                $messageDetail .= $resultCheck['html'];
                break;
            case FlashMessage::ERROR:
                $value = $this->getLanguageService()->getLL('status_insecure');
                $severity = ReportStatus::ERROR;
                $messageDetail .= $resultCheck['html'];
                break;
            default:
        }

        $unsecureUserCount = SaltedPasswordsUtility::getNumberOfBackendUsersWithInsecurePassword();

        if ($unsecureUserCount > 0) {
            $value = $this->getLanguageService()->getLL('status_insecure');
            $severity = ReportStatus::ERROR;
            $messageDetail .= '<div class="panel panel-warning">' .
                '<div class="panel-body">' .
                    $this->getLanguageService()->getLL('status_saltedPasswords_notAllPasswordsHashed') .
                '</div>' .
            '</div>';
        }

        $message .= $messageDetail;

        if (empty($messageDetail)) {
            $message = '';
        }

        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_saltedPasswords'), $value, $message, $severity);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
