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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility;

/**
 * Performs several checks about the system's health
 */
class SecurityStatus implements \TYPO3\CMS\Reports\StatusProviderInterface
{
    /**
     * Determines the security of this TYPO3 installation
     *
     * @return \TYPO3\CMS\Reports\Status[] List of statuses
     */
    public function getStatus()
    {
        $statuses = [
            'trustedHostsPattern' => $this->getTrustedHostsPatternStatus(),
            'adminUserAccount' => $this->getAdminAccountStatus(),
            'encryptionKeyEmpty' => $this->getEncryptionKeyStatus(),
            'fileDenyPattern' => $this->getFileDenyPatternStatus(),
            'htaccessUpload' => $this->getHtaccessUploadStatus(),
            'saltedpasswords' => $this->getSaltedPasswordsStatus(),
            'cacheFloodingProtection' => $this->getCacheFloodingProtectionStatus()
        ];
        return $statuses;
    }

    /**
     * @return \TYPO3\CMS\Reports\Status An object representing whether the check is disabled
     */
    protected function getCacheFloodingProtectionStatus()
    {
        $value = $GLOBALS['LANG']->getLL('status_ok');
        $message = '';
        $severity = \TYPO3\CMS\Reports\Status::OK;
        if (empty($GLOBALS['TYPO3_CONF_VARS']['FE']['cHashIncludePageId'])) {
            $value = $GLOBALS['LANG']->getLL('status_insecure');
            $severity = \TYPO3\CMS\Reports\Status::ERROR;
            $message = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.install_cache_flooding');
        }
        return GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class, $GLOBALS['LANG']->getLL('status_cacheFloodingProtection'), $value, $message, $severity);
    }

    /**
     * Checks if the trusted hosts pattern check is disabled.
     *
     * @return \TYPO3\CMS\Reports\Status An object representing whether the check is disabled
     */
    protected function getTrustedHostsPatternStatus()
    {
        $value = $GLOBALS['LANG']->getLL('status_ok');
        $message = '';
        $severity = \TYPO3\CMS\Reports\Status::OK;
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] === GeneralUtility::ENV_TRUSTED_HOSTS_PATTERN_ALLOW_ALL) {
            $value = $GLOBALS['LANG']->getLL('status_insecure');
            $severity = \TYPO3\CMS\Reports\Status::ERROR;
            $message = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.install_trustedhosts');
        }
        return GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class,
            $GLOBALS['LANG']->getLL('status_trustedHostsPattern'), $value, $message, $severity);
    }

    /**
     * Checks whether a BE user account named admin with default password exists.
     *
     * @return \TYPO3\CMS\Reports\Status An object representing whether a default admin account exists
     */
    protected function getAdminAccountStatus()
    {
        $value = $GLOBALS['LANG']->getLL('status_ok');
        $message = '';
        $severity = \TYPO3\CMS\Reports\Status::OK;
        $whereClause = 'username = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('admin', 'be_users') .
            BackendUtility::deleteClause('be_users');
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, username, password', 'be_users', $whereClause);
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        if (!empty($row)) {
            $secure = true;
            /** @var $saltingObject \TYPO3\CMS\Saltedpasswords\Salt\SaltInterface */
            $saltingObject = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance($row['password']);
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
                $value = $GLOBALS['LANG']->getLL('status_insecure');
                $severity = \TYPO3\CMS\Reports\Status::ERROR;
                $editUserAccountUrl = BackendUtility::getModuleUrl(
                    'record_edit',
                    [
                        'edit[be_users][' . $row['uid'] . ']' => 'edit',
                        'returnUrl' => BackendUtility::getModuleUrl('system_ReportsTxreportsm1')
                    ]
                );
                $message = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.backend_admin'),
                    '<a href="' . htmlspecialchars($editUserAccountUrl) . '">', '</a>');
            }
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);
        return GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class,
            $GLOBALS['LANG']->getLL('status_adminUserAccount'), $value, $message, $severity);
    }

    /**
     * Checks whether the encryption key is empty.
     *
     * @return \TYPO3\CMS\Reports\Status An object representing whether the encryption key is empty or not
     */
    protected function getEncryptionKeyStatus()
    {
        $value = $GLOBALS['LANG']->getLL('status_ok');
        $message = '';
        $severity = \TYPO3\CMS\Reports\Status::OK;
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            $value = $GLOBALS['LANG']->getLL('status_insecure');
            $severity = \TYPO3\CMS\Reports\Status::ERROR;
            $url = 'install/index.php?redirect_url=index.php' . urlencode('?TYPO3_INSTALL[type]=config#set_encryptionKey');
            $message = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.install_encryption'),
                '<a href="' . $url . '">', '</a>');
        }
        return GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class,
            $GLOBALS['LANG']->getLL('status_encryptionKey'), $value, $message, $severity);
    }

    /**
     * Checks if fileDenyPattern was changed which is dangerous on Apache
     *
     * @return \TYPO3\CMS\Reports\Status An object representing whether the file deny pattern has changed
     */
    protected function getFileDenyPatternStatus()
    {
        $value = $GLOBALS['LANG']->getLL('status_ok');
        $message = '';
        $severity = \TYPO3\CMS\Reports\Status::OK;
        $defaultParts = GeneralUtility::trimExplode('|', FILE_DENY_PATTERN_DEFAULT, true);
        $givenParts = GeneralUtility::trimExplode('|', $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'], true);
        $result = array_intersect($defaultParts, $givenParts);
        if ($defaultParts !== $result) {
            $value = $GLOBALS['LANG']->getLL('status_insecure');
            $severity = \TYPO3\CMS\Reports\Status::ERROR;
            $message = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.file_deny_pattern_partsNotPresent'),
                '<br /><pre>' . htmlspecialchars(FILE_DENY_PATTERN_DEFAULT) . '</pre><br />');
        }
        return GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class,
            $GLOBALS['LANG']->getLL('status_fileDenyPattern'), $value, $message, $severity);
    }

    /**
     * Checks if fileDenyPattern allows to upload .htaccess files which is
     * dangerous on Apache.
     *
     * @return \TYPO3\CMS\Reports\Status An object representing whether it's possible to upload .htaccess files
     */
    protected function getHtaccessUploadStatus()
    {
        $value = $GLOBALS['LANG']->getLL('status_ok');
        $message = '';
        $severity = \TYPO3\CMS\Reports\Status::OK;
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] != FILE_DENY_PATTERN_DEFAULT
            && GeneralUtility::verifyFilenameAgainstDenyPattern('.htaccess')) {
            $value = $GLOBALS['LANG']->getLL('status_insecure');
            $severity = \TYPO3\CMS\Reports\Status::ERROR;
            $message = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.file_deny_htaccess');
        }
        return GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class,
            $GLOBALS['LANG']->getLL('status_htaccessUploadProtection'), $value, $message, $severity);
    }

    /**
     * Checks whether memcached is configured, if that's the case we assume it's also used.
     *
     * @return bool TRUE if memcached is used, FALSE otherwise.
     */
    protected function isMemcachedUsed()
    {
        $memcachedUsed = false;
        $memcachedServers = $this->getConfiguredMemcachedServers();
        if (!empty($memcachedServers)) {
            $memcachedUsed = true;
        }
        return $memcachedUsed;
    }

    /**
     * Checks whether salted Passwords are configured or not.
     *
     * @return \TYPO3\CMS\Reports\Status An object representing the security of the saltedpassswords extension
     */
    protected function getSaltedPasswordsStatus()
    {
        $value = $GLOBALS['LANG']->getLL('status_ok');
        $severity = \TYPO3\CMS\Reports\Status::OK;
        /** @var \TYPO3\CMS\Saltedpasswords\Utility\ExtensionManagerConfigurationUtility $configCheck */
        $configCheck = GeneralUtility::makeInstance(\TYPO3\CMS\Saltedpasswords\Utility\ExtensionManagerConfigurationUtility::class);
        $message = '<p>' . $GLOBALS['LANG']->getLL('status_saltedPasswords_infoText') . '</p>';
        $messageDetail = '';
        $resultCheck = $configCheck->checkConfigurationBackend([], new \TYPO3\CMS\Core\TypoScript\ConfigurationForm());
        switch ($resultCheck['errorType']) {
            case FlashMessage::INFO:
                $messageDetail .= $resultCheck['html'];
                break;
            case FlashMessage::WARNING:
                $severity = \TYPO3\CMS\Reports\Status::WARNING;
                $messageDetail .= $resultCheck['html'];
                break;
            case FlashMessage::ERROR:
                $value = $GLOBALS['LANG']->getLL('status_insecure');
                $severity = \TYPO3\CMS\Reports\Status::ERROR;
                $messageDetail .= $resultCheck['html'];
                break;
            default:
        }
        $unsecureUserCount = SaltedPasswordsUtility::getNumberOfBackendUsersWithInsecurePassword();
        if ($unsecureUserCount > 0) {
            $value = $GLOBALS['LANG']->getLL('status_insecure');
            $severity = \TYPO3\CMS\Reports\Status::ERROR;
            $messageDetail .= '<div class="panel panel-warning">' .
                '<div class="panel-body">' .
                    $GLOBALS['LANG']->getLL('status_saltedPasswords_notAllPasswordsHashed') .
                '</div>' .
            '</div>';
        }
        $message .= $messageDetail;
        if (empty($messageDetail)) {
            $message = '';
        }
        return GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class,
            $GLOBALS['LANG']->getLL('status_saltedPasswords'), $value, $message, $severity);
    }
}
