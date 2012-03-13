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
 * Constraints for log entries
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @package TYPO3
 * @subpackage belog
 */
class Tx_Belog_Domain_Model_Constraint extends Tx_Extbase_DomainObject_AbstractEntity {

	/**
	 * @var string Selected user
	 */
	protected $user = 0;

	/**
	 * @var integer Number of log rows to show
	 */
	protected $number = 20;

	/**
	 * @var string Selected workspace
	 */
	protected $workspace = -99;

	/**
	 * @var integer Selected time frame
	 */
	protected $timeFrame = 0;

	/**
	 * @var integer Selected action
	 */
	protected $action = 0;

	/**
	 * @var boolean Whether or not rows should be grouped by page
	 */
	protected $groupByPage = FALSE;

	/**
	 * @var DateTime Manual date start
	 */
	protected $manualDateStart = NULL;

	/**
	 * @var DateTime Manual date stop
	 */
	protected $manualDateStop = NULL;

	/**
	 * @var integer Calculated start timestamp
	 */
	protected $startTimestamp = 0;

	/**
	 * @var integer Calculated end timestamp
	 */
	protected $endTimestamp = 0;

	/**
	 * @var boolean Wether or not the plugin is called in page context (submodule of Web->Info)
	 */
	protected $pageContext = FALSE;

	/**
	 * @var integer Selected page id in page context
	 */
	protected $pageId = 0;

	/**
	 * @var integer Page level depth
	 */
	protected $depth = 0;

	/**
	 * Default constructor
	 */
	public function __construct() {
	}

	/**
	 * Set user
	 *
	 * @param string $user
	 * @return void
	 */
	public function setUser($user) {
		$this->user = $user;
	}

	/**
	 * Get user
	 *
	 * @return string
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * Set number of log rows to show
	 *
	 * @param integer $number
	 * @return void
	 */
	public function setNumber($number) {
		$this->number = $number;
	}

	/**
	 * Get number of log entries to show
	 *
	 * @return int
	 */
	public function getNumber() {
		return (int)$this->number;
	}

	/**
	 * Set workspace
	 *
	 * @param string $workspace
	 * @return void
	 */
	public function setWorkspace($workspace) {
		$this->workspace = $workspace;
	}

	/**
	 * Get workspace
	 *
	 * @return string
	 */
	public function getWorkspace() {
		return $this->workspace;
	}

	/**
	 * Set time frame
	 *
	 * @param integer $timeFrame
	 * @return void
	 */
	public function setTimeFrame($timeFrame) {
		$this->timeFrame = $timeFrame;
	}

	/**
	 * Get time frame
	 *
	 * @return int
	 */
	public function getTimeFrame() {
		return (int)$this->timeFrame;
	}

	/**
	 * Set action
	 *
	 * @param integer $action
	 * @return void
	 */
	public function setAction($action) {
		$this->action = $action;
	}

	/**
	 * Get action
	 *
	 * @return int
	 */
	public function getAction() {
		return (int)$this->action;
	}

	/**
	 * Set group by page
	 *
	 * @param boolean $groupByPage
	 * @return void
	 */
	public function setGroupByPage($groupByPage) {
		$this->groupByPage = $groupByPage;
	}

	/**
	 * Get group by page
	 *
	 * @return bool
	 */
	public function getGroupByPage() {
		return (boolean)$this->groupByPage;
	}

	/**
	 * Set manual date start
	 *
	 * @param DateTime $manualDateStart
	 * @return void
	 */
	public function setManualDateStart(DateTime $manualDateStart) {
		$this->manualDateStart = $manualDateStart;
	}

	/**
	 * Get manual date start
	 *
	 * @return DateTime
	 */
	public function getManualDateStart() {
		return $this->manualDateStart;
	}

	/**
	 * Set manual date stop
	 *
	 * @param DateTime $manualDateStop
	 * @return void
	 */
	public function setManualDateStop(DateTime $manualDateStop) {
		$this->manualDateStop = $manualDateStop;
	}

	/**
	 * Get manual date stop
	 *
	 * @return DateTime
	 */
	public function getManualDateStop() {
		return $this->manualDateStop;
	}

	/**
	 * Set calculated start timestamp from query constraints
	 *
	 * @param integer $timestamp
	 * @return void
	 */
	public function setStartTimestamp($timestamp) {
		$this->startTimestamp = $timestamp;
	}

	/**
	 * Get calculated start timestamp from query constraints
	 *
	 * @return int
	 */
	public function getStartTimestamp() {
		return (int)$this->startTimestamp;
	}

	/**
	 * Set calculated end timestamp from query constraints
	 *
	 * @param $timestamp
	 * @return void
	 */
	public function setEndTimestamp($timestamp) {
		$this->endTimestamp = $timestamp;
	}

	/**
	 * Get calculated end timestamp from query constraints
	 *
	 * @return int
	 */
	public function getEndTimestamp() {
		return (int)$this->endTimestamp;
	}

	/**
	 * Set page context
	 *
	 * @param $pageContext
	 * @return void
	 */
	public function setPageContext($pageContext) {
		$this->pageContext = $pageContext;
	}

	/**
	 * Get page context
	 *
	 * @return bool
	 */
	public function getPageContext() {
		return (boolean)$this->pageContext;
	}

	/**
	 * Set page id
	 *
	 * @param $id
	 * @return void
	 */
	public function setPageId($id) {
		$this->pageId = $id;
	}

	/**
	 * Get page id
	 *
	 * @return int
	 */
	public function getPageId() {
		return (int)$this->pageId;
	}

	/**
	 * Set page level depth
	 *
	 * @param integer $depth
	 * @return void
	 */
	public function setDepth($depth) {
		$this->depth = $depth;
	}

	/**
	 * Get page level depth
	 *
	 * @return int
	 */
	public function getDepth() {
		return (int)$this->depth;
	}
}
?>