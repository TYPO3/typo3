<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2012 Oliver Hader <oliver.hader@typo3.org>
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
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * @author Oliver Hader <oliver.hader@typo3.org>
 * @package Workspaces
 * @subpackage Domain
 */
class Tx_Workspaces_Domain_Model_CombinedRecord {
	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var Tx_Workspaces_Domain_Model_DatabaseRecord
	 */
	protected $versionRecord;

	/**
	 * @var Tx_Workspaces_Domain_Model_DatabaseRecord
	 */
	protected $liveRecord;

	/**
	 * Creates combined record object just by live-id and version-id of database record rows.
	 *
	 * @param string $table Name of the database table
	 * @param integer $liveId Id of the database live-record row
	 * @param integer $versionId Id of the datbase version-record row
	 * @return Tx_Workspaces_Domain_Model_CombinedRecord
	 */
	public static function create($table, $liveId, $versionId) {
		$liveRecord = Tx_Workspaces_Domain_Model_DatabaseRecord::create($table, $liveId);
		$versionRecord = Tx_Workspaces_Domain_Model_DatabaseRecord::create($table, $versionId);

		return t3lib_div::makeInstance(
			'Tx_Workspaces_Domain_Model_CombinedRecord',
			$table, $liveRecord, $versionRecord
		);
	}

	/**
	 * Creates combined record object by relevant database live-record and version-record rows.
	 *
	 * @param string $table Name of the database table
	 * @param array $liveRow The relevant datbase live-record row
	 * @param array $versionRow The relevant database version-record row
	 * @return Tx_Workspaces_Domain_Model_CombinedRecord
	 */
	public static function createFromArrays($table, array $liveRow, array $versionRow) {
		$liveRecord = Tx_Workspaces_Domain_Model_DatabaseRecord::createFromArray($table, $liveRow);
		$versionRecord = Tx_Workspaces_Domain_Model_DatabaseRecord::createFromArray($table, $versionRow);

		return t3lib_div::makeInstance(
			'Tx_Workspaces_Domain_Model_CombinedRecord',
			$table, $liveRecord, $versionRecord
		);
	}

	/**
	 * Creates this object.
	 *
	 * @param string $table
	 * @param Tx_Workspaces_Domain_Model_DatabaseRecord $liveRecord
	 * @param Tx_Workspaces_Domain_Model_DatabaseRecord $versionRecord
	 */
	public function __construct($table, Tx_Workspaces_Domain_Model_DatabaseRecord $liveRecord, Tx_Workspaces_Domain_Model_DatabaseRecord $versionRecord) {
		$this->setTable($table);
		$this->setLiveRecord($liveRecord);
		$this->setVersionRecord($versionRecord);
	}

	/**
	 * Gets the name of the database table.
	 *
	 * @return string
	 */
	public function getTable() {
		return $this->table;
	}

	/**
	 * Sets the name of the database table.
	 *
	 * @param string $table
	 * @return void
	 */
	public function setTable($table) {
		$this->table = $table;
	}

	/**
	 * Gets the live-record object.
	 *
	 * @return Tx_Workspaces_Domain_Model_DatabaseRecord
	 */
	public function getLiveRecord() {
		return $this->liveRecord;
	}

	/**
	 * Sets the live-record object.
	 *
	 * @param Tx_Workspaces_Domain_Model_DatabaseRecord $liveRecord
	 * @return void
	 */
	public function setLiveRecord(Tx_Workspaces_Domain_Model_DatabaseRecord $liveRecord) {
		$this->liveRecord = $liveRecord;
	}

	/**
	 * Gets the version-record object.
	 *
	 * @return Tx_Workspaces_Domain_Model_DatabaseRecord
	 */
	public function getVersionRecord() {
		return $this->versionRecord;
	}

	/**
	 * Sets the version-record object.
	 *
	 * @param Tx_Workspaces_Domain_Model_DatabaseRecord $versionRecord
	 * @return void
	 */
	public function setVersionRecord(Tx_Workspaces_Domain_Model_DatabaseRecord $versionRecord) {
		$this->versionRecord = $versionRecord;
	}

	/**
	 * Gets the id of the live-record.
	 *
	 * @return integer
	 */
	public function getLiveId() {
		return $this->getLiveRecord()->getUid();
	}

	/**
	 * Gets the id of version-record.
	 *
	 * @return integer
	 */
	public function getVersiondId() {
		return $this->getVersionRecord()->getUid();
	}
}
?>