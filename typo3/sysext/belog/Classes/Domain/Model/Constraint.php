<?php
namespace TYPO3\CMS\Belog\Domain\Model;

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

/**
 * Constraints for log entries
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class Constraint
{
    /**
     * Selected user/group; possible values are "gr-<uid>" for a group, "us-<uid>" for a user or -1 for "all users"
     *
     * @var string
     */
    protected $userOrGroup = '0';

    /**
     * Number of log rows to show
     *
     * @var int
     */
    protected $number = 20;

    /**
     * UID of selected workspace
     *
     * @var int
     */
    protected $workspaceUid = \TYPO3\CMS\Belog\Domain\Model\Workspace::UID_ANY_WORKSPACE;

    /**
     * Selected time frame
     *
     * @var int
     */
    protected $timeFrame = 0;

    /**
     * Selected action
     *
     * @var int
     */
    protected $action = 0;

    /**
     * Whether rows should be grouped by page
     *
     * @var bool
     */
    protected $groupByPage = false;

    /**
     * Calculated start timestamp
     *
     * @var int
     */
    protected $startTimestamp = 0;

    /**
     * Calculated end timestamp
     *
     * @var int
     */
    protected $endTimestamp = 0;

    /**
     * Manual date start
     * @var \DateTime
     */
    protected $manualDateStart;

    /**
     * Manual date stop
     * @var \DateTime
     */
    protected $manualDateStop;

    /**
     * Selected page ID in page context
     *
     * @var int
     */
    protected $pageId = 0;

    /**
     * Page level depth
     *
     * @var int
     */
    protected $depth = 0;

    /**
     * Set user
     *
     * @param string $user
     */
    public function setUserOrGroup($user)
    {
        $this->userOrGroup = $user;
    }

    /**
     * Get user
     *
     * @return string
     */
    public function getUserOrGroup()
    {
        return $this->userOrGroup;
    }

    /**
     * Set number of log rows to show
     *
     * @param int $number
     */
    public function setNumber($number)
    {
        $this->number = (int)$number;
    }

    /**
     * Get number of log entries to show
     *
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set workspace
     *
     * @param string $workspace
     */
    public function setWorkspaceUid($workspace)
    {
        $this->workspaceUid = $workspace;
    }

    /**
     * Get workspace
     *
     * @return string
     */
    public function getWorkspaceUid()
    {
        return $this->workspaceUid;
    }

    /**
     * Set time frame
     *
     * @param int $timeFrame
     */
    public function setTimeFrame($timeFrame)
    {
        $this->timeFrame = $timeFrame;
    }

    /**
     * Get time frame
     *
     * @return int
     */
    public function getTimeFrame()
    {
        return (int)$this->timeFrame;
    }

    /**
     * Set action
     *
     * @param int $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * Get action
     *
     * @return int
     */
    public function getAction()
    {
        return (int)$this->action;
    }

    /**
     * Set group by page
     *
     * @param bool $groupByPage
     */
    public function setGroupByPage($groupByPage)
    {
        $this->groupByPage = $groupByPage;
    }

    /**
     * Get group by page
     *
     * @return bool
     */
    public function getGroupByPage()
    {
        return (bool)$this->groupByPage;
    }

    /**
     * Set calculated start timestamp from query constraints
     *
     * @param int $timestamp
     */
    public function setStartTimestamp($timestamp)
    {
        $this->startTimestamp = (int)$timestamp;
    }

    /**
     * Get calculated start timestamp from query constraints
     *
     * @return int
     */
    public function getStartTimestamp()
    {
        return $this->startTimestamp;
    }

    /**
     * Set calculated end timestamp from query constraints
     *
     * @param int $timestamp
     */
    public function setEndTimestamp($timestamp)
    {
        $this->endTimestamp = (int)$timestamp;
    }

    /**
     * Get calculated end timestamp from query constraints
     *
     * @return int
     */
    public function getEndTimestamp()
    {
        return $this->endTimestamp;
    }

    /**
     * Set page id
     *
     * @param int $id
     */
    public function setPageId($id)
    {
        $this->pageId = (int)$id;
    }

    /**
     * Get page id
     *
     * @return int
     */
    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     * Set page level depth
     *
     * @param int $depth
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;
    }

    /**
     * Get page level depth
     *
     * @return int
     */
    public function getDepth()
    {
        return (int)$this->depth;
    }

    /**
     * Set manual date start
     *
     * @param \DateTime $manualDateStart
     */
    public function setManualDateStart(\DateTime $manualDateStart = null)
    {
        $this->manualDateStart = $manualDateStart;
    }

    /**
     * Get manual date start
     *
     * @return \DateTime
     */
    public function getManualDateStart()
    {
        return $this->manualDateStart;
    }

    /**
     * Set manual date stop
     *
     * @param \DateTime $manualDateStop
     */
    public function setManualDateStop(\DateTime $manualDateStop = null)
    {
        $this->manualDateStop = $manualDateStop;
    }

    /**
     * Get manual date stop
     *
     * @return \DateTime
     */
    public function getManualDateStop()
    {
        return $this->manualDateStop;
    }
}
