<?php
namespace TYPO3\CMS\Cshmanual\Domain\Repository;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Tabble manual repository
 */
class TableManualRepository
{
    /**
     * @var \TYPO3\CMS\Cshmanual\Service\AccessService
     */
    protected $accessService;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->accessService = GeneralUtility::makeInstance(\TYPO3\CMS\Cshmanual\Service\AccessService::class);
    }

    /**
     * Get the manual of the given table
     *
     * @param string $table
     * @return array the manual for a TCA table, see getItem() for details
     */
    public function getTableManual($table)
    {
        $parts = [];

        // Load descriptions for table $table
        $this->getLanguageService()->loadSingleTableDescription($table);
        if (is_array($GLOBALS['TCA_DESCR'][$table]['columns']) && ($this->accessService->checkAccess('tables_select', $table))) {
            // Reserved for header of table
            $parts[0] = '';
            // Traverse table columns as listed in TCA_DESCR
            foreach ($GLOBALS['TCA_DESCR'][$table]['columns'] as $field => $_) {
                if (!$this->isExcludableField($table, $field) || $this->accessService->checkAccess('non_exclude_fields', $table . ':' . $field)) {
                    if (!$field) {
                        // Header
                        $parts[0] = $this->getItem($table, '', true);
                    } else {
                        // Field
                        $parts[] = $this->getItem($table, $field, true);
                    }
                }
            }
            if (!$parts[0]) {
                unset($parts[0]);
            }
        }
        return $parts;
    }

    /**
     * Get a single manual
     *
     * @param string $table table name
     * @param string $field field name
     * @return array
     */
    public function getSingleManual($table, $field)
    {
        $this->getLanguageService()->loadSingleTableDescription($table);
        return $this->getItem($table, $field);
    }

    /**
     * Get TOC sections
     *
     * @param string $mode
     * @return array
     */
    public function getSections($mode)
    {
        // Initialize
        $cshKeys = array_flip(array_keys($GLOBALS['TCA_DESCR']));
        $tcaKeys = array_keys($GLOBALS['TCA']);
        $outputSections = [];
        $tocArray = [];
        // TYPO3 Core Features
        $lang = $this->getLanguageService();
        $lang->loadSingleTableDescription('xMOD_csh_corebe');
        $this->renderTableOfContentItem($mode, 'xMOD_csh_corebe', 'core', $outputSections, $tocArray, $cshKeys);
        // Backend Modules
        $loadModules = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Module\ModuleLoader::class);
        $loadModules->load($GLOBALS['TBE_MODULES']);
        foreach ($loadModules->modules as $mainMod => $info) {
            $cshKey = '_MOD_' . $mainMod;
            if ($cshKeys[$cshKey]) {
                $lang->loadSingleTableDescription($cshKey);
                $this->renderTableOfContentItem($mode, $cshKey, 'modules', $outputSections, $tocArray, $cshKeys);
            }
            if (is_array($info['sub'])) {
                foreach ($info['sub'] as $subMod => $subInfo) {
                    $cshKey = '_MOD_' . $mainMod . '_' . $subMod;
                    if ($cshKeys[$cshKey]) {
                        $lang->loadSingleTableDescription($cshKey);
                        $this->renderTableOfContentItem($mode, $cshKey, 'modules', $outputSections, $tocArray, $cshKeys);
                    }
                }
            }
        }
        // Database Tables
        foreach ($tcaKeys as $table) {
            // Load descriptions for table $table
            $lang->loadSingleTableDescription($table);
            if (is_array($GLOBALS['TCA_DESCR'][$table]['columns']) && $this->accessService->checkAccess('tables_select', $table)) {
                $this->renderTableOfContentItem($mode, $table, 'tables', $outputSections, $tocArray, $cshKeys);
            }
        }
        foreach ($cshKeys as $cshKey => $value) {
            // Extensions
            if (GeneralUtility::isFirstPartOfStr($cshKey, 'xEXT_') && !isset($GLOBALS['TCA'][$cshKey])) {
                $lang->loadSingleTableDescription($cshKey);
                $this->renderTableOfContentItem($mode, $cshKey, 'extensions', $outputSections, $tocArray, $cshKeys);
            }
            // Other
            if (!GeneralUtility::isFirstPartOfStr($cshKey, '_MOD_') && !isset($GLOBALS['TCA'][$cshKey])) {
                $lang->loadSingleTableDescription($cshKey);
                $this->renderTableOfContentItem($mode, $cshKey, 'other', $outputSections, $tocArray, $cshKeys);
            }
        }

        if ($mode === \TYPO3\CMS\Cshmanual\Controller\HelpController::TOC_ONLY) {
            return $tocArray;
        }

        return [
            'toc' => $tocArray,
            'content' => $outputSections
        ];
    }

    /**
     * Creates a TOC list element and renders corresponding HELP content if "renderALL" mode is set.
     *
     * @param int $mode Mode
     * @param string $table CSH key / Table name
     * @param string $tocCat TOC category keyword: "core", "modules", "tables", "other
     * @param array $outputSections Array for accumulation of rendered HELP Content (in "renderALL" mode). Passed by reference!
     * @param array $tocArray TOC array; Here TOC index elements are created. Passed by reference!
     * @param array $CSHkeys CSH keys array. Every item rendered will be unset in this array so finally we can see what CSH keys are not processed yet. Passed by reference!
     */
    protected function renderTableOfContentItem($mode, $table, $tocCat, &$outputSections, &$tocArray, &$CSHkeys)
    {
        $tocArray[$tocCat][$table] = $this->getTableFieldLabel($table);
        if (!$mode) {
            // Render full manual right here!
            $outputSections[$table]['content'] = $this->getTableManual($table);
            if (!$outputSections[$table]) {
                unset($outputSections[$table]);
            }
        }

        // Unset CSH key
        unset($CSHkeys[$table]);
    }

    /**
     * Returns composite label for table/field
     *
     * @param string $key CSH key / table name
     * @param string $field Sub key / field name
     * @param string $mergeToken Token to merge the two strings with
     * @return string Labels joined with merge token
     * @see getTableFieldNames()
     */
    protected function getTableFieldLabel($key, $field = '', $mergeToken = ': ')
    {
        // Get table / field parts
        list($tableName, $fieldName) = $this->getTableFieldNames($key, $field);
        // Create label
        return $this->getLanguageService()->sL($tableName) . ($field ? $mergeToken . rtrim(trim($this->getLanguageService()->sL($fieldName)), ':') : '');
    }

    /**
     * Returns labels for a given field in a given structure
     *
     * @param string $key CSH key / table name
     * @param string $field Sub key / field name
     * @return array Table and field labels in a numeric array
     */
    protected function getTableFieldNames($key, $field)
    {
        $this->getLanguageService()->loadSingleTableDescription($key);
        // Define the label for the key
        $keyName = $key;
        if (!empty($GLOBALS['TCA_DESCR'][$key]['columns']['']['alttitle'])) {
            // If there's an alternative title, use it
            $keyName = $GLOBALS['TCA_DESCR'][$key]['columns']['']['alttitle'];
        } elseif (isset($GLOBALS['TCA'][$key])) {
            // Otherwise, if it's a table, use its title
            $keyName = $GLOBALS['TCA'][$key]['ctrl']['title'];
        } else {
            // If no title was found, make sure to remove any "_MOD_"
            $keyName = preg_replace('/^_MOD_/', '', $key);
        }
        // Define the label for the field
        $fieldName = $field;
        if (!empty($GLOBALS['TCA_DESCR'][$key]['columns'][$field]['alttitle'])) {
            // If there's an alternative title, use it
            $fieldName = $GLOBALS['TCA_DESCR'][$key]['columns'][$field]['alttitle'];
        } elseif (!empty($GLOBALS['TCA'][$key]['columns'][$field])) {
            // Otherwise, if it's a table, use its title
            $fieldName = $GLOBALS['TCA'][$key]['columns'][$field]['label'];
        }
        return [$keyName, $fieldName];
    }

    /**
     * Gets a single $table/$field information piece
     * If $anchors is set, then seeAlso references to the same table will be page-anchors, not links.
     *
     * @param string $table CSH key / table name
     * @param string $field Sub key / field name
     * @param bool $anchors If anchors is to be shown.
     * @return array with the information
     */
    protected function getItem($table, $field, $anchors = false)
    {
        if (!empty($table)) {
            $field = !empty($field) ? $field : '';
            $setup = $GLOBALS['TCA_DESCR'][$table]['columns'][$field];
            return [
                'table' => $table,
                'field' => $field,
                'configuration' => $setup,
                'headerLine' => $this->getTableFieldLabel($table, $field),
                'content' => !empty($setup['description']) ? $setup['description'] : '',
                'images' => !empty($setup['image']) ? $this->getImages($setup['image'], $setup['image_descr']) : [],
                'seeAlso' => !empty($setup['seeAlso']) ? $this->getSeeAlsoLinks($setup['seeAlso'], $anchors ? $table : '') : '',
            ];
        }
        return [];
    }

    /**
     * Get see-also links
     *
     * @param string $value See-also input codes
     * @param string $anchorTable If $anchorTable is set to a tablename, then references to this table will be made as anchors, not URLs.
     * @return array See-also links
     */
    protected function getSeeAlsoLinks($value, $anchorTable = '')
    {
        // Split references by comma or linebreak
        $items = preg_split('/[,' . LF . ']/', $value);
        $lines = [];
        foreach ($items as $itemValue) {
            $itemValue = trim($itemValue);
            if ($itemValue) {
                $reference = GeneralUtility::trimExplode(':', $itemValue);
                $referenceUrl = GeneralUtility::trimExplode('|', $itemValue);
                if (substr($referenceUrl[1], 0, 4) === 'http') {
                    // URL reference
                    $lines[] = [
                        'url' => $referenceUrl[1],
                        'title' => $referenceUrl[0],
                        'target' => '_blank'
                    ];
                } elseif (substr($referenceUrl[1], 0, 5) === 'FILE:') {
                    // File reference
                    $fileName = GeneralUtility::getFileAbsFileName(substr($referenceUrl[1], 5));
                    if ($fileName && @is_file($fileName)) {
                        $fileName = '../' . PathUtility::stripPathSitePrefix($fileName);
                        $lines[] = [
                            'url' => $fileName,
                            'title' => $referenceUrl[0],
                            'target' => '_blank'
                        ];
                    }
                } else {
                    // Table reference
                    $table = !empty($reference[0]) ? $reference[0] : '';
                    $field = !empty($reference[1]) ? $reference[1] : '';
                    $accessAllowed = true;
                    // Check if table exists and current user can access it
                    if (!empty($table)) {
                        $accessAllowed = !$this->getTableSetup($table) || $this->accessService->checkAccess('tables_select', $table);
                    }
                    // Check if field exists and is excludable or user can access it
                    if ($accessAllowed && !empty($field)) {
                        $accessAllowed = !$this->isExcludableField($table, $field) || $this->accessService->checkAccess('non_exclude_fields', $table . ':' . $field);
                    }
                    // Check read access
                    if ($accessAllowed && isset($GLOBALS['TCA_DESCR'][$table])) {
                        // Make see-also link
                        $label = $this->getTableFieldLabel($table, $field, ' / ');
                        if ($anchorTable && $table === $anchorTable) {
                            $lines[] = [
                                'url' => '#' . rawurlencode(implode('.', $reference)),
                                'title' => $label,
                            ];
                        } else {
                            $lines[] = [
                                'internal' => true,
                                'arguments' => [
                                    'table' => $table,
                                    'field' => $field
                                ],
                                'title' => $label
                            ];
                        }
                    }
                }
            }
        }
        return $lines;
    }

    /**
     * Check if given table / field is excludable
     *
     * @param string $table The table
     * @param string $field The field
     * @return bool TRUE if given field is excludable
     */
    protected function isExcludableField($table, $field)
    {
        $fieldSetup = $this->getFieldSetup($table, $field);
        if (!empty($fieldSetup)) {
            return !empty($fieldSetup['exclude']);
        }
        return false;
    }

    /**
     * Returns an array of images with description
     *
     * @param string $images Image file reference (list of)
     * @param string $descriptions Description string (divided for each image by line break)
     * @return array
     */
    protected function getImages($images, $descriptions)
    {
        $imageData = [];
        // Splitting
        $imgArray = GeneralUtility::trimExplode(',', $images, true);
        if (!empty($imgArray)) {
            $descrArray = explode(LF, $descriptions, count($imgArray));
            foreach ($imgArray as $k => $image) {
                $descriptions = $descrArray[$k];
                $absImagePath = GeneralUtility::getFileAbsFileName($image);
                if ($absImagePath && @is_file($absImagePath)) {
                    $imgFile = PathUtility::stripPathSitePrefix($absImagePath);
                    $imgInfo = @getimagesize($absImagePath);
                    if (is_array($imgInfo)) {
                        $imageData[] = [
                            'image' => $imgFile,
                            'description' => $descriptions
                        ];
                    }
                }
            }
        }
        return $imageData;
    }

    /**
     * Returns the setup for given table
     *
     * @param string $table The table
     * @return array The table setup
     */
    protected function getTableSetup($table)
    {
        if (!empty($table) && !empty($GLOBALS['TCA'][$table])) {
            return $GLOBALS['TCA'][$table];
        }
        return [];
    }

    /**
     * Returns the setup for given table / field
     *
     * @param string $table The table
     * @param string $field The field
     * @param bool $allowEmptyField Allow empty field
     * @return array The field setup
     */
    protected function getFieldSetup($table, $field, $allowEmptyField = false)
    {
        $tableSetup = $this->getTableSetup($table);
        if (!empty($tableSetup) && (!empty($field) || $allowEmptyField) && !empty($tableSetup['columns'][$field])) {
            return $tableSetup['columns'][$field];
        }
        return [];
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
