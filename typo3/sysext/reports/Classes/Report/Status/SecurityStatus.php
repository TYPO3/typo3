<?php
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\TypoScript\ConfigurationForm;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Reports\Status as ReportStatus;
use TYPO3\CMS\Reports\StatusProviderInterface;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;
use TYPO3\CMS\Saltedpasswords\Utility\ExtensionManagerConfigurationUtility;
use TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility;

/**
 * Performs several checks about the system's health
 */
class SecurityStatus implements StatusProviderInterface
{
    /**
     * Determines the security of this TYPO3 installation
     *
     * @return \TYPO3\CMS\Reports\Status[] List of statuses
     */
    public function getStatus()
    {
        $statuses = array(
            'trustedHostsPattern' => $this->getTrustedHostsPatternStatus(),
            'adminUserAccount' => $this->getAdminAccountStatus(),
            'encryptionKeyEmpty' => $this->getEncryptionKeyStatus(),
            'fileDenyPattern' => $this->getFileDenyPatternStatus(),
            'htaccessUpload' => $this->getHtaccessUploadStatus(),
            'saltedpasswords' => $this->getSaltedPasswordsStatus()
        );
        return $statuses;
    }

    /**
     * Checks if the trusted hosts pattern check is disabled.
     *
     * @return \TYPO3\CMS\Reports\Status An object representing whether the check is disabled
     */
    protected function getTrustedHostsPatternStatus()
    {
        $value = $this->getLanguageService()->getLL('status_ok');
        $message = '';
        $severity = ReportStatus::OK;
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] === GeneralUtility::ENV_TRUSTED_HOSTS_PATTERN_ALLOW_ALL) {
            $value = $this->getLanguageService()->getLL('status_insecure');
            $severity = ReportStatus::ERROR;
            $message = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:warning.install_trustedhosts');
        }
        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_trustedHostsPattern'), $value, $message, $severity);
    }

    /**
     * Checks whether a BE user account named admin with default password exists.
     *
     * @return \TYPO3\CMS\Reports\Status An object representing whether a default admin account exists
     */
    protected function getAdminAccountStatus()
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
                    $queryBuilder->quote('admin')
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
                $value = $this->getLanguageService()->getLL('status_insecure');
                $severity = ReportStatus::ERROR;
                $editUserAccountUrl = BackendUtility::getModuleUrl(
                    'record_edit',
                    array(
                        'edit[be_users][' . $row['uid'] . ']' => 'edit',
                        'returnUrl' => BackendUtility::getModuleUrl('system_ReportsTxreportsm1')
                    )
                );
                $message = sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:warning.backend_admin'),
                    '<a href="' . htmlspecialchars($editUserAccountUrl) . '">',
                    '</a>'
                );
            }
        }
        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_adminUserAccount'), $value, $message, $severity);
    }

    /**
     * Checks whether the encryption key is empty.
     *
     * @return \TYPO3\CMS\Reports\Status An object representing whether the encryption key is empty or not
     */
    protected function getEncryptionKeyStatus()
    {
        $value = $this->getLanguageService()->getLL('status_ok');
        $message = '';
        $severity = ReportStatus::OK;
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            $value = $this->getLanguageService()->getLL('status_insecure');
            $severity = ReportStatus::ERROR;
            $url = 'install/index.php?redirect_url=index.php' . urlencode('?TYPO3_INSTALL[type]=config#set_encryptionKey');
            $message = sprintf(
                $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:warning.install_encryption'),
                '<a href="' . $url . '">',
                '</a>'
            );
        }
        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_encryptionKey'), $value, $message, $severity);
    }

    /**
     * Checks if fileDenyPattern was changed which is dangerous on Apache
     *
     * @return \TYPO3\CMS\Reports\Status An object representing whether the file deny pattern has changed
     */
    protected function getFileDenyPatternStatus()
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
                $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:warning.file_deny_pattern_partsNotPresent'),
                '<br /><pre>' . htmlspecialchars(FILE_DENY_PATTERN_DEFAULT) . '</pre><br />'
            );
        }
        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_fileDenyPattern'), $value, $message, $severity);
    }

    /**
     * Checks if fileDenyPattern allows to upload .htaccess files which is
     * dangerous on Apache.
     *
     * @return \TYPO3\CMS\Reports\Status An object representing whether it's possible to upload .htaccess files
     */
    protected function getHtaccessUploadStatus()
    {
        $value = $this->getLanguageService()->getLL('status_ok');
        $message = '';
        $severity = ReportStatus::OK;
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] != FILE_DENY_PATTERN_DEFAULT
            && GeneralUtility::verifyFilenameAgainstDenyPattern('.htaccess')) {
            $value = $this->getLanguageService()->getLL('status_insecure');
            $severity = ReportStatus::ERROR;
            $message = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:warning.file_deny_htaccess');
        }
        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_htaccessUploadProtection'), $value, $message, $severity);
    }

    /**
     * Checks whether salted Passwords are configured or not.
     *
     * @return \TYPO3\CMS\Reports\Status An object representing the security of the saltedpassswords extension
     */
    protected function getSaltedPasswordsStatus()
    {
        $value = $this->getLanguageService()->getLL('status_ok');
        $severity = ReportStatus::OK;
        /** @var ExtensionManagerConfigurationUtility $configCheck */
        $configCheck = GeneralUtility::makeInstance(ExtensionManagerConfigurationUtility::class);
        $message = '<p>' . $this->getLanguageService()->getLL('status_saltedPasswords_infoText') . '</p>';
        $messageDetail = '';
        $resultCheck = $configCheck->checkConfigurationBackend(array(), new ConfigurationForm());
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
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
