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

use TYPO3\CMS\Core\Schema\RelationshipType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This is a field to a "file" (which is very similar to "inline") but with a hard-coded
 * selection to sys_file_reference.
 *
 * @internal This is an experimental implementation and might change until TYPO3 v13 LTS
 */
final readonly class FileFieldType extends AbstractFieldType implements RelationalFieldTypeInterface
{
    public function __construct(
        protected string $name,
        protected array $configuration,
        protected array $relations,
    ) {}

    public function getType(): string
    {
        return 'file';
    }

    public function getAllowedFileExtensions(): array
    {
        return is_array($this->configuration['allowed'] ?? null)
            ? $this->configuration['allowed']
            : GeneralUtility::trimExplode(',', $this->configuration['allowed'] ?? '', true);
    }

    public function getDisallowedFileExtensions(): array
    {
        return is_array($this->configuration['disallowed'] ?? null)
            ? $this->configuration['disallowed']
            : GeneralUtility::trimExplode(',', $this->configuration['disallowed'] ?? '', true);
    }

    public function getRelations(): array
    {
        return $this->relations;
    }

    public function isSearchable(): false
    {
        return false;
    }

    public function getRelationshipType(): RelationshipType
    {
        return RelationshipType::fromTcaConfiguration($this->configuration);
    }

    public function isNullable(): false
    {
        return false;
    }

    public function hasDefaultValue(): false
    {
        return false;
    }

    public function getSoftReferenceKeys(): false
    {
        return false;
    }
}
