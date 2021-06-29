<?php

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

namespace TYPO3\CMS\Belog\Domain\Model;

use Psr\Log\LogLevel;

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
    protected $workspaceUid = Workspace::UID_ANY_WORKSPACE;

    /**
     * Selected channel
     *
     * @var string
     */
    protected string $channel = '';

    /**
     * Selected level
     *
     * @var string
     */
    protected string $level = LogLevel::DEBUG;

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
     * @var \DateTime|null
     */
    protected $manualDateStart;

    /**
     * Manual date stop
     * @var \DateTime|null
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
     * @param int $workspace
     */
    public function setWorkspaceUid($workspace)
    {
        $this->workspaceUid = $workspace;
    }

    /**
     * Get workspace
     *
     * @return int
     */
    public function getWorkspaceUid()
    {
        return $this->workspaceUid;
    }

    /**
     * Set channel
     */
    public function setChannel(string $channel): void
    {
        $this->channel = $channel;
    }

    /**
     * Get channel
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * Set level
     */
    public function setLevel(string $level): void
    {
        $this->level = $level;
    }

    /**
     * Get level
     */
    public function getLevel(): string
    {
        return $this->level;
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
     * @return \DateTime|null
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
     * @return \DateTime|null
     */
    public function getManualDateStop()
    {
        return $this->manualDateStop;
    }
}
