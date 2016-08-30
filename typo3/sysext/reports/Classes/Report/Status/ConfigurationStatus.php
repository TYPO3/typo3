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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Performs some checks about the install tool protection status
 */
class ConfigurationStatus implements \TYPO3\CMS\Reports\StatusProviderInterface
{
    /**
     * 10MB
     *
     * @var int
     */
    protected $deprecationLogFileSizeWarningThreshold = 10485760;

    /**
     * 100MB
     *
     * @var int
     */
    protected $deprecationLogFileSizeErrorThreshold = 104857600;

    /**
     * Backpath to the typo3 main directory
     *
     * @var string
     */
    protected $backPath = '../';

    /**
     * Determines the Install Tool's status, mainly concerning its protection.
     *
     * @return array List of statuses
     */
    public function getStatus()
    {
        $this->executeAdminCommand();
        $statuses = [
            'emptyReferenceIndex' => $this->getReferenceIndexStatus(),
            'deprecationLog' => $this->getDeprecationLogStatus()
        ];
        if ($this->isMemcachedUsed()) {
            $statuses['memcachedConnection'] = $this->getMemcachedConnectionStatus();
        }
        if (TYPO3_OS !== 'WIN') {
            $statuses['createdFilesWorldWritable'] = $this->getCreatedFilesWorldWritableStatus();
            $statuses['createdDirectoriesWorldWritable'] = $this->getCreatedDirectoriesWorldWritableStatus();
        }
        return $statuses;
    }

    /**
     * Checks if sys_refindex is empty.
     *
     * @return \TYPO3\CMS\Reports\Status An object representing whether the reference index is empty or not
     */
    protected function getReferenceIndexStatus()
    {
        $value = $GLOBALS['LANG']->getLL('status_ok');
        $message = '';
        $severity = \TYPO3\CMS\Reports\Status::OK;
        $count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'sys_refindex');
        $registry = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Registry::class);
        $lastRefIndexUpdate = $registry->get('core', 'sys_refindex_lastUpdate');
        if (!$count && $lastRefIndexUpdate) {
            $value = $GLOBALS['LANG']->getLL('status_empty');
            $severity = \TYPO3\CMS\Reports\Status::WARNING;
            $url =  \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('system_dbint') . '&id=0&SET[function]=refindex';
            $message = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.backend_reference_index'), '<a href="' . htmlspecialchars($url) . '">', '</a>', \TYPO3\CMS\Backend\Utility\BackendUtility::dateTime($lastRefIndexUpdate));
        }
        return GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class, $GLOBALS['LANG']->getLL('status_referenceIndex'), $value, $message, $severity);
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
     * Gets the configured memcached server connections.
     *
     * @return array An array of configured memcached server connections.
     */
    protected function getConfiguredMemcachedServers()
    {
        $memcachedServers = [];
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] as $table => $conf) {
                if (is_array($conf)) {
                    foreach ($conf as $key => $value) {
                        if (!is_array($value) && $value === \TYPO3\CMS\Core\Cache\Backend\MemcachedBackend::class) {
                            $memcachedServers = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$table]['options']['servers'];
                            break;
                        }
                    }
                }
            }
        }
        return $memcachedServers;
    }

    /**
     * Checks whether TYPO3 can connect to the configured memcached servers.
     *
     * @return \TYPO3\CMS\Reports\Status An object representing whether TYPO3 can connect to the configured memcached servers
     */
    protected function getMemcachedConnectionStatus()
    {
        $value = $GLOBALS['LANG']->getLL('status_ok');
        $message = '';
        $severity = \TYPO3\CMS\Reports\Status::OK;
        $failedConnections = [];
        $defaultMemcachedPort = ini_get('memcache.default_port');
        $memcachedServers = $this->getConfiguredMemcachedServers();
        if (function_exists('memcache_connect') && is_array($memcachedServers)) {
            foreach ($memcachedServers as $testServer) {
                $configuredServer = $testServer;
                if (substr($testServer, 0, 7) == 'unix://') {
                    $host = $testServer;
                    $port = 0;
                } else {
                    if (substr($testServer, 0, 6) === 'tcp://') {
                        $testServer = substr($testServer, 6);
                    }
                    if (strstr($testServer, ':') !== false) {
                        list($host, $port) = explode(':', $testServer, 2);
                    } else {
                        $host = $testServer;
                        $port = $defaultMemcachedPort;
                    }
                }
                $memcachedConnection = @memcache_connect($host, $port);
                if ($memcachedConnection != null) {
                    memcache_close($memcachedConnection);
                } else {
                    $failedConnections[] = $configuredServer;
                }
            }
        }
        if (!empty($failedConnections)) {
            $value = $GLOBALS['LANG']->getLL('status_connectionFailed');
            $severity = \TYPO3\CMS\Reports\Status::WARNING;
            $message = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.memcache_not_usable') . '<br /><br />' . '<ul><li>' . implode('</li><li>', $failedConnections) . '</li></ul>';
        }
        return GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class, $GLOBALS['LANG']->getLL('status_memcachedConfiguration'), $value, $message, $severity);
    }

    /**
     * Provides status information on the deprecation log, whether it's enabled
     * and if so whether certain limits in file size are reached.
     *
     * @return \TYPO3\CMS\Reports\Status The deprecation log status.
     */
    protected function getDeprecationLogStatus()
    {
        $title = $GLOBALS['LANG']->getLL('status_configuration_DeprecationLog');
        $value = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:disabled');
        $message = '';
        $severity = \TYPO3\CMS\Reports\Status::OK;
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['enableDeprecationLog']) {
            $value = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:enabled');
            $message = '<p>' . $GLOBALS['LANG']->getLL('status_configuration_DeprecationLogEnabled') . '</p>';
            $severity = \TYPO3\CMS\Reports\Status::NOTICE;
            $logFile = GeneralUtility::getDeprecationLogFileName();
            $logFileSize = 0;
            if (@file_exists($logFile)) {
                $logFileSize = filesize($logFile);
                $message .= '<p>' . sprintf($GLOBALS['LANG']->getLL('status_configuration_DeprecationLogFile'), '<code>' . $this->getDeprecationLogFileLink()) . '</code></p>';
                $removeDeprecationLogFileUrl = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL') . '&amp;adminCmd=removeDeprecationLogFile';
                $message .= '<p>' . sprintf($GLOBALS['LANG']->getLL('status_configuration_DeprecationLogSize'), GeneralUtility::formatSize($logFileSize)) . ' <a href="' . $removeDeprecationLogFileUrl . '">' . $GLOBALS['LANG']->getLL('status_configuration_DeprecationLogDeleteLink') . '</a></p>';
            }
            if ($logFileSize > $this->deprecationLogFileSizeWarningThreshold) {
                $severity = \TYPO3\CMS\Reports\Status::WARNING;
            }
            if ($logFileSize > $this->deprecationLogFileSizeErrorThreshold) {
                $severity = \TYPO3\CMS\Reports\Status::ERROR;
            }
        }
        return GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class, $title, $value, $message, $severity);
    }

    /**
     * Warning, if fileCreateMask has write bit for 'others' set.
     *
     * @return \TYPO3\CMS\Reports\Status The writable status for 'others'
     */
    protected function getCreatedFilesWorldWritableStatus()
    {
        $value = $GLOBALS['LANG']->getLL('status_ok');
        $message = '';
        $severity = \TYPO3\CMS\Reports\Status::OK;
        if ((int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'] % 10 & 2) {
            $value = $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'];
            $severity = \TYPO3\CMS\Reports\Status::WARNING;
            $message = $GLOBALS['LANG']->getLL('status_CreatedFilePermissions.writable');
        }
        return GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class, $GLOBALS['LANG']->getLL('status_CreatedFilePermissions'), $value, $message, $severity);
    }

    /**
     * Warning, if folderCreateMask has write bit for 'others' set.
     *
     * @return \TYPO3\CMS\Reports\Status The writable status for 'others'
     */
    protected function getCreatedDirectoriesWorldWritableStatus()
    {
        $value = $GLOBALS['LANG']->getLL('status_ok');
        $message = '';
        $severity = \TYPO3\CMS\Reports\Status::OK;
        if ((int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'] % 10 & 2) {
            $value = $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'];
            $severity = \TYPO3\CMS\Reports\Status::WARNING;
            $message = $GLOBALS['LANG']->getLL('status_CreatedDirectoryPermissions.writable');
        }
        return GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class, $GLOBALS['LANG']->getLL('status_CreatedDirectoryPermissions'), $value, $message, $severity);
    }

    /**
     * Creates a link to the deprecation log file with the absolute path as the
     * link text.
     *
     * @return string Link to the deprecation log file
     */
    protected function getDeprecationLogFileLink()
    {
        $logFile = GeneralUtility::getDeprecationLogFileName();
        $relativePath = GeneralUtility::resolveBackPath($this->backPath . \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($logFile));
        $link = '<a href="' . $relativePath . '">' . $logFile . '</a>';
        return $link;
    }

    /**
     * Executes admin commands.
     *
     * Currently implemented commands are:
     * - Remove deprecation log file
     *
     * @return void
     */
    protected function executeAdminCommand()
    {
        $command = GeneralUtility::_GET('adminCmd');
        switch ($command) {
            case 'removeDeprecationLogFile':
                self::removeDeprecationLogFile();
                break;
            default:
                // intentionally left blank
        }
    }

    /**
     * Remove deprecation log file.
     *
     * @return void
     */
    protected static function removeDeprecationLogFile()
    {
        if (@unlink(GeneralUtility::getDeprecationLogFileName())) {
            $message = $GLOBALS['LANG']->getLL('status_configuration_DeprecationLogDeletedSuccessful');
            $severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK;
        } else {
            $message = $GLOBALS['LANG']->getLL('status_configuration_DeprecationLogDeletionFailed');
            $severity = \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR;
        }
        $flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $message, '', $severity, true);
        /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
        /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }
}
