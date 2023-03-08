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

use TYPO3\CMS\Core\Log\LogDataTrait;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * A sys log entry
 * This model is 'complete': All current database properties are in there.
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class LogEntry extends AbstractEntity
{
    use LogDataTrait;

    /**
     * Storage page ID of the log entry
     *
     * @var int<0, max>|null
     */
    protected $pid = 0;

    /**
     * This is not a relation to BeUser model, since the user does
     * not always exist, but we want the uid in then anyway.
     * This case is ugly in extbase, the best way we
     * have found now is to resolve the username (if it exists) in a
     * view helper and just use the uid of the be user here.
     */
    protected int $backendUserUid = 0;

    /**
     * Action ID of the action that happened, for example 3 was a file action
     */
    protected int $action = 0;

    /**
     * UID of the record the event happened to
     */
    protected int $recordUid = 0;

    /**
     * Table name
     */
    protected string $tableName = '';

    /**
     * PID of the record the event happened to
     */
    protected int $recordPid = 0;

    /**
     * Error code
     */
    protected int $error = 0;

    /**
     * This is the log message itself, but possibly with %s substitutions.
     */
    protected string $details = '';

    /**
     * Timestamp when the log entry was written
     */
    protected int $tstamp = 0;

    /**
     * Type code
     */
    protected int $type = 0;

    /**
     * Channel name.
     */
    protected string $channel = '';

    /**
     * Level.
     */
    protected string $level = '';

    /**
     * Details number
     */
    protected int $detailsNumber = 0;

    /**
     * IP address of client
     */
    protected string $ip = '';

    /**
     * Serialized log data. This is a serialized array with substitutions for $this->details.
     */
    protected string $logData = '';

    /**
     * Event PID
     */
    protected int $eventPid = 0;

    /**
     * This is only the UID and not the full workspace object for the same reason as in $beUserUid.
     */
    protected int $workspaceUid = 0;

    /**
     * New ID
     *
     * @var string
     * @todo: should be string|int but extbase chokes on this while mapping - see #98132
     */
    protected $newId = 0;

    public function setBackendUserUid(int $beUserUid): void
    {
        $this->backendUserUid = $beUserUid;
    }

    public function getBackendUserUid(): int
    {
        return $this->backendUserUid;
    }

    public function setAction(int $action): void
    {
        $this->action = $action;
    }

    public function getAction(): int
    {
        return $this->action;
    }

    public function setRecordUid(int $recordUid): void
    {
        $this->recordUid = $recordUid;
    }

    public function getRecordUid(): int
    {
        return $this->recordUid;
    }

    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function setRecordPid(int $recordPid): void
    {
        $this->recordPid = $recordPid;
    }

    public function getRecordPid(): int
    {
        return $this->recordPid;
    }

    public function setError(int $error): void
    {
        $this->error = $error;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getErrorIconClass(): string
    {
        return match ($this->getError()) {
            1 => 'status-dialog-warning',
            2, 3 => 'status-dialog-error',
            default => 'empty-empty',
        };
    }

    public function setDetails(string $details): void
    {
        $this->details = $details;
    }

    public function getDetails(): string
    {
        if ($this->type === 255) {
            return str_replace('###IP###', $this->ip, $this->details);
        }
        return $this->details;
    }

    public function setTstamp(int $tstamp): void
    {
        $this->tstamp = $tstamp;
    }

    public function getTstamp(): int
    {
        return $this->tstamp;
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }

    public function getType(): int
    {
        return $this->type;
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

    public function setDetailsNumber(int $detailsNumber): void
    {
        $this->detailsNumber = $detailsNumber;
    }

    public function getDetailsNumber(): int
    {
        return $this->detailsNumber;
    }

    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setLogData(string $logData): void
    {
        $this->logData = $logData;
    }

    public function getLogData(): array
    {
        if ($this->logData === '') {
            return [];
        }
        $logData = $this->unserializeLogData($this->logData);
        return $logData ?? [];
    }

    public function setEventPid(int $eventPid): void
    {
        $this->eventPid = $eventPid;
    }

    public function getEventPid(): int
    {
        return $this->eventPid;
    }

    public function setWorkspaceUid(int $workspaceUid): void
    {
        $this->workspaceUid = $workspaceUid;
    }

    public function getWorkspaceUid(): int
    {
        return $this->workspaceUid;
    }

    /**
     * Set new id
     *
     * @param string $newId
     */
    public function setNewId($newId): void
    {
        $this->newId = $newId;
    }

    /**
     * Get new id
     *
     * @return string|int
     */
    public function getNewId()
    {
        return $this->newId;
    }
}
