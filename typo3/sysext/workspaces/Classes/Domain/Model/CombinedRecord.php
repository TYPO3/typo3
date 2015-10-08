<?php
namespace TYPO3\CMS\Workspaces\Domain\Model;

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
 * Combined record class
 */
class CombinedRecord
{
    /**
     * @var string
     */
    protected $table;

    /**
     * @var \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord
     */
    protected $versionRecord;

    /**
     * @var \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord
     */
    protected $liveRecord;

    /**
     * Creates combined record object just by live-id and version-id of database record rows.
     *
     * @param string $table Name of the database table
     * @param int $liveId Id of the database live-record row
     * @param int $versionId Id of the datbase version-record row
     * @return \TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord
     */
    public static function create($table, $liveId, $versionId)
    {
        $liveRecord = DatabaseRecord::create($table, $liveId);
        $versionRecord = DatabaseRecord::create($table, $versionId);
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord::class, $table, $liveRecord, $versionRecord);
    }

    /**
     * Creates combined record object by relevant database live-record and version-record rows.
     *
     * @param string $table Name of the database table
     * @param array $liveRow The relevant datbase live-record row
     * @param array $versionRow The relevant database version-record row
     * @return \TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord
     */
    public static function createFromArrays($table, array $liveRow, array $versionRow)
    {
        $liveRecord = DatabaseRecord::createFromArray($table, $liveRow);
        $versionRecord = DatabaseRecord::createFromArray($table, $versionRow);
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord::class, $table, $liveRecord, $versionRecord);
    }

    /**
     * Creates this object.
     *
     * @param string $table
     * @param \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord $liveRecord
     * @param \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord $versionRecord
     */
    public function __construct($table, DatabaseRecord $liveRecord, DatabaseRecord $versionRecord)
    {
        $this->setTable($table);
        $this->setLiveRecord($liveRecord);
        $this->setVersionRecord($versionRecord);
    }

    /**
     * Gets the name of the database table.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Sets the name of the database table.
     *
     * @param string $table
     * @return void
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * Gets the live-record object.
     *
     * @return \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord
     */
    public function getLiveRecord()
    {
        return $this->liveRecord;
    }

    /**
     * Sets the live-record object.
     *
     * @param \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord $liveRecord
     * @return void
     */
    public function setLiveRecord(DatabaseRecord $liveRecord)
    {
        $this->liveRecord = $liveRecord;
    }

    /**
     * Gets the version-record object.
     *
     * @return \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord
     */
    public function getVersionRecord()
    {
        return $this->versionRecord;
    }

    /**
     * Sets the version-record object.
     *
     * @param \TYPO3\CMS\Workspaces\Domain\Model\DatabaseRecord $versionRecord
     * @return void
     */
    public function setVersionRecord(DatabaseRecord $versionRecord)
    {
        $this->versionRecord = $versionRecord;
    }

    /**
     * Gets the id of the live-record.
     *
     * @return int
     */
    public function getLiveId()
    {
        return $this->getLiveRecord()->getUid();
    }

    /**
     * Gets the id of version-record.
     *
     * @return int
     */
    public function getVersiondId()
    {
        return $this->getVersionRecord()->getUid();
    }
}
