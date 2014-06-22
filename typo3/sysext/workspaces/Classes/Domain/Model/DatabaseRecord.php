<?php
namespace TYPO3\CMS\Workspaces\Domain\Model;

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
