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

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;

/**
 * An interface for database / TCA records.
 */
interface RecordInterface extends ContainerInterface
{
    public function getUid(): int;
    public function getPid(): int;

    /**
     * The full type contains the type of the record (e.g. "be_users", which is usually the TCA table)
     * and the subtype of the record (such as "textpic" in tt_content records) separated by a ".".
     */
    public function getFullType(): string;

    /**
     * The type contains the subtype of the record (such as "textpic"). Returns null if there is
     * no "subtype".
     */
    public function getRecordType(): ?string;

    /**
     * This is the TCA table for the record, all in lowercase.
     */
    public function getMainType(): string;

    public function toArray(bool $includeSpecialProperties = false): array;

    public function getRawRecord(): ?RawRecord;

    public function getComputedProperties(): ComputedProperties;
}
