<?php
namespace TYPO3\CMS\Install\SystemEnvironment;

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
use TYPO3\CMS\Install\Status;

/**
 * Check database configuration status
 *
 * This class is a hardcoded requirement check for the database server.
 *
 * The status messages and title *must not* include HTML, use plain
 * text only. The return values of this class are not bound to HTML
 * and can be used in different scopes (eg. as json array).
 */
class DatabaseCheck
{
    /**
     * List of MySQL modes that are incompatible with TYPO3 CMS
     *
     * @var array
     */
    protected $incompatibleSqlModes = [
        'NO_BACKSLASH_ESCAPES'
    ];

    /**
     * Get all status information as array with status objects
     *
     * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
     */
    public function getStatus()
    {
        $statusArray = [];
        if ($this->isDbalEnabled() || !$this->getDatabaseConnection()) {
            return $statusArray;
        }
        $statusArray[] = $this->checkMysqlVersion();
        $statusArray[] = $this->checkInvalidSqlModes();
        return $statusArray;
    }

    /**
     * Check if any SQL mode is set which is not compatible with TYPO3
     *
     * @return Status\StatusInterface
     */
    protected function checkInvalidSqlModes()
    {
        $detectedIncompatibleSqlModes = $this->getIncompatibleSqlModes();
        if (!empty($detectedIncompatibleSqlModes)) {
            $status = new Status\ErrorStatus();
            $status->setTitle('Incompatible SQL modes found!');
            $status->setMessage(
                'Incompatible SQL modes have been detected:' .
                ' ' . implode(', ', $detectedIncompatibleSqlModes) . '.' .
                ' The listed modes are not compatible with TYPO3 CMS.' .
                ' You have to change that setting in your MySQL environment' .
                ' or in $GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'setDBinit\']'
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('No incompatible SQL modes found.');
        }

        return $status;
    }

    /**
     * Check minimum MySQL version
     *
     * @return Status\StatusInterface
     */
    protected function checkMysqlVersion()
    {
        $minimumMysqlVersion = '5.5.0';
        $currentMysqlVersion = '';
        $resource = $this->getDatabaseConnection()->sql_query('SHOW VARIABLES LIKE \'version\';');
        if ($resource !== false) {
            $result = $this->getDatabaseConnection()->sql_fetch_row($resource);
            if (isset($result[1])) {
                $currentMysqlVersion = $result[1];
            }
        }
        if (version_compare($currentMysqlVersion, $minimumMysqlVersion) < 0) {
            $status = new Status\ErrorStatus();
            $status->setTitle('MySQL version too low');
            $status->setMessage(
                'Your MySQL version ' . $currentMysqlVersion . ' is too old. TYPO3 CMS does not run' .
                ' with this version. Update to at least MySQL ' . $minimumMysqlVersion
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('MySQL version is fine');
        }

        return $status;
    }

    /**
     * Returns an array with the current sql mode settings
     *
     * @return array Contains all configured SQL modes that are incompatible
     */
    protected function getIncompatibleSqlModes()
    {
        $sqlModes = [];
        $resource = $this->getDatabaseConnection()->sql_query('SELECT @@SESSION.sql_mode;');
        if ($resource !== false) {
            $result = $this->getDatabaseConnection()->sql_fetch_row($resource);
            if (isset($result[0])) {
                $sqlModes = explode(',', $result[0]);
            }
        }
        return array_intersect($this->incompatibleSqlModes, $sqlModes);
    }

    /**
     * Get database instance.
     * Will be initialized if it does not exist yet.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        static $database;
        if (!is_object($database)) {
            /** @var \TYPO3\CMS\Core\Database\DatabaseConnection $database */
            $database = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
            $database->setDatabaseUsername($GLOBALS['TYPO3_CONF_VARS']['DB']['username']);
            $database->setDatabasePassword($GLOBALS['TYPO3_CONF_VARS']['DB']['password']);
            $database->setDatabaseHost($GLOBALS['TYPO3_CONF_VARS']['DB']['host']);
            $database->setDatabasePort($GLOBALS['TYPO3_CONF_VARS']['DB']['port']);
            $database->setDatabaseSocket($GLOBALS['TYPO3_CONF_VARS']['DB']['socket']);
            $database->setDatabaseName($GLOBALS['TYPO3_CONF_VARS']['DB']['database']);
            $database->initialize();
            $database->connectDB();
        }
        return $database;
    }

    /**
     * Checks if DBAL is enabled for the database connection
     *
     * @return bool
     */
    protected function isDbalEnabled()
    {
        return \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('dbal');
    }
}
