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

use Doctrine\DBAL\Driver\PDO\Exception;
use Doctrine\DBAL\Driver\Result as ResultInterface;

/**
 * TYPO3's custom Result object for Database statements based on Doctrine DBAL.
 *
 * This is a lowlevel wrapper around PDO for TYPO3 based drivers to ensure mapResourceToString()
 * is called when retrieving data. This isn't the actual Result object (Doctrine\DBAL\Result) which
 * is used in user-land code.
 *
 * Because Doctrine's DBAL Driver Result object is marked as final, all logic is copied from the ResultInterface.
 *
 * @internal this implementation is not part of TYPO3's Public API.
 */
class DriverResult implements ResultInterface
{
    private \PDOStatement $statement;

    /**
     * @internal The result can be only instantiated by its driver connection or statement.
     */
    public function __construct(\PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchNumeric()
    {
        return $this->fetch(\PDO::FETCH_NUM);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAssociative()
    {
        return $this->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchOne()
    {
        return $this->fetch(\PDO::FETCH_COLUMN);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllNumeric(): array
    {
        return $this->fetchAll(\PDO::FETCH_NUM);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllAssociative(): array
    {
        return $this->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchFirstColumn(): array
    {
        return $this->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function rowCount(): int
    {
        try {
            return $this->statement->rowCount();
        } catch (\PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    public function columnCount(): int
    {
        try {
            return $this->statement->columnCount();
        } catch (\PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    public function free(): void
    {
        $this->statement->closeCursor();
    }

    /**
     * @return mixed|false
     *
     * @throws Exception
     */
    private function fetch(int $mode)
    {
        try {
            $result = $this->statement->fetch($mode);
            $result = $this->mapResourceToString($result);
            return $result;
        } catch (\PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    /**
     * @return list<mixed>
     *
     * @throws Exception
     */
    private function fetchAll(int $mode): array
    {
        try {
            $data = $this->statement->fetchAll($mode);
        } catch (\PDOException $exception) {
            throw Exception::new($exception);
        }

        assert(is_array($data));
        return array_map([$this, 'mapResourceToString'], $data);
    }

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
}
