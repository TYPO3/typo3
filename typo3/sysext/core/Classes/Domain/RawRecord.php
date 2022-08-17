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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Holds all properties of a raw database row with unfiltered and unprocessed values.
 *
 * @internal not part of public API, as this needs to be streamlined and proven
 */
readonly class RawRecord implements \ArrayAccess, RecordInterface
{
    public function __construct(
        protected int $uid,
        protected int $pid,
        protected array $properties,
        protected ComputedProperties $computedProperties,
        protected string $type
    ) {}

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
        return $this->type;
    }

    public function getRecordType(): ?string
    {
        if (str_contains($this->type, '.')) {
            return GeneralUtility::revExplode('.', $this->type, 2)[1];
        }
        return null;
    }

    public function getMainType(): string
    {
        if (str_contains($this->type, '.')) {
            return explode('.', $this->type)[0];
        }
        return $this->type;
    }

    public function toArray(): array
    {
        return $this->properties + ['uid' => $this->uid, 'pid' => $this->pid];
    }

    /**
     * In addition to `isset()`, this considers `null` values as well.
     */
    public function isDefined(int|string $offset): bool
    {
        return array_key_exists($offset, $this->properties);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->properties[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->properties[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \InvalidArgumentException('Record properties cannot be set.', 1712139284);
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \InvalidArgumentException('Record properties cannot be unset.', 1712139283);
    }

    public function getComputedProperties(): ComputedProperties
    {
        return $this->computedProperties;
    }
}
