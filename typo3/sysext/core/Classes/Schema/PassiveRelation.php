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
 * A relation from another field / schema.
 *
 * Examples:
 * - A table "tx_myextension_author" has a passive relation FROM the table "tx_books" and its field "authors".
 * - A TCA table of type inline has a passthrough field in the child table, and that's a PASSIVE relation FROM the
 *   parent table.
 */
final readonly class PassiveRelation
{
    public function __construct(
        protected string $fromTable,
        protected ?string $fromField,
        protected ?string $flexPointer,
    ) {}

    public function fromTable(): string
    {
        return $this->fromTable;
    }

    public function fromField(): ?string
    {
        return $this->fromField;
    }

    public static function __set_state(array $state): self
    {
        return new self(...$state);
    }
}
