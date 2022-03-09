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

namespace TYPO3\CMS\Core\DataHandling\History;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\Model\CorrelationId;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Used to save any history to a record
 *
 * @internal should only be used by the TYPO3 Core
 */
class RecordHistoryStore
{
    public const ACTION_ADD = 1;
    public const ACTION_MODIFY = 2;
    public const ACTION_MOVE = 3;
    public const ACTION_DELETE = 4;
    public const ACTION_UNDELETE = 5;
    public const ACTION_STAGECHANGE = 6;

    public const USER_BACKEND = 'BE';
    public const USER_FRONTEND = 'FE';
    public const USER_ANONYMOUS = '';

    /**
     * @var int|null
     */
    protected $userId;

    /**
     * @var string
     */
    protected $userType;

    /**
     * @var int|null
     */
    protected $originalUserId;

    /**
     * @var int|null
     */
    protected $tstamp;

    /**
     * @var int
     */
    protected $workspaceId;

    /**
     * @param string $userType
     * @param int|null $userId
     * @param int|null $originalUserId
     * @param int|null $tstamp
     * @param int $workspaceId
     */
    public function __construct(string $userType = self::USER_BACKEND, int $userId = null, int $originalUserId = null, int $tstamp = null, int $workspaceId = 0)
    {
        $this->userType = $userType;
        $this->userId = $userId;
        $this->originalUserId = $originalUserId;
        $this->tstamp = $tstamp ?: $GLOBALS['EXEC_TIME'];
        $this->workspaceId = $workspaceId;
    }

    /**
     * @param string $table
     * @param int $uid
     * @param array $payload
     * @param CorrelationId|null $correlationId
     * @return string
     */
    public function addRecord(string $table, int $uid, array $payload, CorrelationId $correlationId = null): string
    {
        $data = [
            'actiontype' => self::ACTION_ADD,
            'usertype' => $this->userType,
            'userid' => $this->userId,
            'originaluserid' => $this->originalUserId,
            'tablename' => $table,
            'recuid' => $uid,
            'tstamp' => $this->tstamp,
            'history_data' => json_encode($payload),
            'workspace' => $this->workspaceId,
            'correlation_id' => (string)$this->createCorrelationId($table, $uid, $correlationId),
        ];
        $this->getDatabaseConnection()->insert('sys_history', $data);
        return $this->getDatabaseConnection()->lastInsertId('sys_history');
    }

    /**
     * @param string $table
     * @param int $uid
     * @param array $payload
     * @param CorrelationId|null $correlationId
     * @return string
     */
    public function modifyRecord(string $table, int $uid, array $payload, CorrelationId $correlationId = null): string
    {
        $data = [
            'actiontype' => self::ACTION_MODIFY,
            'usertype' => $this->userType,
            'userid' => $this->userId,
            'originaluserid' => $this->originalUserId,
            'tablename' => $table,
            'recuid' => $uid,
            'tstamp' => $this->tstamp,
            'history_data' => json_encode($payload),
            'workspace' => $this->workspaceId,
            'correlation_id' => (string)$this->createCorrelationId($table, $uid, $correlationId),
        ];
        $this->getDatabaseConnection()->insert('sys_history', $data);
        return $this->getDatabaseConnection()->lastInsertId('sys_history');
    }

    /**
     * @param string $table
     * @param int $uid
     * @param CorrelationId|null $correlationId
     * @return string
     */
    public function deleteRecord(string $table, int $uid, CorrelationId $correlationId = null): string
    {
        $data = [
            'actiontype' => self::ACTION_DELETE,
            'usertype' => $this->userType,
            'userid' => $this->userId,
            'originaluserid' => $this->originalUserId,
            'tablename' => $table,
            'recuid' => $uid,
            'tstamp' => $this->tstamp,
            'workspace' => $this->workspaceId,
            'correlation_id' => (string)$this->createCorrelationId($table, $uid, $correlationId),
        ];
        $this->getDatabaseConnection()->insert('sys_history', $data);
        return $this->getDatabaseConnection()->lastInsertId('sys_history');
    }

    /**
     * @param string $table
     * @param int $uid
     * @param CorrelationId|null $correlationId
     * @return string
     */
    public function undeleteRecord(string $table, int $uid, CorrelationId $correlationId = null): string
    {
        $data = [
            'actiontype' => self::ACTION_UNDELETE,
            'usertype' => $this->userType,
            'userid' => $this->userId,
            'originaluserid' => $this->originalUserId,
            'tablename' => $table,
            'recuid' => $uid,
            'tstamp' => $this->tstamp,
            'workspace' => $this->workspaceId,
            'correlation_id' => (string)$this->createCorrelationId($table, $uid, $correlationId),
        ];
        $this->getDatabaseConnection()->insert('sys_history', $data);
        return $this->getDatabaseConnection()->lastInsertId('sys_history');
    }

    /**
     * @param string $table
     * @param int $uid
     * @param array $payload
     * @param CorrelationId|null $correlationId
     * @return string
     */
    public function moveRecord(string $table, int $uid, array $payload, CorrelationId $correlationId = null): string
    {
        $data = [
            'actiontype' => self::ACTION_MOVE,
            'usertype' => $this->userType,
            'userid' => $this->userId,
            'originaluserid' => $this->originalUserId,
            'tablename' => $table,
            'recuid' => $uid,
            'tstamp' => $this->tstamp,
            'history_data' => json_encode($payload),
            'workspace' => $this->workspaceId,
            'correlation_id' => (string)$this->createCorrelationId($table, $uid, $correlationId),
        ];
        $this->getDatabaseConnection()->insert('sys_history', $data);
        return $this->getDatabaseConnection()->lastInsertId('sys_history');
    }

    public function changeStageForRecord(string $table, int $uid, array $payload, CorrelationId $correlationId = null): string
    {
        $data = [
            'actiontype' => self::ACTION_STAGECHANGE,
            'usertype' => $this->userType,
            'userid' => $this->userId,
            'originaluserid' => $this->originalUserId,
            'tablename' => $table,
            'recuid' => $uid,
            'tstamp' => $this->tstamp,
            'history_data' => json_encode($payload),
            'workspace' => $this->workspaceId,
            'correlation_id' => (string)$this->createCorrelationId($table, $uid, $correlationId),
        ];
        $this->getDatabaseConnection()->insert('sys_history', $data);
        return $this->getDatabaseConnection()->lastInsertId('sys_history');
    }

    protected function createCorrelationId(string $tableName, int $uid, ?CorrelationId $correlationId): CorrelationId
    {
        if ($correlationId !== null && $correlationId->getSubject() !== null) {
            return $correlationId;
        }
        $subject = md5($tableName . ':' . $uid);
        return $correlationId !== null ? $correlationId->withSubject($subject) : CorrelationId::forSubject($subject);
    }

    /**
     * @return Connection
     */
    protected function getDatabaseConnection(): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_history');
    }
}
