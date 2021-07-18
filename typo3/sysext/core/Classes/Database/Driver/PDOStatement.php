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

namespace TYPO3\CMS\Core\Database\Driver;

use Doctrine\DBAL\Driver\PDO\Exception as PDOException;
use Doctrine\DBAL\Driver\PDO\Statement as DoctrineDbalPDOStatement;
use PDO;

class PDOStatement extends DoctrineDbalPDOStatement
{
    /**
     * The method fetchAll() is moved into a separate trait to switch method signatures
     * depending on the PHP major version in use to support PHP8
     */
    use PDOStatementImplementation;

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
    public function fetchOne($columnIndex = 0)
    {
        try {
            $record = parent::fetchColumn($columnIndex);
            $record = $this->mapResourceToString($record);
            return $record;
        } catch (\PDOException $exception) {
            throw new PDOException($exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($columnIndex = 0)
    {
        return $this->fetchOne($columnIndex);
    }
}
