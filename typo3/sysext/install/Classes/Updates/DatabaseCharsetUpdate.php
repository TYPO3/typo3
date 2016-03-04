<?php
namespace TYPO3\CMS\Install\Updates;

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

/**
 * Move "wizard done" flags to system registry
 */
class DatabaseCharsetUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Set default database charset to utf-8';

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        $result = false;
        $description = 'Sets the default database charset to utf-8 to ensure new tables are created with correct charset.
        WARNING: This will NOT convert any existing data.';

        if ($this->isDbalEnabled()) {
            return $result;
        }
        // check if database charset is utf-8
        $defaultDatabaseCharset = $this->getDefaultDatabaseCharset();
        // also allow utf8mb4
        if (substr($defaultDatabaseCharset, 0, 4) !== 'utf8') {
            $result = true;
        }

        return $result;
    }

    /**
     * Performs the accordant updates.
     *
     * @param array &$dbQueries Queries done in this update
     * @param mixed &$customMessages Custom messages
     * @return bool Whether everything went smoothly or not
     */
    public function performUpdate(array &$dbQueries, &$customMessages)
    {
        $result = true;
        $db = $this->getDatabaseConnection();
        $query = 'ALTER DATABASE `' . $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'] . '` DEFAULT CHARACTER SET utf8';
        $db->admin_query($query);
        $dbQueries[] = $query;
        if ($db->sql_error()) {
            $customMessages = 'SQL-ERROR: ' . htmlspecialchars($db->sql_error());
            $result = false;
        }
        return $result;
    }

    /**
     * Return TRUE if dbal and adodb extension is loaded
     *
     * @return bool TRUE if dbal and adodb is loaded
     */
    protected function isDbalEnabled()
    {
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('adodb')
            && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('dbal')
        ) {
            return true;
        }
        return false;
    }

    /**
     * Retrieves the default character set of the database.
     *
     * @return string
     */
    protected function getDefaultDatabaseCharset()
    {
        $db = $this->getDatabaseConnection();
        $result = $db->admin_query('SHOW VARIABLES LIKE "character_set_database"');
        $row = $db->sql_fetch_assoc($result);

        $key = $row['Variable_name'];
        $value = $row['Value'];
        $databaseCharset = '';

        if ($key == 'character_set_database') {
            $databaseCharset = $value;
        }

        return $databaseCharset;
    }
}
