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

namespace TYPO3\CMS\Core\Domain;

use TYPO3\CMS\Core\Domain\Exception\RecordPropertyNotFoundException;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;

/**
 * Holds all properties of a raw database row with unfiltered and unprocessed values.
 *
 * @internal not part of public API, as this needs to be streamlined and proven
 */
readonly class RawRecord implements RecordInterface
{
    protected string $mainType;
    protected ?string $recordType;

    public function __construct(
        protected int $uid,
        protected int $pid,
        protected array $properties,
        protected ComputedProperties $computedProperties,
        protected string $fullType
    ) {
        $parts = $this->normalizeTypeParts($this->fullType);
        $this->mainType = $parts[0] ?? '';
        $this->recordType = $parts[1] ?? null;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function getFullType(): string
    {
        return $this->fullType;
    }

    /**
     * @return non-empty-string|null
     */
    public function getRecordType(): ?string
    {
        return $this->recordType;
    }

    public function getMainType(): string
    {
        return $this->mainType;
    }

    public function toArray(bool $includeSpecialProperties = false): array
    {
        $properties = $this->properties;
        $properties += ['uid' => $this->uid, 'pid' => $this->pid];
        if ($includeSpecialProperties) {
            $properties += [
                '_computed' => $this->computedProperties->toArray(),
            ];
        }
        return $properties;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->properties);
    }

    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new RecordPropertyNotFoundException(
                'Record property "' . $id . '" is not available.',
                1725892140
            );
        }

        return $this->properties[$id] ?? null;
    }

    public function getComputedProperties(): ComputedProperties
    {
        return $this->computedProperties;
    }

    public function getRawRecord(): RawRecord
    {
        return $this;
    }

    /**
     * @return array{0?: string, 1?: string}
     */
    protected function normalizeTypeParts(string $type): array
    {
        return array_filter(
            array_map(trim(...), explode('.', $type, 2)),
            static fn(string $part): bool => $part !== ''
        );
    }
}
