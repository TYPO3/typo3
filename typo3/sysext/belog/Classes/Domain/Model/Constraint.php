<?php

declare(strict_types=1);

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
     */
    protected string $userOrGroup = '0';

    /**
     * Number of log rows to show
     */
    protected int $number = 20;

    /**
     * UID of selected workspace
     */
    protected int $workspaceUid = -99;

    /**
     * Selected channel
     */
    protected string $channel = '';

    /**
     * Selected level
     */
    protected string $level = LogLevel::DEBUG;

    /**
     * Calculated start timestamp
     */
    protected int $startTimestamp = 0;

    /**
     * Calculated end timestamp
     */
    protected int $endTimestamp = 0;

    /**
     * Manual date start
     */
    protected ?\DateTime $manualDateStart = null;

    /**
     * Manual date stop
     */
    protected ?\DateTime $manualDateStop = null;

    /**
     * Selected page ID in page context
     */
    protected int $pageId = 0;

    /**
     * Page level depth
     */
    protected int $depth = 0;

    public function setUserOrGroup(string $user): void
    {
        $this->userOrGroup = $user;
    }

    public function getUserOrGroup(): string
    {
        return $this->userOrGroup;
    }

    public function setNumber(int $number): void
    {
        $this->number = (int)$number;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setWorkspaceUid(int $workspace): void
    {
        $this->workspaceUid = $workspace;
    }

    public function getWorkspaceUid(): int
    {
        return $this->workspaceUid;
    }

    public function setChannel(string $channel): void
    {
        $this->channel = $channel;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function setLevel(string $level): void
    {
        $this->level = $level;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function setStartTimestamp(int $timestamp): void
    {
        $this->startTimestamp = $timestamp;
    }

    public function getStartTimestamp(): int
    {
        return $this->startTimestamp;
    }

    public function setEndTimestamp(int $timestamp): void
    {
        $this->endTimestamp = $timestamp;
    }

    public function getEndTimestamp(): int
    {
        return $this->endTimestamp;
    }

    public function setPageId(int $id): void
    {
        $this->pageId = $id;
    }

    public function getPageId(): int
    {
        return $this->pageId;
    }

    public function setDepth(int $depth): void
    {
        $this->depth = $depth;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function setManualDateStart(?\DateTime $manualDateStart = null): void
    {
        $this->manualDateStart = $manualDateStart;
    }

    public function getManualDateStart(): ?\DateTime
    {
        return $this->manualDateStart;
    }

    public function setManualDateStop(?\DateTime $manualDateStop = null): void
    {
        $this->manualDateStop = $manualDateStop;
    }

    public function getManualDateStop(): ?\DateTime
    {
        return $this->manualDateStop;
    }
}
