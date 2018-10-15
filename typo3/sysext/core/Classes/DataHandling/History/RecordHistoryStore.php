<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\DataHandling\History;

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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
     * @param int $originalUserId
     * @param int $tstamp
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
     * @return string
     */
    public function addRecord(string $table, int $uid, array $payload): string
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
        ];
        $this->getDatabaseConnection()->insert('sys_history', $data);
        return $this->getDatabaseConnection()->lastInsertId('sys_history');
    }

    /**
     * @param string $table
     * @param int $uid
     * @param array $payload
     * @return string
     */
    public function modifyRecord(string $table, int $uid, array $payload): string
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
        ];
        $this->getDatabaseConnection()->insert('sys_history', $data);
        return $this->getDatabaseConnection()->lastInsertId('sys_history');
    }

    /**
     * @param string $table
     * @param int $uid
     * @return string
     */
    public function deleteRecord(string $table, int $uid): string
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
        ];
        $this->getDatabaseConnection()->insert('sys_history', $data);
        return $this->getDatabaseConnection()->lastInsertId('sys_history');
    }

    /**
     * @param string $table
     * @param int $uid
     * @return string
     */
    public function undeleteRecord(string $table, int $uid): string
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
        ];
        $this->getDatabaseConnection()->insert('sys_history', $data);
        return $this->getDatabaseConnection()->lastInsertId('sys_history');
    }

    /**
     * @param string $table
     * @param int $uid
     * @param array $payload
     * @return string
     */
    public function moveRecord(string $table, int $uid, array $payload): string
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
        ];
        $this->getDatabaseConnection()->insert('sys_history', $data);
        return $this->getDatabaseConnection()->lastInsertId('sys_history');
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
