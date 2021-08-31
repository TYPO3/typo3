<?php

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

namespace TYPO3\CMS\Core\Log\Writer;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Log writer that writes the log records into a database table.
 */
class DatabaseWriter extends AbstractWriter
{
    /**
     * Table to write the log records to.
     *
     * @var string
     */
    protected $logTable = 'sys_log';

    /**
     * Set name of database log table
     *
     * @param string $tableName Database table name
     * @return \TYPO3\CMS\Core\Log\Writer\AbstractWriter
     */
    public function setLogTable($tableName)
    {
        $this->logTable = $tableName;
        return $this;
    }

    /**
     * Get name of database log table
     *
     * @return string Database table name
     */
    public function getLogTable()
    {
        return $this->logTable;
    }

    /**
     * Writes the log record
     *
     * @param LogRecord $record Log record
     * @return \TYPO3\CMS\Core\Log\Writer\WriterInterface $this
     */
    public function writeLog(LogRecord $record)
    {
        try {
            // Avoid database writes until boot has been completed.
            if (!GeneralUtility::getContainer()->get('boot.state')->complete) {
                return $this;
            }
        } catch (\LogicException $e) {
            // LogicException will be thrown if the container isn't available yet.
            return $this;
        }

        $data = '';
        $context = $record->getData();
        if (!empty($context)) {
            // Fold an exception into the message, and string-ify it into context so it can be jsonified.
            if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
                $context['exception'] = (string)$context['exception'];
            }
            $data = '- ' . json_encode($context);
        }

        $fieldValues = [
            'request_id' => $record->getRequestId(),
            'time_micro' => $record->getCreated(),
            'component' => $record->getComponent(),
            'level' => LogLevel::normalizeLevel($record->getLevel()),
            'message' => $record->getMessage(),
            'data' => $data,
        ];

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->logTable)
            ->insert($this->logTable, $fieldValues);

        return $this;
    }
}
