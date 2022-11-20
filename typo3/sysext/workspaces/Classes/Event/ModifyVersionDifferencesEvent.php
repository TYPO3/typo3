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
    ) {
    }

    public function getVersionDifferences(): array
    {
        return $this->versionDifferences;
    }

    public function setVersionDifferences(array $versionDifferences): void
    {
        $this->versionDifferences = $versionDifferences;
    }

    public function getLiveRecordData(): array
    {
        return $this->liveRecordData;
    }

    public function getParameters(): \stdClass
    {
        return $this->parameters;
    }
}
