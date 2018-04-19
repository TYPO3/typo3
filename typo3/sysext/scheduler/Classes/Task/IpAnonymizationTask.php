<?php

namespace TYPO3\CMS\Scheduler\Task;

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

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\IpAnonymizationUtility;

/**
 * Anonymize IP addresses in records
 *
 * This task anonymizes IP addresses in tables older than the given number of days.
 *
 * Available tables must be registered in
 * $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\IpAnonymizationTask::class]['options']['tables']
 * See ext_localconf.php of scheduler extension for an example
 */
class IpAnonymizationTask extends AbstractTask
{

    /**
     * @var int Number of days
     */
    public $numberOfDays = 180;

    /**
     * @var int mask level see \TYPO3\CMS\Core\Utility\IpAnonymizationUtility::anonymizeIp
     */
    public $mask = 2;

    /**
     * @var string Table to clean up
     */
    public $table = '';

    /**
     * Execute garbage collection, called by scheduler.
     *
     * @throws \RuntimeException If configured table was not cleaned up
     * @return bool TRUE if task run was successful
     */
    public function execute()
    {
        $configuration = isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][self::class]['options']['tables'][$this->table])
            ? $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][self::class]['options']['tables'][$this->table]
            : [];
        if (empty($configuration)) {
            throw new \RuntimeException(self::class . ' misconfiguration: ' . $this->table . ' does not exist in configuration', 1524502548);
        }
        $this->handleTable($this->table, $configuration);
        return true;
    }

    /**
     * Execute clean up of a specific table
     *
     * @throws \RuntimeException If table configuration is broken
     * @param string $table The table to handle
     * @param array $configuration Clean up configuration
     * @return bool TRUE if cleanup was successful
     */
    protected function handleTable($table, array $configuration)
    {
        $database = $this->getDatabase();
        if (empty($configuration['dateField'])) {
            throw new \RuntimeException(self::class . ' misconfiguration: "dateField" must be defined for table ' . $table, 1524502549);
        }
        if (empty($configuration['ipField'])) {
            throw new \RuntimeException(self::class . ' misconfiguration: "ipField" must be defined for table ' . $table, 1524502666);
        }
        $deleteTimestamp = strtotime('-' . $this->numberOfDays . 'days');
        if ($deleteTimestamp === false) {
            throw new \RuntimeException(self::class . ' misconfiguration: number of days could not be calculated for table ' . $table, 1524526354);
        }
        $where = $configuration['dateField'] . ' < ' . (int)$deleteTimestamp
                 . ' AND ' . $configuration['ipField'] . ' <> \'\''
                 . ' AND ' . $configuration['ipField'] . ' is not null'
                 . ' AND ' . $configuration['ipField'] . ' not like \'%.0.0\''
                 . ' AND ' . $configuration['ipField'] . ' not like \'%::\'';
        $res = $database->exec_SELECTquery('uid,' . $configuration['ipField'], $table, $where);
        if (!$res) {
            throw new \RuntimeException(self::class . ' failed for table ' . $this->table . ' with error: ' . $database->sql_error(), 1524502550);
        }
        while ($row = $database->sql_fetch_assoc($res)) {
            $ip = (string)$row[$configuration['ipField']];
            $database->exec_UPDATEquery($table, 'uid = ' . (int)$row['uid'], [
                $configuration['ipField'] => IpAnonymizationUtility::anonymizeIp($ip, $this->mask)
            ]);
        }
        $database->sql_free_result($res);
        return true;
    }

    /**
     * This method returns the selected table as additional information
     *
     * @return string Information to display
     */
    public function getAdditionalInformation()
    {
        return sprintf($GLOBALS['LANG']->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.ipAnonymization.additionalInformationTable'), $this->table, $this->numberOfDays);
    }

    /**
     * @return DatabaseConnection|null
     */
    private function getDatabase()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
