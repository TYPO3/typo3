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
use TYPO3\CMS\Core\DataHandling\ItemProcessingService;
use TYPO3\CMS\Core\DataHandling\ItemsProcessorContext;
use TYPO3\CMS\Core\Schema\Struct\SelectItemCollection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resolves labels for TCA field items based on schema configuration
 * and optional Page TSconfig overrides.
 *
 * Returns raw (untranslated) labels — callers are responsible for
 * running language translation (e.g. sL()) when needed.
 */
#[Autoconfigure(public: true)]
readonly class SchemaLabelResolver
{
    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
        private ItemProcessingService $itemProcessingService,
    ) {}

    /**
     * Resolve the label for a single field item value.
     *
     * @param string $table Table name
     * @param string $field Field name
     * @param string $value The item value to look up
     * @param array $row Record row, needed for itemsProcFunc/itemsProcessors context
     * @param array $columnTsConfig Optional TCEFORM.<table>.<field> TSconfig array for addItems/altLabels overrides
     * @param array $fieldConfiguration Optional explicit field configuration (used for volatile configs like FlexForms)
     * @return string The raw (untranslated) label, or empty string if not found
     */
    public function getLabelForFieldValue(
        string $table,
        string $field,
        string $value,
        array $row = [],
        array $columnTsConfig = [],
        array $fieldConfiguration = [],
    ): string {
        if ($columnTsConfig !== []) {
            $tsConfigLabel = $this->resolveFromTsConfig($value, $columnTsConfig);
            if ($tsConfigLabel !== null) {
                return $tsConfigLabel;
            }
        }

        $fieldConfiguration = $this->resolveFieldConfiguration($table, $field, $fieldConfiguration);
        if ($fieldConfiguration === []) {
            return '';
        }

        $items = $this->resolveItems($table, $field, $row, $fieldConfiguration);

        foreach ($items as $itemConfiguration) {
            if ((string)$itemConfiguration['value'] === $value) {
                return $itemConfiguration['label'];
            }
        }

        return '';
    }

    /**
     * Resolve labels for a comma-separated list of field item values.
     *
     * @param string $table Table name
     * @param string $field Field name
     * @param string $valueList Comma-separated list of item values
     * @param array $row Record row, needed for itemsProcFunc/itemsProcessors context
     * @param array $columnTsConfig Optional TCEFORM.<table>.<field> TSconfig array for addItems/altLabels overrides
     * @param array $fieldConfiguration Optional explicit field configuration (used for volatile configs like FlexForms)
     * @return array<string> Array of raw (untranslated) labels for each matched value
     */
    public function getLabelsForFieldValues(
        string $table,
        string $field,
        string $valueList,
        array $row = [],
        array $columnTsConfig = [],
        array $fieldConfiguration = [],
    ): array {
        $fieldConfiguration = $this->resolveFieldConfiguration($table, $field, $fieldConfiguration);
        if ($valueList === '' || $fieldConfiguration === []) {
            return [];
        }

        $items = $this->resolveItems($table, $field, $row, $fieldConfiguration);
        $keys = GeneralUtility::trimExplode(',', $valueList);
        $labels = [];

        foreach ($keys as $key) {
            $label = null;
            if ($columnTsConfig !== []) {
                $label = $this->resolveFromTsConfig($key, $columnTsConfig);
            }
            if ($label === null) {
                foreach ($items as $itemConfiguration) {
                    if ($key === (string)$itemConfiguration['value']) {
                        $label = $itemConfiguration['label'];
                        break;
                    }
                }
            }
            if ($label !== null) {
                $labels[] = $label;
            }
        }

        return $labels;
    }

    private function resolveFromTsConfig(string $value, array $columnTsConfig): ?string
    {
        if ($value === '' && isset($columnTsConfig['altLabels'])) {
            return $columnTsConfig['altLabels'];
        }
        if (isset($columnTsConfig['addItems.'][$value])) {
            return $columnTsConfig['addItems.'][$value];
        }
        if (isset($columnTsConfig['altLabels.'][$value])) {
            return $columnTsConfig['altLabels.'][$value];
        }
        return null;
    }

    private function resolveFieldConfiguration(string $table, string $field, array $fieldConfiguration): array
    {
        if ($fieldConfiguration !== []) {
            return $fieldConfiguration;
        }
        if (!$this->tcaSchemaFactory->has($table)) {
            return [];
        }
        $schema = $this->tcaSchemaFactory->get($table);
        if (!$schema->hasField($field)) {
            return [];
        }
        return $schema->getField($field)->getConfiguration();
    }

    private function resolveItems(string $table, string $field, array $row, array $fieldConfiguration): array
    {
        if (isset($fieldConfiguration['items']) && !is_array($fieldConfiguration['items'])) {
            return [];
        }

        $items = $fieldConfiguration['items'] ?? [];

        if (
            ($fieldConfiguration['itemsProcFunc'] ?? '') !== ''
            || ($fieldConfiguration['itemsProcessors'] ?? []) !== []
        ) {
            $itemsCollection = SelectItemCollection::createFromArray($items, $fieldConfiguration['type']);
            $context = new ItemsProcessorContext(
                table: $table,
                field: $field,
                row: $row,
                fieldConfiguration: $fieldConfiguration,
                processorParameters: [],
                realPid: (int)($row['pid'] ?? 0),
                site: $this->itemProcessingService->resolveSite((int)($row['pid'] ?? 0))
            );
            $items = $this->itemProcessingService->processItems($itemsCollection, $context)->toArray();
        }

        return $items;
    }
}
