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

final readonly class GroupFieldType extends AbstractFieldType implements RelationalFieldTypeInterface
{
    public function __construct(
        protected string $name,
        protected array $configuration,
        private array $relations,
    ) {}

    public function getType(): string
    {
        return 'group';
    }

    /**
     * Names of the schemata this field is allowed to point to, based on TCA "allowed".
     *
     * The wildcard "*" is part of the returned list and denotes that all schemata are
     * allowed, see allowsAllSchemata().
     *
     * @return list<string>
     */
    public function getAllowedSchemaNames(): array
    {
        return GeneralUtility::trimExplode(',', (string)($this->configuration['allowed'] ?? ''), true);
    }

    public function allowsAllSchemata(): bool
    {
        return in_array('*', $this->getAllowedSchemaNames(), true);
    }

    public function isNullable(): false
    {
        return false;
    }

    public function getRelations(): array
    {
        return $this->relations;
    }

    public function getRelationshipType(): RelationshipType
    {
        return RelationshipType::fromTcaConfiguration($this->configuration);
    }

    public function isSearchable(): false
    {
        return false;
    }

    public function getSoftReferenceKeys(): false
    {
        return false;
    }
}
