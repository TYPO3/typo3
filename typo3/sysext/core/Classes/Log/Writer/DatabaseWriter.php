<?php
namespace TYPO3\CMS\Core\Log\Writer;

/**
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
 * Log writer that writes the log records into a database table.
 *
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 */
class DatabaseWriter extends \TYPO3\CMS\Core\Log\Writer\AbstractWriter {

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
	public function setLogTable($tableName) {
		$this->logTable = $tableName;
		return $this;
	}

	/**
	 * Get name of database log table
	 *
	 * @return string Database table name
	 */
	public function getLogTable() {
		return $this->logTable;
	}

	/**
	 * Writes the log record
	 *
	 * @param \TYPO3\CMS\Core\Log\LogRecord $record Log record
	 * @return \TYPO3\CMS\Core\Log\Writer\WriterInterface $this
	 * @throws \RuntimeException
	 */
	public function writeLog(\TYPO3\CMS\Core\Log\LogRecord $record) {
		$data = array(
			'request_id' => $record['requestId'],
			'time_micro' => $record['created'],
			'component' => $record['component'],
			'level' => $record['level'],
			'message' => $record['message'],
			'data' => !empty($record['data']) ? json_encode($record['data']) : ''
		);
		if (FALSE === $GLOBALS['TYPO3_DB']->exec_INSERTquery($this->logTable, $data)) {
			throw new \RuntimeException('Could not write log record to database', 1345036334);
		}
		return $this;
	}

}
