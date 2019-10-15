<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Database\Driver;

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

use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Driver\PDOStatement as DoctrineDbalPDOStatement;
use PDO;

class PDOStatement extends DoctrineDbalPDOStatement
{
    /**
     * Map resources to string like is done for e.g. in mysqli driver
     *
     * @param mixed $record
     * @return mixed
     */
    protected function mapResourceToString($record)
    {
        if (is_array($record)) {
            return array_map(
                static function ($value) {
                    if (is_resource($value)) {
                        $value = stream_get_contents($value);
                    }

                    return $value;
                },
                $record
            );
        }

        return $record;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($fetchMode = null, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        try {
            $record = parent::fetch($fetchMode, $cursorOrientation, $cursorOffset);
            $record = $this->mapResourceToString($record);
            return $record;
        } catch (\PDOException $exception) {
            throw new PDOException($exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null)
    {
        try {
            $records = parent::fetchAll($fetchMode, $fetchArgument, $ctorArgs);

            if ($records !== false) {
                $records = array_map([$this, 'mapResourceToString'], $records);
            }

            return $records;
        } catch (\PDOException $exception) {
            throw new PDOException($exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($columnIndex = 0)
    {
        try {
            $record = parent::fetchColumn($columnIndex);
            $record = $this->mapResourceToString($record);
            return $record;
        } catch (\PDOException $exception) {
            throw new PDOException($exception);
        }
    }
}
