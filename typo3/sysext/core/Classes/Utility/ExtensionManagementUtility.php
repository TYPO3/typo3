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

namespace TYPO3\CMS\Core\Utility;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\Exception as PackageException;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;

/**
 * Extension Management functions
 *
 * This class is never instantiated, rather the methods inside is called as functions like
 * \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('my_extension');
 */
class ExtensionManagementUtility
{
    protected static PackageManager $packageManager;

    /**
     * Sets the package manager for all that backwards compatibility stuff,
     * so it doesn't have to be fetched through the bootstrap.
     *
     * @internal
     */
    public static function setPackageManager(PackageManager $packageManager): void
    {
        static::$packageManager = $packageManager;
    }

    /**************************************
     *
     * PATHS and other evaluation
     *
     ***************************************/

    /**
     * Returns TRUE if the extension with extension key $key is loaded.
     */
    public static function isLoaded(string $key): bool
    {
        return static::$packageManager->isPackageActive($key);
    }

    /**
     * Temporary helper method to resolve paths with the PackageManager.
     *
     * The PackageManager is statically injected to this class already. This
     * method will be removed without substitution in TYPO3 12 once a proper
     * resource API is introduced.
     *
     * @throws PackageException
     * @internal This method is only allowed to be called from GeneralUtility::getFileAbsFileName()! DONT'T introduce other usages!
     */
    public static function resolvePackagePath(string $path): string
    {
        return static::$packageManager->resolvePackagePath($path);
    }

    /**
     * Returns the absolute path to the extension with extension key $key.
     *
     * @param string $key Extension key
     * @param string $script $script is appended to the output if set.
     * @throws \BadFunctionCallException
     */
    public static function extPath(string $key, string $script = ''): string
    {
        if (!static::$packageManager->isPackageActive($key)) {
            throw new \BadFunctionCallException('TYPO3 Fatal Error: Extension key "' . $key . '" is NOT loaded!', 1365429656);
        }
        return static::$packageManager->getPackage($key)->getPackagePath() . $script;
    }

    /**
     * Returns the correct class name prefix for the extension key $key
     *
     * @param string $key Extension key
     * @internal
     */
    public static function getCN(string $key): string
    {
        return str_starts_with($key, 'user_')
            ? 'user_' . str_replace('_', '', substr($key, 5))
            : 'tx_' . str_replace('_', '', $key);
    }

    /**
     * Retrieves the version of an installed extension.
     * If the extension is not installed, this function returns an empty string.
     *
     * @param string $key The key of the extension to look up; must not be empty.
     *
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Core\Package\Exception
     * @return string The extension version as a string in the format "x.y.z",
     */
    public static function getExtensionVersion(string $key): string
    {
        if (empty($key)) {
            throw new \InvalidArgumentException('Extension key must be a non-empty string.', 1294586096);
        }
        if (!static::isLoaded($key)) {
            return '';
        }
        $version = static::$packageManager->getPackage($key)->getPackageMetaData()->getVersion();
        if (empty($version)) {
            throw new PackageException('Version number in composer manifest of package "' . $key . '" is missing or invalid', 1395614959);
        }
        return $version;
    }

    /**************************************
     *
     *	 Adding BACKEND features
     *	 (related to core features)
     *
     ***************************************/

    /**
     * Adding fields to an existing table definition in $GLOBALS['TCA']
     * Adds an array with $GLOBALS['TCA'] column-configuration to the $GLOBALS['TCA']-entry for that table.
     * This function adds the configuration needed for rendering of the field in TCEFORMS - but it does NOT add the field names to the types lists!
     * So to have the fields displayed you must also call fx. addToAllTCAtypes or manually add the fields to the types list.
     * FOR USE IN files in Configuration/TCA/Overrides/*.php . Use in ext_tables.php FILES may break the frontend.
     *
     * @param string $table The table name of a table already present in $GLOBALS['TCA'] with a columns section
     * @param array $columnArray The array with the additional columns (typical some fields an extension wants to add)
     */
    public static function addTCAcolumns(string $table, array $columnArray): void
    {
        if (is_array($GLOBALS['TCA'][$table]['columns'] ?? false)) {
            // Candidate for array_merge() if integer-keys will some day make trouble...
            $GLOBALS['TCA'][$table]['columns'] = array_merge($GLOBALS['TCA'][$table]['columns'], $columnArray);
        }
    }

    /**
     * Makes fields visible in the TCEforms, adding them to the end of (all) "types"-configurations
     *
     * Adds a string $string (comma separated list of field names) to all ["types"][xxx]["showitem"] entries for table $table (unless limited by $typeList)
     * This is needed to have new fields shown automatically in the TCEFORMS of a record from $table.
     * Typically this function is called after having added new columns (database fields) with the addTCAcolumns function
     * FOR USE IN files in Configuration/TCA/Overrides/*.php Use in ext_tables.php FILES may break the frontend.
     *
     * @param string $table Table name
     * @param string $newFieldsString Field list to add.
     * @param string $typeList Comma-separated list of specific types to add the field list to. (If empty, all type entries are affected)
     * @param string $position Insert fields before (default) or after one, or replace a field
     */
    public static function addToAllTCAtypes(string $table, string $newFieldsString, string $typeList = '', string $position = ''): void
    {
        $newFieldsString = trim($newFieldsString);
        if ($newFieldsString === '' || !is_array($GLOBALS['TCA'][$table]['types'] ?? false)) {
            return;
        }
        if ($position !== '') {
            [$positionIdentifier, $entityName] = GeneralUtility::trimExplode(':', $position, false, 2);
        } else {
            $positionIdentifier = '';
            $entityName = '';
        }
        $palettesChanged = [];

        foreach ($GLOBALS['TCA'][$table]['types'] as $type => &$typeDetails) {
            // skip if we don't want to add the field for this type
            if ($typeList !== '' && !GeneralUtility::inList($typeList, $type)) {
                continue;
            }
            // skip if fields were already added
            if (!isset($typeDetails['showitem'])) {
                continue;
            }

            $fieldArray = GeneralUtility::trimExplode(',', $typeDetails['showitem'], true);
            if (in_array($newFieldsString, $fieldArray, true)) {
                continue;
            }

            $fieldExists = false;
            $newPosition = '';
            if (is_array($GLOBALS['TCA'][$table]['palettes'] ?? false)) {
                // Get the palette names used in current showitem
                $paletteCount = preg_match_all('/(?:^|,)                    # Line start or a comma
					(?:
					    \\s*\\-\\-palette\\-\\-;[^;]*;([^,$]*)|             # --palette--;label;paletteName
					    \\s*\\b[^;,]+\\b(?:;[^;]*;([^;,]+))?[^,]*           # field;label;paletteName
					)/x', $typeDetails['showitem'], $paletteMatches);
                if ($paletteCount > 0) {
                    $paletteNames = array_filter(array_merge($paletteMatches[1], $paletteMatches[2]));
                    if (!empty($paletteNames)) {
                        foreach ($paletteNames as $paletteName) {
                            if (!isset($GLOBALS['TCA'][$table]['palettes'][$paletteName])) {
                                continue;
                            }
                            $palette = $GLOBALS['TCA'][$table]['palettes'][$paletteName];
                            switch ($positionIdentifier) {
                                case 'after':
                                case 'before':
                                    if (preg_match('/\\b' . preg_quote($entityName, '/') . '\\b/', $palette['showitem']) > 0 || $entityName === 'palette:' . $paletteName) {
                                        $newPosition = $positionIdentifier . ':--palette--;;' . $paletteName;
                                    }
                                    break;
                                case 'replace':
                                    // check if fields have been added to palette before
                                    if (isset($palettesChanged[$paletteName])) {
                                        $fieldExists = true;
                                        continue 2;
                                    }
                                    if (preg_match('/\\b' . preg_quote($entityName, '/') . '\\b/', $palette['showitem']) > 0) {
                                        self::addFieldsToPalette($table, $paletteName, $newFieldsString, $position);
                                        // Memorize that we already changed this palette, in case other types also use it
                                        $palettesChanged[$paletteName] = true;
                                        $fieldExists = true;
                                        continue 2;
                                    }
                                    break;
                                default:
                                    // Intentionally left blank
                            }
                        }
                    }
                }
            }
            if ($fieldExists === false) {
                $typeDetails['showitem'] = self::executePositionedStringInsertion(
                    $typeDetails['showitem'],
                    $newFieldsString,
                    $newPosition !== '' ? $newPosition : $position
                );
            }
        }
        unset($typeDetails);
    }

    /**
     * Adds new fields to all palettes that is defined after an existing field.
     * If the field does not have a following palette yet, it's created automatically
     * and gets called "generatedFor-$field".
     * FOR USE IN files in Configuration/TCA/Overrides/*.php Use in ext_tables.php FILES may break the frontend.
     *
     * See unit tests for more examples and edge cases.
     *
     * Example:
     *
     * 'aTable' => array(
     * 	'types' => array(
     * 		'aType' => array(
     * 			'showitem' => 'aField, --palette--;;aPalette',
     * 		),
     * 	),
     * 	'palettes' => array(
     * 		'aPalette' => array(
     * 			'showitem' => 'fieldB, fieldC',
     * 		),
     * 	),
     * ),
     *
     * Calling addFieldsToAllPalettesOfField('aTable', 'aField', 'newA', 'before: fieldC') results in:
     *
     * 'aTable' => array(
     * 	'types' => array(
     * 		'aType' => array(
     * 			'showitem' => 'aField, --palette--;;aPalette',
     * 		),
     * 	),
     * 	'palettes' => array(
     * 		'aPalette' => array(
     * 			'showitem' => 'fieldB, newA, fieldC',
     * 		),
     * 	),
     * ),
     *
     * @param string $table Name of the table
     * @param string $field Name of the field that has the palette to be extended
     * @param string $addFields Comma-separated list of fields to be added to the palette
     * @param string $insertionPosition Insert fields before (default) or after one
     */
    public static function addFieldsToAllPalettesOfField(string $table, string $field, string $addFields, string $insertionPosition = ''): void
    {
        if (!isset($GLOBALS['TCA'][$table]['columns'][$field])) {
            return;
        }
        if (!is_array($GLOBALS['TCA'][$table]['types'])) {
            return;
        }

        // Iterate through all types and search for the field that defines the palette to be extended
        foreach ($GLOBALS['TCA'][$table]['types'] as $typeName => $typeArray) {
            // Continue if types has no showitem at all or if requested field is not in it
            if (!isset($typeArray['showitem']) || !str_contains($typeArray['showitem'], $field)) {
                continue;
            }
            $fieldArrayWithOptions = GeneralUtility::trimExplode(',', $typeArray['showitem']);
            // Find the field we're handling
            $newFieldStringArray = [];
            foreach ($fieldArrayWithOptions as $fieldNumber => $fieldString) {
                $newFieldStringArray[] = $fieldString;
                $fieldArray = GeneralUtility::trimExplode(';', $fieldString);
                if ($fieldArray[0] !== $field) {
                    continue;
                }
                if (
                    isset($fieldArrayWithOptions[$fieldNumber + 1])
                    && str_starts_with($fieldArrayWithOptions[$fieldNumber + 1], '--palette--')
                ) {
                    // Match for $field and next field is a palette - add fields to this one
                    $paletteName = GeneralUtility::trimExplode(';', $fieldArrayWithOptions[$fieldNumber + 1]);
                    $paletteName = $paletteName[2];
                    self::addFieldsToPalette($table, $paletteName, $addFields, $insertionPosition);
                } else {
                    // Match for $field but next field is no palette - create a new one
                    $newPaletteName = 'generatedFor-' . $field;
                    self::addFieldsToPalette($table, 'generatedFor-' . $field, $addFields, $insertionPosition);
                    $newFieldStringArray[] = '--palette--;;' . $newPaletteName;
                }
            }
            $GLOBALS['TCA'][$table]['types'][$typeName]['showitem'] = implode(', ', $newFieldStringArray);
        }
    }

    /**
     * Adds new fields to a palette.
     * If the palette does not exist yet, it's created automatically.
     * FOR USE IN files in Configuration/TCA/Overrides/*.php Use in ext_tables.php FILES may break the frontend.
     *
     * @param string $table Name of the table
     * @param string $palette Name of the palette to be extended
     * @param string $addFields Comma-separated list of fields to be added to the palette
     * @param string $insertionPosition Insert fields before (default) or after one
     */
    public static function addFieldsToPalette(string $table, string $palette, string $addFields, string $insertionPosition = ''): void
    {
        if (isset($GLOBALS['TCA'][$table])) {
            $paletteData = &$GLOBALS['TCA'][$table]['palettes'][$palette];
            // If palette already exists, merge the data:
            if (is_array($paletteData)) {
                $paletteData['showitem'] = self::executePositionedStringInsertion($paletteData['showitem'], $addFields, $insertionPosition);
            } else {
                $paletteData['showitem'] = self::removeDuplicatesForInsertion($addFields);
            }
        }
    }

    /**
     * Add an item to a select field item list.
     *
     * Warning: Do not use this method for radio or check types, especially not
     * with $relativeToField and $relativePosition parameters. This would shift
     * existing database data 'off by one'.
     * FOR USE IN files in Configuration/TCA/Overrides/*.php Use in ext_tables.php FILES may break the frontend.
     *
     * As an example, this can be used to add an item to tt_content CType select
     * drop-down after the existing 'mailform' field with these parameters:
     * - $table = 'tt_content'
     * - $field = 'CType'
     * - $item = array(
     * 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.10',
     * 'login',
     * 'i/imagename.gif',
     * ),
     * - $relativeToField = mailform
     * - $relativePosition = after
     *
     * $item has an optional fourth parameter for the groupId (string), to attach the
     * new item to. The groupname is defined when a group is added with addTcaSelectItemGroup
     *
     * @throws \InvalidArgumentException If given parameters are not of correct
     * @throws \RuntimeException If reference to related position fields can not
     * @param string $table Name of TCA table
     * @param string $field Name of TCA field
     * @param array|SelectItem $item New item to add
     * @param string $relativeToField Add item relative to existing field
     * @param string $relativePosition Valid keywords: 'before', 'after'
     */
    public static function addTcaSelectItem(string $table, string $field, array|SelectItem $item, string $relativeToField = '', string $relativePosition = ''): void
    {
        $item = $item instanceof SelectItem ? $item->toArray() : $item;
        if ($relativePosition !== '' && $relativePosition !== 'before' && $relativePosition !== 'after' && $relativePosition !== 'replace') {
            throw new \InvalidArgumentException('Relative position must be either empty or one of "before", "after", "replace".', 1303236967);
        }
        if (!isset($GLOBALS['TCA'][$table]['columns'][$field]['config']['items'])
            || !is_array($GLOBALS['TCA'][$table]['columns'][$field]['config']['items'])
        ) {
            throw new \RuntimeException('Given select field item list was not found.', 1303237468);
        }
        // Make sure item keys are integers
        $GLOBALS['TCA'][$table]['columns'][$field]['config']['items'] = array_values($GLOBALS['TCA'][$table]['columns'][$field]['config']['items']);
        if ($relativePosition !== '') {
            // Insert at specified position
            $matchedPosition = ArrayUtility::filterByValueRecursive($relativeToField, $GLOBALS['TCA'][$table]['columns'][$field]['config']['items']);
            if (!empty($matchedPosition)) {
                $relativeItemKey = key($matchedPosition);
                if ($relativePosition === 'replace') {
                    $GLOBALS['TCA'][$table]['columns'][$field]['config']['items'][$relativeItemKey] = $item;
                } else {
                    if ($relativePosition === 'before') {
                        $offset = $relativeItemKey;
                    } else {
                        $offset = $relativeItemKey + 1;
                    }
                    array_splice($GLOBALS['TCA'][$table]['columns'][$field]['config']['items'], $offset, 0, [0 => $item]);
                }
            } else {
                // Insert at new item at the end of the array if relative position was not found
                $GLOBALS['TCA'][$table]['columns'][$field]['config']['items'][] = $item;
            }
        } else {
            // Insert at new item at the end of the array
            $GLOBALS['TCA'][$table]['columns'][$field]['config']['items'][] = $item;
        }
    }

    /**
     * Adds an item group to a TCA select field, allows to add a group so addTcaSelectItem() can add a groupId
     * with a label and its position within other groups.
     *
     * @param string $table the table name in TCA - e.g. tt_content
     * @param string $field the field name in TCA - e.g. CType
     * @param string $groupId the unique identifier for a group, where all items from addTcaSelectItem() with a group ID are connected
     * @param string $groupLabel the label e.g. LLL:EXT:my_extension/Resources/Private/Language/locallang_tca.xlf:group.mygroupId
     * @param string|null $position e.g. "before:special", "after:default" (where the part after the colon is an existing groupId) or "top" or "bottom"
     */
    public static function addTcaSelectItemGroup(string $table, string $field, string $groupId, string $groupLabel, ?string $position = 'bottom'): void
    {
        if (!is_array($GLOBALS['TCA'][$table]['columns'][$field]['config'] ?? null)) {
            throw new \RuntimeException('Given select field item list was not found.', 1586728563);
        }
        $itemGroups = $GLOBALS['TCA'][$table]['columns'][$field]['config']['itemGroups'] ?? [];
        // Group has been defined already, nothing to do
        if (isset($itemGroups[$groupId])) {
            return;
        }
        $position = (string)$position;
        $positionGroupId = '';
        if (str_contains($position, ':')) {
            [$position, $positionGroupId] = explode(':', $position, 2);
        }
        // Referenced group was not not found, just append to the bottom
        if (!isset($itemGroups[$positionGroupId])) {
            $position = 'bottom';
        }
        switch ($position) {
            case 'after':
                $newItemGroups = [];
                foreach ($itemGroups as $existingGroupId => $existingGroupLabel) {
                    $newItemGroups[$existingGroupId] = $existingGroupLabel;
                    if ($positionGroupId === $existingGroupId) {
                        $newItemGroups[$groupId] = $groupLabel;
                    }
                }
                $itemGroups = $newItemGroups;
                break;
            case 'before':
                $newItemGroups = [];
                foreach ($itemGroups as $existingGroupId => $existingGroupLabel) {
                    if ($positionGroupId === $existingGroupId) {
                        $newItemGroups[$groupId] = $groupLabel;
                    }
                    $newItemGroups[$existingGroupId] = $existingGroupLabel;
                }
                $itemGroups = $newItemGroups;
                break;
            case 'top':
                $itemGroups = array_merge([$groupId => $groupLabel], $itemGroups);
                break;
            case 'bottom':
            default:
                $itemGroups[$groupId] = $groupLabel;
        }
        $GLOBALS['TCA'][$table]['columns'][$field]['config']['itemGroups'] = $itemGroups;
    }

    /**
     * Adds a list of new fields to the TYPO3 USER SETTINGS configuration "showitem" list, the array with
     * the new fields itself needs to be added additionally to show up in the user setup, like
     * $GLOBALS['TYPO3_USER_SETTINGS']['columns'] += $tempColumns
     *
     * @param string $addFields List of fields to be added to the user settings
     * @param string $insertionPosition Insert fields before (default) or after one
     */
    public static function addFieldsToUserSettings(string $addFields, string $insertionPosition = ''): void
    {
        $GLOBALS['TYPO3_USER_SETTINGS']['showitem'] = self::executePositionedStringInsertion($GLOBALS['TYPO3_USER_SETTINGS']['showitem'] ?? '', $addFields, $insertionPosition);
    }

    /**
     * Inserts as list of data into an existing list.
     * The insertion position can be defined accordant before of after existing list items.
     *
     * Example:
     * + list: 'field_a, field_b, field_c'
     * + insertionList: 'field_d, field_e'
     * + insertionPosition: 'after:field_b'
     * -> 'field_a, field_b, field_d, field_e, field_c'
     *
     * $insertPosition may contain ; and - characters: after:--palette--;;title
     *
     * @param string $list The list of items to be extended
     * @param string $insertionList The list of items to inserted
     * @param string $insertionPosition Insert fields before (default) or after one
     * @return string The extended list
     */
    protected static function executePositionedStringInsertion(string $list, string $insertionList, string $insertionPosition = ''): string
    {
        $list = trim($list, ", \t\n\r\0\x0B");

        if ($insertionPosition !== '') {
            [$location, $positionName] = GeneralUtility::trimExplode(':', $insertionPosition, false, 2);
        } else {
            $location = '';
            $positionName = '';
        }

        if ($location !== 'replace') {
            $insertionList = self::removeDuplicatesForInsertion($insertionList, $list);
        }

        if ($insertionList === '') {
            return $list;
        }
        if ($list === '') {
            return $insertionList;
        }
        if ($insertionPosition === '') {
            return $list . ', ' . $insertionList;
        }

        // The $insertPosition may be a palette: after:--palette--;;title
        // In the $list the palette may contain a LLL string in between the ;;
        // Adjust the regex to match that
        $positionName = preg_quote($positionName, '/');
        if (str_contains($positionName, ';;')) {
            $positionName = str_replace(';;', ';[^;]*;', $positionName);
        }

        $pattern = ('/(^|,\\s*)(' . $positionName . ')(;[^,$]+)?(,|$)/');
        $newList = match ($location) {
            'after' => preg_replace($pattern, '$1$2$3, ' . $insertionList . '$4', $list),
            'before' => preg_replace($pattern, '$1' . $insertionList . ', $2$3$4', $list),
            'replace' => preg_replace($pattern, '$1' . $insertionList . '$4', $list),
            default => $list,
        };

        // When preg_replace did not replace anything; append the $insertionList.
        if ($newList === $list) {
            return $list . ', ' . $insertionList;
        }
        return $newList;
    }

    /**
     * Compares an existing list of items and a list of items to be inserted
     * and returns a duplicate-free variant of that insertion list.
     *
     * Example:
     * + list: 'field_a, field_b, field_c'
     * + insertion: 'field_b, field_d, field_c'
     * -> new insertion: 'field_d'
     *
     * Duplicate values in $insertionList are removed.
     *
     * @param string $insertionList The comma-separated list of items to inserted
     * @param string $list The comma-separated list of items to be extended
     * @return string Duplicate-free list of items to be inserted
     */
    protected static function removeDuplicatesForInsertion(string $insertionList, string $list = ''): string
    {
        $insertionListParts = preg_split('/\\s*,\\s*/', $insertionList);
        $listMatches = [];
        if ($list !== '') {
            preg_match_all('/(?:^|,)\\s*\\b([^;,]+)\\b[^,]*/', $list, $listMatches);
            $listMatches = $listMatches[1];
        }

        $cleanInsertionListParts = [];
        foreach ($insertionListParts as $fieldName) {
            $fieldNameParts = explode(';', $fieldName, 2);
            $cleanFieldName = $fieldNameParts[0];
            if (
                $cleanFieldName === '--linebreak--'
                || (
                    !in_array($cleanFieldName, $cleanInsertionListParts, true)
                    && !in_array($cleanFieldName, $listMatches, true)
                )
            ) {
                $cleanInsertionListParts[] = $fieldName;
            }
        }
        return implode(', ', $cleanInsertionListParts);
    }

    /**************************************
     *
     *	 Adding SERVICES features
     *
     ***************************************/
    /**
     * Adds a service to the global services array
     *
     * @param string $extKey Extension key
     * @param string $serviceType Service type, must not be prefixed "tx_" or "Tx_"
     * @param string $serviceKey Service key, must be prefixed "tx_", "Tx_" or "user_"
     * @param array $info Service description array
     */
    public static function addService(string $extKey, string $serviceType, string $serviceKey, array $info): void
    {
        if (!$serviceType) {
            throw new \InvalidArgumentException('No serviceType given.', 1507321535);
        }
        $info['priority'] = max(0, min(100, $info['priority']));
        $GLOBALS['T3_SERVICES'][$serviceType][$serviceKey] = $info;
        $GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['extKey'] = $extKey;
        $GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['serviceKey'] = $serviceKey;
        $GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['serviceType'] = $serviceType;
        // Change the priority (and other values) from $GLOBALS['TYPO3_CONF_VARS']
        // $GLOBALS['TYPO3_CONF_VARS']['T3_SERVICES'][$serviceType][$serviceKey]['priority']
        // even the activation is possible (a unix service might be possible on windows for some reasons)
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['T3_SERVICES'][$serviceType][$serviceKey] ?? false)) {
            // No check is done here - there might be configuration values only the service type knows about, so
            // we pass everything
            $GLOBALS['T3_SERVICES'][$serviceType][$serviceKey] = array_merge($GLOBALS['T3_SERVICES'][$serviceType][$serviceKey], $GLOBALS['TYPO3_CONF_VARS']['T3_SERVICES'][$serviceType][$serviceKey]);
        }
        // OS check
        // Empty $os means 'not limited to one OS', therefore a check is not needed
        if (!empty($GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['available']) && ($GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['os'] ?? '') != '') {
            $os_type = Environment::isWindows() ? 'WIN' : 'UNIX';
            $os = GeneralUtility::trimExplode(',', strtoupper($GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['os']));
            if (!in_array($os_type, $os, true)) {
                self::deactivateService($serviceType, $serviceKey);
            }
        }
        // Convert subtype list to array for quicker access
        $GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['serviceSubTypes'] = [];
        $serviceSubTypes = GeneralUtility::trimExplode(',', $info['subtype']);
        foreach ($serviceSubTypes as $subtype) {
            $GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['serviceSubTypes'][$subtype] = $subtype;
        }
    }

    /**
     * Find the available service with highest priority
     *
     * @param string $serviceType Service type
     * @param string $serviceSubType Service sub type
     * @param array $excludeServiceKeys Service keys that should be excluded in the search for a service.
     * @return array|false Service info array if a service was found, FALSE otherwise
     */
    public static function findService(string $serviceType, string $serviceSubType = '', array $excludeServiceKeys = []): array|false
    {
        $serviceKey = false;
        $serviceInfo = false;
        $priority = 0;
        $quality = 0;
        if (is_array($GLOBALS['T3_SERVICES'][$serviceType])) {
            foreach ($GLOBALS['T3_SERVICES'][$serviceType] as $key => $info) {
                if (in_array($key, $excludeServiceKeys)) {
                    continue;
                }
                // Select a subtype randomly
                // Useful to start a service by service key without knowing his subtypes - for testing purposes
                if ($serviceSubType === '*') {
                    $serviceSubType = key($info['serviceSubTypes']);
                }
                // This matches empty subtype too
                if (($info['available'] ?? false)
                    && (($info['subtype'] ?? null) == $serviceSubType || ($info['serviceSubTypes'][$serviceSubType] ?? false))
                    && ($info['priority'] ?? 0) >= $priority
                ) {
                    // Has a lower quality than the already found, therefore we skip this service
                    if ($info['priority'] == $priority && $info['quality'] < $quality) {
                        continue;
                    }
                    // Check if the service is available
                    $info['available'] = self::isServiceAvailable($serviceType, $key, $info);
                    // Still available after exec check?
                    if ($info['available']) {
                        $serviceKey = $key;
                        $priority = $info['priority'];
                        $quality = $info['quality'];
                    }
                }
            }
        }
        if ($serviceKey) {
            $serviceInfo = $GLOBALS['T3_SERVICES'][$serviceType][$serviceKey];
        }
        return $serviceInfo;
    }

    /**
     * Find a specific service identified by its key
     * Note that this completely bypasses the notions of priority and quality
     *
     * @param string $serviceKey Service key
     * @return array Service info array if a service was found
     * @throws \TYPO3\CMS\Core\Exception
     */
    public static function findServiceByKey(string $serviceKey): array
    {
        if (is_array($GLOBALS['T3_SERVICES'])) {
            // Loop on all service types
            // NOTE: we don't care about the actual type, we are looking for a specific key
            foreach ($GLOBALS['T3_SERVICES'] as $serviceType => $servicesPerType) {
                if (isset($servicesPerType[$serviceKey])) {
                    $serviceDetails = $servicesPerType[$serviceKey];
                    // Test if service is available
                    if (self::isServiceAvailable($serviceType, $serviceKey, $serviceDetails)) {
                        // We have found the right service, return its information
                        return $serviceDetails;
                    }
                }
            }
        }
        throw new \TYPO3\CMS\Core\Exception('Service not found for key: ' . $serviceKey, 1319217244);
    }

    /**
     * Check if a given service is available, based on the executable files it depends on
     *
     * @param string $serviceType Type of service
     * @param string $serviceKey Specific key of the service
     * @param array $serviceDetails Information about the service
     * @return bool Service availability
     */
    public static function isServiceAvailable(string $serviceType, string $serviceKey, array $serviceDetails): bool
    {
        // If the service depends on external programs - check if they exists
        if (trim($serviceDetails['exec'] ?? '')) {
            $executables = GeneralUtility::trimExplode(',', $serviceDetails['exec'], true);
            foreach ($executables as $executable) {
                // If at least one executable file is not available, exit early returning FALSE
                if (!CommandUtility::checkCommand($executable)) {
                    self::deactivateService($serviceType, $serviceKey);
                    return false;
                }
            }
        }
        // The service is available
        return true;
    }

    /**
     * Deactivate a service
     *
     * @param string $serviceType Service type
     * @param string $serviceKey Service key
     */
    public static function deactivateService(string $serviceType, string $serviceKey): void
    {
        // ... maybe it's better to move non-available services to a different array??
        $GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['available'] = false;
    }

    /**************************************
     *
     *	 Adding FRONTEND features
     *
     ***************************************/

    /**
     * Convenience method so you don't have to deal with strings and arrays and $GLOBALS[TCA] directly that much.
     *
     * Adds a new entry to an existing TCA DB table that has a type field configured (via $TCA[$table][ctrl][type])
     * such as "tt_content" or "pages" tables.
     *
     * Takes the $item (label, value[, icon] etc.) and adds the item to the items-array of $TCA[$table]
     * of the "type" field. The position in the list can be chosen via the $position argument.
     *
     * In addition, a type-icon gets registered, and, based on the $item[value], the record type is also added
     * to $TCA[$table]['types'][$newType], where $showItemList is added as 'showitem' key, as well as $additionalTypeInformation
     * such as 'columnsOverride' or 'creationOptions'.
     *
     * In addition, the $showItemList will receive a 'extended' tab at the very end, so other extensions
     * that add additional fields, will receive this at the extended tab automatically.
     *
     * Can be used in favor of addPlugin() and addTcaSelectItem().
     *
     * FOR USE IN files in Configuration/TCA/Overrides/*.php Use in ext_tables.php FILES may break the frontend.
     *
     * @param array|SelectItem $item The item to add to the select field
     * @param string $showItemList A string containing all fields to be used / displayed in this type
     * @param array $additionalTypeInformation Additional type information to be added to the type in $TCA[$table]['types']
     * @param string $position The position in the list where the new item should be added, something like "after:textpic"
     * @param string $table The table name, defaults to 'tt_content'
     */
    public static function addRecordType(array|SelectItem $item, string $showItemList, array $additionalTypeInformation = [], string $position = '', string $table = 'tt_content'): void
    {
        $selectItem = is_array($item) ? SelectItem::fromTcaItemArray($item) : $item;
        $typeField = $GLOBALS['TCA'][$table]['ctrl']['type'] ?? null;
        // Throw exception if no type is set
        if ($typeField === null) {
            throw new \RuntimeException('Cannot add record type "' . $selectItem->getValue() . '" for TCA table "' . $table . '" without type field defined.', 1725997543);
        }
        // Set the type icon as well
        if ($selectItem->getIcon()) {
            $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'][$selectItem->getValue()] = $selectItem->getIcon();
        }
        if (!$selectItem->hasGroup()) {
            $selectItem = $selectItem->withGroup('default');
        }

        $relativeInformation = GeneralUtility::trimExplode(':', $position, true, 2);
        self::addTcaSelectItem($table, $typeField, $selectItem, $relativeInformation[1] ?? '', $relativeInformation[0] ?? '');

        $showItemList = trim($showItemList, ', ');
        // Add the extended tab if not already added manually at the very end.
        if ($showItemList !== '' && !str_contains($showItemList, '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended')) {
            $showItemList .= ',--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended';
        }
        if ($showItemList !== '') {
            $showItemList .= ',';
        }

        $additionalTypeInformation['showitem'] = $showItemList;
        $GLOBALS['TCA'][$table]['types'][$selectItem->getValue()] = $additionalTypeInformation;
    }

    /**
     * This is a helper method to add a new "frontend plugin". It therefore takes the $itemArray (label, value[,icon]) and
     * adds to the items-array of $GLOBALS['TCA']['tt_content']['columns']['CType'|. So basically, this method adds
     * a new "select item" to the tt_content record type column ("CType").
     *
     * Additionally, this registers a given icon for the new record type and adds the plugin to the "plugin" group,
     * in case no group is manually specified in the items array. If the value (array pos. 1) is already found in
     * that items-array, the entry is substituted, otherwise the input array is added to the bottom.
     *
     * Finally a basic "showitem" configuration is added for the plugin. However, this should be adjusted by either
     * manually defining $GLOBALS['TCA']['tt_content']['types']['my_plugin'|['showitem'] or by calling further
     * helper methods, such as {@see ExtensionManagementUtility::addToAllTCAtypes()}.
     *
     * FOR USE IN files in Configuration/TCA/Overrides/*.php Use in ext_tables.php FILES may break the frontend.
     *
     * @param array|SelectItem $itemArray Numerical or assoc array: [0 or 'label'] => Plugin label, [1 or 'value'] => Plugin identifier / plugin key, ideally prefixed with an extension-specific name (e.g. "events2_list"), [2 or 'icon'] => Icon identifier or path to plugin icon, [3 or 'group'] => an optional "group" ID, falls back to "plugins"
     * @param string $flexForm The flex form (data structure) to be used for the plugin. Either a reference to a flex-form XML file (eg. "FILE:EXT:newloginbox/flexform_ds.xml") or the XML directly.
     */
    public static function addPlugin(array|SelectItem $itemArray, string $flexForm = ''): void
    {
        $selectItem = is_array($itemArray) ? SelectItem::fromTcaItemArray($itemArray) : $itemArray;
        if ($selectItem->getIcon() && !isset($GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$selectItem->getValue()])) {
            // Set the type icon as well
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$selectItem->getValue()] = $selectItem->getIcon();
        }
        if (!$selectItem->hasGroup()) {
            $selectItem = $selectItem->withGroup('plugins');
        }
        // Override possible existing entries.
        foreach ($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] ?? [] as $index => $item) {
            if ((string)($item['value'] ?? '') === (string)$selectItem->getValue()) {
                $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][$index] = $selectItem->toArray();
                return;
            }
        }
        $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'][] = $selectItem->toArray();

        // Ensure to have at least some basic information available when editing the new type in FormEngine
        if (!isset($GLOBALS['TCA']['tt_content']['types'][$selectItem->getValue()])
            && isset($GLOBALS['TCA']['tt_content']['types']['header'])
        ) {
            $GLOBALS['TCA']['tt_content']['types'][$selectItem->getValue()] = $GLOBALS['TCA']['tt_content']['types']['header'];
        }

        // Add data structure for the plugin
        if ($flexForm !== '') {
            $GLOBALS['TCA']['tt_content']['types'][$selectItem->getValue()]['columnsOverrides']['pi_flexform']['config']['ds'] = $flexForm;
            // Add flexform to showitem list
            self::addToAllTCAtypes(
                'tt_content',
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:plugin, pi_flexform',
                $selectItem->getValue(),
                'after:palette:headers'
            );
        }
    }

    /**
     * Adds an entry to the "ds" array of the tt_content field "pi_flexform".
     * This is used by plugins to add a flexform XML reference / content for use when they are selected as plugin or content element.
     * FOR USE IN files in Configuration/TCA/Overrides/*.php Use in ext_tables.php FILES may break the frontend.
     *
     * @param string $_ previously $piKeyToMatch but now unused since there is no plugin key anymore => plugins are proper record (content) types
     * @param string $value Either a reference to a flex-form XML file (eg. "FILE:EXT:newloginbox/flexform_ds.xml") or the XML directly.
     * @param string $CTypeToMatch Value of tt_content.CType (Content Type) to add the data structure
     * @see addPlugin()
     * @deprecated Will be removed in TYPO3 v15
     */
    public static function addPiFlexFormValue(string $_, string $value, string $CTypeToMatch = ''): void
    {
        trigger_error(
            __METHOD__ . ' is deprecated and will be removed in TYPO3 v15. Define the data structure for you content type by adding it in the addPlugin() call or setting it via columnsOverrides directly.',
            E_USER_DEPRECATED
        );

        if ($CTypeToMatch === '' || $value === '') {
            return;
        }

        $GLOBALS['TCA']['tt_content']['types'][$CTypeToMatch]['columnsOverrides']['pi_flexform']['config']['ds'] = $value;
    }

    /**
     * Adds the $table tablename to the list of tables allowed to be includes by content element type "Insert records"
     * By using $content_table and $content_field you can also use the function for other tables.
     * FOR USE IN files in Configuration/TCA/Overrides/*.php Use in ext_tables.php FILES may break the frontend.
     *
     * @param string $table Table name to allow for "insert record
     * @param string $content_table Table name TO WHICH the $table name is applied. See $content_field as well.
     * @param string $content_field Field name in the database $content_table in which $table is allowed to be added as a reference ("Insert Record")
     */
    public static function addToInsertRecords(string $table, string $content_table = 'tt_content', string $content_field = 'records'): void
    {
        if (is_array($GLOBALS['TCA'][$content_table]['columns']) && isset($GLOBALS['TCA'][$content_table]['columns'][$content_field]['config']['allowed'])) {
            $GLOBALS['TCA'][$content_table]['columns'][$content_field]['config']['allowed'] .= ',' . $table;
        }
    }

    /**
     * Call this method to add an entry in the static template list found in sys_templates
     * FOR USE IN Configuration/TCA/Overrides/sys_template.php Use in ext_tables.php may break the frontend.
     *
     * @param string $extKey Is of course the extension key
     * @param string $path Is the path where the template files "constants.typoscript", "setup.typoscript", and "include_static_file.txt"
     *                     are found (relative to extPath, eg. "Configuration/TypoScript/Static/"). The file "include_static_file.txt",
     *                     allows including other static templates defined in files, from your static template, and thus corresponds
     *                     to the field 'include_static_file' in the sys_template table. The syntax for this is a comma separated list
     *                     of static templates to include, example:
     *                     EXT:fluid_styled_content/Configuration/TypoScript/,EXT:other_extension/Configuration/TypoScript/
     * @param string $title Is the title in the selector box.
     * @throws \InvalidArgumentException
     * @see addTypoScript()
     */
    public static function addStaticFile(string $extKey, string $path, string $title): void
    {
        if (!$extKey) {
            throw new \InvalidArgumentException('No extension key given.', 1507321291);
        }
        if (!$path) {
            throw new \InvalidArgumentException('No file path given.', 1507321297);
        }
        if (is_array($GLOBALS['TCA']['sys_template']['columns'])) {
            $value = str_replace(',', '', 'EXT:' . $extKey . '/' . $path);
            $itemArray = ['label' => trim($title . ' (' . $extKey . ')'), 'value' => $value];
            $GLOBALS['TCA']['sys_template']['columns']['include_static_file']['config']['items'][] = $itemArray;
        }
    }

    /**
     * Call this method to add an entry in the page TSconfig list found in pages
     * FOR USE in Configuration/TCA/Overrides/pages.php
     *
     * @param string $extKey The extension key
     * @param string $filePath The path where the TSconfig file is located
     * @param string $title The title in the selector box
     * @throws \InvalidArgumentException
     */
    public static function registerPageTSConfigFile(string $extKey, string $filePath, string $title): void
    {
        if (!$extKey) {
            throw new \InvalidArgumentException('No extension key given.', 1447789490);
        }
        if (!$filePath) {
            throw new \InvalidArgumentException('No file path given.', 1447789491);
        }
        if (!is_array($GLOBALS['TCA']['pages']['columns'] ?? null)) {
            throw new \InvalidArgumentException('No TCA definition for table "pages".', 1447789492);
        }

        $value = str_replace(',', '', 'EXT:' . $extKey . '/' . $filePath);
        $itemArray = ['label' => trim($title . ' (' . $extKey . ')'), 'value' => $value];
        $GLOBALS['TCA']['pages']['columns']['tsconfig_includes']['config']['items'][] = $itemArray;
    }

    /**
     * Adds $content to the default TypoScript setup code as set in $GLOBALS['TYPO3_CONF_VARS'][FE]['defaultTypoScript_setup'].
     * NOT prefixed with a [GLOBAL] line, other calls MUST properly close their conditions!
     * FOR USE IN ext_localconf.php FILES
     *
     * @param string $content TypoScript Setup string
     * @param bool $includeInSiteSets
     */
    public static function addTypoScriptSetup(string $content, bool $includeInSiteSets = true): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup'] ??= '';
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup'])) {
            $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup'] .= LF;
        }
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup'] .= $content;

        if ($includeInSiteSets) {
            $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['siteSets'] ??= '';
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['siteSets'])) {
                $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['siteSets'] .= LF;
            }
            $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['siteSets'] .= $content;
        }
    }

    /**
     * Adds $content to the default TypoScript constants code as set in $GLOBALS['TYPO3_CONF_VARS'][FE]['defaultTypoScript_constants']
     * NOT prefixed with a [GLOBAL] line, other calls MUST properly close their conditions!
     * FOR USE IN ext_localconf.php FILES
     *
     * @param string $content TypoScript Constants string
     * @param bool $includeInSiteSets
     */
    public static function addTypoScriptConstants(string $content, bool $includeInSiteSets = true): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants'] ??= '';
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants'])) {
            $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants'] .= LF;
        }
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants'] .= $content;
        if ($includeInSiteSets) {
            $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants.']['siteSets'] ??= '';
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants.']['siteSets'])) {
                $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants.']['siteSets'] .= LF;
            }
            $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants.']['siteSets'] .= $content;
        }
    }

    /**
     * Adds $content to the default TypoScript code for either setup or constants as set in $GLOBALS['TYPO3_CONF_VARS'][FE]['defaultTypoScript_*']
     * (Basically this function can do the same as addTypoScriptSetup and addTypoScriptConstants - just with a little more hazzle, but also with some more options!)
     * FOR USE IN ext_localconf.php FILES
     * Note: As of TYPO3 CMS 6.2, static template #43 (content: default) was replaced with "defaultContentRendering" which makes it
     * possible that a first extension like fluid_styled_content registers a "contentRendering" template (= a template that defines default content rendering TypoScript)
     * by adding itself to $TYPO3_CONF_VARS[FE][contentRenderingTemplates][] = 'myext/Configuration/TypoScript'.
     * An extension calling addTypoScript('myext', 'setup', $typoScript, 'defaultContentRendering') will add its TypoScript directly after;
     * For now, "43" and "defaultContentRendering" can be used, but "defaultContentRendering" is more descriptive and
     * should be used in the future.
     *
     * @param string $key Is the extension key (informative only).
     * @param string $type Is either "setup" or "constants" and obviously determines which kind of TypoScript code we are adding.
     * @param string $content Is the TS content, will be prefixed with a [GLOBAL] line and a comment-header.
     * @param int|string $afterStaticUid string pointing to the "key" of a static_file template ([reduced extension_key]/[local path]). The points is that the TypoScript you add is included only IF that static template is included (and in that case, right after). So effectively the TypoScript you set can specifically overrule settings from those static templates.
     * @throws \InvalidArgumentException
     */
    public static function addTypoScript(string $key, string $type, string $content, int|string $afterStaticUid = 0, bool $includeInSiteSets = true): void
    {
        if ($type !== 'setup' && $type !== 'constants') {
            throw new \InvalidArgumentException('Argument $type must be set to either "setup" or "constants" when calling addTypoScript from extension "' . $key . '"', 1507321200);
        }
        $content = '

[GLOBAL]
#############################################
## TypoScript added by extension "' . $key . '"
#############################################

' . $content;
        if ($afterStaticUid) {
            // If 'defaultContentRendering' is targeted (formerly static uid 43),
            // the content is added after TypoScript of type contentRendering, e.g. fluid_styled_content, see
            // EXT:core/Classes/TypoScript/IncludeTree/SysTemplateTreeBuilder.php for more information on how the code is parsed.
            if ($afterStaticUid === 'defaultContentRendering' || $afterStaticUid == 43) {
                $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type . '.']['defaultContentRendering'] ??= '';
                $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type . '.']['defaultContentRendering'] .= $content;
            } else {
                $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type . '.'][$afterStaticUid] ??= '';
                $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type . '.'][$afterStaticUid] .= $content;
            }
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type] ??= '';
            $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type] .= $content;
            if ($includeInSiteSets) {
                // 'siteSets' is an @internal identifier
                $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type . '.']['siteSets'] ??= '';
                $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type . '.']['siteSets'] .= $content;
            }
        }
    }

    /***************************************
     *
     * Internal extension management methods
     *
     ***************************************/
    /**
     * Gets an array of loaded extension keys
     */
    public static function getLoadedExtensionListArray(): array
    {
        return array_keys(static::$packageManager->getActivePackages());
    }

    /**
     * Loads given extension
     *
     * @param string $extensionKey Extension key to load
     * @throws \RuntimeException
     */
    public static function loadExtension(string $extensionKey): void
    {
        if (static::$packageManager->isPackageActive($extensionKey)) {
            throw new \RuntimeException('Extension already loaded', 1342345486);
        }
        static::$packageManager->activatePackage($extensionKey);
    }

    /**
     * Unloads given extension
     *
     * @throws \RuntimeException
     */
    public static function unloadExtension(string $extensionKey): void
    {
        if (!static::$packageManager->isPackageActive($extensionKey)) {
            throw new \RuntimeException('Extension not loaded', 1342345487);
        }
        static::$packageManager->deactivatePackage($extensionKey);
    }
}
