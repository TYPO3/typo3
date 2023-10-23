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

namespace TYPO3\CMS\Workspaces\Event;

/**
 * Listeners to this event will be able to modify the differences of versioned records
 */
final class ModifyVersionDifferencesEvent
{
    /**
     * @param list<array{field: string, label: string, content: string}> $versionDifferences
     * @param list<array{field: string, label: string, content: string}> $liveRecordData
     */
    public function __construct(
        private array $versionDifferences,
        private readonly array $liveRecordData,
        private readonly \stdClass $parameters,
    ) {}

    /**
     * Get the version differences.
     *
     * This array contains the differences of each field with the following keys:
     *
     * - field: The corresponding field name
     * - label: The corresponding field label
     * - content: The field values difference
     *
     * @return list<array{field: string, label: string, content: string}>
     */
    public function getVersionDifferences(): array
    {
        return $this->versionDifferences;
    }

    /**
     * Modifies the version differences data
     *
     * @param list<array{field: string, label: string, content: string}> $versionDifferences
     */
    public function setVersionDifferences(array $versionDifferences): void
    {
        $this->versionDifferences = $versionDifferences;
    }

    /**
     * Returns the records live data (used to create the version difference)
     *
     * @return list<array{field: string, label: string, content: string}>
     */
    public function getLiveRecordData(): array
    {
        return $this->liveRecordData;
    }

    /**
     * Returns meta information like current stage and current workspace
     */
    public function getParameters(): \stdClass
    {
        return $this->parameters;
    }
}
