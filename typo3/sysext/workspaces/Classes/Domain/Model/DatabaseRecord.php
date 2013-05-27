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
 * Database record class
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class DatabaseRecord {

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var integer
	 */
	protected $uid;

	/**
	 * @var array
	 */
	protected $row;

	/**
	 * Creates database record object just by id of database record.
	 *
	 * @param string $table Name of the database table
	 * @param integer $uid Id of the datbase record row
	 * @return \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord
	 */
	static public function create($table, $uid) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Domain\\Model\\DatabaseRecord', $table, $uid);
	}

	/**
	 * Creates datbase record object by relevant database record row.
	 *
	 * @param string $table Name of the database table
	 * @param array $row The relevant database record row
	 * @return \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord
	 */
	static public function createFromArray($table, array $row) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Domain\\Model\\DatabaseRecord', $table, $row['uid'], $row);
	}

	/**
	 * @param string $table Name of the database table
	 * @param integer $uid Id of the datbase record row
	 * @param array|NULL $row The relevant database record row
	 */
	public function __construct($table, $uid, array $row = NULL) {
		$this->setTable($table);
		$this->setUid($uid);
		if ($row !== NULL) {
			$this->setRow($row);
		}
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
	 * Gets the id of the database record row.
	 *
	 * @return integer
	 */
	public function getUid() {
		return $this->uid;
	}

	/**
	 * Sets the id of the database record row.
	 *
	 * @param integer $uid
	 * @return void
	 */
	public function setUid($uid) {
		$this->uid = $uid;
	}

	/**
	 * Gets the database record row.
	 *
	 * @return array
	 */
	public function getRow() {
		$this->loadRow();
		return $this->row;
	}

	/**
	 * Sets the database record row.
	 *
	 * @param array $row
	 * @return void
	 */
	public function setRow(array $row) {
		$this->row = $row;
	}

	/**
	 * Gets the record identifier (table:id).
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return implode(':', array($this->getTable(), $this->getUid()));
	}

	/**
	 * Loads the database record row (if not available yet).
	 *
	 * @return void
	 */
	protected function loadRow() {
		if ($this->row === NULL) {
			$this->row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($this->getTable(), $this->getUid());
		}
	}

}


?>