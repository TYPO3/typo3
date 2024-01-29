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

use Doctrine\DBAL\Driver\Middleware\AbstractResultMiddleware;

/**
 * TYPO3's custom Result object for Database statements based on Doctrine DBAL.
 *
 * This is a lowlevel wrapper around PDO for TYPO3 based drivers to ensure mapResourceToString()
 * is called when retrieving data. This isn't the actual Result object (Doctrine\DBAL\Result) which
 * is used in user-land code.
 *
 * @internal this implementation is not part of TYPO3's Public API.
 */
class DriverResult extends AbstractResultMiddleware
{
    /**
     * {@inheritDoc}
     */
    public function fetchNumeric(): array|false
    {
        return $this->mapResourceToString(parent::fetchNumeric());
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAssociative(): array|false
    {
        return $this->mapResourceToString(parent::fetchAssociative());
    }

    /**
     * {@inheritDoc}
     */
    public function fetchOne(): mixed
    {
        return $this->mapResourceToString(parent::fetchOne());
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllNumeric(): array
    {
        $data = $this->mapResourceToString(parent::fetchAllNumeric());
        assert(is_array($data));
        return array_map($this->mapResourceToString(...), $data);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAllAssociative(): array
    {
        $data = $this->mapResourceToString(parent::fetchAllAssociative());
        assert(is_array($data));
        return array_map($this->mapResourceToString(...), $data);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchFirstColumn(): array
    {
        $data = $this->mapResourceToString(parent::fetchFirstColumn());
        assert(is_array($data));
        return array_map($this->mapResourceToString(...), $data);
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
            foreach ($record as $k => $value) {
                if (is_resource($value)) {
                    $record[$k] = stream_get_contents($value);
                }
            }
        }
        return $record;
    }
}
