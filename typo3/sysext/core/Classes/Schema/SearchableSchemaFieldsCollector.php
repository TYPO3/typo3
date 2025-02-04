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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Schema\Field\FieldCollection;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class that accesses the TCA[table][searchFields] via TcaSchema factory
 */
#[Autoconfigure(public: true)]
readonly class SearchableSchemaFieldsCollector
{
    public function __construct(private TcaSchemaFactory $schemaFactory) {}

    public function getFields(string $schemaName, array $searchFields = []): FieldCollection
    {
        if (!$this->schemaFactory->has($schemaName)) {
            return new FieldCollection();
        }
        $schema = $this->schemaFactory->get($schemaName);
        $searchFields = $searchFields !== [] ? $searchFields : GeneralUtility::trimExplode(',', (string)($schema->getRawConfiguration()['searchFields'] ?? ''), true);
        return $searchFields === []
            // No searchFields defined, return all searchable fields
            ? $schema->getFields(static fn(FieldTypeInterface $field): bool => $field->isSearchable())
            // Return given searchFields by filtering whether they are actually searchable
            : $schema->getFields(static fn(FieldTypeInterface $field): bool => in_array($field->getName(), $searchFields, true) && $field->isSearchable());
    }

    /**
     * @return string[]
     */
    public function getFieldNames(string $schemaName, array $searchFields = []): array
    {
        return array_map(static fn(FieldTypeInterface $field) => $field->getName(), iterator_to_array($this->getFields($schemaName, $searchFields)));
    }

    /**
     * @return string[]
     */
    public function getUniqueFieldList(string $schemaName, array $existingFieldList, bool $includeSpecialFields): array
    {
        // Add special fields
        if ($includeSpecialFields) {
            $existingFieldList[] = 'uid';
            $existingFieldList[] = 'pid';
        }
        // @todo should existing fields also be validated?
        return array_unique(array_merge($existingFieldList, $this->getFieldNames($schemaName)));
    }
}
