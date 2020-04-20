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

namespace TYPO3\CMS\Backend\View\ValueFormatter;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * The FlexFormValueFormatter formats a FlexForm value into a human-readable
 * format. This is used internally to display changes in FlexForm values as a
 * nicely formatted plain-text diff.
 *
 * @internal
 */
class FlexFormValueFormatter
{
    protected const VALUE_MAX_LENGTH = 50;

    public function format(
        string $tableName,
        string $fieldName,
        ?string $value,
        int $uid,
        array $fieldConfiguration
    ): string {
        if ($value === null || $value === '') {
            return '';
        }

        $record = BackendUtility::getRecord($tableName, $uid) ?? [];

        // Get FlexForm data and structure
        $flexFormDataArray = GeneralUtility::xml2array($value);
        $flexFormDataStructure = $this->getFlexFormDataStructure($fieldConfiguration, $tableName, $fieldName, $record);

        // Map data to FlexForm structure and build an easy to handle array
        $processedSheets = $this->getProcessedSheets($flexFormDataStructure, $flexFormDataArray['data']);

        // Render a human-readable plain text representation of the FlexForm data
        $renderedPlainValue = $this->renderFlexFormValuePlain($processedSheets);
        return trim($renderedPlainValue, PHP_EOL);
    }

    /**
     * @param array<string, mixed> $tcaConfiguration
     * @param string $tableName
     * @param string $fieldName
     * @param array<string, mixed> $record
     * @return array
     */
    protected function getFlexFormDataStructure(
        array $tcaConfiguration,
        string $tableName,
        string $fieldName,
        array $record
    ): array {
        $conf['config'] = $tcaConfiguration;
        $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
        $flexFormIdentifier = $flexFormTools->getDataStructureIdentifier($conf, $tableName, $fieldName, $record);
        return $flexFormTools->parseDataStructureByIdentifier($flexFormIdentifier);
    }

    /**
     * @param array<string, mixed> $processedData
     * @param int $currentHierarchy
     * @return string
     */
    protected function renderFlexFormValuePlain(array $processedData, int $currentHierarchy = 1): string
    {
        $value = '';
        foreach ($processedData as $processedKey => $processedValue) {
            $title = !empty($processedValue['section']) ? $processedKey : $processedValue['title'];

            if (!empty($processedValue['children'])) {
                $children = $this->renderFlexFormValuePlain($processedValue['children'], $currentHierarchy + 1);

                if (empty($processedValue['section']) && empty($processedValue['container'])) {
                    $value .= $this->getSectionHeadline($title) . PHP_EOL . $children . PHP_EOL;
                } elseif ($children) {
                    $value .= $children . PHP_EOL;
                }
            } elseif (isset($processedValue['value'])) {
                $wrappedValue = $this->wrapValue($processedValue['value'], $title);
                $colon = ':';
                // Add space after colon, if it fits into one line.
                if (!str_contains($wrappedValue, PHP_EOL)) {
                    $colon .= ' ';
                }
                $value .= $title . $colon . $wrappedValue . PHP_EOL . PHP_EOL;
            }
        }
        return $value;
    }

    /**
     * Get the formatted headline of a FlexForm section
     */
    protected function getSectionHeadline(string $title): string
    {
        $sectionSpacer = str_repeat('-', self::VALUE_MAX_LENGTH);
        return $title . PHP_EOL . $sectionSpacer;
    }

    protected function getProcessedSheets(array $dataStructure, array $valueStructure): array
    {
        $processedSheets = [];

        foreach ($dataStructure['sheets'] as $sheetKey => $sheetStructure) {
            if (!empty($sheetStructure['ROOT']['el'])) {
                $sheetTitle = $sheetKey;
                if (!empty($sheetStructure['ROOT']['sheetTitle'])) {
                    $sheetTitle = $this->getLanguageService()->sL($sheetStructure['ROOT']['sheetTitle']);
                }

                if (!empty($valueStructure[$sheetKey]['lDEF'])) {
                    $processedElements = $this->getProcessedElements(
                        $sheetStructure['ROOT']['el'],
                        $valueStructure[$sheetKey]['lDEF']
                    );
                    $processedSheets[$sheetKey] = [
                        'title' => $sheetTitle,
                        'children' => $processedElements,
                    ];
                }
            }
        }

        return $processedSheets;
    }

    protected function getProcessedElements(array $dataStructure, array $valueStructure): array
    {
        $processedElements = [];

        // Values used to fake TCA
        $processingTableValue = StringUtility::getUniqueId('processing');
        $processingColumnValue = StringUtility::getUniqueId('processing');

        foreach ($dataStructure as $elementKey => $elementStructure) {
            $elementTitle = $this->getElementTitle($elementKey, $elementStructure);

            if (($elementStructure['type'] ?? '') === 'array') {
                // Render section or container
                if (empty($valueStructure[$elementKey]['el'])) {
                    continue;
                }

                if (!empty($elementStructure['section'])) {
                    // Render section
                    $processedElements[$elementKey] = [
                        'section' => true,
                        'title' => $elementTitle,
                        'children' => $this->getProcessedSections(
                            $elementStructure['el'],
                            $valueStructure[$elementKey]['el']
                        ),
                    ];
                } else {
                    // Render container
                    $processedElements[$elementKey] = [
                        'container' => true,
                        'title' => $elementTitle,
                        'children' => $this->getProcessedElements(
                            $elementStructure['el'],
                            $valueStructure[$elementKey]['el']
                        ),
                    ];
                }
            } elseif (!empty($elementStructure['config'])) {
                // Render plain elements
                $relationTable = $this->getRelationTable($elementStructure['config']);
                $labelUserFunction = $relationTable !== null ? $this->getRelationLabelUserFunction($relationTable) : null;

                if ($relationTable !== null && $labelUserFunction !== null) {
                    $parameters = [
                        'table' => $relationTable,
                        'row' => BackendUtility::getRecord($relationTable, $valueStructure[$elementKey]['vDEF']),
                        'title' => $valueStructure[$elementKey]['vDEF'],
                    ];
                    GeneralUtility::callUserFunction($labelUserFunction, $parameters);
                    $processedValue = $parameters['title'];
                } else {
                    $GLOBALS['TCA'][$processingTableValue]['columns'][$processingColumnValue]['config'] = $elementStructure['config'];
                    $processedValue = BackendUtility::getProcessedValue(
                        $processingTableValue,
                        $processingColumnValue,
                        $valueStructure[$elementKey]['vDEF'] ?? '',
                    );
                }

                $processedElements[$elementKey] = [
                    'title' => $elementTitle,
                    'value' => $processedValue,
                ];
            }
        }

        if (!empty($GLOBALS['TCA'][$processingTableValue])) {
            unset($GLOBALS['TCA'][$processingTableValue]);
        }

        return $processedElements;
    }

    protected function getRelationTable(array $configuration): ?string
    {
        // If allowed tables is defined, but with only one(!) table:
        if (($configuration['allowed'] ?? '') !== '' && !str_contains($configuration['allowed'], ',')) {
            return $configuration['allowed'];
        }

        return $configuration['foreign_table'] ?? null;
    }

    /**
     * @param string $relationTable
     * @return non-empty-string|null
     */
    protected function getRelationLabelUserFunction(string $relationTable): ?string
    {
        if (!empty($GLOBALS['TCA'][$relationTable]['ctrl']['label_userFunc'])) {
            return $GLOBALS['TCA'][$relationTable]['ctrl']['label_userFunc'];
        }

        return null;
    }

    protected function getProcessedSections(array $dataStructure, array $valueStructure): array
    {
        $processedSections = [];

        foreach ($valueStructure as $sectionValueIndex => $sectionValueStructure) {
            $processedSections[$sectionValueIndex] = [
                'section' => true,
                'children' => $this->getProcessedElements(
                    $dataStructure,
                    $sectionValueStructure
                ),
            ];
        }

        return $processedSections;
    }

    protected function getElementTitle(string $key, array $structure): string
    {
        if (!empty($structure['label'])) {
            return $this->getLanguageService()->sL($structure['label']);
        }

        return $key;
    }

    protected function wrapValue(string $value, string $title): string
    {
        if ($value === '') {
            return '';
        }

        // If the length of the value is equal or less than the maxlength, no wrapping is needed.
        if ((mb_strlen($title) + mb_strlen($value)) <= self::VALUE_MAX_LENGTH) {
            return $value;
        }

        // wordwrap the value and add an indention for each line.
        $multilineIndention = "\t";
        $value = PHP_EOL . $multilineIndention . $value;
        $lines = explode(PHP_EOL, $value);
        $newValue = '';
        foreach (array_map(trim(...), $lines) as $line) {
            if ($line !== '') {
                $newValue .= PHP_EOL . $line;
            }
        }
        $value = wordwrap($newValue, self::VALUE_MAX_LENGTH, PHP_EOL);
        return str_replace(PHP_EOL, PHP_EOL . $multilineIndention, $value);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
