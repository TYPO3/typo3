<?php
namespace TYPO3\CMS\Core\Utility;

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

use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Category\CategoryRegistry;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Migrations\TcaMigration;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Preparations\TcaPreparation;

/**
 * Extension Management functions
 *
 * This class is never instantiated, rather the methods inside is called as functions like
 * \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('my_extension');
 */
class ExtensionManagementUtility
{
    /**
     * @var array
     */
    protected static $extensionKeyMap;

    /**
     * TRUE, if ext_tables file was read from cache for this script run.
     * The frontend tends to do that multiple times, but the caching framework does
     * not allow this (via a require_once call). This variable is used to track
     * the access to the cache file to read the single ext_tables.php if it was
     * already read from cache
     *
     * @todo See if we can get rid of the 'load multiple times' scenario in fe
     * @var bool
     */
    protected static $extTablesWasReadFromCacheOnce = false;

    /**
     * @var PackageManager
     */
    protected static $packageManager;

    /**
     * Sets the package manager for all that backwards compatibility stuff,
     * so it doesn't have to be fetched through the bootstap.
     *
     * @param PackageManager $packageManager
     * @internal
     */
    public static function setPackageManager(PackageManager $packageManager)
    {
        static::$packageManager = $packageManager;
    }

    /**
     * @var \TYPO3\CMS\Core\Cache\CacheManager
     */
    protected static $cacheManager;

    /**
     * Getter for the cache manager
     *
     * @return \TYPO3\CMS\Core\Cache\CacheManager
     */
    protected static function getCacheManager()
    {
        if (static::$cacheManager === null) {
            static::$cacheManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
        }
        return static::$cacheManager;
    }

    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected static $signalSlotDispatcher;

    /**
     * Getter for the signal slot dispatcher
     *
     * @return \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected static function getSignalSlotDispatcher()
    {
        if (static::$signalSlotDispatcher === null) {
            static::$signalSlotDispatcher = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
        }
        return static::$signalSlotDispatcher;
    }

    /**************************************
     *
     * PATHS and other evaluation
     *
     ***************************************/
    /**
     * Returns TRUE if the extension with extension key $key is loaded.
     *
     * @param string $key Extension key to test
     * @param bool $exitOnError If $exitOnError is TRUE and the extension is not loaded the function will die with an error message, this is deprecated and will be removed in TYPO3 v10.0.
     * @return bool
     * @throws \BadFunctionCallException
     */
    public static function isLoaded($key, $exitOnError = null)
    {
        // safety net for extensions checking for "EXT:version", can be removed in TYPO3 v10.0.
        if ($key === 'version') {
            trigger_error('EXT:version has been moved into EXT:workspaces, you should check against "workspaces", as this might lead to unexpected behaviour in the future.', E_USER_DEPRECATED);
            $key = 'workspaces';
        }
        if ($key === 'sv') {
            trigger_error('EXT:sv has been moved into EXT:core, you should remove your check as code is always loaded, as this might lead to unexpected behaviour in the future.', E_USER_DEPRECATED);
            return true;
        }
        if ($key === 'saltedpasswords') {
            trigger_error('EXT:saltedpasswords has been moved into EXT:core, you should remove your check as code is always loaded, as this might lead to unexpected behaviour in the future.', E_USER_DEPRECATED);
            return true;
        }
        if ($exitOnError !== null) {
            trigger_error('Calling ExtensionManagementUtility::isLoaded() with a second argument via "exitOnError" will be removed in TYPO3 v10.0, handle an unloaded package yourself in the future.', E_USER_DEPRECATED);
        }
        $isLoaded = static::$packageManager->isPackageActive($key);
        if ($exitOnError && !$isLoaded) {
            // @deprecated, once $exitOnError is gone, this check can be removed.
            throw new \BadFunctionCallException('TYPO3 Fatal Error: Extension "' . $key . '" is not loaded!', 1270853910);
        }
        return $isLoaded;
    }

    /**
     * Returns the absolute path to the extension with extension key $key.
     *
     * @param string $key Extension key
     * @param string $script $script is appended to the output if set.
     * @throws \BadFunctionCallException
     * @return string
     */
    public static function extPath($key, $script = '')
    {
        if (!static::$packageManager->isPackageActive($key)) {
            throw new \BadFunctionCallException('TYPO3 Fatal Error: Extension key "' . $key . '" is NOT loaded!', 1365429656);
        }
        return static::$packageManager->getPackage($key)->getPackagePath() . $script;
    }

    /**
     * Returns the relative path to the extension as measured from the public web path
     * If the extension is not loaded the function will die with an error message
     * Useful for images and links from the frontend
     *
     * @param string $key Extension key
     * @return string
     * @deprecated use extPath() or GeneralUtility::getFileAbsFileName() together with PathUtility::getAbsoluteWebPath() instead.
     */
    public static function siteRelPath($key)
    {
        trigger_error('ExtensionManagementUtility::siteRelPath() will be removed in TYPO3 v10.0, use extPath() in conjunction with PathUtility::getAbsoluteWebPath() instead.', E_USER_DEPRECATED);
        return PathUtility::stripPathSitePrefix(self::extPath($key));
    }

    /**
     * Returns the correct class name prefix for the extension key $key
     *
     * @param string $key Extension key
     * @return string
     * @internal
     */
    public static function getCN($key)
    {
        return strpos($key, 'user_') === 0 ? 'user_' . str_replace('_', '', substr($key, 5)) : 'tx_' . str_replace('_', '', $key);
    }

    /**
     * Returns the real extension key like 'tt_news' from an extension prefix like 'tx_ttnews'.
     *
     * @param string $prefix The extension prefix (e.g. 'tx_ttnews')
     * @return mixed Real extension key (string)or FALSE (bool) if something went wrong
     * @deprecated since TYPO3 v9, just use the proper extension key directly
     */
    public static function getExtensionKeyByPrefix($prefix)
    {
        trigger_error('ExtensionManagementUtility::getExtensionKeyByPrefix() will be removed in TYPO3 v10.0. Use extension keys directly.', E_USER_DEPRECATED);
        $result = false;
        // Build map of short keys referencing to real keys:
        if (!isset(self::$extensionKeyMap)) {
            self::$extensionKeyMap = [];
            foreach (static::$packageManager->getActivePackages() as $package) {
                $shortKey = str_replace('_', '', $package->getPackageKey());
                self::$extensionKeyMap[$shortKey] = $package->getPackageKey();
            }
        }
        // Lookup by the given short key:
        $parts = explode('_', $prefix);
        if (isset(self::$extensionKeyMap[$parts[1]])) {
            $result = self::$extensionKeyMap[$parts[1]];
        }
        return $result;
    }

    /**
     * Clears the extension key map.
     */
    public static function clearExtensionKeyMap()
    {
        self::$extensionKeyMap = null;
    }

    /**
     * Retrieves the version of an installed extension.
     * If the extension is not installed, this function returns an empty string.
     *
     * @param string $key The key of the extension to look up, must not be empty
     *
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Core\Package\Exception
     * @return string The extension version as a string in the format "x.y.z",
     */
    public static function getExtensionVersion($key)
    {
        if (!is_string($key) || empty($key)) {
            throw new \InvalidArgumentException('Extension key must be a non-empty string.', 1294586096);
        }
        if (!static::isLoaded($key)) {
            return '';
        }
        $version = static::$packageManager->getPackage($key)->getPackageMetaData()->getVersion();
        if (empty($version)) {
            throw new \TYPO3\CMS\Core\Package\Exception('Version number in composer manifest of package "' . $key . '" is missing or invalid', 1395614959);
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
    public static function addTCAcolumns($table, $columnArray)
    {
        if (is_array($columnArray) && is_array($GLOBALS['TCA'][$table]) && is_array($GLOBALS['TCA'][$table]['columns'])) {
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
     * @param string $typeList List of specific types to add the field list to. (If empty, all type entries are affected)
     * @param string $position Insert fields before (default) or after one, or replace a field
     */
    public static function addToAllTCAtypes($table, $newFieldsString, $typeList = '', $position = '')
    {
        $newFieldsString = trim($newFieldsString);
        if ($newFieldsString === '' || !is_array($GLOBALS['TCA'][$table]['types'] ?? false)) {
            return;
        }
        if ($position !== '') {
            list($positionIdentifier, $entityName) = GeneralUtility::trimExplode(':', $position);
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
                                    if (preg_match('/\\b' . $entityName . '\\b/', $palette['showitem']) > 0) {
                                        $newPosition = $positionIdentifier . ':--palette--;;' . $paletteName;
                                    }
                                    break;
                                case 'replace':
                                    // check if fields have been added to palette before
                                    if (isset($palettesChanged[$paletteName])) {
                                        $fieldExists = true;
                                        continue 2;
                                    }
                                    if (preg_match('/\\b' . $entityName . '\\b/', $palette['showitem']) > 0) {
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
     * 		'aPallete' => array(
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
     * 		'aPallete' => array(
     * 			'showitem' => 'fieldB, newA, fieldC',
     * 		),
     * 	),
     * ),
     *
     * @param string $table Name of the table
     * @param string $field Name of the field that has the palette to be extended
     * @param string $addFields List of fields to be added to the palette
     * @param string $insertionPosition Insert fields before (default) or after one
     */
    public static function addFieldsToAllPalettesOfField($table, $field, $addFields, $insertionPosition = '')
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
            if (!isset($typeArray['showitem']) || strpos($typeArray['showitem'], $field) === false) {
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
                    && strpos($fieldArrayWithOptions[$fieldNumber + 1], '--palette--') === 0
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
     * @param string $addFields List of fields to be added to the palette
     * @param string $insertionPosition Insert fields before (default) or after one
     */
    public static function addFieldsToPalette($table, $palette, $addFields, $insertionPosition = '')
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
     * @throws \InvalidArgumentException If given parameters are not of correct
     * @throws \RuntimeException If reference to related position fields can not
     * @param string $table Name of TCA table
     * @param string $field Name of TCA field
     * @param array $item New item to add
     * @param string $relativeToField Add item relative to existing field
     * @param string $relativePosition Valid keywords: 'before', 'after'
     */
    public static function addTcaSelectItem($table, $field, array $item, $relativeToField = '', $relativePosition = '')
    {
        if (!is_string($table)) {
            throw new \InvalidArgumentException('Given table is of type "' . gettype($table) . '" but a string is expected.', 1303236963);
        }
        if (!is_string($field)) {
            throw new \InvalidArgumentException('Given field is of type "' . gettype($field) . '" but a string is expected.', 1303236964);
        }
        if (!is_string($relativeToField)) {
            throw new \InvalidArgumentException('Given relative field is of type "' . gettype($relativeToField) . '" but a string is expected.', 1303236965);
        }
        if (!is_string($relativePosition)) {
            throw new \InvalidArgumentException('Given relative position is of type "' . gettype($relativePosition) . '" but a string is expected.', 1303236966);
        }
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
     * Gets the TCA configuration for a field handling (FAL) files.
     *
     * @param string $fieldName Name of the field to be used
     * @param array $customSettingOverride Custom field settings overriding the basics
     * @param string $allowedFileExtensions Comma list of allowed file extensions (e.g. "jpg,gif,pdf")
     * @param string $disallowedFileExtensions
     *
     * @return array
     */
    public static function getFileFieldTCAConfig($fieldName, array $customSettingOverride = [], $allowedFileExtensions = '', $disallowedFileExtensions = '')
    {
        $fileFieldTCAConfig = [
            'type' => 'inline',
            'foreign_table' => 'sys_file_reference',
            'foreign_field' => 'uid_foreign',
            'foreign_sortby' => 'sorting_foreign',
            'foreign_table_field' => 'tablenames',
            'foreign_match_fields' => [
                'fieldname' => $fieldName
            ],
            'foreign_label' => 'uid_local',
            'foreign_selector' => 'uid_local',
            'overrideChildTca' => [
                'columns' => [
                    'uid_local' => [
                        'config' => [
                            'appearance' => [
                                'elementBrowserType' => 'file',
                                'elementBrowserAllowed' => $allowedFileExtensions
                            ],
                        ],
                    ],
                ],
            ],
            'filter' => [
                [
                    'userFunc' => \TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter::class . '->filterInlineChildren',
                    'parameters' => [
                        'allowedFileExtensions' => $allowedFileExtensions,
                        'disallowedFileExtensions' => $disallowedFileExtensions
                    ]
                ]
            ],
            'appearance' => [
                'useSortable' => true,
                'headerThumbnail' => [
                    'field' => 'uid_local',
                    'width' => '45',
                    'height' => '45c',
                ],

                'enabledControls' => [
                    'info' => true,
                    'new' => false,
                    'dragdrop' => true,
                    'sort' => false,
                    'hide' => true,
                    'delete' => true,
                ],
            ]
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
    public static function addFieldsToUserSettings($addFields, $insertionPosition = '')
    {
        $GLOBALS['TYPO3_USER_SETTINGS']['showitem'] = self::executePositionedStringInsertion($GLOBALS['TYPO3_USER_SETTINGS']['showitem'], $addFields, $insertionPosition);
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
    protected static function executePositionedStringInsertion($list, $insertionList, $insertionPosition = '')
    {
        $list = $newList = trim($list, ", \t\n\r\0\x0B");

        if ($insertionPosition !== '') {
            list($location, $positionName) = GeneralUtility::trimExplode(':', $insertionPosition, false, 2);
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
        if (strpos($positionName, ';;') !== false) {
            $positionName = str_replace(';;', ';[^;]*;', $positionName);
        }

        $pattern = ('/(^|,\\s*)(' . $positionName . ')(;[^,$]+)?(,|$)/');
        switch ($location) {
            case 'after':
                $newList = preg_replace($pattern, '$1$2$3, ' . $insertionList . '$4', $list);
                break;
            case 'before':
                $newList = preg_replace($pattern, '$1' . $insertionList . ', $2$3$4', $list);
                break;
            case 'replace':
                $newList = preg_replace($pattern, '$1' . $insertionList . '$4', $list);
                break;
            default:
        }

        // When preg_replace did not replace anything; append the $insertionList.
        if ($list === $newList) {
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
     * @param string $insertionList The list of items to inserted
     * @param string $list The list of items to be extended (default: '')
     * @return string Duplicate-free list of items to be inserted
     */
    protected static function removeDuplicatesForInsertion($insertionList, $list = '')
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
     * Generates an array of fields/items with additional information such as e.g. the name of the palette.
     *
     * @param string $itemList List of fields/items to be splitted up
     * @return array An array with the names of the fields/items as keys and additional information
     */
    protected static function explodeItemList($itemList)
    {
        $items = [];
        $itemParts = GeneralUtility::trimExplode(',', $itemList, true);
        foreach ($itemParts as $itemPart) {
            $itemDetails = GeneralUtility::trimExplode(';', $itemPart, false, 5);
            $key = $itemDetails[0];
            if (strpos($key, '--') !== false) {
                // If $key is a separator (--div--) or palette (--palette--) then it will be appended by a unique number. This must be removed again when using this value!
                $key .= count($items);
            }
            if (!isset($items[$key])) {
                $items[$key] = [
                    'rawData' => $itemPart,
                    'details' => []
                ];
                $details = [0 => 'field', 1 => 'label', 2 => 'palette'];
                foreach ($details as $id => $property) {
                    $items[$key]['details'][$property] = $itemDetails[$id] ?? '';
                }
            }
        }
        return $items;
    }

    /**
     * Generates a list of fields/items out of an array provided by the function getFieldsOfFieldList().
     *
     * @see explodeItemList
     * @param array $items The array of fields/items with optional additional information
     * @param bool $useRawData Use raw data instead of building by using the details (default: FALSE)
     * @return string The list of fields/items which gets used for $GLOBALS['TCA'][<table>]['types'][<type>]['showitem']
     */
    protected static function generateItemList(array $items, $useRawData = false)
    {
        $itemParts = [];
        foreach ($items as $item => $itemDetails) {
            if (strpos($item, '--') !== false) {
                // If $item is a separator (--div--) or palette (--palette--) then it may have been appended by a unique number. This must be stripped away here.
                $item = str_replace([0, 1, 2, 3, 4, 5, 6, 7, 8, 9], '', $item);
            }
            if ($useRawData) {
                $itemParts[] = $itemDetails['rawData'];
            } else {
                if (count($itemDetails['details']) > 1) {
                    $details = ['palette', 'label', 'field'];
                    $elements = [];
                    $addEmpty = false;
                    foreach ($details as $property) {
                        if ($itemDetails['details'][$property] !== '' || $addEmpty) {
                            $addEmpty = true;
                            array_unshift($elements, $itemDetails['details'][$property]);
                        }
                    }
                    $item = implode(';', $elements);
                }
                $itemParts[] = $item;
            }
        }
        return implode(', ', $itemParts);
    }

    /**
     * Add tablename to default list of allowed tables on pages (in $PAGES_TYPES)
     * Will add the $table to the list of tables allowed by default on pages as setup by $PAGES_TYPES['default']['allowedTables']
     * FOR USE IN ext_tables.php FILES
     *
     * @param string $table Table name
     */
    public static function allowTableOnStandardPages($table)
    {
        $GLOBALS['PAGES_TYPES']['default']['allowedTables'] .= ',' . $table;
    }

    /**
     * This method is called from \TYPO3\CMS\Backend\Module\ModuleLoader::checkMod
     * and it replaces old conf.php.
     *
     * @param string $moduleSignature The module name
     * @return array Configuration of the module
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, addModule() works the same way nowadays.
     */
    public static function configureModule($moduleSignature)
    {
        trigger_error('ExtensionManagementUtility::configureModule will be removed in TYPO3 v10.0, as the same functionality is found in addModule() as well.', E_USER_DEPRECATED);
        $moduleConfiguration = $GLOBALS['TBE_MODULES']['_configuration'][$moduleSignature];

        // Register the icon and move it too "iconIdentifier"
        if (!empty($moduleConfiguration['icon'])) {
            $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
            $iconIdentifier = 'module-' . $moduleSignature;
            $iconProvider = $iconRegistry->detectIconProvider($moduleConfiguration['icon']);
            $iconRegistry->registerIcon(
                $iconIdentifier,
                $iconProvider,
                ['source' => GeneralUtility::getFileAbsFileName($moduleConfiguration['icon'])]
            );
            $moduleConfiguration['iconIdentifier'] = $iconIdentifier;
            unset($moduleConfiguration['icon']);
        }

        return $moduleConfiguration;
    }

    /**
     * Adds a module (main or sub) to the backend interface
     * FOR USE IN ext_tables.php FILES
     *
     * @param string $main The main module key, $sub is the submodule key. So $main would be an index in the $TBE_MODULES array and $sub could be an element in the lists there.
     * @param string $sub The submodule key. If $sub is not set a blank $main module is created.
     * @param string $position Can be used to set the position of the $sub module within the list of existing submodules for the main module. $position has this syntax: [cmd]:[submodule-key]. cmd can be "after", "before" or "top" (or blank which is default). If "after"/"before" then submodule will be inserted after/before the existing submodule with [submodule-key] if found. If not found, the bottom of list. If "top" the module is inserted in the top of the submodule list.
     * @param string $path The absolute path to the module. Was used prior to TYPO3 v8, use $moduleConfiguration[routeTarget] now
     * @param array $moduleConfiguration additional configuration, previously put in "conf.php" of the module directory
     */
    public static function addModule($main, $sub = '', $position = '', $path = null, $moduleConfiguration = [])
    {
        if (($moduleConfiguration['navigationComponentId'] ?? '') === 'typo3-pagetree') {
            trigger_error(
                'Referencing the navigation component ID "typo3-pagetree" will be removed in TYPO3 v10.0.'
                . 'Use "TYPO3/CMS/Backend/PageTree/PageTreeElement" instead. Module key: ' . $main . '-' . $sub,
                E_USER_DEPRECATED
            );
            $moduleConfiguration['navigationComponentId'] = 'TYPO3/CMS/Backend/PageTree/PageTreeElement';
        }

        // If there is already a main module by this name:
        // Adding the submodule to the correct position:
        if (isset($GLOBALS['TBE_MODULES'][$main]) && $sub) {
            list($place, $modRef) = array_pad(GeneralUtility::trimExplode(':', $position, true), 2, null);
            $modules = ',' . $GLOBALS['TBE_MODULES'][$main] . ',';
            if ($place === null || ($modRef !== null && !GeneralUtility::inList($modules, $modRef))) {
                $place = 'bottom';
            }
            $modRef = ',' . $modRef . ',';
            if (!GeneralUtility::inList($modules, $sub)) {
                switch (strtolower($place)) {
                    case 'after':
                        $modules = str_replace($modRef, $modRef . $sub . ',', $modules);
                        break;
                    case 'before':
                        $modules = str_replace($modRef, ',' . $sub . $modRef, $modules);
                        break;
                    case 'top':
                        $modules = $sub . $modules;
                        break;
                    case 'bottom':
                    default:
                        $modules = $modules . $sub;
                }
            }
            // Re-inserting the submodule list:
            $GLOBALS['TBE_MODULES'][$main] = trim($modules, ',');
        } else {
            // Create new main modules with only one submodule, $sub (or none if $sub is blank)
            $GLOBALS['TBE_MODULES'][$main] = $sub;
        }

        // add additional configuration
        $fullModuleSignature = $main . ($sub ? '_' . $sub : '');
        if (is_array($moduleConfiguration) && !empty($moduleConfiguration)) {
            // remove default icon if an icon identifier is available
            if (!empty($moduleConfiguration['iconIdentifier']) && $moduleConfiguration['icon'] === 'EXT:extbase/Resources/Public/Icons/Extension.png') {
                unset($moduleConfiguration['icon']);
            }
            if (!empty($moduleConfiguration['icon'])) {
                $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
                $iconIdentifier = 'module-' . $fullModuleSignature;
                $iconProvider = $iconRegistry->detectIconProvider($moduleConfiguration['icon']);
                $iconRegistry->registerIcon(
                    $iconIdentifier,
                    $iconProvider,
                    ['source' => GeneralUtility::getFileAbsFileName($moduleConfiguration['icon'])]
                );
                $moduleConfiguration['iconIdentifier'] = $iconIdentifier;
                unset($moduleConfiguration['icon']);
            }

            $GLOBALS['TBE_MODULES']['_configuration'][$fullModuleSignature] = $moduleConfiguration;
        }

        // Also register the module as regular route
        $routeName = $moduleConfiguration['id'] ?? $fullModuleSignature;
        // Build Route objects from the data
        $path = $moduleConfiguration['path'] ?? str_replace('_', '/', $fullModuleSignature);
        $path = '/' . trim($path, '/') . '/';

        $options = [
            'module' => true,
            'moduleName' => $fullModuleSignature,
            'access' => !empty($moduleConfiguration['access']) ? $moduleConfiguration['access'] : 'user,group'
        ];
        if (!empty($moduleConfiguration['routeTarget'])) {
            $options['target'] = $moduleConfiguration['routeTarget'];
        }

        $router = GeneralUtility::makeInstance(Router::class);
        $router->addRoute($routeName, GeneralUtility::makeInstance(Route::class, $path, $options));
    }

    /**
     * Adds a "Function menu module" ('third level module') to an existing function menu for some other backend module
     * The arguments values are generally determined by which function menu this is supposed to interact with
     * See Inside TYPO3 for information on how to use this function.
     * FOR USE IN ext_tables.php FILES
     *
     * @param string $modname Module name
     * @param string $className Class name
     * @param string $_ unused
     * @param string $title Title of module
     * @param string $MM_key Menu array key - default is "function
     * @param string $WS Workspace conditions. Blank means all workspaces, any other string can be a comma list of "online", "offline" and "custom
     * @see \TYPO3\CMS\Backend\Module\BaseScriptClass::mergeExternalItems()
     */
    public static function insertModuleFunction($modname, $className, $_ = null, $title, $MM_key = 'function', $WS = '')
    {
        $GLOBALS['TBE_MODULES_EXT'][$modname]['MOD_MENU'][$MM_key][$className] = [
            'name' => $className,
            'title' => $title,
            'ws' => $WS
        ];
    }

    /**
     * Adds $content to the default Page TSconfig as set in $GLOBALS['TYPO3_CONF_VARS'][BE]['defaultPageTSconfig']
     * Prefixed with a [GLOBAL] line
     * FOR USE IN ext_localconf.php FILE
     *
     * @param string $content Page TSconfig content
     */
    public static function addPageTSConfig($content)
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'] .= '
[GLOBAL]
' . $content;
    }

    /**
     * Adds $content to the default User TSconfig as set in $GLOBALS['TYPO3_CONF_VARS'][BE]['defaultUserTSconfig']
     * Prefixed with a [GLOBAL] line
     * FOR USE IN ext_localconf.php FILE
     *
     * @param string $content User TSconfig content
     */
    public static function addUserTSConfig($content)
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'] .= '
[GLOBAL]
' . $content;
    }

    /**
     * Adds a reference to a locallang file with $GLOBALS['TCA_DESCR'] labels
     * FOR USE IN ext_tables.php FILES
     * eg. \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('pages', 'EXT:core/Resources/Private/Language/locallang_csh_pages.xlf'); for the pages table or \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_layout', 'EXT:frontend/Resources/Private/Language/locallang_csh_weblayout.xlf'); for the Web > Page module.
     *
     * @param string $key Description key. Typically a database table (like "pages") but for applications can be other strings, but prefixed with "_MOD_")
     * @param string $file File reference to locallang file, eg. "EXT:core/Resources/Private/Language/locallang_csh_pages.xlf" (or ".xml")
     */
    public static function addLLrefForTCAdescr($key, $file)
    {
        if (empty($key)) {
            throw new \RuntimeException('No description key set in addLLrefForTCAdescr(). Provide it as first parameter', 1507321596);
        }
        if (!is_array($GLOBALS['TCA_DESCR'][$key] ?? false)) {
            $GLOBALS['TCA_DESCR'][$key] = [];
        }
        if (!is_array($GLOBALS['TCA_DESCR'][$key]['refs'] ?? false)) {
            $GLOBALS['TCA_DESCR'][$key]['refs'] = [];
        }
        $GLOBALS['TCA_DESCR'][$key]['refs'][] = $file;
    }

    /**
     * Registers a navigation component e.g. page tree
     *
     * @param string $module
     * @param string $componentId componentId is also an RequireJS module name e.g. 'TYPO3/CMS/MyExt/MyNavComponent'
     * @param string $extensionKey
     * @throws \RuntimeException
     */
    public static function addNavigationComponent($module, $componentId, $extensionKey)
    {
        if (empty($extensionKey)) {
            throw new \RuntimeException('No extensionKey set in addNavigationComponent(). Provide it as third parameter', 1404068039);
        }
        $GLOBALS['TBE_MODULES']['_navigationComponents'][$module] = [
            'componentId' => $componentId,
            'extKey' => $extensionKey,
            'isCoreComponent' => false
        ];
    }

    /**
     * Registers a core navigation component
     *
     * @param string $module
     * @param string $componentId
     */
    public static function addCoreNavigationComponent($module, $componentId)
    {
        self::addNavigationComponent($module, $componentId, 'core');
        $GLOBALS['TBE_MODULES']['_navigationComponents'][$module]['isCoreComponent'] = true;
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
    public static function addService($extKey, $serviceType, $serviceKey, $info)
    {
        if (!$serviceType) {
            throw new \InvalidArgumentException('No serviceType given.', 1507321535);
        }
        if (!is_array($info)) {
            throw new \InvalidArgumentException('No information array given.', 1507321542);
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
        if ($GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['available'] && $GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['os'] != '') {
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
     * @param mixed $excludeServiceKeys Service keys that should be excluded in the search for a service. Array or comma list.
     * @return mixed Service info array if a service was found, FALSE otherwise
     */
    public static function findService($serviceType, $serviceSubType = '', $excludeServiceKeys = [])
    {
        $serviceKey = false;
        $serviceInfo = false;
        $priority = 0;
        $quality = 0;
        if (!is_array($excludeServiceKeys)) {
            $excludeServiceKeys = GeneralUtility::trimExplode(',', $excludeServiceKeys, true);
        }
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
                if ($info['available'] && ($info['subtype'] == $serviceSubType || $info['serviceSubTypes'][$serviceSubType]) && $info['priority'] >= $priority) {
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
    public static function findServiceByKey($serviceKey)
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
    public static function isServiceAvailable($serviceType, $serviceKey, $serviceDetails)
    {
        // If the service depends on external programs - check if they exists
        if (trim($serviceDetails['exec'])) {
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
    public static function deactivateService($serviceType, $serviceKey)
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
     * @param array $itemArray Numerical array: [0] => Plugin label, [1] => Plugin identifier / plugin key, ideally prefixed with a extension-specific name (e.g. "events2_list"), [2] => Path to plugin icon relative to TYPO3_mainDir
     * @param string $type Type (eg. "list_type") - basically a field from "tt_content" table
     * @param string $extensionKey The extension key
     * @throws \RuntimeException
     */
    public static function addPlugin($itemArray, $type = 'list_type', $extensionKey = null)
    {
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
        if (!isset($itemArray[2]) || !$itemArray[2]) {
            // @todo do we really set $itemArray[2], even if we cannot find an icon? (as that means it's set to 'EXT:foobar/')
            $itemArray[2] = 'EXT:' . $extensionKey . '/' . static::getExtensionIcon(static::$packageManager->getPackage($extensionKey)->getPackagePath());
        }
        if (is_array($GLOBALS['TCA']['tt_content']['columns']) && is_array($GLOBALS['TCA']['tt_content']['columns'][$type]['config']['items'])) {
            foreach ($GLOBALS['TCA']['tt_content']['columns'][$type]['config']['items'] as $k => $v) {
                if ((string)$v[1] === (string)$itemArray[1]) {
                    $GLOBALS['TCA']['tt_content']['columns'][$type]['config']['items'][$k] = $itemArray;
                    return;
                }
            }
            $GLOBALS['TCA']['tt_content']['columns'][$type]['config']['items'][] = $itemArray;
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
    public static function addPiFlexFormValue($piKeyToMatch, $value, $CTypeToMatch = 'list')
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
    public static function addToInsertRecords($table, $content_table = 'tt_content', $content_field = 'records')
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
     * + menu_type - a "Menu/Sitemap" entry
     * + CType - a new content element type
     * + header_layout - an additional header type (added to the selection of layout1-5)
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
    public static function addPItoST43($key, $_ = '', $suffix = '', $type = 'list_type', $cacheable = false)
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
            case 'menu_type':
                $addLine = 'tt_content.menu.20.' . $key . $suffix . ' = < plugin.' . $cN . $suffix;
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
            case 'header_layout':
                $addLine = 'lib.stdheader.10.' . $key . $suffix . ' = < plugin.' . $cN . $suffix;
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
     * @param string $path Is the path where the template files (fixed names) include_static.txt, constants.txt, setup.txt, and include_static_file.txt is found (relative to extPath, eg. 'static/'). The file include_static_file.txt, allows you to include other static templates defined in files, from your static template, and thus corresponds to the field 'include_static_file' in the sys_template table. The syntax for this is a comma separated list of static templates to include, like:  EXT:fluid_styled_content/Configuration/TypoScript/,EXT:da_newsletter_subscription/static/,EXT:cc_random_image/pi2/static/
     * @param string $title Is the title in the selector box.
     * @throws \InvalidArgumentException
     * @see addTypoScript()
     */
    public static function addStaticFile($extKey, $path, $title)
    {
        if (!$extKey) {
            throw new \InvalidArgumentException('No extension key given.', 1507321291);
        }
        if (!$path) {
            throw new \InvalidArgumentException('No file path given.', 1507321297);
        }
        if (is_array($GLOBALS['TCA']['sys_template']['columns'])) {
            $value = str_replace(',', '', 'EXT:' . $extKey . '/' . $path);
            $itemArray = [trim($title . ' (' . $extKey . ')'), $value];
            $GLOBALS['TCA']['sys_template']['columns']['include_static_file']['config']['items'][] = $itemArray;
        }
    }

    /**
     * Call this method to add an entry in the pageTSconfig list found in pages
     * FOR USE in Configuration/TCA/Overrides/pages.php
     *
     * @param string $extKey The extension key
     * @param string $filePath The path where the TSconfig file is located
     * @param string $title The title in the selector box
     * @throws \InvalidArgumentException
     */
    public static function registerPageTSConfigFile($extKey, $filePath, $title)
    {
        if (!$extKey) {
            throw new \InvalidArgumentException('No extension key given.', 1447789490);
        }
        if (!$filePath) {
            throw new \InvalidArgumentException('No file path given.', 1447789491);
        }
        if (!isset($GLOBALS['TCA']['pages']['columns']) || !is_array($GLOBALS['TCA']['pages']['columns'])) {
            throw new \InvalidArgumentException('No TCA definition for table "pages".', 1447789492);
        }

        $value = str_replace(',', '', 'EXT:' . $extKey . '/' . $filePath);
        $itemArray = [trim($title . ' (' . $extKey . ')'), $value];
        $GLOBALS['TCA']['pages']['columns']['tsconfig_includes']['config']['items'][] = $itemArray;
    }

    /**
     * Adds $content to the default TypoScript setup code as set in $GLOBALS['TYPO3_CONF_VARS'][FE]['defaultTypoScript_setup']
     * Prefixed with a [GLOBAL] line
     * FOR USE IN ext_localconf.php FILES
     *
     * @param string $content TypoScript Setup string
     */
    public static function addTypoScriptSetup($content)
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup'] .= '
[GLOBAL]
' . $content;
    }

    /**
     * Adds $content to the default TypoScript constants code as set in $GLOBALS['TYPO3_CONF_VARS'][FE]['defaultTypoScript_constants']
     * Prefixed with a [GLOBAL] line
     * FOR USE IN ext_localconf.php FILES
     *
     * @param string $content TypoScript Constants string
     */
    public static function addTypoScriptConstants($content)
    {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants'] .= '
[GLOBAL]
' . $content;
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
     * @param int|string string pointing to the "key" of a static_file template ([reduced extension_key]/[local path]). The points is that the TypoScript you add is included only IF that static template is included (and in that case, right after). So effectively the TypoScript you set can specifically overrule settings from those static templates.
     * @throws \InvalidArgumentException
     */
    public static function addTypoScript(string $key, string $type, string $content, $afterStaticUid = 0)
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
            // If 'content (default)' is targeted (static uid 43),
            // the content is added after typoscript of type contentRendering, eg. fluid_styled_content, see EXT:frontend/TemplateService for more information on how the code is parsed
            if ($afterStaticUid === 'defaultContentRendering' || $afterStaticUid == 43) {
                if (!isset($GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type . '.']['defaultContentRendering'])) {
                    $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type . '.']['defaultContentRendering'] = '';
                }
                $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type . '.']['defaultContentRendering'] .= $content;
            } else {
                if (!isset($GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type . '.'][$afterStaticUid])) {
                    $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type . '.'][$afterStaticUid] = '';
                }
                $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type . '.'][$afterStaticUid] .= $content;
            }
        } else {
            if (!isset($GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type])) {
                $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type] = '';
            }
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
     *
     * @return string
     */
    public static function getExtensionIcon($extensionPath, $returnFullPath = false)
    {
        $icon = '';
        $locationsToCheckFor = [
            'Resources/Public/Icons/Extension.svg',
            'Resources/Public/Icons/Extension.png',
            'Resources/Public/Icons/Extension.gif',
            'ext_icon.svg',
            'ext_icon.png',
            'ext_icon.gif',
        ];
        foreach ($locationsToCheckFor as $fileLocation) {
            if (file_exists($extensionPath . $fileLocation)) {
                $icon = $fileLocation;
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
    public static function loadExtLocalconf($allowCaching = true, FrontendInterface $codeCache = null)
    {
        if ($allowCaching) {
            $codeCache = $codeCache ?? self::getCacheManager()->getCache('cache_core');
            $cacheIdentifier = self::getExtLocalconfCacheIdentifier();
            if ($codeCache->has($cacheIdentifier)) {
                $codeCache->require($cacheIdentifier);
            } else {
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
    protected static function loadSingleExtLocalconfFiles()
    {
        // This is the main array meant to be manipulated in the ext_localconf.php files
        // In general it is recommended to not rely on it to be globally defined in that
        // scope but to use $GLOBALS['TYPO3_CONF_VARS'] instead.
        // Nevertheless we define it here as global for backwards compatibility.
        global $TYPO3_CONF_VARS;
        foreach (static::$packageManager->getActivePackages() as $package) {
            $extLocalconfPath = $package->getPackagePath() . 'ext_localconf.php';
            if (@file_exists($extLocalconfPath)) {
                // $_EXTKEY and $_EXTCONF are available in ext_localconf.php
                // and are explicitly set in cached file as well
                $_EXTKEY = $package->getPackageKey();
                $_EXTCONF = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY] ?? null;
                require $extLocalconfPath;
            }
        }
    }

    /**
     * Create cache entry for concatenated ext_localconf.php files
     *
     * @param FrontendInterface $codeCache
     */
    protected static function createExtLocalconfCacheEntry(FrontendInterface $codeCache)
    {
        $phpCodeToCache = [];
        // Set same globals as in loadSingleExtLocalconfFiles()
        $phpCodeToCache[] = '/**';
        $phpCodeToCache[] = ' * Compiled ext_localconf.php cache file';
        $phpCodeToCache[] = ' */';
        $phpCodeToCache[] = '';
        $phpCodeToCache[] = 'global $TYPO3_CONF_VARS, $T3_SERVICES, $T3_VAR;';
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
                // Set $_EXTKEY and $_EXTCONF for this extension
                $phpCodeToCache[] = '$_EXTKEY = \'' . $extensionKey . '\';';
                $phpCodeToCache[] = '$_EXTCONF = $GLOBALS[\'TYPO3_CONF_VARS\'][\'EXT\'][\'extConf\'][$_EXTKEY] ?? null;';
                $phpCodeToCache[] = '';
                // Add ext_localconf.php content of extension
                $phpCodeToCache[] = trim(file_get_contents($extLocalconfPath));
                $phpCodeToCache[] = '';
                $phpCodeToCache[] = '';
            }
        }
        $phpCodeToCache = implode(LF, $phpCodeToCache);
        // Remove all start and ending php tags from content
        $phpCodeToCache = preg_replace('/<\\?php|\\?>/is', '', $phpCodeToCache);
        $codeCache->set(self::getExtLocalconfCacheIdentifier(), $phpCodeToCache);
    }

    /**
     * Cache identifier of concatenated ext_localconf file
     *
     * @return string
     */
    protected static function getExtLocalconfCacheIdentifier()
    {
        return 'ext_localconf_' . sha1(TYPO3_version . Environment::getProjectPath() . 'extLocalconf' . serialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['runtimeActivatedPackages']));
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
    public static function loadBaseTca($allowCaching = true, FrontendInterface $codeCache = null)
    {
        if ($allowCaching) {
            $codeCache = $codeCache ?? self::getCacheManager()->getCache('cache_core');
            $cacheIdentifier = static::getBaseTcaCacheIdentifier();
            $cacheData = $codeCache->require($cacheIdentifier);
            if ($cacheData) {
                $GLOBALS['TCA'] = $cacheData['tca'];
                GeneralUtility::setSingletonInstance(
                    CategoryRegistry::class,
                    unserialize(
                        $cacheData['categoryRegistry'],
                        ['allowed_classes' => [CategoryRegistry::class]]
                    )
                );
            } else {
                static::buildBaseTcaFromSingleFiles();
                static::createBaseTcaCacheFile($codeCache);
            }
        } else {
            static::buildBaseTcaFromSingleFiles();
        }
    }

    /**
     * Find all Configuration/TCA/* files of extensions and create base TCA from it.
     * The filename must be the table name in $GLOBALS['TCA'], and the content of
     * the file should return an array with content of a specific table.
     *
     * @see Extension core, extensionmanager and others for examples.
     */
    protected static function buildBaseTcaFromSingleFiles()
    {
        $GLOBALS['TCA'] = [];

        $activePackages = static::$packageManager->getActivePackages();

        // First load "full table" files from Configuration/TCA
        foreach ($activePackages as $package) {
            try {
                $finder = Finder::create()->files()->sortByName()->depth(0)->name('*.php')->in($package->getPackagePath() . 'Configuration/TCA');
            } catch (\InvalidArgumentException $e) {
                // No such directory in this package
                continue;
            }
            foreach ($finder as $fileInfo) {
                $tcaOfTable = require $fileInfo->getPathname();
                if (is_array($tcaOfTable)) {
                    $tcaTableName = substr($fileInfo->getBasename(), 0, -4);
                    $GLOBALS['TCA'][$tcaTableName] = $tcaOfTable;
                }
            }
        }

        // Apply category stuff
        CategoryRegistry::getInstance()->applyTcaForPreRegisteredTables();

        // Execute override files from Configuration/TCA/Overrides
        foreach ($activePackages as $package) {
            try {
                $finder = Finder::create()->files()->sortByName()->depth(0)->name('*.php')->in($package->getPackagePath() . 'Configuration/TCA/Overrides');
            } catch (\InvalidArgumentException $e) {
                // No such directory in this package
                continue;
            }
            foreach ($finder as $fileInfo) {
                require $fileInfo->getPathname();
            }
        }

        // TCA migration
        // @deprecated since TYPO3 CMS 7. Not removed in TYPO3 CMS 8 though. This call will stay for now to allow further TCA migrations in 8.
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

        static::emitTcaIsBeingBuiltSignal($GLOBALS['TCA']);
    }

    /**
     * Emits the signal and uses the result of slots for the final TCA
     * This means, that *all* slots *must* return the complete TCA to
     * be effective. If a slot calls methods that manipulate the global array,
     * it needs to return the global array in the end. To be future proof,
     * a slot should manipulate the signal argument only and return it
     * after manipulation.
     *
     * @param array $tca
     */
    protected static function emitTcaIsBeingBuiltSignal(array $tca)
    {
        list($tca) = static::getSignalSlotDispatcher()->dispatch(__CLASS__, 'tcaIsBeingBuilt', [$tca]);
        $GLOBALS['TCA'] = $tca;
    }

    /**
     * Cache base $GLOBALS['TCA'] to cache file to require the whole thing in one
     * file for next access instead of cycling through all extensions again.
     *
     * @param FrontendInterface $codeCache
     */
    protected static function createBaseTcaCacheFile(FrontendInterface $codeCache)
    {
        $codeCache->set(
            static::getBaseTcaCacheIdentifier(),
            'return '
                . var_export(['tca' => $GLOBALS['TCA'], 'categoryRegistry' => serialize(CategoryRegistry::getInstance())], true)
                . ';'
        );
    }

    /**
     * Cache identifier of base TCA cache entry.
     *
     * @return string
     */
    protected static function getBaseTcaCacheIdentifier()
    {
        return 'tca_base_' . sha1(TYPO3_version . Environment::getProjectPath() . 'tca_code' . serialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['runtimeActivatedPackages']));
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
    public static function loadExtTables($allowCaching = true)
    {
        if ($allowCaching && !self::$extTablesWasReadFromCacheOnce) {
            self::$extTablesWasReadFromCacheOnce = true;
            $cacheIdentifier = self::getExtTablesCacheIdentifier();
            /** @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $codeCache */
            $codeCache = self::getCacheManager()->getCache('cache_core');
            if ($codeCache->has($cacheIdentifier)) {
                $codeCache->require($cacheIdentifier);
            } else {
                self::loadSingleExtTablesFiles();
                self::createExtTablesCacheEntry();
            }
        } else {
            self::loadSingleExtTablesFiles();
        }
    }

    /**
     * Load ext_tables.php as single files
     */
    protected static function loadSingleExtTablesFiles()
    {
        // In general it is recommended to not rely on it to be globally defined in that
        // scope, but we can not prohibit this without breaking backwards compatibility
        global $T3_SERVICES, $T3_VAR, $TYPO3_CONF_VARS;
        global $TBE_MODULES, $TBE_MODULES_EXT, $TCA;
        global $PAGES_TYPES, $TBE_STYLES;
        global $_EXTKEY;
        // Load each ext_tables.php file of loaded extensions
        foreach (static::$packageManager->getActivePackages() as $package) {
            $extTablesPath = $package->getPackagePath() . 'ext_tables.php';
            if (@file_exists($extTablesPath)) {
                // $_EXTKEY and $_EXTCONF are available in ext_tables.php
                // and are explicitly set in cached file as well
                $_EXTKEY = $package->getPackageKey();
                $_EXTCONF = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY] ?? null;
                require $extTablesPath;
            }
        }
    }

    /**
     * Create concatenated ext_tables.php cache file
     */
    protected static function createExtTablesCacheEntry()
    {
        $phpCodeToCache = [];
        // Set same globals as in loadSingleExtTablesFiles()
        $phpCodeToCache[] = '/**';
        $phpCodeToCache[] = ' * Compiled ext_tables.php cache file';
        $phpCodeToCache[] = ' */';
        $phpCodeToCache[] = '';
        $phpCodeToCache[] = 'global $T3_SERVICES, $T3_VAR, $TYPO3_CONF_VARS;';
        $phpCodeToCache[] = 'global $TBE_MODULES, $TBE_MODULES_EXT, $TCA;';
        $phpCodeToCache[] = 'global $PAGES_TYPES, $TBE_STYLES;';
        $phpCodeToCache[] = 'global $_EXTKEY;';
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
                // Set $_EXTKEY and $_EXTCONF for this extension
                $phpCodeToCache[] = '$_EXTKEY = \'' . $extensionKey . '\';';
                $phpCodeToCache[] = '$_EXTCONF = $GLOBALS[\'TYPO3_CONF_VARS\'][\'EXT\'][\'extConf\'][$_EXTKEY] ?? null;';
                $phpCodeToCache[] = '';
                // Add ext_tables.php content of extension
                $phpCodeToCache[] = trim(file_get_contents($extTablesPath));
                $phpCodeToCache[] = '';
            }
        }
        $phpCodeToCache = implode(LF, $phpCodeToCache);
        // Remove all start and ending php tags from content
        $phpCodeToCache = preg_replace('/<\\?php|\\?>/is', '', $phpCodeToCache);
        self::getCacheManager()->getCache('cache_core')->set(self::getExtTablesCacheIdentifier(), $phpCodeToCache);
    }

    /**
     * Cache identifier for concatenated ext_tables.php files
     *
     * @return string
     */
    protected static function getExtTablesCacheIdentifier()
    {
        return 'ext_tables_' . sha1(TYPO3_version . Environment::getProjectPath() . 'extTables' . serialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['runtimeActivatedPackages']));
    }

    /**
     * Remove cache files from php code cache, grouped by 'system'
     *
     * This removes the following cache entries:
     * - autoloader cache registry
     * - cache loaded extension array
     * - ext_localconf concatenation
     * - ext_tables concatenation
     *
     * This method is usually only used by extension that fiddle
     * with the loaded extensions. An example is the extension
     * manager and the install tool.
     *
     * @deprecated CacheManager provides the functionality directly
     */
    public static function removeCacheFiles()
    {
        trigger_error('ExtensionManagementUtility::removeCacheFiles() will be removed in TYPO3 v10.0. Use CacheManager directly to flush all system caches.', E_USER_DEPRECATED);
        self::getCacheManager()->flushCachesInGroup('system');
    }

    /**
     * Gets an array of loaded extension keys
     *
     * @return array Loaded extensions
     */
    public static function getLoadedExtensionListArray()
    {
        return array_keys(static::$packageManager->getActivePackages());
    }

    /**
     * Loads given extension
     *
     * Warning: This method only works if the ugrade wizard to transform
     * localconf.php to LocalConfiguration.php was already run
     *
     * @param string $extensionKey Extension key to load
     * @throws \RuntimeException
     */
    public static function loadExtension($extensionKey)
    {
        if (static::$packageManager->isPackageActive($extensionKey)) {
            throw new \RuntimeException('Extension already loaded', 1342345486);
        }
        static::$packageManager->activatePackage($extensionKey);
    }

    /**
     * Unloads given extension
     *
     * Warning: This method only works if the ugrade wizard to transform
     * localconf.php to LocalConfiguration.php was already run
     *
     * @param string $extensionKey Extension key to remove
     * @throws \RuntimeException
     */
    public static function unloadExtension($extensionKey)
    {
        if (!static::$packageManager->isPackageActive($extensionKey)) {
            throw new \RuntimeException('Extension not loaded', 1342345487);
        }
        static::$packageManager->deactivatePackage($extensionKey);
    }

    /**
     * Makes a table categorizable by adding value into the category registry.
     * FOR USE IN ext_localconf.php FILES or files in Configuration/TCA/Overrides/*.php Use the latter to benefit from TCA caching!
     *
     * @param string $extensionKey Extension key to be used
     * @param string $tableName Name of the table to be categorized
     * @param string $fieldName Name of the field to be used to store categories
     * @param array $options Additional configuration options
     * @param bool $override If TRUE, any category configuration for the same table / field is removed before the new configuration is added
     * @see addTCAcolumns
     * @see addToAllTCAtypes
     */
    public static function makeCategorizable($extensionKey, $tableName, $fieldName = 'categories', array $options = [], $override = false)
    {
        // Update the category registry
        $result = CategoryRegistry::getInstance()->add($extensionKey, $tableName, $fieldName, $options, $override);
        if ($result === false) {
            GeneralUtility::makeInstance(LogManager::class)
                ->getLogger(__CLASS__)
                ->warning(sprintf(
                    CategoryRegistry::class . ': no category registered for table "%s". Key was already registered.',
                    $tableName
                ));
        }
    }
}
