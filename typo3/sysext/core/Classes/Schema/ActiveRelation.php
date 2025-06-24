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
 */
final readonly class ActiveRelation
{
    public function __construct(
        protected string $toTable,
        protected ?string $toField
    ) {}

    public function toTable(): string
    {
        return $this->toTable;
    }

    public function toField(): ?string
    {
        return $this->toField;
    }

    public static function __set_state(array $state): self
    {
        return new self(...$state);
    }

}
