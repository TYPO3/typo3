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

namespace TYPO3\CMS\Workspaces\Domain\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Combined record class
 *
 * @internal
 */
class CombinedRecord
{
    protected string $table;
    protected DatabaseRecord $versionRecord;
    protected DatabaseRecord $liveRecord;

    /**
     * Creates combined record object just by live-id and version-id of database record rows.
     *
     * @param string $table Name of the database table
     * @param int $liveId Id of the database live-record row
     * @param int $versionId Id of the database version-record row
     */
    public static function create(string $table, int $liveId, int $versionId): CombinedRecord
    {
        $liveRecord = DatabaseRecord::create($table, $liveId);
        $versionRecord = DatabaseRecord::create($table, $versionId);
        return GeneralUtility::makeInstance(CombinedRecord::class, $table, $liveRecord, $versionRecord);
    }

    /**
     * Creates combined record object by relevant database live-record and version-record rows.
     *
     * @param string $table Name of the database table
     * @param array $liveRow The relevant database live-record row
     * @param array $versionRow The relevant database version-record row
     */
    public static function createFromArrays(string $table, array $liveRow, array $versionRow): CombinedRecord
    {
        $liveRecord = DatabaseRecord::createFromArray($table, $liveRow);
        $versionRecord = DatabaseRecord::createFromArray($table, $versionRow);
        return GeneralUtility::makeInstance(CombinedRecord::class, $table, $liveRecord, $versionRecord);
    }

    public function __construct(string $table, DatabaseRecord $liveRecord, DatabaseRecord $versionRecord)
    {
        $this->setTable($table);
        $this->setLiveRecord($liveRecord);
        $this->setVersionRecord($versionRecord);
    }

    /**
     * Gets the name of the database table.
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Sets the name of the database table.
     */
    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    /**
     * Gets the live-record object.
     */
    public function getLiveRecord(): DatabaseRecord
    {
        return $this->liveRecord;
    }

    /**
     * Sets the live-record object.
     */
    public function setLiveRecord(DatabaseRecord $liveRecord): void
    {
        $this->liveRecord = $liveRecord;
    }

    /**
     * Gets the version-record object.
     */
    public function getVersionRecord(): DatabaseRecord
    {
        return $this->versionRecord;
    }

    /**
     * Sets the version-record object.
     */
    public function setVersionRecord(DatabaseRecord $versionRecord): void
    {
        $this->versionRecord = $versionRecord;
    }

    /**
     * Gets the id of the live-record.
     */
    public function getLiveId(): int
    {
        return $this->getLiveRecord()->getUid();
    }

    /**
     * Gets the id of version-record.
     */
    public function getVersiondId(): int
    {
        return $this->getVersionRecord()->getUid();
    }
}
