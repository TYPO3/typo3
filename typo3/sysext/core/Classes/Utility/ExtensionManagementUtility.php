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

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\Migrations\TcaMigration;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Package\Exception as PackageException;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Preparations\TcaPreparation;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;

/**
 * Extension Management functions
 *
 * This class is never instantiated, rather the methods inside is called as functions like
 * \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('my_extension');
 */
class ExtensionManagementUtility
{
    /**
     * TRUE, if ext_tables file was read from cache for this script run.
     * The frontend tends to do that multiple times, but the caching framework does
     * not allow this (via a require_once call). This variable is used to track
     * the access to the cache file to read the single ext_tables.php if it was
     * already read from cache
     *
     * @todo See if we can get rid of the 'load multiple times' scenario in fe
     */
    protected static bool $extTablesWasReadFromCacheOnce = false;
    protected static PackageManager $packageManager;
    protected static EventDispatcherInterface $eventDispatcher;
    protected static ?CacheManager $cacheManager;

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

    /**
     * Sets the event dispatcher to be available.
     *
     * @internal only used for tests and the internal TYPO3 Bootstrap process
     */
    public static function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        static::$eventDispatcher = $eventDispatcher;
    }

    /**
     * Returns the Cache manager
     */
    protected static function getCacheManager(): CacheManager
    {
        return static::$cacheManager ??= GeneralUtility::makeInstance(CacheManager::class);
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
     * @param array $item New item to add
     * @param string $relativeToField Add item relative to existing field
     * @param string $relativePosition Valid keywords: 'before', 'after'
     */
    public static function addTcaSelectItem(string $table, string $field, array $item, string $relativeToField = '', string $relativePosition = ''): void
    {
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
     * Gets the TCA configuration for a field handling (FAL) files.
     *
     * @param string $fieldName Name of the field to be used
     * @param array $customSettingOverride Custom field settings overriding the basics
     * @param string $allowedFileExtensions Comma-separated list of allowed file extensions (e.g. "jpg,gif,pdf")
     * @param string $disallowedFileExtensions Comma-separated list of disallowed file extensions (e.g. "doc,docx")
     *
     * @deprecated since TYPO3 v12.0. Use the TCA type "file" directly
     */
    public static function getFileFieldTCAConfig(string $fieldName, array $customSettingOverride = [], string $allowedFileExtensions = '', string $disallowedFileExtensions = ''): array
    {
        trigger_error(
            'ExtensionManagementUtility::getFileFieldTCAConfig() will be removed in TYPO3 v13.0. Use TCA type "file" directly instead.',
            E_USER_DEPRECATED
        );

        $fileFieldTCAConfig = [
            'type' => 'file',
            'allowed' => $allowedFileExtensions,
            'disallowed' => $disallowedFileExtensions,
        ];
        ArrayUtility::mergeRecursiveWithOverrule($fileFieldTCAConfig, $customSettingOverride);
        return $fileFieldTCAConfig;
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

    /**
     * Add tablename to default list of allowed tables on pages (in $PAGES_TYPES)
     * Will add the $table to the list of tables allowed by default on pages as setup by $PAGES_TYPES['default']['allowedTables']
     * FOR USE IN ext_tables.php FILES
     *
     * @param string $table Table name
     * @deprecated will be removed in TYPO3 v13.0. Use $GLOBALS['TCA'][$table]['ctrl']['security']['ignorePageTypeRestriction'] instead.
     */
    public static function allowTableOnStandardPages(string $table): void
    {
        if ($table === '') {
            return;
        }
        $registry = GeneralUtility::makeInstance(PageDoktypeRegistry::class);
        $tables = explode(',', $table);
        foreach ($tables as $singleTable) {
            if (!$registry->isRecordTypeAllowedForDoktype($singleTable, null)) {
                $registry->addAllowedRecordTypes(explode(',', $singleTable));
            }
        }
    }

    /**
     * To allow extension authors to support multiple versions, this method is kept until
     * TYPO3 v13, but is no longer used nor evaluated from TYPO3 v12.0. To add modules,
     * place the configuration in your extensions' Configuration/Backend/Modules.php file.
     *
     * The method deliberately does not throw a deprecation warning in order to keep the noise
     * of deprecation warnings small.
     *
     * @deprecated The functionality has been removed in v12. The method will be removed in TYPO3 v13.
     */
    public static function addModule($main, $sub = '', $position = '', $path = null, $moduleConfiguration = []) {}

    /**
     * Adds a "Function menu module" ('third level module') to an existing function menu of some other backend module.
     *
     * FOR USE IN ext_tables.php FILES
     *
     * @param string $modname Module name
     * @param string $className Class name
     * @param string $_unused not in use anymore
     * @param string $title Title of module
     * @param string $MM_key Menu array key - default is "function
     * @param string $WS Workspace conditions. Blank means all workspaces, any other string can be a comma list of "online", "offline" and "custom
     * @deprecated use the Module Registration API to define calls, will be removed in TYPO3 v13.0.
     */
    public static function insertModuleFunction($modname, $className, $_unused, $title, $MM_key = 'function', $WS = '')
    {
        // no-op: This is not in use anymore, use Modules.php instead
        // This does not trigger a deprecation message as everything continues to work
    }

    /**
     * Adds $content to the default page TSconfig as set in $GLOBALS['TYPO3_CONF_VARS'][BE]['defaultPageTSconfig']
     * Prefixed with a [GLOBAL] line
     * FOR USE IN ext_localconf.php FILE
     *
     * @param string $content Page TSconfig content
     */
    public static function addPageTSConfig(string $content): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'] .= chr(10) . $content;
    }

    /**
     * Adds $content to the default user TSconfig as set in $GLOBALS['TYPO3_CONF_VARS'][BE]['defaultUserTSconfig']
     * Prefixed with a [GLOBAL] line
     * FOR USE IN ext_localconf.php FILE
     *
     * @param string $content User TSconfig content
     */
    public static function addUserTSConfig(string $content): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'] .= chr(10) . $content;
    }

    /**
     * Adds a reference to a locallang file with $GLOBALS['TCA_DESCR'] labels
     * FOR USE IN ext_tables.php FILES
     * eg. \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('pages', 'EXT:core/Resources/Private/Language/locallang_csh_pages.xlf'); for the pages table or \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_layout', 'EXT:frontend/Resources/Private/Language/locallang_csh_weblayout.xlf'); for the Web > Page module.
     *
     * @param string $key Description key. Typically a database table (like "pages") but for applications can be other strings, but prefixed with "_MOD_")
     * @param string $file File reference to locallang file, eg. "EXT:core/Resources/Private/Language/locallang_csh_pages.xlf"
     *
     * @deprecated The functionality has been removed in v12. The method will be removed in TYPO3 v13.
     */
    public static function addLLrefForTCAdescr($key, $file) {}

    /**
     * Registers a navigation component e.g. page tree
     *
     * @param string $module
     * @param string $componentId componentId is also a RequireJS module name e.g. 'TYPO3/CMS/MyExt/MyNavComponent'
     * @param string $extensionKey
     * @throws \RuntimeException
     * @deprecated no longer in use. Will be removed in TYPO3 v13.0.
     */
    public static function addNavigationComponent($module, $componentId, $extensionKey)
    {
        trigger_error('ExtensionManagementUtility::addNavigationComponent() will be removed in TYPO3 v13.0. Is not needed anymore. Remove any calls to this method.', E_USER_DEPRECATED);
    }

    /**
     * Registers a core navigation component
     *
     * @param string $module
     * @param string $componentId
     * @deprecated no longer in use. Will be removed in TYPO3 v13.0.
     */
    public static function addCoreNavigationComponent($module, $componentId)
    {
        trigger_error('ExtensionManagementUtility::addCoreNavigationComponent() will be removed in TYPO3 v13.0. Is not needed anymore. Remove any calls to this method.', E_USER_DEPRECATED);
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
     * Adds an entry to the list of plugins in content elements of type "Insert plugin"
     * Takes the $itemArray (label, value[,icon]) and adds to the items-array of $GLOBALS['TCA'][tt_content] elements with CType "listtype" (or another field if $type points to another fieldname)
     * If the value (array pos. 1) is already found in that items-array, the entry is substituted, otherwise the input array is added to the bottom.
     * Use this function to add a frontend plugin to this list of plugin-types - or more generally use this function to add an entry to any selectorbox/radio-button set in the FormEngine
     *
     * FOR USE IN files in Configuration/TCA/Overrides/*.php Use in ext_tables.php FILES may break the frontend.
     *
     * @param array|SelectItem $itemArray Numerical or assoc array: [0 or 'label'] => Plugin label, [1 or 'value'] => Plugin identifier / plugin key, ideally prefixed with an extension-specific name (e.g. "events2_list"), [2 or 'icon'] => Icon identifier or path to plugin icon, [3 or 'group'] => an optional "group" ID, falls back to "default"
     * @param string|null $extensionKey The extension key
     * @throws \RuntimeException
     */
    public static function addPlugin(array|SelectItem $itemArray, string $type = 'list_type', ?string $extensionKey = null): void
    {
        // $extensionKey is required, but presumably for BC reasons it still lives after $type in the
        // parameter list, and $type is nominally optional.
        if (!isset($extensionKey)) {
            throw new \InvalidArgumentException(
                'No extension key could be determined when calling addPlugin()!'
                . LF
                . 'This method is meant to be called from Configuration/TCA/Overrides files. '
                . 'The extension key needs to be specified as third parameter. '
                . 'Calling it from any other place e.g. ext_localconf.php does not work and is not supported.',
                1404068038
            );
        }
        $selectItem = is_array($itemArray) ? SelectItem::fromTcaItemArray($itemArray) : $itemArray;
        if (!$selectItem->hasIcon()) {
            $iconPath = static::getExtensionIcon(static::$packageManager->getPackage($extensionKey)->getPackagePath());
            if ($iconPath) {
                $selectItem = $selectItem->withIcon('EXT:' . $extensionKey . '/' . $iconPath);
            }
        } elseif ($type === 'CType' && !isset($GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$selectItem->getValue()])) {
            // Set the type icon as well
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$selectItem->getValue()] = $selectItem->getIcon();
        }
        if (!$selectItem->hasGroup()) {
            $selectItem = $selectItem->withGroup('default');
        }
        // Override possible existing entries.
        foreach ($GLOBALS['TCA']['tt_content']['columns'][$type]['config']['items'] ?? [] as $index => $item) {
            if ((string)($item['value'] ?? '') === (string)$selectItem->getValue()) {
                $GLOBALS['TCA']['tt_content']['columns'][$type]['config']['items'][$index] = $selectItem->toArray();
                return;
            }
        }
        $GLOBALS['TCA']['tt_content']['columns'][$type]['config']['items'][] = $selectItem->toArray();

        // Populate plugin subtype groups with CType group if missing.
        if ($type === 'list_type' && !isset($GLOBALS['TCA']['tt_content']['columns'][$type]['itemGroups'][$selectItem->getGroup()])) {
            $GLOBALS['TCA']['tt_content']['columns'][$type]['config']['itemGroups'][$selectItem->getGroup()] =
                $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemGroups'][$selectItem->getGroup()] ?? [];
        }

        // Ensure to have at least some basic information available when editing the new type in FormEngine
        if (
            $type === 'CType'
            && !isset($GLOBALS['TCA']['tt_content']['types'][$selectItem->getValue()])
            && isset($GLOBALS['TCA']['tt_content']['types']['header'])
        ) {
            $GLOBALS['TCA']['tt_content']['types'][$selectItem->getValue()] = $GLOBALS['TCA']['tt_content']['types']['header'];
        }
    }

    /**
     * Adds an entry to the "ds" array of the tt_content field "pi_flexform".
     * This is used by plugins to add a flexform XML reference / content for use when they are selected as plugin or content element.
     * FOR USE IN files in Configuration/TCA/Overrides/*.php Use in ext_tables.php FILES may break the frontend.
     *
     * @param string $piKeyToMatch Plugin key as used in the list_type field. Use the asterisk * to match all list_type values.
     * @param string $value Either a reference to a flex-form XML file (eg. "FILE:EXT:newloginbox/flexform_ds.xml") or the XML directly.
     * @param string $CTypeToMatch Value of tt_content.CType (Content Type) to match. The default is "list" which corresponds to the "Insert Plugin" content element.  Use the asterisk * to match all CType values.
     * @see addPlugin()
     */
    public static function addPiFlexFormValue(string $piKeyToMatch, string $value, string $CTypeToMatch = 'list'): void
    {
        if (is_array($GLOBALS['TCA']['tt_content']['columns']) && is_array($GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds'])) {
            $GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds'][$piKeyToMatch . ',' . $CTypeToMatch] = $value;
        }
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
     * Add PlugIn to the default template rendering (previously called "Static Template #43")
     *
     * When adding a frontend plugin you will have to add both an entry to the TCA definition of tt_content table AND to the TypoScript template which must initiate the rendering.
     *
     * The naming of #43 has historic reason and is rooted inside code which is now put into a TER extension called
     * "statictemplates". Since the static template with uid 43 is the "content.default" and practically always used
     * for rendering the content elements it's very useful to have this function automatically adding the necessary
     * TypoScript for calling your plugin.
     * The logic is now generalized and called "defaultContentRendering", see addTypoScript() as well.
     *
     * $type determines the type of frontend plugin:
     * + list_type (default) - the good old "Insert plugin" entry
     * + CType - a new content element type
     * + includeLib - just includes the library for manual use somewhere in TypoScript.
     * (Remember that your $type definition should correspond to the column/items array in $GLOBALS['TCA'][tt_content] where you added the selector item for the element! See addPlugin() function)
     * FOR USE IN ext_localconf.php FILES
     *
     * @param string $key The extension key
     * @param string $_ unused since TYPO3 CMS 8
     * @param string $suffix Is used as a suffix of the class name (e.g. "_pi1")
     * @param string $type See description above
     * @param bool $cacheable If $cached is set as USER content object (cObject) is created - otherwise a USER_INT object is created.
     */
    public static function addPItoST43(string $key, string $_ = '', string $suffix = '', string $type = 'list_type', bool $cacheable = false): void
    {
        $cN = self::getCN($key);
        // General plugin
        $pluginContent = trim('
plugin.' . $cN . $suffix . ' = USER' . ($cacheable ? '' : '_INT') . '
plugin.' . $cN . $suffix . '.userFunc = ' . $cN . $suffix . '->main
');
        self::addTypoScript($key, 'setup', '
# Setting ' . $key . ' plugin TypoScript
' . $pluginContent);
        // Add after defaultContentRendering
        switch ($type) {
            case 'list_type':
                $addLine = 'tt_content.list.20.' . $key . $suffix . ' = < plugin.' . $cN . $suffix;
                break;
            case 'CType':
                $addLine = trim('
tt_content.' . $key . $suffix . ' =< lib.contentElement
tt_content.' . $key . $suffix . ' {
    templateName = Generic
    20 =< plugin.' . $cN . $suffix . '
}
');
                break;
            case 'includeLib':
                $addLine = 'page.1000 = < plugin.' . $cN . $suffix;
                break;
            default:
                $addLine = '';
        }
        if ($addLine) {
            self::addTypoScript($key, 'setup', '
# Setting ' . $key . ' plugin TypoScript
' . $addLine . '
', 'defaultContentRendering');
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
     */
    public static function addTypoScriptSetup(string $content): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup'] ??= '';
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup'])) {
            $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup'] .= LF;
        }
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup'] .= $content;
    }

    /**
     * Adds $content to the default TypoScript constants code as set in $GLOBALS['TYPO3_CONF_VARS'][FE]['defaultTypoScript_constants']
     * NOT prefixed with a [GLOBAL] line, other calls MUST properly close their conditions!
     * FOR USE IN ext_localconf.php FILES
     *
     * @param string $content TypoScript Constants string
     */
    public static function addTypoScriptConstants(string $content): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants'] ??= '';
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants'])) {
            $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants'] .= LF;
        }
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants'] .= $content;
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
    public static function addTypoScript(string $key, string $type, string $content, int|string $afterStaticUid = 0): void
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
        }
    }

    /***************************************
     *
     * Internal extension management methods
     *
     ***************************************/
    /**
     * Find extension icon
     *
     * @param string $extensionPath Path to extension directory.
     * @param bool $returnFullPath Return full path of file.
     */
    public static function getExtensionIcon(string $extensionPath, bool $returnFullPath = false): string
    {
        $icon = '';
        // @deprecated In v13 remove the boolean array value and use the file location string as value again
        $locationsToCheckFor = [
            'Resources/Public/Icons/Extension.svg' => false,
            'Resources/Public/Icons/Extension.png' => false,
            'Resources/Public/Icons/Extension.gif' => false,
            'ext_icon.svg' => true,
            'ext_icon.png' => true,
            'ext_icon.gif' => true,
        ];
        foreach ($locationsToCheckFor as $fileLocation => $legacyLocation) {
            if (file_exists($extensionPath . $fileLocation)) {
                $icon = $fileLocation;
                if ($legacyLocation) {
                    trigger_error(
                        'Usage of ' . $fileLocation . ' for the extension icon is deprecated since v12 and will ' .
                        'stop working with TYPO3 v13. Add your icon as Resources/Public/Icons/Extension.' .
                        substr($fileLocation, -3, 3) . ' instead.',
                        E_USER_DEPRECATED
                    );
                }
                break;
            }
        }
        return $returnFullPath ? $extensionPath . $icon : $icon;
    }

    /**
     * Execute all ext_localconf.php files of loaded extensions.
     * The method implements an optionally used caching mechanism that concatenates all
     * ext_localconf.php files in one file.
     *
     * This is an internal method. It is only used during bootstrap and
     * extensions should not use it!
     *
     * @param bool $allowCaching Whether or not to load / create concatenated cache file
     * @param FrontendInterface $codeCache
     * @internal
     */
    public static function loadExtLocalconf(bool $allowCaching = true, FrontendInterface $codeCache = null): void
    {
        if ($allowCaching) {
            $codeCache = $codeCache ?? self::getCacheManager()->getCache('core');
            $cacheIdentifier = self::getExtLocalconfCacheIdentifier();
            $hasCache = $codeCache->require($cacheIdentifier) !== false;
            if (!$hasCache) {
                self::loadSingleExtLocalconfFiles();
                self::createExtLocalconfCacheEntry($codeCache);
            }
        } else {
            self::loadSingleExtLocalconfFiles();
        }
    }

    /**
     * Execute ext_localconf.php files from extensions
     */
    protected static function loadSingleExtLocalconfFiles(): void
    {
        foreach (static::$packageManager->getActivePackages() as $package) {
            $extLocalconfPath = $package->getPackagePath() . 'ext_localconf.php';
            if (file_exists($extLocalconfPath)) {
                require $extLocalconfPath;
            }
        }
    }

    /**
     * Create cache entry for concatenated ext_localconf.php files
     *
     * @internal
     */
    public static function createExtLocalconfCacheEntry(FrontendInterface $codeCache): void
    {
        $phpCodeToCache = [];
        // Set same globals as in loadSingleExtLocalconfFiles()
        $phpCodeToCache[] = '/**';
        $phpCodeToCache[] = ' * Compiled ext_localconf.php cache file';
        $phpCodeToCache[] = ' */';
        $phpCodeToCache[] = '';
        // Iterate through loaded extensions and add ext_localconf content
        foreach (static::$packageManager->getActivePackages() as $package) {
            $extensionKey = $package->getPackageKey();
            $extLocalconfPath = $package->getPackagePath() . 'ext_localconf.php';
            if (@file_exists($extLocalconfPath)) {
                // Include a header per extension to make the cache file more readable
                $phpCodeToCache[] = '/**';
                $phpCodeToCache[] = ' * Extension: ' . $extensionKey;
                $phpCodeToCache[] = ' * File: ' . $extLocalconfPath;
                $phpCodeToCache[] = ' */';
                $phpCodeToCache[] = '';
                // Add ext_localconf.php content of extension
                $phpCodeToCache[] = 'namespace {';
                $phpCodeToCache[] = trim((string)file_get_contents($extLocalconfPath));
                $phpCodeToCache[] = '}';
                $phpCodeToCache[] = '';
                $phpCodeToCache[] = '';
            }
        }
        $phpCodeToCache = implode(LF, $phpCodeToCache);
        // Remove all start and ending php tags from content
        $phpCodeToCache = preg_replace('/<\\?php|\\?>/is', '', $phpCodeToCache);
        $phpCodeToCache = preg_replace('/declare\\s?+\\(\\s?+strict_types\\s?+=\\s?+1\\s?+\\);/is', '', (string)$phpCodeToCache);
        $codeCache->set(self::getExtLocalconfCacheIdentifier(), $phpCodeToCache);
    }

    /**
     * Cache identifier of concatenated ext_localconf file
     */
    protected static function getExtLocalconfCacheIdentifier(): string
    {
        return (new PackageDependentCacheIdentifier(self::$packageManager))->withPrefix('ext_localconf')->toString();
    }

    /**
     * Wrapper for buildBaseTcaFromSingleFiles handling caching.
     *
     * This builds 'base' TCA that is later overloaded by ext_tables.php.
     *
     * Use a cache file if exists and caching is allowed.
     *
     * This is an internal method. It is only used during bootstrap and
     * extensions should not use it!
     *
     * @param bool $allowCaching Whether or not to load / create concatenated cache file
     * @internal
     */
    public static function loadBaseTca(bool $allowCaching = true, FrontendInterface $codeCache = null): void
    {
        if ($allowCaching) {
            $codeCache = $codeCache ?? self::getCacheManager()->getCache('core');
            $cacheIdentifier = static::getBaseTcaCacheIdentifier();
            $cacheData = $codeCache->require($cacheIdentifier);
            if ($cacheData) {
                $GLOBALS['TCA'] = $cacheData['tca'];
            } else {
                static::buildBaseTcaFromSingleFiles();
                static::createBaseTcaCacheFile($codeCache);
            }
        } else {
            static::buildBaseTcaFromSingleFiles();
        }

        $allowedRecordTypesForDefault = [];
        foreach ($GLOBALS['TCA'] as $table => $tableConfiguration) {
            if ($tableConfiguration['ctrl']['security']['ignorePageTypeRestriction'] ?? false) {
                $allowedRecordTypesForDefault[] = $table;
            }
        }
        GeneralUtility::makeInstance(PageDoktypeRegistry::class)->addAllowedRecordTypes($allowedRecordTypesForDefault);
    }

    /**
     * Find all Configuration/TCA/* files of extensions and create base TCA from it.
     * The filename must be the table name in $GLOBALS['TCA'], and the content of
     * the file should return an array with content of a specific table.
     *
     * @see Extension core, extensionmanager and others for examples.
     * @internal
     */
    public static function buildBaseTcaFromSingleFiles(): void
    {
        $GLOBALS['TCA'] = [];

        $activePackages = static::$packageManager->getActivePackages();

        // To require TCA in a safe scoped environment avoiding local variable clashes.
        // @see TYPO3\CMS\Core\Tests\Functional\Utility\ExtensionManagementUtility\ExtensionManagementUtilityTcaRequireTest
        // Note: Return type 'mixed' is intended, otherwise broken TCA files with missing "return [];" statement would
        //       emit a "return value must be of type array, int returned" PHP TypeError. This is mitigated by an array
        //       check below.
        $scopedReturnRequire = static function (string $filename): mixed {
            return require $filename;
        };
        // First load "full table" files from Configuration/TCA
        foreach ($activePackages as $package) {
            try {
                $finder = Finder::create()->files()->sortByName()->depth(0)->name('*.php')->in($package->getPackagePath() . 'Configuration/TCA');
            } catch (\InvalidArgumentException $e) {
                // No such directory in this package
                continue;
            }
            foreach ($finder as $fileInfo) {
                $tcaOfTable = $scopedReturnRequire($fileInfo->getPathname());
                if (is_array($tcaOfTable)) {
                    $tcaTableName = substr($fileInfo->getBasename(), 0, -4);
                    $GLOBALS['TCA'][$tcaTableName] = $tcaOfTable;
                }
            }
        }

        // To require TCA Overrides in a safe scoped environment avoiding local variable clashes.
        // @see TYPO3\CMS\Core\Tests\Functional\Utility\ExtensionManagementUtility\ExtensionManagementUtilityTcaOverrideRequireTest
        $scopedRequire = static function (string $filename): void {
            require $filename;
        };
        // Execute override files from Configuration/TCA/Overrides
        foreach ($activePackages as $package) {
            try {
                $finder = Finder::create()->files()->sortByName()->depth(0)->name('*.php')->in($package->getPackagePath() . 'Configuration/TCA/Overrides');
            } catch (\InvalidArgumentException $e) {
                // No such directory in this package
                continue;
            }
            foreach ($finder as $fileInfo) {
                $scopedRequire($fileInfo->getPathname());
            }
        }

        // Call the TcaMigration and log any deprecations.
        $tcaMigration = GeneralUtility::makeInstance(TcaMigration::class);
        $GLOBALS['TCA'] = $tcaMigration->migrate($GLOBALS['TCA']);
        $messages = $tcaMigration->getMessages();
        if (!empty($messages)) {
            $context = 'Automatic TCA migration done during bootstrap. Please adapt TCA accordingly, these migrations'
                . ' will be removed. The backend module "Configuration -> TCA" shows the modified values.'
                . ' Please adapt these areas:';
            array_unshift($messages, $context);
            trigger_error(implode(LF, $messages), E_USER_DEPRECATED);
        }

        // TCA preparation
        $tcaPreparation = GeneralUtility::makeInstance(TcaPreparation::class);
        $GLOBALS['TCA'] = $tcaPreparation->prepare($GLOBALS['TCA']);

        static::dispatchTcaIsBeingBuiltEvent($GLOBALS['TCA']);
    }

    /**
     * Triggers an event for manipulating the final TCA
     */
    protected static function dispatchTcaIsBeingBuiltEvent(array $tca): void
    {
        $GLOBALS['TCA'] = static::$eventDispatcher->dispatch(new AfterTcaCompilationEvent($tca))->getTca();
    }

    /**
     * Cache base $GLOBALS['TCA'] to cache file to require the whole thing in one
     * file for next access instead of cycling through all extensions again.
     *
     * @internal
     */
    public static function createBaseTcaCacheFile(FrontendInterface $codeCache): void
    {
        $codeCache->set(
            static::getBaseTcaCacheIdentifier(),
            'return '
                . var_export(['tca' => $GLOBALS['TCA']], true)
                . ';'
        );
    }

    /**
     * Cache identifier of base TCA cache entry.
     */
    protected static function getBaseTcaCacheIdentifier(): string
    {
        return (new PackageDependentCacheIdentifier(self::$packageManager))->withPrefix('tca_base')->toString();
    }

    /**
     * Execute all ext_tables.php files of loaded extensions.
     * The method implements an optionally used caching mechanism that concatenates all
     * ext_tables.php files in one file.
     *
     * This is an internal method. It is only used during bootstrap and
     * extensions should not use it!
     *
     * @param bool $allowCaching Whether to load / create concatenated cache file
     * @internal
     */
    public static function loadExtTables(bool $allowCaching = true, FrontendInterface $codeCache = null): void
    {
        if ($allowCaching && !self::$extTablesWasReadFromCacheOnce) {
            self::$extTablesWasReadFromCacheOnce = true;
            $cacheIdentifier = self::getExtTablesCacheIdentifier();
            $codeCache = $codeCache ?? self::getCacheManager()->getCache('core');
            $hasCache = $codeCache->require($cacheIdentifier) !== false;
            if (!$hasCache) {
                self::loadSingleExtTablesFiles();
                self::createExtTablesCacheEntry($codeCache);
            }
        } else {
            self::loadSingleExtTablesFiles();
        }
    }

    /**
     * Load ext_tables.php as single files
     */
    protected static function loadSingleExtTablesFiles(): void
    {
        // Load each ext_tables.php file of loaded extensions
        foreach (static::$packageManager->getActivePackages() as $package) {
            $extTablesPath = $package->getPackagePath() . 'ext_tables.php';
            if (@file_exists($extTablesPath)) {
                require $extTablesPath;
            }
        }
    }

    /**
     * Create concatenated ext_tables.php cache file
     *
     * @internal
     */
    public static function createExtTablesCacheEntry(FrontendInterface $codeCache): void
    {
        $phpCodeToCache = [];
        // Set same globals as in loadSingleExtTablesFiles()
        $phpCodeToCache[] = '/**';
        $phpCodeToCache[] = ' * Compiled ext_tables.php cache file';
        $phpCodeToCache[] = ' */';
        $phpCodeToCache[] = '';
        // Iterate through loaded extensions and add ext_tables content
        foreach (static::$packageManager->getActivePackages() as $package) {
            $extensionKey = $package->getPackageKey();
            $extTablesPath = $package->getPackagePath() . 'ext_tables.php';
            if (@file_exists($extTablesPath)) {
                // Include a header per extension to make the cache file more readable
                $phpCodeToCache[] = '/**';
                $phpCodeToCache[] = ' * Extension: ' . $extensionKey;
                $phpCodeToCache[] = ' * File: ' . $extTablesPath;
                $phpCodeToCache[] = ' */';
                $phpCodeToCache[] = '';
                // Add ext_tables.php content of extension
                $phpCodeToCache[] = 'namespace {';
                $phpCodeToCache[] = trim((string)file_get_contents($extTablesPath));
                $phpCodeToCache[] = '}';
                $phpCodeToCache[] = '';
            }
        }
        $phpCodeToCache = implode(LF, $phpCodeToCache);
        // Remove all start and ending php tags from content
        $phpCodeToCache = preg_replace('/<\\?php|\\?>/is', '', $phpCodeToCache);
        $phpCodeToCache = preg_replace('/declare\\s?+\\(\\s?+strict_types\\s?+=\\s?+1\\s?+\\);/is', '', (string)$phpCodeToCache);
        $codeCache->set(self::getExtTablesCacheIdentifier(), $phpCodeToCache);
    }

    /**
     * Cache identifier for concatenated ext_tables.php files
     */
    protected static function getExtTablesCacheIdentifier(): string
    {
        return (new PackageDependentCacheIdentifier(self::$packageManager))->withPrefix('ext_tables')->toString();
    }

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
