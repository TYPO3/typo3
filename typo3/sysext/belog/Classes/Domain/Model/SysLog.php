<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Christian Kuhn <lolli@schwarzbu.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * A sys log entry
 * This model is 'complete': All current database properties are in there.
 * @TODO: This should be stuffed to some more central place
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @package TYPO3
 * @subpackage belog
 */
class Tx_Belog_Domain_Model_SysLog extends Tx_Extbase_DomainObject_AbstractEntity {

	/**
	 * @var integer Storage page id of the log entry
	 */
	protected $pid;

	/**
	 * This is not a relation to BeUser model, since the user does
	 * not always exist, but we want the uid in then anyway.
	 * This case is ugly in extbase, the best way we
	 * have found now is to resolve the username (if it exists) in a
	 * view helper and just use the uid of the be user here.
	 *
	 * @var int
	 */
	protected $beUserUid;

	/**
	 * @var int action Id of the action that happened: For example '3' was a file action
	 */
	protected $action;

	/**
	 * @var int Uid of the record the event happened to
	 */
	protected $recordUid;

	/**
	 * @var string table name
	 */
	protected $tableName;

	/**
	 * @var int Pid of the record the event happened to
	 */
	protected $recordPid;

	/**
	 * @var int Error
	 */
	protected $error;

	/**
	 * @var string Details This is the log message itself, but possibly with %s substitutions
	 */
	protected $details;

	/**
	 * @var int Timestamp when the log entry was written
	 */
	protected $tstamp;

	/**
	 * @var int Type
	 */
	protected $type;

	/**
	 * @var int Details number
	 */
	protected $detailsNumber;

	/**
	 * @var string Ip address of client
	 */
	protected $ip;

	/**
	 * @var string Serialized log data This is a serialized array with substitutions for $this->details
	 */
	protected $logData;

	/**
	 * @var int event pid
	 */
	protected $eventPid;

	/**
	 * This is only the uid and not the full workspace object for
	 * the same reason as in $beUserUid
	 *
	 * @var integer
	 */
	protected $workspaceUid;

	/**
	 * @var string new id
	 */
	protected $newId;

	/**
	 * Set pid
	 *
	 * @param int $pid
	 * @return void
	 */
	public function setPid($pid) {
		$this->pid = (int)$pid;
	}

	/**
	 * Get pid
	 *
	 * @return integer
	 */
	public function getPid() {
		return $this->pid;
	}

	/**
	 * Set backend user uid
	 *
	 * @param integer $beUserUid
	 * @return void
	 */
	public function setBeUserUid($beUserUid) {
		$this->beUserUid = $beUserUid;
	}

	/**
	 * Get backend user id
	 *
	 * @return integer
	 */
	public function getBeUserUid() {
		return $this->beUserUid;
	}

	/**
	 * Set action
	 *
	 * @param int $action
	 * @return void
	 */
	public function setAction($action) {
		$this->action = (int)$action;
	}

	/**
	 * Get action
	 *
	 * @return int
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * Set record uid
	 *
	 * @param int $recordUid
	 * @return void
	 */
	public function setRecordUid($recordUid) {
		$this->recordUid = $recordUid;
	}

	/**
	 * Get record uid
	 *
	 * @return int
	 */
	public function getRecordUid() {
		return $this->recordUid;
	}

	/**
	 * Set table name
	 *
	 * @param string $tableName
	 * @return void
	 */
	public function setTableName($tableName) {
		$this->tableName = $tableName;
	}

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public function getTableName() {
		return $this->tableName;
	}

	/**
	 * Set record pid
	 *
	 * @param int $recordPid
	 * @return void
	 */
	public function setRecordPid($recordPid) {
		$this->recordPid = $recordPid;
	}

	/**
	 * Get record pid
	 *
	 * @return int
	 */
	public function getRecordPid() {
		return $this->recordPid;
	}

	/**
	 * Set error
	 *
	 * @param int $error
	 * @return void
	 */
	public function setError($error) {
		$this->error = (int)$error;
	}

	/**
	 * Get error
	 *
	 * @return int
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Set details
	 *
	 * @param string $details
	 * @return void
	 */
	public function setDetails($details) {
		$this->details = $details;
	}

	/**
	 * Get details
	 *
	 * @return string
	 */
	public function getDetails() {
		return $this->details;
	}

	/**
	 * Set tstamp
	 *
	 * @param int $tstamp
	 * @return void
	 */
	public function setTstamp($tstamp) {
		$this->tstamp = $tstamp;
	}

	/**
	 * Get tstamp
	 *
	 * @return int
	 */
	public function getTstamp() {
		return $this->tstamp;
	}

	/**
	 * Set type
	 *
	 * @param int $type
	 * @return void
	 */
	public function setType($type) {
		$this->type = (int)$type;
	}

	/**
	 * Get type
	 *
	 * @return int
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Set details number
	 *
	 * @param int $detailsNumber
	 * @return void
	 */
	public function setDetailsNumber($detailsNumber) {
		$this->detailsNumber = $detailsNumber;
	}

	/**
	 * Get details number
	 *
	 * @return int
	 */
	public function getDetailsNumber() {
		return $this->detailsNumber;
	}

	/**
	 * Set ip
	 *
	 * @param string $ip
	 * @return void
	 */
	public function setIp($ip) {
		$this->ip = $ip;
	}

	/**
	 * Get ip
	 *
	 * @return string
	 */
	public function getIp() {
		return $this->ip;
	}

	/**
	 * Set log data
	 *
	 * @param string $logData
	 * @return void
	 */
	public function setLogData($logData) {
		$this->logData = $logData;
	}

	/**
	 * Get log data
	 *
	 * @return string
	 */
	public function getLogData() {
		return @unserialize($this->logData);
	}

	/**
	 * Set event pid
	 *
	 * @param int $eventPid
	 * @return void
	 */
	public function setEventPid($eventPid) {
		$this->eventPid = $eventPid;
	}

	/**
	 * Get event pid
	 *
	 * @return int
	 */
	public function getEventPid() {
		return $this->eventPid;
	}

	/**
	 * Set workspace uid
	 *
	 * @param integer $workspace Uid
	 * @return void
	 */
	public function setWorkspaceUid($workspaceUid) {
		$this->workspaceUid = $workspaceUid;
	}

	/**
	 * Get workspace
	 *
	 * @return integer
	 */
	public function getWorkspaceUid() {
		return $this->workspaceUid;
	}

	/**
	 * Set new id
	 *
	 * @param string $newId
	 * @return void
	 */
	public function setNewId($newId) {
		$this->newId = $newId;
	}

	/**
	 * Get new id
	 *
	 * @return string
	 */
	public function getNewId() {
		return $this->newId;
	}
}
?>