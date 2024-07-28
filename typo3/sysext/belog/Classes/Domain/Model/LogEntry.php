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

/**
 * A sys log entry
 * This model is 'complete': All current database properties are in there.
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class LogEntry
{
    use LogDataTrait;

    /**
     * @var int<0, max>
     */
    protected int $uid = 0;

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
     */
    protected string|int $newId = 0;

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getBackendUserUid(): int
    {
        return $this->backendUserUid;
    }

    public function getAction(): int
    {
        return $this->action;
    }

    public function getRecordUid(): int
    {
        return $this->recordUid;
    }

    public function getTableName(): string
    {
        return $this->tableName;
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

    public function getDetails(): string
    {
        if ($this->type === 255) {
            return str_replace('###IP###', $this->ip, $this->details);
        }
        return $this->details;
    }

    public function getTstamp(): int
    {
        return $this->tstamp;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getDetailsNumber(): int
    {
        return $this->detailsNumber;
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

    public function getLogDataRaw(): string
    {
        return $this->logData;
    }

    public function getEventPid(): int
    {
        return $this->eventPid;
    }

    public function getWorkspaceUid(): int
    {
        return $this->workspaceUid;
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

    public static function createFromDatabaseRecord(array $row): self
    {
        $obj = new self();
        $obj->uid = $row['uid'] ?? $obj->uid;
        $obj->tstamp = $row['tstamp'] ?? $obj->tstamp;
        $obj->backendUserUid = $row['userid'] ?? $obj->backendUserUid;
        $obj->action = $row['action'] ?? $obj->action;
        $obj->recordUid = $row['recuid'] ?? $obj->recordUid;
        $obj->tableName = $row['tablename'] ?? $obj->tableName;
        $obj->recordPid = $row['recpid'] ?? $obj->recordPid;
        $obj->error = $row['error'] ?? $obj->error;
        $obj->type = $row['type'] ?? $obj->type;
        $obj->details = $row['details'] ?? $obj->details;
        $obj->detailsNumber = $row['details_nr'] ?? $obj->detailsNumber;
        $obj->ip = $row['IP'] ?? $obj->ip;
        $obj->logData = $row['log_data'] ?? $obj->logData;
        $obj->eventPid = $row['event_pid'] ?? $obj->eventPid;
        $obj->workspaceUid = $row['workspace'] ?? $obj->workspaceUid;
        $obj->newId = $row['NEWid'] ?? $obj->newId;
        $obj->channel = $row['channel'] ?? $obj->channel;
        $obj->level = $row['level'] ?? $obj->level;
        return $obj;
    }
}
