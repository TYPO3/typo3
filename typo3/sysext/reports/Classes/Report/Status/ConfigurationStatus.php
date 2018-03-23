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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Status as ReportStatus;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Performs some checks about the install tool protection status
 */
class ConfigurationStatus implements StatusProviderInterface
{
    /**
     * Determines the Install Tool's status, mainly concerning its protection.
     *
     * @return array List of statuses
     */
    public function getStatus()
    {
        $statuses = [
            'emptyReferenceIndex' => $this->getReferenceIndexStatus(),
        ];
        if ($this->isMemcachedUsed()) {
            $statuses['memcachedConnection'] = $this->getMemcachedConnectionStatus();
        }
        if (!Environment::isWindows()) {
            $statuses['createdFilesWorldWritable'] = $this->getCreatedFilesWorldWritableStatus();
            $statuses['createdDirectoriesWorldWritable'] = $this->getCreatedDirectoriesWorldWritableStatus();
        }
        if ($this->isMysqlUsed()) {
            $statuses['mysqlDatabaseUsesUtf8'] = $this->getMysqlDatabaseUtf8Status();
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
        $value = $this->getLanguageService()->getLL('status_ok');
        $message = '';
        $severity = ReportStatus::OK;

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');
        $count = $queryBuilder
            ->count('*')
            ->from('sys_refindex')
            ->execute()
            ->fetchColumn(0);

        $registry = GeneralUtility::makeInstance(Registry::class);
        $lastRefIndexUpdate = $registry->get('core', 'sys_refindex_lastUpdate');
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        if (!$count && $lastRefIndexUpdate) {
            $value = $this->getLanguageService()->getLL('status_empty');
            $severity = ReportStatus::WARNING;
            $url = (string)$uriBuilder->buildUriFromRoute('system_dbint') . '&id=0&SET[function]=refindex';
            $message = sprintf($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.backend_reference_index'), '<a href="' . htmlspecialchars($url) . '">', '</a>', BackendUtility::datetime($lastRefIndexUpdate));
        }
        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_referenceIndex'), $value, $message, $severity);
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
        $configurations = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] ?? [];
        $memcachedServers = [];
        foreach ($configurations as $table => $conf) {
            if (is_array($conf)) {
                foreach ($conf as $key => $value) {
                    if ($value === \TYPO3\CMS\Core\Cache\Backend\MemcachedBackend::class) {
                        $memcachedServers = $configurations[$table]['options']['servers'];
                        break;
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
        $value = $this->getLanguageService()->getLL('status_ok');
        $message = '';
        $severity = ReportStatus::OK;
        $failedConnections = [];
        $defaultMemcachedPort = ini_get('memcache.default_port');
        $memcachedServers = $this->getConfiguredMemcachedServers();
        if (function_exists('memcache_connect') && is_array($memcachedServers)) {
            foreach ($memcachedServers as $testServer) {
                $configuredServer = $testServer;
                if (strpos($testServer, 'unix://') === 0) {
                    $host = $testServer;
                    $port = 0;
                } else {
                    if (strpos($testServer, 'tcp://') === 0) {
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
                    memcache_close();
                } else {
                    $failedConnections[] = $configuredServer;
                }
            }
        }
        if (!empty($failedConnections)) {
            $value = $this->getLanguageService()->getLL('status_connectionFailed');
            $severity = ReportStatus::WARNING;
            $message = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.memcache_not_usable') . '<br /><br />' . '<ul><li>' . implode('</li><li>', $failedConnections) . '</li></ul>';
        }
        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_memcachedConfiguration'), $value, $message, $severity);
    }

    /**
     * Warning, if fileCreateMask has write bit for 'others' set.
     *
     * @return \TYPO3\CMS\Reports\Status The writable status for 'others'
     */
    protected function getCreatedFilesWorldWritableStatus()
    {
        $value = $this->getLanguageService()->getLL('status_ok');
        $message = '';
        $severity = ReportStatus::OK;
        if ((int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'] % 10 & 2) {
            $value = $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'];
            $severity = ReportStatus::WARNING;
            $message = $this->getLanguageService()->getLL('status_CreatedFilePermissions.writable');
        }
        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_CreatedFilePermissions'), $value, $message, $severity);
    }

    /**
     * Warning, if folderCreateMask has write bit for 'others' set.
     *
     * @return \TYPO3\CMS\Reports\Status The writable status for 'others'
     */
    protected function getCreatedDirectoriesWorldWritableStatus()
    {
        $value = $this->getLanguageService()->getLL('status_ok');
        $message = '';
        $severity = ReportStatus::OK;
        if ((int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'] % 10 & 2) {
            $value = $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'];
            $severity = ReportStatus::WARNING;
            $message = $this->getLanguageService()->getLL('status_CreatedDirectoryPermissions.writable');
        }
        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_CreatedDirectoryPermissions'), $value, $message, $severity);
    }

    /**
     * Checks if the default connection is a MySQL compatible database instance.
     *
     * @return bool
     */
    protected function isMysqlUsed()
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);

        return strpos($connection->getServerVersion(), 'MySQL') === 0;
    }

    /**
     * Checks the character set of the default database and reports an error if it is not utf-8.
     *
     * @return ReportStatus
     */
    protected function getMysqlDatabaseUtf8Status()
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connection->createQueryBuilder();
        $defaultDatabaseCharset = (string)$queryBuilder->select('DEFAULT_CHARACTER_SET_NAME')
            ->from('information_schema.SCHEMATA')
            ->where(
                $queryBuilder->expr()->eq(
                    'SCHEMA_NAME',
                    $queryBuilder->createNamedParameter($connection->getDatabase(), \PDO::PARAM_STR)
                )
            )
            ->setMaxResults(1)
            ->execute()
            ->fetchColumn();

        $severity = ReportStatus::OK;
        $statusValue = $this->getLanguageService()->getLL('status_ok');
        // also allow utf8mb4
        if (strpos($defaultDatabaseCharset, 'utf8') !== 0) {
            // If the default character set is e.g. latin1, BUT all tables in the system are UTF-8,
            // we assume that TYPO3 has the correct charset for adding tables, and everything is fine
            $queryBuilder = $connection->createQueryBuilder();
            $nonUtf8TableCollationsFound = $queryBuilder->select('table_collation')
                ->from('information_schema.tables')
                ->where(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq('table_schema', $queryBuilder->quote($connection->getDatabase())),
                        $queryBuilder->expr()->notLike('table_collation', $queryBuilder->quote('utf8%'))
                    )
                )
                ->setMaxResults(1)
                ->execute();

            if ($nonUtf8TableCollationsFound->rowCount() > 0) {
                $message = sprintf($this->getLanguageService()
                    ->getLL('status_MysqlDatabaseCharacterSet_Unsupported'), $defaultDatabaseCharset);
                $severity = ReportStatus::ERROR;
                $statusValue = $this->getLanguageService()->getLL('status_wrongValue');
            } else {
                $message = $this->getLanguageService()->getLL('status_MysqlDatabaseCharacterSet_Info');
                $severity = ReportStatus::INFO;
                $statusValue = $this->getLanguageService()->getLL('status_info');
            }
        } elseif (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['tableoptions'])) {
            $message = $this->getLanguageService()->getLL('status_MysqlDatabaseCharacterSet_Ok');

            $tableOptions = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME]['tableoptions'];
            if (isset($tableOptions['collate'])) {
                $collationConstraint = $queryBuilder->expr()->neq('table_collation', $queryBuilder->quote($tableOptions['collate']));
                $charset = $tableOptions['collate'];
            } elseif (isset($tableOptions['charset'])) {
                $collationConstraint = $queryBuilder->expr()->notLike('table_collation', $queryBuilder->quote($tableOptions['charset'] . '%'));
                $charset = $tableOptions['charset'];
            }

            if (isset($collationConstraint)) {
                $queryBuilder = $connection->createQueryBuilder();
                $wrongCollationTablesFound = $queryBuilder->select('table_collation')
                    ->from('information_schema.tables')
                    ->where(
                        $queryBuilder->expr()->andX(
                            $queryBuilder->expr()->eq('table_schema', $queryBuilder->quote($connection->getDatabase())),
                            $collationConstraint
                        )
                    )
                    ->setMaxResults(1)
                    ->execute();

                if ($wrongCollationTablesFound->rowCount() > 0) {
                    $message = sprintf($this->getLanguageService()->getLL('status_MysqlDatabaseCharacterSet_MixedCollations'), $charset);
                    $severity = ReportStatus::ERROR;
                    $statusValue = $this->getLanguageService()->getLL('status_checkFailed');
                } else {
                    if (isset($tableOptions['collate'])) {
                        $collationConstraint = $queryBuilder->expr()->neq('collation_name', $queryBuilder->quote($tableOptions['collate']));
                    } elseif (isset($tableOptions['charset'])) {
                        $collationConstraint = $queryBuilder->expr()->notLike('collation_name', $queryBuilder->quote($tableOptions['charset'] . '%'));
                    }

                    $queryBuilder = $connection->createQueryBuilder();
                    $wrongCollationColumnsFound = $queryBuilder->select('collation_name')
                        ->from('information_schema.columns')
                        ->where(
                            $queryBuilder->expr()->andX(
                                $queryBuilder->expr()->eq('table_schema', $queryBuilder->quote($connection->getDatabase())),
                                $collationConstraint
                            )
                        )
                        ->setMaxResults(1)
                        ->execute();

                    if ($wrongCollationColumnsFound->rowCount() > 0) {
                        $message = sprintf($this->getLanguageService()->getLL('status_MysqlDatabaseCharacterSet_MixedCollations'), $charset);
                        $severity = ReportStatus::ERROR;
                        $statusValue = $this->getLanguageService()->getLL('status_checkFailed');
                    }
                }
            }
        } else {
            $message = $this->getLanguageService()->getLL('status_MysqlDatabaseCharacterSet_Ok');
        }

        return GeneralUtility::makeInstance(
            ReportStatus::class,
            $this->getLanguageService()->getLL('status_MysqlDatabaseCharacterSet'),
            $statusValue,
            $message,
            $severity
        );
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
