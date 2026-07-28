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

namespace TYPO3\CMS\Core\Schema;

/**
 * A relation to another field / schema.
 *
 * An example:
 * - A field "authors" in table "books" has an active relation to the field "written_books" in table "tx_myextension_author"
 * - A field "assets" in table "tt_content" has an active relation TO "sys_file_reference.uid".
 *
 * For relations stored in an intermediate table (TCA "MM"), toTable() is the schema the relation
 * finally points to, while manyToManyTable() is the intermediate table holding the relation rows.
 */
final readonly class ActiveRelation
{
    public function __construct(
        private string $toTable,
        private ?string $toField,
        private ?string $manyToManyTable = null,
    ) {}

    public function toTable(): string
    {
        return $this->toTable;
    }

    public function toField(): ?string
    {
        return $this->toField;
    }

    public function manyToManyTable(): ?string
    {
        return $this->manyToManyTable;
    }

    public function isManyToMany(): bool
    {
        return $this->manyToManyTable !== null;
    }

    public static function __set_state(array $state): self
    {
        return new self(...$state);
    }

}
