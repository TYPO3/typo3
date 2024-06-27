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

use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\CMS\Core\Domain\Record\LanguageInfo;
use TYPO3\CMS\Core\Domain\Record\SystemProperties;
use TYPO3\CMS\Core\Domain\Record\VersionInfo;

/**
 * Represents a record with all properties valid for this record type.
 *
 * @internal not part of public API, as this needs to be streamlined and proven
 */
class Record implements \ArrayAccess, RecordInterface
{
    public function __construct(
        protected readonly RawRecord $rawRecord,
        protected array $properties,
        protected readonly ?SystemProperties $systemProperties
    ) {}

    public function getUid(): int
    {
        return $this->rawRecord->getUid();
    }

    public function getPid(): int
    {
        return $this->rawRecord->getPid();
    }

    public function getFullType(): string
    {
        return $this->rawRecord->getFullType();
    }

    public function getRecordType(): ?string
    {
        return $this->rawRecord->getRecordType();
    }

    public function getMainType(): string
    {
        return $this->rawRecord->getMainType();
    }

    public function toArray(bool $includeSpecialProperties = false): array
    {
        foreach ($this->properties as $key => $property) {
            if ($property instanceof RecordPropertyClosure) {
                $this->properties[$key] = $property->instantiate();
            }
        }
        if ($includeSpecialProperties) {
            return ['uid' => $this->getUid(), 'pid' => $this->getPid()] + $this->properties + ($this->systemProperties?->toArray() ?? []);
        }
        return ['uid' => $this->getUid(), 'pid' => $this->getPid()] + $this->properties;
    }

    public function offsetExists(mixed $offset): bool
    {
        if (isset($this->properties[$offset])) {
            return true;
        }

        // Only fall back to the raw record in case no record type is defined.
        // This allows to properly check for only record type specific fields.
        if ($this->getRecordType() === null && isset($this->rawRecord[$offset])) {
            return true;
        }

        return false;
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (isset($this->properties[$offset])) {
            $property = $this->properties[$offset];
            if ($property instanceof RecordPropertyClosure) {
                $property = $property->instantiate();
                $this->properties[$offset] = $property;
            }
            return $property;
        }

        if (in_array($offset, ['uid', 'pid'], true)) {
            // Enable access of uid and pid via array access
            return $this->rawRecord[$offset];
        }

        if ($this->getRecordType() === null && isset($this->rawRecord[$offset])) {
            // Only fall back to the raw record in case no record type is defined.
            // This ensures that only record type specific fields are being returned.
            return $this->rawRecord[$offset];
        }

        return null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \InvalidArgumentException('Record properties cannot be modified.', 1712139281);
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \InvalidArgumentException('Record properties cannot be unset.', 1712139282);
    }

    public function getVersionInfo(): ?VersionInfo
    {
        return $this->systemProperties?->getVersion();
    }

    public function getLanguageInfo(): ?LanguageInfo
    {
        return $this->systemProperties?->getLanguage();
    }

    public function getLanguageId(): ?int
    {
        return $this->systemProperties?->getLanguage()?->getLanguageId();
    }

    public function getSystemProperties(): ?SystemProperties
    {
        return $this->systemProperties;
    }

    public function getComputedProperties(): ComputedProperties
    {
        return $this->rawRecord->getComputedProperties();
    }

    public function getRawRecord(): RawRecord
    {
        return $this->rawRecord;
    }

    public function getOverlaidUid(): int
    {
        $computedProperties = $this->getComputedProperties();
        if ($computedProperties->getLocalizedUid() !== null) {
            return $computedProperties->getLocalizedUid();
        }
        if ($computedProperties->getVersionedUid() !== null) {
            return $computedProperties->getVersionedUid();
        }
        return $this->getUid();
    }
}
