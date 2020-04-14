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

namespace TYPO3\CMS\Core\Database\Event;

/**
 * Event to intercept the "CREATE TABLE" statement from all loaded extensions.
 */
final class AlterTableDefinitionStatementsEvent
{
    /**
     * RAW Array of definitions from each file found.
     * @var array
     */
    private $sqlData;

    public function __construct(array $sqlData)
    {
        $this->sqlData = $sqlData;
    }

    public function addSqlData($data): void
    {
        $this->sqlData[] = $data;
    }

    public function getSqlData(): array
    {
        return $this->sqlData;
    }

    public function setSqlData(array $sqlData): void
    {
        $this->sqlData = $sqlData;
    }
}
