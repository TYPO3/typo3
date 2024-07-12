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

namespace TYPO3\CMS\Core\Domain\Persistence;

use TYPO3\CMS\Core\Domain\RecordInterface;

/**
 * The identity map for records is a database access design pattern used to improve
 * performance by providing a context-specific, in-memory cache to prevent duplicate retrieval
 * of the same object data from the database.
 *
 * In TYPO3 Context, an instance of the RecordIdentityMap is also shared in Frontend
 * (e.g. RecordFactory) and Backend (Page Module) to know which records have been created
 * already.
 *
 * For this reason, the identity map is shared, but needs to be explicitly shared, and
 * is thus, NOT, marked as a singleton.
 *
 * Why? Since we deal with "overlays", we also need to keep track of
 * - Language (Chain)
 * - Workspace
 *
 * In TYPO3 Context, the identity map is especially important to avoid infinite recursions
 * when resolving relations in records but allows to re-use existing objects used by this
 * map.
 *
 * The purpose for the Identity Map for Records is currently only for reading record objects
 * within one records (not writing, and not shared between requests).
 *
 * @internal not part of TYPO3 Core API as it is used on a low-level basis to keep state of created record objects.
 */
class RecordIdentityMap
{
    /**
     * @var array<int|string, RecordInterface>
     */
    protected array $recordMap = [];

    public function add(RecordInterface $record): void
    {
        $this->recordMap[$record->getMainType()][$record->getUid()] = $record;
    }

    public function has(RecordInterface $record): bool
    {
        return isset($this->recordMap[$record->getMainType()][$record->getUid()]);
    }

    public function findByIdentifier(string $mainType, int $identifier): RecordInterface
    {
        if ($this->hasIdentifier($mainType, $identifier)) {
            return $this->recordMap[$mainType][$identifier];
        }
        throw new \InvalidArgumentException(
            'Record with type "' . $mainType . '" and identifier "' . $identifier . '" not found in the Identity Map.',
            1720730774
        );
    }

    public function hasIdentifier(string $mainType, int $identifier): bool
    {
        return isset($this->recordMap[$mainType][$identifier]);
    }
}
