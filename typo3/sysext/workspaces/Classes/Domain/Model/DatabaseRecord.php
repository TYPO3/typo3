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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Database record class
 *
 * @internal
 */
class DatabaseRecord
{
    protected string $table;
    protected int $uid;
    protected ?array $row;

    /**
     * Creates database record object just by id of database record.
     *
     * @param string $table Name of the database table
     * @param int $uid Id of the database record row
     */
    public static function create(string $table, int $uid): DatabaseRecord
    {
        return GeneralUtility::makeInstance(DatabaseRecord::class, $table, $uid);
    }

    /**
     * Creates database record object by relevant database record row.
     *
     * @param string $table Name of the database table
     * @param array $row The relevant database record row
     */
    public static function createFromArray(string $table, array $row): DatabaseRecord
    {
        return GeneralUtility::makeInstance(DatabaseRecord::class, $table, $row['uid'], $row);
    }

    public function __construct(string $table, int $uid, ?array $row = null)
    {
        $this->setTable($table);
        $this->setUid($uid);
        if ($row !== null) {
            $this->setRow($row);
        }
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
     * Gets the id of the database record row.
     */
    public function getUid(): int
    {
        return $this->uid;
    }

    /**
     * Sets the id of the database record row.
     */
    public function setUid(int $uid): void
    {
        $this->uid = $uid;
    }

    /**
     * Gets the database record row.
     */
    public function getRow(): array
    {
        $this->loadRow();
        return $this->row;
    }

    /**
     * Sets the database record row.
     */
    public function setRow(array $row): void
    {
        $this->row = $row;
    }

    /**
     * Gets the record identifier (table:id).
     */
    public function getIdentifier(): string
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
