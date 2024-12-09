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

namespace TYPO3\CMS\Backend\Form;

use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handle inline stack.
 *
 * Code related to inline elements need to know their nesting level. This class takes
 * care of the according handling and can return field prefixes to be used in DOM.
 *
 * @internal: This class may change any time or vanish altogether
 */
readonly class InlineStackProcessor
{
    /**
     * Get a level from the stack and return the data.
     * If the $level value is negative, this function works top-down,
     * if the $level value is positive, this function works bottom-up.
     * If -1 is given, the "current" - most bottom "stable" item is returned
     *
     * @param int $level Which level to return
     * @return array|null The item of the stack at the requested level, or null if not found
     */
    public function getStructureLevelFromStructure(array $structure, int $level): ?array
    {
        $structureCount = count($structure['stable'] ?? []);
        if ($level < 0) {
            // A level like -1 requests "the level before last" / "direct parent level"
            return $structure['stable'][$structureCount + $level] ?? null;
        }
        return $structure['stable'][$level] ?? null;
    }

    /**
     * Get the "unstable" structure item from structure stack.
     */
    public function getUnstableStructureFromStructure(array $structure): array
    {
        if (!is_array($structure['unstable'] ?? false)) {
            throw new \RuntimeException('No unstable inline structure found', 1428582655);
        }
        return $structure['unstable'];
    }

    /**
     * Convert the DOM object-id of an inline container to an array.
     * The object-id could look like 'data-bs-parentPageId-tx_mmftest_company-1-employees'.
     * There are two keys:
     * - 'stable': Containing full qualified identifiers (table, uid and field)
     * - 'unstable': Containing partly filled data (e.g. only table and possibly field)
     *
     * @param string $domObjectId The DOM object-id
     */
    public function getStructureFromString(string $domObjectId): array
    {
        $structure = [];
        $unstable = [];
        $vector = ['table', 'uid', 'field'];

        // Substitute FlexForm addition and make parsing a bit easier
        $domObjectId = str_replace('---', ':', $domObjectId);
        // The starting pattern of an object identifier (e.g. "data-<firstPidValue>-<anything>)
        $pattern = '/^data-(.+?)-(.+)$/';

        if (preg_match($pattern, $domObjectId, $match)) {
            $inlineFirstPid = $match[1];
            $parts = explode('-', $match[2]);
            $partsCnt = count($parts);
            for ($i = 0; $i < $partsCnt; $i++) {
                if ($i > 0 && $i % 3 == 0) {
                    // Load the TCA configuration of the table field and store it in the stack
                    // @todo: This TCA loading here must fall - config sub-array shouldn't exist at all!
                    $unstable['config'] = $GLOBALS['TCA'][$unstable['table']]['columns'][$unstable['field']]['config'] ?? [];
                    // Fetch TSconfig:
                    // @todo: aaargs ;)
                    $TSconfig = FormEngineUtility::getTSconfigForTableRow($unstable['table'], ['uid' => $unstable['uid'], 'pid' => $inlineFirstPid], $unstable['field']);
                    // Override TCA field config by TSconfig:
                    if (!isset($TSconfig['disabled']) || !$TSconfig['disabled']) {
                        $unstable['config'] = FormEngineUtility::overrideFieldConf($unstable['config'], $TSconfig);
                    }

                    // Extract FlexForm from field part (if any)
                    if (str_contains($unstable['field'], ':')) {
                        $fieldParts = GeneralUtility::trimExplode(':', $unstable['field']);
                        $unstable['field'] = array_shift($fieldParts);
                        // FlexForm parts start with data:
                        if (!empty($fieldParts) && $fieldParts[0] === 'data') {
                            $unstable['flexform'] = $fieldParts;
                        }
                    }

                    $structure['stable'][] = $unstable;
                    $unstable = [];
                }
                $unstable[$vector[$i % 3]] = $parts[$i];
            }
            $structure['unstable'] = $unstable;
        }
        return $structure;
    }

    /**
     * Injects configuration via AJAX calls.
     * This is used by inline ajax calls that transfer configuration options back to the stack for initialization.
     *
     * @param array $config Given config extracted from ajax call
     * @todo: Review this construct - Why can't the ajax call fetch these data on its own and transfers it to client instead?
     */
    public function addAjaxConfigurationToStructure(array $structure, array $config): array
    {
        $lastStableStructureKey = count($structure['stable'] ?? []) - 1;
        if (empty($config) || $lastStableStructureKey < 0) {
            return $structure;
        }
        $structure['stable'][$lastStableStructureKey]['config'] = $config;
        return $structure;
    }

    /**
     * Prefix for inline form fields
     */
    public function getFormPrefixFromStructure(array $structure): string
    {
        $current = $this->getStructureLevelFromStructure($structure, -1);
        if ($current) {
            return 'data' . $this->getStructureItemNameFromStructure($structure, $current, 'Disposal_AttributeName');
        }
        return '';
    }

    /**
     * DOM object-id for this inline level
     *
     * @param int|string $inlineFirstPid Pid of top level inline element storage or "NEW..."
     */
    public function getDomObjectIdPrefixFromStructure(array $structure, int|string $inlineFirstPid): string
    {
        $current = $this->getStructureLevelFromStructure($structure, -1);
        // If there are still more inline levels available
        if ($current) {
            return 'data-' . $inlineFirstPid . '-' . $this->getStructurePathFromStructure($structure);
        }
        return '';
    }

    /**
     * Get the identifiers of a given depth of level, from the top of the stack to the bottom.
     * An identifier looks like "<table>-<uid>-<field>".
     *
     * @return string The path of identifiers
     */
    private function getStructurePathFromStructure(array $structure): string
    {
        $structureLevels = [];
        $structureCount = count($structure['stable'] ?? []);
        for ($i = 1; $i <= $structureCount; $i++) {
            $currentStructure = $this->getStructureLevelFromStructure($structure, -$i) ?: [];
            $currentItem = $this->getStructureItemNameFromStructure($structure, $currentStructure, 'Disposal_AttributeId');
            array_unshift($structureLevels, $currentItem);
        }
        return implode('-', $structureLevels);
    }

    /**
     * Create a name/id for usage in HTML output of a level of the structure stack to be used in form names.
     *
     * @param array $levelData Array of a level of the structure stack (containing the keys table, uid and field)
     * @param string $disposal How the structure name is used (e.g. as <div id="..."> or <input name="..." />)
     * @return string The name/id of that level, to be used for HTML output
     */
    private function getStructureItemNameFromStructure(array $structure, array $levelData, string $disposal): string
    {
        $parts = [$levelData['table'], $levelData['uid']];
        if (!empty($levelData['field'])) {
            $parts[] = $levelData['field'];
        }
        if ($disposal === 'Disposal_AttributeName') {
            // Use in name attributes
            $parent = $this->getStructureLevelFromStructure($structure, -1);
            if (!empty($levelData['field']) && !empty($levelData['flexform']) && $parent === $levelData) {
                $parts[] = implode('][', $levelData['flexform']);
            }
            $name = '[' . implode('][', $parts) . ']';
        } else {
            // Use in object id attributes
            $name = implode('-', $parts);
            if (!empty($levelData['field']) && !empty($levelData['flexform'])) {
                array_unshift($levelData['flexform'], $name);
                $name = implode('---', $levelData['flexform']);
            }
        }
        return $name;
    }
}
