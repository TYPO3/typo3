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

namespace TYPO3\CMS\Core\Schema\Field;

use TYPO3\CMS\Core\DataHandling\TableColumnType;

/**
 * Interface for a Schema Field.
 */
interface FieldTypeInterface
{
    public function getType(): string;
    public function isType(TableColumnType ...$columnType): bool;
    public function getName(): string;
    public function getLabel(): string;
    public function supportsAccessControl(): bool;
    public function isRequired(): bool;
    public function isNullable(): bool;
    public function isSearchable(): bool;
    public function getDisplayConditions(): array|string;
    public function getDefaultValue(): mixed;
    public function hasDefaultValue(): bool;
    public function getTranslationBehaviour(): FieldTranslationBehaviour;
    public function getConfiguration(): array;
    public function getSoftReferenceKeys(): array|false;
    public static function __set_state(array $state): FieldTypeInterface;
}
