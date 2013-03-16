<?php
namespace TYPO3\CMS\Belog\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 */
class Constraint extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * Selected user/group; possible values are "gr-<uid>" for a group, "us-<uid>" for a user or -1 for "all users"
	 *
	 * @var string
	 */
	protected $userOrGroup = '0';

	/**
	 * Number of log rows to show
	 *
	 * @var integer
	 */
	protected $number = 20;

	/**
	 * UID of selected workspace
	 *
	 * @var integer
	 */
	protected $workspaceUid = \TYPO3\CMS\Belog\Domain\Model\Workspace::UID_ANY_WORKSPACE;

	/**
	 * Selected time frame
	 *
	 * @var integer
	 */
	protected $timeFrame = 0;

	/**
	 * Selected action
	 *
	 * @var integer
	 */
	protected $action = 0;

	/**
	 * Whether rows should be grouped by page
	 *
	 * @var boolean
	 */
	protected $groupByPage = FALSE;

	/**
	 * Manual date start
	 *
	 * @var \DateTime
	 */
	protected $manualDateStart = NULL;

	/**
	 * Manual date stop
	 *
	 * @var \DateTime
	 */
	protected $manualDateStop = NULL;

	/**
	 * Calculated start timestamp
	 *
	 * @var integer
	 */
	protected $startTimestamp = 0;

	/**
	 * Calculated end timestamp
	 *
	 * @var integer
	 */
	protected $endTimestamp = 0;

	/**
	 * Whether the plugin is called in page context (submodule of Web > Info)
	 *
	 * @var boolean
	 */
	protected $isInPageContext = FALSE;

	/**
	 * Selected page ID in page context
	 *
	 * @var integer
	 */
	protected $pageId = 0;

	/**
	 * Page level depth
	 *
	 * @var integer
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
	public function setUserOrGroup($user) {
		$this->userOrGroup = $user;
	}

	/**
	 * Get user
	 *
	 * @return string
	 */
	public function getUserOrGroup() {
		return $this->userOrGroup;
	}

	/**
	 * Set number of log rows to show
	 *
	 * @param integer $number
	 * @return void
	 */
	public function setNumber($number) {
		$this->number = intval($number);
	}

	/**
	 * Get number of log entries to show
	 *
	 * @return integer
	 */
	public function getNumber() {
		return $this->number;
	}

	/**
	 * Set workspace
	 *
	 * @param string $workspace
	 * @return void
	 */
	public function setWorkspaceUid($workspace) {
		$this->workspaceUid = $workspace;
	}

	/**
	 * Get workspace
	 *
	 * @return string
	 */
	public function getWorkspaceUid() {
		return $this->workspaceUid;
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
	 * @return integer
	 */
	public function getTimeFrame() {
		return intval($this->timeFrame);
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
	 * @return integer
	 */
	public function getAction() {
		return intval($this->action);
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
	 * @return boolean
	 */
	public function getGroupByPage() {
		return (bool) $this->groupByPage;
	}

	/**
	 * Set manual date start
	 *
	 * @param \DateTime $manualDateStart
	 * @return void
	 */
	public function setManualDateStart(\DateTime $manualDateStart) {
		$this->manualDateStart = $manualDateStart;
	}

	/**
	 * Get manual date start
	 *
	 * @return \DateTime
	 */
	public function getManualDateStart() {
		return $this->manualDateStart;
	}

	/**
	 * Set manual date stop
	 *
	 * @param \DateTime $manualDateStop
	 * @return void
	 */
	public function setManualDateStop(\DateTime $manualDateStop) {
		$this->manualDateStop = $manualDateStop;
	}

	/**
	 * Get manual date stop
	 *
	 * @return \DateTime
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
		$this->startTimestamp = intval($timestamp);
	}

	/**
	 * Get calculated start timestamp from query constraints
	 *
	 * @return integer
	 */
	public function getStartTimestamp() {
		return $this->startTimestamp;
	}

	/**
	 * Set calculated end timestamp from query constraints
	 *
	 * @param integer $timestamp
	 * @return void
	 */
	public function setEndTimestamp($timestamp) {
		$this->endTimestamp = intval($timestamp);
	}

	/**
	 * Get calculated end timestamp from query constraints
	 *
	 * @return integer
	 */
	public function getEndTimestamp() {
		return $this->endTimestamp;
	}

	/**
	 * Set page context
	 *
	 * @param boolean $pageContext
	 * @return void
	 */
	public function setIsInPageContext($pageContext) {
		$this->isInPageContext = $pageContext;
	}

	/**
	 * Get page context
	 *
	 * @return boolean
	 */
	public function getIsInPageContext() {
		return (bool) $this->isInPageContext;
	}

	/**
	 * Set page id
	 *
	 * @param integer $id
	 * @return void
	 */
	public function setPageId($id) {
		$this->pageId = intval($id);
	}

	/**
	 * Get page id
	 *
	 * @return integer
	 */
	public function getPageId() {
		return $this->pageId;
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
	 * @return integer
	 */
	public function getDepth() {
		return intval($this->depth);
	}

}

?>