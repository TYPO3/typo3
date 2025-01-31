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
 * A single field definition containing the basic information for a field
 */
abstract readonly class AbstractFieldType implements FieldTypeInterface
{
    public function __construct(
        protected string $name,
        protected array $configuration,
    ) {}

    public static function __set_state(array $state): self
    {
        /** @phpstan-ignore-next-line Usage is safe because state is exported by PHP var_export() from the static instance */
        return new static(...$state);
    }

    abstract public function getType(): string;

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return (string)($this->configuration['label'] ?? '');
    }

    public function getDescription(): string
    {
        return (string)($this->configuration['description'] ?? '');
    }

    public function supportsAccessControl(): bool
    {
        return (bool)($this->configuration['exclude'] ?? false);
    }

    public function isRequired(): bool
    {
        return (bool)($this->configuration['required'] ?? false);
    }

    public function isNullable(): bool
    {
        return (bool)($this->configuration['nullable'] ?? false);
    }

    abstract public function isSearchable(): bool;

    public function getDefaultValue(): mixed
    {
        return $this->configuration['default'] ?? null;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function getTranslationBehaviour(): FieldTranslationBehaviour
    {
        return FieldTranslationBehaviour::tryFromFieldConfiguration($this->configuration);
    }

    public function getDisplayConditions(): array|string
    {
        return $this->configuration['displayCond'] ?? [];
    }

    public function isType(TableColumnType ...$tableColumnTypes): bool
    {
        return in_array(TableColumnType::tryFrom($this->getType()), $tableColumnTypes, true);
    }
}
