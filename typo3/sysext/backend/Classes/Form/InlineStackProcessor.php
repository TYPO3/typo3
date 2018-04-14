<?php
namespace TYPO3\CMS\Backend\Form;

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

use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handle inline stack.
 *
 * Code related to inline elements need to know their nesting level. This class takes
 * care of the according handling and can return field prefixes to be used in DOM.
 *
 * @internal: This class may change any time or vanish altogether
 */
class InlineStackProcessor
{
    /**
     * The structure/hierarchy where working in, e.g. cascading inline tables
     *
     * @var array
     */
    protected $inlineStructure = [];

    /**
     * One of two possible initialize methods setting a given structure.
     *
     * @param array $structure
     */
    public function initializeByGivenStructure(array $structure = [])
    {
        $this->inlineStructure = $structure;
    }

    /**
     * Convert the DOM object-id of an inline container to an array.
     * The object-id could look like 'data-parentPageId-tx_mmftest_company-1-employees'.
     * This initializes $this->inlineStructure - used by AJAX entry points
     * There are two keys:
     * - 'stable': Containing full qualified identifiers (table, uid and field)
     * - 'unstable': Containing partly filled data (e.g. only table and possibly field)
     *
     * @param string $domObjectId The DOM object-id
     */
    public function initializeByParsingDomObjectIdString($domObjectId)
    {
        $unstable = [];
        $vector = ['table', 'uid', 'field'];

        // Substitute FlexForm addition and make parsing a bit easier
        $domObjectId = str_replace('---', ':', $domObjectId);
        // The starting pattern of an object identifier (e.g. "data-<firstPidValue>-<anything>)
        $pattern = '/^data' . '-' . '(.+?)' . '-' . '(.+)$/';

        if (preg_match($pattern, $domObjectId, $match)) {
            $inlineFirstPid = $match[1];
            $parts = explode('-', $match[2]);
            $partsCnt = count($parts);
            for ($i = 0; $i < $partsCnt; $i++) {
                if ($i > 0 && $i % 3 == 0) {
                    // Load the TCA configuration of the table field and store it in the stack
                    // @todo: This TCA loading here must fall - config sub-array shouldn't exist at all!
                    $unstable['config'] = $GLOBALS['TCA'][$unstable['table']]['columns'][$unstable['field']]['config'];
                    // Fetch TSconfig:
                    // @todo: aaargs ;)
                    $TSconfig = FormEngineUtility::getTSconfigForTableRow($unstable['table'], ['uid' => $unstable['uid'], 'pid' => $inlineFirstPid], $unstable['field']);
                    // Override TCA field config by TSconfig:
                    if (!$TSconfig['disabled']) {
                        $unstable['config'] = FormEngineUtility::overrideFieldConf($unstable['config'], $TSconfig);
                    }
                    $unstable['localizationMode'] = BackendUtility::getInlineLocalizationMode($unstable['table'], $unstable['config']);

                    // Extract FlexForm from field part (if any)
                    if (strpos($unstable['field'], ':') !== false) {
                        $fieldParts = GeneralUtility::trimExplode(':', $unstable['field']);
                        $unstable['field'] = array_shift($fieldParts);
                        // FlexForm parts start with data:
                        if (!empty($fieldParts) && $fieldParts[0] === 'data') {
                            $unstable['flexform'] = $fieldParts;
                        }
                    }

                    $this->inlineStructure['stable'][] = $unstable;
                    $unstable = [];
                }
                $unstable[$vector[$i % 3]] = $parts[$i];
            }
            if (!empty($unstable)) {
                $this->inlineStructure['unstable'] = $unstable;
            }
        }
    }

    /**
     * Injects configuration via AJAX calls.
     * This is used by inline ajax calls that transfer configuration options back to the stack for initialization
     *
     * @param array $config Given config extracted from ajax call
     * @todo: Review this construct - Why can't the ajax call fetch these data on its own and transfers it to client instead?
     */
    public function injectAjaxConfiguration(array $config)
    {
        $level = $this->calculateStructureLevel(-1);
        if (empty($config) || $level === false) {
            return;
        }
        $current = &$this->inlineStructure['stable'][$level];
        $current['config'] = $config;
        $current['localizationMode'] = BackendUtility::getInlineLocalizationMode(
            $current['table'],
            $current['config']
        );
    }

    /**
     * Get current structure stack
     *
     * @return array Current structure stack
     */
    public function getStructure()
    {
        return $this->inlineStructure;
    }

    /**
     * Add a stable structure to the stack
     *
     * @param array $structureItem
     */
    public function pushStableStructureItem(array $structureItem = [])
    {
        $this->inlineStructure['stable'][] = $structureItem;
    }

    /**
     * Prefix for inline form fields
     *
     * @return string
     */
    public function getCurrentStructureFormPrefix()
    {
        $current = $this->getStructureLevel(-1);
        $inlineFormName = '';
        // If there are still more inline levels available
        if ($current !== false) {
            $inlineFormName = 'data' . $this->getStructureItemName($current, 'Disposal_AttributeName');
        }
        return $inlineFormName;
    }

    /**
     * DOM object-id for this inline level
     *
     * @param int $inlineFirstPid Pid of top level inline element storage
     * @return string
     */
    public function getCurrentStructureDomObjectIdPrefix($inlineFirstPid)
    {
        $current = $this->getStructureLevel(-1);
        $inlineDomObjectId = '';
        // If there are still more inline levels available
        if ($current !== false) {
            $inlineDomObjectId = 'data' . '-' . $inlineFirstPid . '-' . $this->getStructurePath();
        }
        return $inlineDomObjectId;
    }

    /**
     * Get a level from the stack and return the data.
     * If the $level value is negative, this function works top-down,
     * if the $level value is positive, this function works bottom-up.
     * Hint: If -1 is given, the "current" - most bottom "stable" item is returned
     *
     * @param int $level Which level to return
     * @return array The item of the stack at the requested level
     */
    public function getStructureLevel($level)
    {
        $level = $this->calculateStructureLevel($level);

        if ($level !== false) {
            return $this->inlineStructure['stable'][$level];
        }
        return false;
    }

    /**
     * Get the "unstable" structure item from structure stack.
     * This is typically initialized by initializeByParsingDomObjectIdString()
     *
     * @return array Unstable structure item
     * @throws \RuntimeException
     */
    public function getUnstableStructure()
    {
        if (!isset($this->inlineStructure['unstable'])) {
            throw new \RuntimeException('No unstable inline structure found', 1428582655);
        }
        return $this->inlineStructure['unstable'];
    }

    /**
     * Calculates structure level.
     *
     * @param int $level Which level to return
     * @return bool|int
     */
    protected function calculateStructureLevel($level)
    {
        $result = false;
        $structureCount = $this->getStructureDepth();
        if ($level < 0) {
            $level = $structureCount + $level;
        }
        if ($level >= 0 && $level < $structureCount) {
            $result = $level;
        }
        return $result;
    }

    /**
     * Get the identifiers of a given depth of level, from the top of the stack to the bottom.
     * An identifier looks like "<table>-<uid>-<field>".
     *
     * @param int $structureDepth How much levels to output, beginning from the top of the stack
     * @return string The path of identifiers
     */
    protected function getStructurePath($structureDepth = -1)
    {
        $structureLevels = [];
        $structureCount = $this->getStructureDepth();
        if ($structureDepth < 0 || $structureDepth > $structureCount) {
            $structureDepth = $structureCount;
        }
        for ($i = 1; $i <= $structureDepth; $i++) {
            array_unshift($structureLevels, $this->getStructureItemName($this->getStructureLevel(-$i), 'Disposal_AttributeId'));
        }
        return implode('-', $structureLevels);
    }

    /**
     * Get the depth of the stable structure stack.
     * (count($this->inlineStructure['stable'])
     *
     * @return int The depth of the structure stack
     */
    public function getStructureDepth()
    {
        if (!isset($this->inlineStructure['stable']) || !is_array($this->inlineStructure['stable'])) {
            return 0;
        }
        return count($this->inlineStructure['stable']);
    }

    /**
     * Create a name/id for usage in HTML output of a level of the structure stack to be used in form names.
     *
     * @param array $levelData Array of a level of the structure stack (containing the keys table, uid and field)
     * @param string $disposal How the structure name is used (e.g. as <div id="..."> or <input name="..." />)
     * @return string The name/id of that level, to be used for HTML output
     */
    protected function getStructureItemName($levelData, $disposal = 'Disposal_AttributeId')
    {
        $name = null;

        if (is_array($levelData)) {
            $parts = [$levelData['table'], $levelData['uid']];

            if (!empty($levelData['field'])) {
                $parts[] = $levelData['field'];
            }

            // Use in name attributes:
            if ($disposal === 'Disposal_AttributeName') {
                if (!empty($levelData['field']) && !empty($levelData['flexform']) && $this->getStructureLevel(-1) === $levelData) {
                    $parts[] = implode('][', $levelData['flexform']);
                }
                $name = '[' . implode('][', $parts) . ']';
            // Use in object id attributes:
            } else {
                $name = implode('-', $parts);

                if (!empty($levelData['field']) && !empty($levelData['flexform'])) {
                    array_unshift($levelData['flexform'], $name);
                    $name = implode('---', $levelData['flexform']);
                }
            }
        }

        return $name;
    }
}
