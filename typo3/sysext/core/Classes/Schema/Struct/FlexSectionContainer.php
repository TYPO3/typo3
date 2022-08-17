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

namespace TYPO3\CMS\Core\Schema\Struct;

use TYPO3\CMS\Core\Schema\Field\FieldCollection;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;

/**
 * FlexForms Sheets can contain a "repeatable set of fields", which we call "Section Container".
 * The section container only contains fields, which is a very simple format.
 *
 * @internal This is an experimental implementation and might change until TYPO3 v13 LTS
 */
final readonly class FlexSectionContainer
{
    public function __construct(
        protected string $sheetIdentifier,
        protected string $title,
        protected string $description,
        protected FieldCollection $fields
    ) {}

    public function getFields(): FieldCollection
    {
        return $this->fields;
    }

    public function hasField(string $fieldName): bool
    {
        return isset($this->fields[$fieldName]);
    }

    public function getField(string $fieldName): FieldTypeInterface
    {
        return $this->fields[$fieldName];
    }

    public static function __set_state(array $state): self
    {
        return new self(...$state);
    }
}
