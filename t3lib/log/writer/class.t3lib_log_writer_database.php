<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2012 Steffen Gebert (steffen.gebert@typo3.org)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 * Log writer that writes the log records into a database table.
 *
 * @author Steffen Gebert <steffen.gebert@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_log_writer_Database extends t3lib_log_writer_Abstract {

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
	 * @return t3lib_log_writer_Abstract
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
	 * @param t3lib_log_Record $record Log record
	 * @return t3lib_log_writer_Writer $this
	 * @throws RuntimeException
	 */
	public function writeLog(t3lib_log_Record $record) {
		$data = array(
			'request_id' => $record['requestId'],
			'time_micro' => $record['created'],
			'component'  => $record['component'],
			'level'      => $record['level'],
			'message'    => $record['message'],
			'data'       => (!empty($record['data'])) ? json_encode($record['data']) : '',
		);

		if (FALSE === $GLOBALS['TYPO3_DB']->exec_INSERTquery($this->logTable, $data)) {
			throw new RuntimeException('Could not write log record to database', 1345036334);
		}

		return $this;
	}
}

?>