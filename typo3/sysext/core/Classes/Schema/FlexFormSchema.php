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

use TYPO3\CMS\Core\Schema\Field\FieldCollection;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Struct\FlexSheet;

final readonly class FlexFormSchema implements SchemaInterface
{
    public function __construct(
        protected string $structIdentifier,
        /** @var FlexSheet[] */
        protected array $sheets
    ) {}

    public function getSheets(): array
    {
        return $this->sheets;
    }

    public function getFields(?callable $filterFunction = null): FieldCollection
    {
        $allFields = [];
        foreach ($this->sheets as $sheet) {
            $allFields = array_merge($allFields, iterator_to_array($sheet->getFields()));
        }
        if ($filterFunction === null) {
            return new FieldCollection($allFields);
        }

        return new FieldCollection(array_filter(iterator_to_array($allFields), $filterFunction));
    }

    public function getName(): string
    {
        return $this->structIdentifier;
    }

    public function getField(string $fieldName, ?string $sheetName = null): ?FieldTypeInterface
    {
        if ($sheetName !== null) {
            return $this->getFieldFromSheet($sheetName, $fieldName);
        }

        foreach ($this->sheets as $name => $sheet) {
            if ($field = $this->getFieldFromSheet($name, $fieldName)) {
                return $field;
            }
        }

        return null;
    }

    public static function __set_state(array $state): self
    {
        return new self(...$state);
    }

    /**
     * This method attempts to find a field within a given sheet.
     *
     * If the field is not set directly on the sheet, each section
     * of the sheet will be checked for a matching field.
     */
    protected function getFieldFromSheet(string $sheetName, string $fieldName): ?FieldTypeInterface
    {
        if (!isset($this->sheets[$sheetName])) {
            return null;
        }

        $sheet = $this->sheets[$sheetName];

        if ($sheet->hasField($sheetName . '/' . $fieldName)) {
            return $sheet->getField($sheetName . '/' . $fieldName);
        }

        return $this->getFieldFromSections($sheetName, $fieldName);
    }

    /**
     * This method searches for a field name within all sections of a sheet.
     *
     * Any slashes in the field name, section name, or container name
     * are replaced with dots to support field names such as:
     *  - settings.mysettings.67fb88e136a4a575936...
     *  - my_settings.67fb88e136a4a575936...
     */
    protected function getFieldFromSections(string $sheetName, string $fieldName): ?FieldTypeInterface
    {
        $sheet = $this->sheets[$sheetName];
        $fieldPath = $sheetName . '.' . $fieldName;

        foreach ($sheet->getSections() as $sectionName => $section) {
            $sectionPath = str_replace('/', '.', $sectionName);

            // If the field is not inside the current section, continue to the next
            if (!str_starts_with($fieldPath, $sectionPath)) {
                continue;
            }

            // Remove the section path from the field name
            $relativeField = substr($fieldPath, strlen($sectionPath) + 1);

            if (($pos = strpos($relativeField, '.')) !== false) {
                // Get the container name from the field
                $containerField = substr($relativeField, $pos + 1);

                foreach ($section as $containerName => $container) {
                    // If the field is not inside the current container, continue to the next
                    if (!str_starts_with($sectionName . '/' . $containerField, $containerName)) {
                        continue;
                    }

                    // Get the field name
                    $finalFieldName = substr($sectionName . '/' . $containerField, strlen($containerName) + 1);

                    /** @var \TYPO3\CMS\Core\Schema\Struct\FlexSectionContainer $container */
                    if ($container->hasField($finalFieldName)) {
                        return $container->getField($finalFieldName);
                    }
                }
            }
        }

        return null;
    }
}
