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

namespace TYPO3\CMS\Workspaces\Domain\Model;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Database record class
 */
class DatabaseRecord
{
    /**
     * @var string
     */
    protected $table;

    /**
     * @var int
     */
    protected $uid;

    /**
     * @var array|null
     */
    protected $row;

    /**
     * Creates database record object just by id of database record.
     *
     * @param string $table Name of the database table
     * @param int $uid Id of the database record row
     * @return DatabaseRecord
     */
    public static function create($table, $uid)
    {
        return GeneralUtility::makeInstance(DatabaseRecord::class, $table, $uid);
    }

    /**
     * Creates database record object by relevant database record row.
     *
     * @param string $table Name of the database table
     * @param array $row The relevant database record row
     * @return DatabaseRecord
     */
    public static function createFromArray($table, array $row)
    {
        return GeneralUtility::makeInstance(DatabaseRecord::class, $table, $row['uid'], $row);
    }

    /**
     * @param string $table Name of the database table
     * @param int $uid Id of the database record row
     * @param array|null $row The relevant database record row
     */
    public function __construct($table, $uid, array $row = null)
    {
        $this->setTable($table);
        $this->setUid($uid);
        if ($row !== null) {
            $this->setRow($row);
        }
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
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * Gets the id of the database record row.
     *
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Sets the id of the database record row.
     *
     * @param int $uid
     */
    public function setUid($uid)
    {
        $this->uid = (int)$uid;
    }

    /**
     * Gets the database record row.
     *
     * @return array
     */
    public function getRow()
    {
        $this->loadRow();
        return $this->row;
    }

    /**
     * Sets the database record row.
     *
     * @param array $row
     */
    public function setRow(array $row)
    {
        $this->row = $row;
    }

    /**
     * Gets the record identifier (table:id).
     *
     * @return string
     */
    public function getIdentifier()
    {
        return implode(':', [$this->getTable(), $this->getUid()]);
    }

    /**
     * Loads the database record row (if not available yet).
     */
    protected function loadRow(): void
    {
        $this->row ??= BackendUtility::getRecord($this->getTable(), $this->getUid()) ?? [];
    }
}
