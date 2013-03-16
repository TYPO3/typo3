<?php
namespace TYPO3\CMS\Workspaces\Domain\Model;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Oliver Hader <oliver.hader@typo3.org>
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
 * Combined record class
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class CombinedRecord {

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord
	 */
	protected $versionRecord;

	/**
	 * @var \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord
	 */
	protected $liveRecord;

	/**
	 * Creates combined record object just by live-id and version-id of database record rows.
	 *
	 * @param string $table Name of the database table
	 * @param integer $liveId Id of the database live-record row
	 * @param integer $versionId Id of the datbase version-record row
	 * @return \TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord
	 */
	static public function create($table, $liveId, $versionId) {
		$liveRecord = \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord::create($table, $liveId);
		$versionRecord = \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord::create($table, $versionId);
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Domain\\Model\\CombinedRecord', $table, $liveRecord, $versionRecord);
	}

	/**
	 * Creates combined record object by relevant database live-record and version-record rows.
	 *
	 * @param string $table Name of the database table
	 * @param array $liveRow The relevant datbase live-record row
	 * @param array $versionRow The relevant database version-record row
	 * @return \TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord
	 */
	static public function createFromArrays($table, array $liveRow, array $versionRow) {
		$liveRecord = \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord::createFromArray($table, $liveRow);
		$versionRecord = \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord::createFromArray($table, $versionRow);
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Domain\\Model\\CombinedRecord', $table, $liveRecord, $versionRecord);
	}

	/**
	 * Creates this object.
	 *
	 * @param string $table
	 * @param \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord $liveRecord
	 * @param \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord $versionRecord
	 */
	public function __construct($table, \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord $liveRecord, \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord $versionRecord) {
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
	 * @return \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord
	 */
	public function getLiveRecord() {
		return $this->liveRecord;
	}

	/**
	 * Sets the live-record object.
	 *
	 * @param \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord $liveRecord
	 * @return void
	 */
	public function setLiveRecord(\TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord $liveRecord) {
		$this->liveRecord = $liveRecord;
	}

	/**
	 * Gets the version-record object.
	 *
	 * @return \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord
	 */
	public function getVersionRecord() {
		return $this->versionRecord;
	}

	/**
	 * Sets the version-record object.
	 *
	 * @param \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord $versionRecord
	 * @return void
	 */
	public function setVersionRecord(\TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord $versionRecord) {
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