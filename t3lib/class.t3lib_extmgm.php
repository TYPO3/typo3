<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Contains a class with Extension Management functions
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */


/**
 * Extension Management functions
 *
 * This class is never instantiated, rather the methods inside is called as functions like
 *		 t3lib_extMgm::isLoaded('my_extension');
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
final class t3lib_extMgm {
	protected static $extensionKeyMap;


	/**************************************
	 *
	 * PATHS and other evaluation
	 *
	 ***************************************/

	/**
	 * Returns TRUE if the extension with extension key $key is loaded.
	 * Usage: 109
	 *
	 * @param	string		Extension key to test
	 * @param	boolean		If $exitOnError is TRUE and the extension is not loaded the function will die with an error message
	 * @return	boolean
	 */
	public static function isLoaded($key, $exitOnError = 0) {
		if ($exitOnError && !isset($GLOBALS['TYPO3_LOADED_EXT'][$key])) {
			throw new BadFunctionCallException(
				'TYPO3 Fatal Error: Extension "' . $key . '" was not loaded!',
				1270853910
			);
		}
		return isset($GLOBALS['TYPO3_LOADED_EXT'][$key]);
	}

	/**
	 * Returns the absolute path to the extension with extension key $key
	 * If the extension is not loaded the function will die with an error message
	 * Useful for internal fileoperations
	 * Usage: 136
	 *
	 * @param	string		Extension key
	 * @param	string		$script is appended to the output if set.
	 * @return	string
	 */
	public static function extPath($key, $script = '') {
		if (!isset($GLOBALS['TYPO3_LOADED_EXT'][$key])) {
			throw new BadFunctionCallException(
				'TYPO3 Fatal Error: Extension key "' . $key . '" was NOT loaded!',
				1270853878
			);
		}
		return PATH_site . $GLOBALS['TYPO3_LOADED_EXT'][$key]['siteRelPath'] . $script;
	}

	/**
	 * Returns the relative path to the extension as measured from from the TYPO3_mainDir
	 * If the extension is not loaded the function will die with an error message
	 * Useful for images and links from backend
	 * Usage: 54
	 *
	 * @param	string		Extension key
	 * @return	string
	 */
	public static function extRelPath($key) {
		if (!isset($GLOBALS['TYPO3_LOADED_EXT'][$key])) {
			throw new BadFunctionCallException(
				'TYPO3 Fatal Error: Extension key "' . $key . '" was NOT loaded!',
				1270853879
			);
		}
		return $GLOBALS['TYPO3_LOADED_EXT'][$key]['typo3RelPath'];
	}

	/**
	 * Returns the relative path to the extension as measured from the PATH_site (frontend)
	 * If the extension is not loaded the function will die with an error message
	 * Useful for images and links from the frontend
	 * Usage: 6
	 *
	 * @param	string		Extension key
	 * @return	string
	 */
	public static function siteRelPath($key) {
		return substr(self::extPath($key), strlen(PATH_site));
	}

	/**
	 * Returns the correct class name prefix for the extension key $key
	 * Usage: 3
	 *
	 * @param	string		Extension key
	 * @return	string
	 * @internal
	 */
	public static function getCN($key) {
		return substr($key, 0, 5) == 'user_' ? 'user_' . str_replace('_', '', substr($key, 5)) : 'tx_' . str_replace('_', '', $key);
	}

	/**
	 * Returns the real extension key like 'tt_news' from an extension prefix like 'tx_ttnews'.
	 *
	 * @param	string		$prefix: The extension prefix (e.g. 'tx_ttnews')
	 * @return	mixed		Real extension key (string) or FALSE (boolean) if something went wrong
	 */
	public static function getExtensionKeyByPrefix($prefix) {
		$result = FALSE;
			// Build map of short keys referencing to real keys:
		if (!isset(self::$extensionKeyMap)) {
			self::$extensionKeyMap = array();
			foreach (array_keys($GLOBALS['TYPO3_LOADED_EXT']) as $extensionKey) {
				$shortKey = str_replace('_', '', $extensionKey);
				self::$extensionKeyMap[$shortKey] = $extensionKey;
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
	 *
	 * @return	void
	 */
	public static function clearExtensionKeyMap() {
		self::$extensionKeyMap = NULL;
	}

	/**
	 * Retrieves the version of an installed extension.
	 * If the extension is not installed, this function returns an empty string.
	 *
	 * @param string $key the key of the extension to look up, must not be empty
	 * @return string the extension version as a string in the format "x.y.z",
	 *				will be an empty string if the extension is not loaded
	 */
	public static function getExtensionVersion($key) {
		if (!is_string($key) || empty($key)) {
			throw new InvalidArgumentException('Extension key must be a non-empty string.', 1294586096);
		}
		if (!self::isLoaded($key)) {
			return '';
		}

		$EM_CONF = array();
		$_EXTKEY = $key;
		include(self::extPath($key) . 'ext_emconf.php');

		return $EM_CONF[$key]['version'];
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
	 * FOR USE IN ext_tables.php FILES
	 * Usage: 4
	 *
	 * @param	string		$table is the table name of a table already present in $GLOBALS['TCA'] with a columns section
	 * @param	array		$columnArray is the array with the additional columns (typical some fields an extension wants to add)
	 * @param	boolean		If $addTofeInterface is TRUE the list of fields are also added to the fe_admin_fieldList.
	 * @return	void
	 */
	public static function addTCAcolumns($table, $columnArray, $addTofeInterface = 0) {
		t3lib_div::loadTCA($table);
		if (is_array($columnArray) && is_array($GLOBALS['TCA'][$table]) && is_array($GLOBALS['TCA'][$table]['columns'])) {
				 // Candidate for t3lib_div::array_merge() if integer-keys will some day make trouble...
			$GLOBALS['TCA'][$table]['columns'] = array_merge($GLOBALS['TCA'][$table]['columns'], $columnArray);
			if ($addTofeInterface) {
				$GLOBALS['TCA'][$table]['feInterface']['fe_admin_fieldList'] .= ',' . implode(',', array_keys($columnArray));
			}
		}
	}

	/**
	 * Makes fields visible in the TCEforms, adding them to the end of (all) "types"-configurations
	 *
	 * Adds a string $str (comma list of field names) to all ["types"][xxx]["showitem"] entries for table $table (unless limited by $specificTypesList)
	 * This is needed to have new fields shown automatically in the TCEFORMS of a record from $table.
	 * Typically this function is called after having added new columns (database fields) with the addTCAcolumns function
	 * FOR USE IN ext_tables.php FILES
	 * Usage: 1
	 *
	 * @param	string		Table name
	 * @param	string		Field list to add.
	 * @param	string		List of specific types to add the field list to. (If empty, all type entries are affected)
	 * @param	string		Insert fields before (default) or after one
	 *						of this fields (commalist with "before:", "after:" or "replace:" commands).
	 *						Example: "before:keywords,--palette--;;4,after:description".
	 *						Palettes must be passed like in the example no matter how the palette definition looks like in TCA.
	 *						It will add the list of new fields before or after a palette or replace the field inside the palette,
	 *						when the field given in $position is found inside a palette used by the type.
	 * @return	void
	 */
	public static function addToAllTCAtypes($table, $str, $specificTypesList = '', $position = '') {
		t3lib_div::loadTCA($table);
		$str = trim($str);
		$palettesChanged = array();
		if ($str && is_array($GLOBALS['TCA'][$table]) && is_array($GLOBALS['TCA'][$table]['types'])) {
			foreach ($GLOBALS['TCA'][$table]['types'] as $type => &$typeDetails) {
				if ($specificTypesList === '' || t3lib_div::inList($specificTypesList, $type)) {
					$fieldExists = FALSE;
					if ($position != '' && is_array($GLOBALS['TCA'][$table]['palettes'])) {
						$positionArray = t3lib_div::trimExplode(':', $position);
						if ($positionArray[0] == 'replace') {
							foreach ($GLOBALS['TCA'][$table]['palettes'] as $palette => $paletteDetails) {
								if (preg_match('/\b' . $palette . '\b/', $typeDetails['showitem']) > 0
										&& preg_match('/\b' . $positionArray[1] . '\b/', $paletteDetails['showitem']) > 0) {
									self::addFieldsToPalette($table, $palette, $str, $position);
										// save that palette in case other types use it
									$palettesChanged[] = $palette;
									$fieldExists = TRUE;
								} elseif (in_array($palette, $palettesChanged)) {
									$fieldExists = TRUE;
								}
							}
						} else {
							if (strpos($typeDetails['showitem'], $str) !== FALSE) {
								$fieldExists = TRUE;
							} else {
								foreach ($GLOBALS['TCA'][$table]['palettes'] as $palette => $paletteDetails) {
									if (preg_match('/\b' . $palette . '\b/', $typeDetails['showitem']) > 0
									&& preg_match('/\b' . $positionArray[1] . '\b/', $paletteDetails['showitem']) > 0) {
										$position = $positionArray[0] . ':--palette--;;' . $palette;
									}
								}
							}
						}
					} else {
						if (strpos($typeDetails['showitem'], $str) !== FALSE) {
							$fieldExists = TRUE;
						} elseif (is_array($GLOBALS['TCA'][$table]['palettes'])) {
							foreach ($GLOBALS['TCA'][$table]['palettes'] as $palette => $paletteDetails) {
								if (preg_match('/\b' . $palette . '\b/', $typeDetails['showitem']) > 0
								&& strpos($paletteDetails['showitem'], $str) !== FALSE) {
									$fieldExists = TRUE;
								}
							}
						}
					}
					if ($fieldExists === FALSE) {
						$typeDetails['showitem'] = self::executePositionedStringInsertion(
							$typeDetails['showitem'],
							$str,
							$position
						);
					}
				}
			}
			unset($typeDetails);
		}
	}

	/**
	 * Adds new fields to all palettes of an existing field.
	 * If the field does not have a palette yet, it's created automatically and
	 * gets called "generatedFor-$field".
	 *
	 * @param	string		$table: Name of the table
	 * @param	string		$field: Name of the field that has the palette to be extended
	 * @param	string		$addFields: List of fields to be added to the palette
	 * @param	string		$insertionPosition: Insert fields before (default) or after one
	 *						 of this fields (commalist with "before:", "after:" or "replace:" commands).
	 *						 Example: "before:keywords,--palette--;;4,after:description".
	 *						 Palettes must be passed like in the example no matter how the
	 *						 palette definition looks like in TCA.
	 * @return	void
	 */
	public static function addFieldsToAllPalettesOfField($table, $field, $addFields, $insertionPosition = '') {
		$generatedPalette = '';
		$processedPalettes = array();
		t3lib_div::loadTCA($table);

		if (isset($GLOBALS['TCA'][$table]['columns'][$field])) {
			$types =& $GLOBALS['TCA'][$table]['types'];
			if (is_array($types)) {
					// Iterate through all types and search for the field that defines the palette to be extended:
				foreach (array_keys($types) as $type) {
					$items = self::explodeItemList($types[$type]['showitem']);
					if (isset($items[$field])) {
							// If the field already has a palette, extend it:
						if ($items[$field]['details']['palette']) {
							$palette = $items[$field]['details']['palette'];
							if (!isset($processedPalettes[$palette])) {
								self::addFieldsToPalette($table, $palette, $addFields, $insertionPosition);
								$processedPalettes[$palette] = TRUE;
							}
							// If there's not palette yet, create one:
						} else {
							if ($generatedPalette) {
								$palette = $generatedPalette;
							} else {
								$palette = $generatedPalette = 'generatedFor-' . $field;
								self::addFieldsToPalette($table, $palette, $addFields, $insertionPosition);
							}
							$items[$field]['details']['palette'] = $palette;
							$types[$type]['showitem'] = self::generateItemList($items);
						}
					}
				}
			}
		}
	}

	/**
	 * Adds new fields to a palette.
	 * If the palette does not exist yet, it's created automatically.
	 *
	 * @param	string		$table: Name of the table
	 * @param	string		$palette: Name of the palette to be extended
	 * @param	string		$addFields: List of fields to be added to the palette
	 * @param	string		$insertionPosition: Insert fields before (default) or after one
	 *						 of this fields (commalist with "before:", "after:" or "replace:" commands).
	 *						 Example: "before:keywords,--palette--;;4,after:description".
	 *						 Palettes must be passed like in the example no matter how the
	 *						 palette definition looks like in TCA.
	 * @return	void
	 */
	public static function addFieldsToPalette($table, $palette, $addFields, $insertionPosition = '') {
		t3lib_div::loadTCA($table);

		if (isset($GLOBALS['TCA'][$table])) {
			$paletteData =& $GLOBALS['TCA'][$table]['palettes'][$palette];
				// If palette already exists, merge the data:
			if (is_array($paletteData)) {
				$paletteData['showitem'] = self::executePositionedStringInsertion(
					$paletteData['showitem'],
					$addFields,
					$insertionPosition
				);
				// If it's a new palette, just set the data:
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
	 *
	 * As an example, this can be used to add an item to tt_content CType select
	 * drop-down after the existing 'mailform' field with these parameters:
	 * - $table = 'tt_content'
	 * - $field = 'CType'
	 * - $item = array(
	 * 		'LLL:EXT:cms/locallang_ttc.xml:CType.I.10',
	 * 		'login',
	 * 		'i/tt_content_login.gif',
	 * 	),
	 * - $relativeToField = mailform
	 * - $relativePosition = after
	 *
	 * @throws InvalidArgumentException If given paramenters are not of correct
	 * 		type or out of bounds
	 * @throws RuntimeException If reference to related position fields can not
	 * 		be found or if select field is not defined
	 *
	 * @param string $table Name of TCA table
	 * @param string $field Name of TCA field
	 * @param array $item New item to add
	 * @param string $relativeToField Add item relative to existing field
	 * @param string $relativePosition Valid keywords: 'before', 'after'
	 * 		or 'replace' to relativeToField field
	 */
	public static function addTcaSelectItem($table, $field, array $item, $relativeToField = '', $relativePosition = '') {
		if (!is_string($table)) {
			throw new InvalidArgumentException(
				'Given table is of type "' . gettype($table) . '" but a string is expected.',
				1303236963
			);
		}
		if (!is_string($field)) {
			throw new InvalidArgumentException(
				'Given field is of type "' . gettype($field) . '" but a string is expected.',
				1303236964
			);
		}
		if (!is_string($relativeToField)) {
			throw new InvalidArgumentException(
				'Given relative field is of type "' . gettype($relativeToField) . '" but a string is expected.',
				1303236965
			);
		}
		if (!is_string($relativePosition)) {
			throw new InvalidArgumentException(
				'Given relative position is of type "' . gettype($relativePosition) . '" but a string is expected.',
				1303236966
			);
		}
		if ($relativePosition !== '' && $relativePosition !== 'before' && $relativePosition !== 'after' && $relativePosition !== 'replace') {
			throw new InvalidArgumentException(
				'Relative position must be either empty or one of "before", "after", "replace".',
				1303236967
			);
		}

		t3lib_div::loadTCA($table);

		if (!is_array($GLOBALS['TCA'][$table]['columns'][$field]['config']['items'])) {
			throw new RuntimeException(
				'Given select field item list was not found.',
				1303237468
			);
		}

			// Make sure item keys are integers
		$GLOBALS['TCA'][$table]['columns'][$field]['config']['items'] = array_values($GLOBALS['TCA'][$table]['columns'][$field]['config']['items']);

		if (strlen($relativePosition) > 0) {
				// Insert at specified position
			$matchedPosition = t3lib_utility_Array::filterByValueRecursive(
				$relativeToField,
				$GLOBALS['TCA'][$table]['columns'][$field]['config']['items']
			);
			if (count($matchedPosition) > 0) {
				$relativeItemKey = key($matchedPosition);
				if ($relativePosition === 'replace') {
					$GLOBALS['TCA'][$table]['columns'][$field]['config']['items'][$relativeItemKey] = $item;
				} else {
					if ($relativePosition === 'before') {
						$offset = $relativeItemKey;
					} else {
						$offset = $relativeItemKey + 1;
					}
					array_splice($GLOBALS['TCA'][$table]['columns'][$field]['config']['items'], $offset, 0, array(0 => $item));
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
	 * Adds a list of new fields to the TYPO3 USER SETTINGS configuration "showitem" list, the array with
	 * the new fields itself needs to be added additionally to show up in the user setup, like
	 * $GLOBALS['TYPO3_USER_SETTINGS']['columns'] += $tempColumns
	 *
	 * @param	string	$addFields: List of fields to be added to the user settings
	 * @param	string	$insertionPosition: Insert fields before (default) or after one
	 *					 of this fields (commalist with "before:", "after:" or "replace:" commands).
	 *					 Example: "before:password,after:email".
	 * @return void
	 */
	public function addFieldsToUserSettings($addFields, $insertionPosition = '') {
		$GLOBALS['TYPO3_USER_SETTINGS']['showitem'] = self::executePositionedStringInsertion(
			$GLOBALS['TYPO3_USER_SETTINGS']['showitem'],
			$addFields,
			$insertionPosition
		);
	}

	/**
	 * Inserts as list of data into an existing list.
	 * The insertion position can be defined accordant before of after existing list items.
	 *
	 * @param	string		$list: The list of items to be extended
	 * @param	string		$insertionList: The list of items to inserted
	 * @param	string		$insertionPosition: Insert fields before (default) or after one
	 *						 of this fields (commalist with "before:" or "after:" commands).
	 *						 Example: "before:keywords,--palette--;;4,after:description".
	 *						 Palettes must be passed like in the example no matter how the
	 *						 palette definition looks like in TCA.
	 * @return	string		The extended list
	 */
	protected static function executePositionedStringInsertion($list, $insertionList, $insertionPosition = '') {
		$list = trim($list);
		$insertionList = self::removeDuplicatesForInsertion($insertionList, $list);

		if ($insertionList) {
				// Append data to the end (default):
			if ($insertionPosition === '') {
				$list .= ($list ? ', ' : '') . $insertionList;
				// Insert data before or after insertion points:
			} else {
				$positions = t3lib_div::trimExplode(',', $insertionPosition, TRUE);
				$items = self::explodeItemList($list);
				$isInserted = FALSE;
					// Iterate through all fields an check whether it's possible to inserte there:
				foreach ($items as $item => &$itemDetails) {
					$needles = self::getInsertionNeedles($item, $itemDetails['details']);
						// Insert data before:
					foreach ($needles['before'] as $needle) {
						if (in_array($needle, $positions)) {
							$itemDetails['rawData'] = $insertionList . ', ' . $itemDetails['rawData'];
							$isInserted = TRUE;
							break;
						}
					}
						// Insert data after:
					foreach ($needles['after'] as $needle) {
						if (in_array($needle, $positions)) {
							$itemDetails['rawData'] .= ', ' . $insertionList;
							$isInserted = TRUE;
							break;
						}
					}
						// Replace with data:
					foreach ($needles['replace'] as $needle) {
						if (in_array($needle, $positions)) {
							$itemDetails['rawData'] = $insertionList;
							$isInserted = TRUE;
							break;
						}
					}
						// Break if insertion was already done:
					if ($isInserted) {
						break;
					}
				}
					// If insertion point could not be determined, append the data:
				if (!$isInserted) {
					$list .= ($list ? ', ' : '') . $insertionList;
					// If data was correctly inserted before or after existing items, recreate the list:
				} else {
					$list = self::generateItemList($items, TRUE);
				}
			}
		}

		return $list;
	}

	/**
	 * Compares an existing list of items and a list of items to be inserted
	 * and returns a duplicate-free variant of that insertion list.
	 *
	 * Example:
	 *  + list: 'field_a, field_b;;;;2-2-2, field_c;;;;3-3-3'
	 *  + insertion: 'field_b, field_d, field_c;;;4-4-4'
	 * -> new insertion: 'field_d'
	 *
	 * @param	string		$insertionList: The list of items to inserted
	 * @param	string		$list: The list of items to be extended (default: '')
	 * @return	string		Duplicate-free list of items to be inserted
	 */
	protected static function removeDuplicatesForInsertion($insertionList, $list = '') {
		$pattern = '/(^|,)\s*\b([^;,]+)\b[^,]*/';
		$listItems = array();

		if ($list && preg_match_all($pattern, $list, $listMatches)) {
			$listItems = $listMatches[2];
		}

		if ($insertionList && preg_match_all($pattern, $insertionList, $insertionListMatches)) {
			$insertionItems = array();
			$insertionDuplicates = FALSE;

			foreach ($insertionListMatches[2] as $insertionIndex => $insertionItem) {
				if (!isset($insertionItems[$insertionItem]) && !in_array($insertionItem, $listItems)) {
					$insertionItems[$insertionItem] = TRUE;
				} else {
					unset($insertionListMatches[0][$insertionIndex]);
					$insertionDuplicates = TRUE;
				}
			}

			if ($insertionDuplicates) {
				$insertionList = implode('', $insertionListMatches[0]);
			}
		}

		return $insertionList;
	}

	/**
	 * Generates search needles that are used for inserting fields/items into an existing list.
	 *
	 * @see		executePositionedStringInsertion
	 * @param	string		$item: The name of the field/item
	 * @param	array		$itemDetails: Additional details of the field/item like e.g. palette information
	 *						 (this array gets created by the function explodeItemList())
	 * @return	array		The needled to be used for inserting content before or after existing fields/items
	 */
	protected static function getInsertionNeedles($item, array $itemDetails) {
		if (strstr($item, '--')) {
				// If $item is a separator (--div--) or palette (--palette--) then it may have been appended by a unique number. This must be stripped away here.
			$item = preg_replace('/[0-9]+$/', '', $item);
		}

		$needles = array(
			'before' => array($item, 'before:' . $item),
			'after' => array('after:' . $item),
			'replace' => array('replace:' . $item),
		);

		if ($itemDetails['palette']) {
			$palette = $item . ';;' . $itemDetails['palette'];
			$needles['before'][] = $palette;
			$needles['before'][] = 'before:' . $palette;
			$needles['after'][] = 'after:' . $palette;
			$needles['replace'][] = 'replace:' . $palette;
		}

		return $needles;
	}

	/**
	 * Generates an array of fields/items with additional information such as e.g. the name of the palette.
	 *
	 * @param	string		$itemList: List of fields/items to be splitted up
	 *						 (this mostly reflects the data in $GLOBALS['TCA'][<table>]['types'][<type>]['showitem'])
	 * @return	array		An array with the names of the fields/items as keys and additional information
	 */
	protected static function explodeItemList($itemList) {
		$items = array();
		$itemParts = t3lib_div::trimExplode(',', $itemList, TRUE);

		foreach ($itemParts as $itemPart) {
			$itemDetails = t3lib_div::trimExplode(';', $itemPart, FALSE, 5);
			$key = $itemDetails[0];
			if (strstr($key, '--')) {
					// If $key is a separator (--div--) or palette (--palette--) then it will be appended by a unique number. This must be removed again when using this value!
				$key .= count($items);
			}

			if (!isset($items[$key])) {
				$items[$key] = array(
					'rawData' => $itemPart,
					'details' => array(
						'field' => $itemDetails[0],
						'label' => $itemDetails[1],
						'palette' => $itemDetails[2],
						'special' => $itemDetails[3],
						'styles' => $itemDetails[4],
					),
				);
			}
		}

		return $items;
	}

	/**
	 * Generates a list of fields/items out of an array provided by the function getFieldsOfFieldList().
	 *
	 * @see		explodeItemList
	 * @param	array		$items: The array of fields/items with optional additional information
	 * @param	boolean		$useRawData: Use raw data instead of building by using the details (default: FALSE)
	 * @return	string		The list of fields/items which gets used for $GLOBALS['TCA'][<table>]['types'][<type>]['showitem']
	 *						 or $GLOBALS['TCA'][<table>]['palettes'][<palette>]['showitem'] in most cases
	 */
	protected static function generateItemList(array $items, $useRawData = FALSE) {
		$itemParts = array();

		foreach ($items as $item => $itemDetails) {
			if (strstr($item, '--')) {
					// If $item is a separator (--div--) or palette (--palette--) then it may have been appended by a unique number. This must be stripped away here.
				$item = preg_replace('/[0-9]+$/', '', $item);
			}

			if ($useRawData) {
				$itemParts[] = $itemDetails['rawData'];
			} else {
				$itemParts[] = (count($itemDetails['details']) > 1 ? implode(';', $itemDetails['details']) : $item);
			}
		}

		return implode(', ', $itemParts);
	}

	/**
	 * Add tablename to default list of allowed tables on pages (in $PAGES_TYPES)
	 * Will add the $table to the list of tables allowed by default on pages as setup by $PAGES_TYPES['default']['allowedTables']
	 * FOR USE IN ext_tables.php FILES
	 * Usage: 11
	 *
	 * @param	string		Table name
	 * @return	void
	 */
	public static function allowTableOnStandardPages($table) {
		$GLOBALS['PAGES_TYPES']['default']['allowedTables'] .= ',' . $table;
	}

	/**
	 * Adds a module (main or sub) to the backend interface
	 * FOR USE IN ext_tables.php FILES
	 * Usage: 18
	 *
	 * @param	string		$main is the main module key, $sub is the submodule key. So $main would be an index in the $TBE_MODULES array and $sub could be an element in the lists there.
	 * @param	string		$sub is the submodule key. If $sub is not set a blank $main module is created.
	 * @param	string		$position can be used to set the position of the $sub module within the list of existing submodules for the main module. $position has this syntax: [cmd]:[submodule-key]. cmd can be "after", "before" or "top" (or blank which is default). If "after"/"before" then submodule will be inserted after/before the existing submodule with [submodule-key] if found. If not found, the bottom of list. If "top" the module is inserted in the top of the submodule list.
	 * @param	string		$path is the absolute path to the module. If this value is defined the path is added as an entry in $TBE_MODULES['_PATHS'][  main_sub  ] = $path; and thereby tells the backend where the newly added modules is found in the system.
	 * @return	void
	 */
	public static function addModule($main, $sub = '', $position = '', $path = '') {
		if (isset($GLOBALS['TBE_MODULES'][$main]) && $sub) {
			// if there is already a main module by this name:

				// Adding the submodule to the correct position:
			list($place, $modRef) = t3lib_div::trimExplode(':', $position, 1);
			$mods = t3lib_div::trimExplode(',', $GLOBALS['TBE_MODULES'][$main], 1);
			if (!in_array($sub, $mods)) {
				switch (strtolower($place)) {
					case 'after':
					case 'before':
						$pointer = 0;
						$found = FALSE;
						foreach ($mods as $k => $m) {
							if (!strcmp($m, $modRef)) {
								$pointer = strtolower($place) == 'after' ? $k + 1 : $k;
								$found = TRUE;
							}
						}
						if ($found) {
							array_splice(
								$mods, // The modules array
								$pointer, // To insert one position from the end of the list
								0, // Don't remove any items, just insert
								$sub // Module to insert
							);
						} else {
								// If requested module is not found: Add at the end
							array_push($mods, $sub);
						}
					break;
					default:
						if (strtolower($place) == 'top') {
							array_unshift($mods, $sub);
						} else {
							array_push($mods, $sub);
						}
					break;
				}
			}
				// Re-inserting the submodule list:
			$GLOBALS['TBE_MODULES'][$main] = implode(',', $mods);
		} else { // Create new main modules with only one submodule, $sub (or none if $sub is blank)
			$GLOBALS['TBE_MODULES'][$main] = $sub;
		}

			// Adding path:
		if ($path) {
			$GLOBALS['TBE_MODULES']['_PATHS'][$main . ($sub ? '_' . $sub : '')] = $path;
		}
	}

	/**
	 * Adds a module path to $GLOBALS['TBE_MODULES'] for used with the module dispatcher, mod.php
	 * Used only for modules that are not placed in the main/sub menu hierarchy by the traditional mechanism of addModule()
	 * Examples for this is context menu functionality (like import/export) which runs as an independent module through mod.php
	 * FOR USE IN ext_tables.php FILES
	 * Example:  t3lib_extMgm::addModulePath('xMOD_tximpexp', t3lib_extMgm::extPath($_EXTKEY).'app/');
	 *
	 * @param	string		$name is the name of the module, refer to conf.php of the module.
	 * @param	string		$path is the absolute path to the module directory inside of which "index.php" and "conf.php" is found.
	 * @return	void
	 */
	public static function addModulePath($name, $path) {
		$GLOBALS['TBE_MODULES']['_PATHS'][$name] = $path;
	}

	/**
	 * Adds a "Function menu module" ('third level module') to an existing function menu for some other backend module
	 * The arguments values are generally determined by which function menu this is supposed to interact with
	 * See Inside TYPO3 for information on how to use this function.
	 * FOR USE IN ext_tables.php FILES
	 * Usage: 26
	 *
	 * @param	string		Module name
	 * @param	string		Class name
	 * @param	string		Class path
	 * @param	string		Title of module
	 * @param	string		Menu array key - default is "function"
	 * @param	string		Workspace conditions. Blank means all workspaces, any other string can be a comma list of "online", "offline" and "custom"
	 * @return	void
	 * @see t3lib_SCbase::mergeExternalItems()
	 */
	public static function insertModuleFunction($modname, $className, $classPath, $title, $MM_key = 'function', $WS = '') {
		$GLOBALS['TBE_MODULES_EXT'][$modname]['MOD_MENU'][$MM_key][$className] = array(
			'name' => $className,
			'path' => $classPath,
			'title' => $title,
			'ws' => $WS
		);
	}

	/**
	 * Adds $content to the default Page TSconfig as set in $GLOBALS['TYPO3_CONF_VARS'][BE]['defaultPageTSconfig']
	 * Prefixed with a [GLOBAL] line
	 * FOR USE IN ext_tables.php/ext_localconf.php FILES
	 * Usage: 5
	 *
	 * @param	string		Page TSconfig content
	 * @return	void
	 */
	public static function addPageTSConfig($content) {
		$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'] .= "\n[GLOBAL]\n" . $content;
	}

	/**
	 * Adds $content to the default User TSconfig as set in $GLOBALS['TYPO3_CONF_VARS'][BE]['defaultUserTSconfig']
	 * Prefixed with a [GLOBAL] line
	 * FOR USE IN ext_tables.php/ext_localconf.php FILES
	 * Usage: 3
	 *
	 * @param	string		User TSconfig content
	 * @return	void
	 */
	public static function addUserTSConfig($content) {
		$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig'] .= "\n[GLOBAL]\n" . $content;
	}

	/**
	 * Adds a reference to a locallang file with $GLOBALS['TCA_DESCR'] labels
	 * FOR USE IN ext_tables.php FILES
	 * eg. t3lib_extMgm::addLLrefForTCAdescr('pages', 'EXT:lang/locallang_csh_pages.xml'); for the pages table or t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_layout', 'EXT:cms/locallang_csh_weblayout.php'); for the Web > Page module.
	 * Usage: 31
	 *
	 * @param	string		Description key. Typically a database table (like "pages") but for applications can be other strings, but prefixed with "_MOD_")
	 * @param	string		File reference to locallang file, eg. "EXT:lang/locallang_csh_pages.php" (or ".xml")
	 * @return	void
	 */
	public static function addLLrefForTCAdescr($tca_descr_key, $file_ref) {
		if ($tca_descr_key) {
			if (!is_array($GLOBALS['TCA_DESCR'][$tca_descr_key])) {
				$GLOBALS['TCA_DESCR'][$tca_descr_key] = array();
			}
			if (!is_array($GLOBALS['TCA_DESCR'][$tca_descr_key]['refs'])) {
				$GLOBALS['TCA_DESCR'][$tca_descr_key]['refs'] = array();
			}
			$GLOBALS['TCA_DESCR'][$tca_descr_key]['refs'][] = $file_ref;
		}
	}

	/**
	 * Registers a navigation component
	 *
	 * @param string $module
	 * @param string $componentId
	 * @return void
	 */
	public static function addNavigationComponent($module, $componentId) {
		$GLOBALS['TBE_MODULES']['_navigationComponents'][$module] = array(
			'componentId' => $componentId,
			'extKey' => $GLOBALS['_EXTKEY'],
			'isCoreComponent' => FALSE,
		);
	}

	/**
	 * Registers a core navigation component
	 *
	 * @param string $module
	 * @param string $componentId
	 * @return void
	 */
	public static function addCoreNavigationComponent($module, $componentId) {
		self::addNavigationComponent($module, $componentId);
		$GLOBALS['TBE_MODULES']['_navigationComponents'][$module]['isCoreComponent'] = TRUE;
	}


	/**************************************
	 *
	 *	 Adding SERVICES features
	 *
	 * @author	René Fritz <r.fritz@colorcube.de>
	 *
	 ***************************************/

	/**
	 * Adds a service to the global services array
	 *
	 * @param	string		Extension key
	 * @param	string		Service type, must not be prefixed "tx_" or "Tx_"
	 * @param	string		Service key, must be prefixed "tx_", "Tx_" or "user_"
	 * @param	array		Service description array
	 * @return	void
	 * @author	René Fritz <r.fritz@colorcube.de>
	 */
	public static function addService($extKey, $serviceType, $serviceKey, $info) {
			// even not available services will be included to make it possible to give the admin a feedback of non-available services.
			// but maybe it's better to move non-available services to a different array??

		if ($serviceType &&
				!t3lib_div::hasValidClassPrefix($serviceType) &&
				t3lib_div::hasValidClassPrefix($serviceKey, array('user_')) &&
				is_array($info)) {

			$info['priority'] = max(0, min(100, $info['priority']));

			$GLOBALS['T3_SERVICES'][$serviceType][$serviceKey] = $info;

			$GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['extKey'] = $extKey;
			$GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['serviceKey'] = $serviceKey;
			$GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['serviceType'] = $serviceType;


				// mapping a service key to a service type
				// all service keys begin with tx_ or Tx_ - service types don't
				// this way a selection of a special service key as service type is easy
			$GLOBALS['T3_SERVICES'][$serviceKey][$serviceKey] = &$GLOBALS['T3_SERVICES'][$serviceType][$serviceKey];


				// change the priority (and other values) from $GLOBALS['TYPO3_CONF_VARS']
				// $GLOBALS['TYPO3_CONF_VARS']['T3_SERVICES'][$serviceType][$serviceKey]['priority']
				// even the activation is possible (a unix service might be possible on windows for some reasons)
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['T3_SERVICES'][$serviceType][$serviceKey])) {

					// no check is done here - there might be configuration values only the service type knows about, so
					// we pass everything
				$GLOBALS['T3_SERVICES'][$serviceType][$serviceKey] = array_merge(
					$GLOBALS['T3_SERVICES'][$serviceType][$serviceKey],
					$GLOBALS['TYPO3_CONF_VARS']['T3_SERVICES'][$serviceType][$serviceKey]
				);
			}


				// OS check
				// empty $os means 'not limited to one OS', therefore a check is not needed
			if ($GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['available']
				&& $GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['os'] != '') {

					// TYPO3_OS is not yet defined
				$os_type = stristr(PHP_OS, 'win') && !stristr(PHP_OS, 'darwin') ? 'WIN' : 'UNIX';

				$os = t3lib_div::trimExplode(',', strtoupper($GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['os']));

				if (!in_array($os_type, $os)) {
					self::deactivateService($serviceType, $serviceKey);
				}
			}

				// convert subtype list to array for quicker access
			$GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['serviceSubTypes'] = array();
			$serviceSubTypes = t3lib_div::trimExplode(',', $info['subtype']);
			foreach ($serviceSubTypes as $subtype) {
				$GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['serviceSubTypes'][$subtype] = $subtype;
			}
		}
	}

	/**
	 * Find the available service with highest priority
	 *
	 * @param	string		Service type
	 * @param	string		Service sub type
	 * @param	mixed		Service keys that should be excluded in the search for a service. Array or comma list.
	 * @return	mixed		Service info array if a service was found, FLASE otherwise
	 * @author	René Fritz <r.fritz@colorcube.de>
	 */
	public static function findService($serviceType, $serviceSubType = '', $excludeServiceKeys = array()) {
		$serviceKey = FALSE;
		$serviceInfo = FALSE;
		$priority = 0;
		$quality = 0;

		if (!is_array($excludeServiceKeys)) {
			$excludeServiceKeys = t3lib_div::trimExplode(',', $excludeServiceKeys, 1);
		}

		if (is_array($GLOBALS['T3_SERVICES'][$serviceType])) {
			foreach ($GLOBALS['T3_SERVICES'][$serviceType] as $key => $info) {

				if (in_array($key, $excludeServiceKeys)) {
					continue;
				}

					// select a subtype randomly
					// usefull to start a service by service key without knowing his subtypes - for testing purposes
				if ($serviceSubType == '*') {
					$serviceSubType = key($info['serviceSubTypes']);
				}

					// this matches empty subtype too
				if ($info['available'] && ($info['subtype'] == $serviceSubType || $info['serviceSubTypes'][$serviceSubType]) && $info['priority'] >= $priority) {

						// has a lower quality than the already found, therefore we skip this service
					if ($info['priority'] == $priority && $info['quality'] < $quality) {
						continue;
					}

						// service depends on external programs - check if they exists
					if (trim($info['exec'])) {
						$executables = t3lib_div::trimExplode(',', $info['exec'], 1);
						foreach ($executables as $executable) {
							if (!t3lib_exec::checkCommand($executable)) {
								self::deactivateService($serviceType, $key);
								$info['available'] = FALSE;
								break;
							}
						}
					}

						// still available after exec check?
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
	 * Deactivate a service
	 *
	 * @param	string		Service type
	 * @param	string		Service key
	 * @return	void
	 * @author	René Fritz <r.fritz@colorcube.de>
	 */
	public static function deactivateService($serviceType, $serviceKey) {
			// ... maybe it's better to move non-available services to a different array??
		$GLOBALS['T3_SERVICES'][$serviceType][$serviceKey]['available'] = FALSE;
	}


	/**************************************
	 *
	 *	 Adding FRONTEND features
	 *	 (related specifically to "cms" extension)
	 *
	 ***************************************/

	/**
	 * Adds an entry to the list of plugins in content elements of type "Insert plugin"
	 * Takes the $itemArray (label, value[,icon]) and adds to the items-array of $GLOBALS['TCA'][tt_content] elements with CType "listtype" (or another field if $type points to another fieldname)
	 * If the value (array pos. 1) is already found in that items-array, the entry is substituted, otherwise the input array is added to the bottom.
	 * Use this function to add a frontend plugin to this list of plugin-types - or more generally use this function to add an entry to any selectorbox/radio-button set in the TCEFORMS
	 * FOR USE IN ext_tables.php FILES
	 * Usage: 13
	 *
	 * @param	array		Item Array
	 * @param	string		Type (eg. "list_type") - basically a field from "tt_content" table
	 * @return	void
	 */
	public static function addPlugin($itemArray, $type = 'list_type') {
		$_EXTKEY = $GLOBALS['_EXTKEY'];
		if ($_EXTKEY && !$itemArray[2]) {
			$itemArray[2] = self::extRelPath($_EXTKEY) . 'ext_icon.gif';
		}

		t3lib_div::loadTCA('tt_content');
		if (is_array($GLOBALS['TCA']['tt_content']['columns']) && is_array($GLOBALS['TCA']['tt_content']['columns'][$type]['config']['items'])) {
			foreach ($GLOBALS['TCA']['tt_content']['columns'][$type]['config']['items'] as $k => $v) {
				if (!strcmp($v[1], $itemArray[1])) {
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
	 * Usage: 0
	 *
	 * @param	string		Plugin key as used in the list_type field. Use the asterisk * to match all list_type values.
	 * @param	string		Either a reference to a flex-form XML file (eg. "FILE:EXT:newloginbox/flexform_ds.xml") or the XML directly.
	 * @param	string		Value of tt_content.CType (Content Type) to match. The default is "list" which corresponds to the "Insert Plugin" content element.  Use the asterisk * to match all CType values.
	 * @return	void
	 * @see addPlugin()
	 */
	public static function addPiFlexFormValue($piKeyToMatch, $value, $CTypeToMatch = 'list') {
		t3lib_div::loadTCA('tt_content');

		if (is_array($GLOBALS['TCA']['tt_content']['columns']) && is_array($GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds'])) {
			$GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds'][$piKeyToMatch . ',' . $CTypeToMatch] = $value;
		}
	}

	/**
	 * Adds the $table tablename to the list of tables allowed to be includes by content element type "Insert records"
	 * By using $content_table and $content_field you can also use the function for other tables.
	 * FOR USE IN ext_tables.php FILES
	 * Usage: 9
	 *
	 * @param	string		Table name to allow for "insert record"
	 * @param	string		Table name TO WHICH the $table name is applied. See $content_field as well.
	 * @param	string		Field name in the database $content_table in which $table is allowed to be added as a reference ("Insert Record")
	 * @return	void
	 */
	public static function addToInsertRecords($table, $content_table = 'tt_content', $content_field = 'records') {
		t3lib_div::loadTCA($content_table);
		if (is_array($GLOBALS['TCA'][$content_table]['columns']) && isset($GLOBALS['TCA'][$content_table]['columns'][$content_field]['config']['allowed'])) {
			$GLOBALS['TCA'][$content_table]['columns'][$content_field]['config']['allowed'] .= ',' . $table;
		}
	}

	/**
	 * Add PlugIn to Static Template #43
	 *
	 * When adding a frontend plugin you will have to add both an entry to the TCA definition of tt_content table AND to the TypoScript template which must initiate the rendering.
	 * Since the static template with uid 43 is the "content.default" and practically always used for rendering the content elements it's very useful to have this function automatically adding the necessary TypoScript for calling your plugin. It will also work for the extension "css_styled_content"
	 * $type determines the type of frontend plugin:
	 *		 "list_type" (default)	- the good old "Insert plugin" entry
	 *		 "menu_type"	- a "Menu/Sitemap" entry
	 *		 "splash_layout" - a "Textbox" entry
	 *		 "CType" - a new content element type
	 *		 "header_layout" - an additional header type (added to the selection of layout1-5)
	 *		 "includeLib" - just includes the library for manual use somewhere in TypoScript.
	 *	 (Remember that your $type definition should correspond to the column/items array in $GLOBALS['TCA'][tt_content] where you added the selector item for the element! See addPlugin() function)
	 * FOR USE IN ext_localconf.php FILES
	 * Usage: 2
	 *
	 * @param	string		$key is the extension key
	 * @param	string		$classFile is the PHP-class filename relative to the extension root directory. If set to blank a default value is chosen according to convensions.
	 * @param	string		$prefix is used as a - yes, suffix - of the class name (fx. "_pi1")
	 * @param	string		$type, see description above
	 * @param	boolean		If $cached is set as USER content object (cObject) is created - otherwise a USER_INT object is created.
	 * @return	void
	 */
	public static function addPItoST43($key, $classFile = '', $prefix = '', $type = 'list_type', $cached = 0) {
		$classFile = $classFile ? $classFile : 'pi/class.tx_' . str_replace('_', '', $key) . $prefix . '.php';
		$cN = self::getCN($key);

			// General plugin:
		$pluginContent = trim('
plugin.' . $cN . $prefix . ' = USER' . ($cached ? '' : '_INT') . '
plugin.' . $cN . $prefix . ' {
  includeLibs = ' . $GLOBALS['TYPO3_LOADED_EXT'][$key]['siteRelPath'] . $classFile . '
  userFunc = ' . $cN . $prefix . '->main
}');
		self::addTypoScript($key, 'setup', '
# Setting ' . $key . ' plugin TypoScript
' . $pluginContent);

			// After ST43:
		switch ($type) {
			case 'list_type':
				$addLine = 'tt_content.list.20.' . $key . $prefix . ' = < plugin.' . $cN . $prefix;
			break;
			case 'menu_type':
				$addLine = 'tt_content.menu.20.' . $key . $prefix . ' = < plugin.' . $cN . $prefix;
			break;
			case 'splash_layout':
				$addLine = 'tt_content.splash.' . $key . $prefix . ' = < plugin.' . $cN . $prefix;
			break;
			case 'CType':
				$addLine = trim('
tt_content.' . $key . $prefix . ' = COA
tt_content.' . $key . $prefix . ' {
	10 = < lib.stdheader
	20 = < plugin.' . $cN . $prefix . '
}
				');
			break;
			case 'header_layout':
				$addLine = 'lib.stdheader.10.' . $key . $prefix . ' = < plugin.' . $cN . $prefix;
			break;
			case 'includeLib':
				$addLine = 'page.1000 = < plugin.' . $cN . $prefix;
			break;
			default:
				$addLine = '';
			break;
		}
		if ($addLine) {
			self::addTypoScript($key, 'setup', '
# Setting ' . $key . ' plugin TypoScript
' . $addLine . '
', 43);
		}
	}

	/**
	 * Call this method to add an entry in the static template list found in sys_templates
	 * "static template files" are the modern equivalent (provided from extensions) to the traditional records in "static_templates"
	 * FOR USE IN ext_localconf.php FILES
	 * Usage: 3
	 *
	 * @param	string		$extKey is of course the extension key
	 * @param	string		$path is the path where the template files (fixed names) include_static.txt (integer list of uids from the table "static_templates"), constants.txt, setup.txt, editorcfg.txt, and include_static_file.txt is found (relative to extPath, eg. 'static/'). The file include_static_file.txt, allows you to include other static templates defined in files, from your static template, and thus corresponds to the field 'include_static_file' in the sys_template table. The syntax for this is a commaseperated list of static templates to include, like:  EXT:css_styled_content/static/,EXT:da_newsletter_subscription/static/,EXT:cc_random_image/pi2/static/
	 * @param	string		$title is the title in the selector box.
	 * @return	void
	 * @see addTypoScript()
	 */
	public static function addStaticFile($extKey, $path, $title) {
		t3lib_div::loadTCA('sys_template');
		if ($extKey && $path && is_array($GLOBALS['TCA']['sys_template']['columns'])) {
			$value = str_replace(',', '', 'EXT:' . $extKey . '/' . $path);
			$itemArray = array(trim($title . ' (' . $extKey . ')'), $value);
			$GLOBALS['TCA']['sys_template']['columns']['include_static_file']['config']['items'][] = $itemArray;
		}
	}

	/**
	 * Adds $content to the default TypoScript setup code as set in $GLOBALS['TYPO3_CONF_VARS'][FE]['defaultTypoScript_setup']
	 * Prefixed with a [GLOBAL] line
	 * FOR USE IN ext_localconf.php FILES
	 * Usage: 6
	 *
	 * @param	string		TypoScript Setup string
	 * @return	void
	 */
	public static function addTypoScriptSetup($content) {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup'] .= "\n[GLOBAL]\n" . $content;
	}

	/**
	 * Adds $content to the default TypoScript constants code as set in $GLOBALS['TYPO3_CONF_VARS'][FE]['defaultTypoScript_constants']
	 * Prefixed with a [GLOBAL] line
	 * FOR USE IN ext_localconf.php FILES
	 * Usage: 0
	 *
	 * @param	string		TypoScript Constants string
	 * @return	void
	 */
	public static function addTypoScriptConstants($content) {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants'] .= "\n[GLOBAL]\n" . $content;
	}

	/**
	 * Adds $content to the default TypoScript code for either setup, constants or editorcfg as set in $GLOBALS['TYPO3_CONF_VARS'][FE]['defaultTypoScript_*']
	 * (Basically this function can do the same as addTypoScriptSetup and addTypoScriptConstants - just with a little more hazzle, but also with some more options!)
	 * FOR USE IN ext_localconf.php FILES
	 * Usage: 7
	 *
	 * @param	string		$key is the extension key (informative only).
	 * @param	string		$type is either "setup", "constants" or "editorcfg" and obviously determines which kind of TypoScript code we are adding.
	 * @param	string		$content is the TS content, prefixed with a [GLOBAL] line and a comment-header.
	 * @param	string		$afterStaticUid is either an integer pointing to a uid of a static_template or a string pointing to the "key" of a static_file template ([reduced extension_key]/[local path]). The points is that the TypoScript you add is included only IF that static template is included (and in that case, right after). So effectively the TypoScript you set can specifically overrule settings from those static templates.
	 * @return	void
	 */
	public static function addTypoScript($key, $type, $content, $afterStaticUid = 0) {
		if ($type == 'setup' || $type == 'editorcfg' || $type == 'constants') {
			$content = '

[GLOBAL]
#############################################
## TypoScript added by extension "' . $key . '"
#############################################

' . $content;
			if ($afterStaticUid) {
				$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type . '.'][$afterStaticUid] .= $content;
					// If 'content (default)' is targeted, also add to other 'content rendering templates', eg. css_styled_content
				if ($afterStaticUid == 43 && is_array($GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'])) {
					foreach ($GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'] as $templateName) {
						$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type . '.'][$templateName] .= $content;
					}
				}
			} else {
				$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type] .= $content;
			}
		}
	}


	/**************************************
	 *
	 *	 INTERNAL EXTENSION MANAGEMENT:
	 *
	 ***************************************/

	/**
	 * Loading extensions configured in $GLOBALS['TYPO3_CONF_VARS']['EXT']['extList']
	 *
	 * CACHING ON: ($GLOBALS['TYPO3_CONF_VARS']['EXT']['extCache'] = 1 or 2)
	 *		 If caching is enabled (and possible), the output will be $extensions['_CACHEFILE'] set to the cacheFilePrefix. Subsequently the cache files must be included then since those will eventually set up the extensions.
	 *		 If cachefiles are not found they will be generated
	 * CACHING OFF:	($GLOBALS['TYPO3_CONF_VARS']['EXT']['extCache'] = 0)
	 *		 The returned value will be an array where each key is an extension key and the value is an array with filepaths for the extension.
	 *		 This array will later be set in the global var $GLOBALS['TYPO3_LOADED_EXT']
	 *
	 * Usages of this function can be seen in config_default.php
	 * Extensions are always detected in the order local - global - system.
	 * Usage: 1
	 *
	 * @return	array		Extension Array
	 * @internal
	 */
	public static function typo3_loadExtensions() {

			// Caching behaviour of ext_tables.php and ext_localconf.php files:
		$extensionCacheBehaviour = self::getExtensionCacheBehaviour();
			// Full list of extensions includes both required and extList:
		$rawExtList = self::getEnabledExtensionList();

			// Empty array as a start.
		$extensions = array();

			//
		if ($rawExtList) {
				// The cached File prefix.
			$cacheFilePrefix = self::getCacheFilePrefix();

				// If cache files available, set cache file prefix and return:
			if ($extensionCacheBehaviour && self::isCacheFilesAvailable($cacheFilePrefix)) {
					// Return cache file prefix:
				$extensions['_CACHEFILE'] = $cacheFilePrefix;
			} else { // ... but if not, configure...

					// Prepare reserved filenames:
				$files = array('ext_localconf.php', 'ext_tables.php', 'ext_tables.sql', 'ext_tables_static+adt.sql', 'ext_typoscript_constants.txt', 'ext_typoscript_editorcfg.txt', 'ext_typoscript_setup.txt');
					// Traverse extensions and check their existence:
				clearstatcache(); // Clear file state cache to make sure we get good results from is_dir()
				$temp_extensions = array_unique(t3lib_div::trimExplode(',', $rawExtList, 1));
				foreach ($temp_extensions as $temp_extKey) {
						// Check local, global and system locations:
					if (@is_dir(PATH_typo3conf . 'ext/' . $temp_extKey . '/')) {
						$extensions[$temp_extKey] = array('type' => 'L', 'siteRelPath' => 'typo3conf/ext/' . $temp_extKey . '/', 'typo3RelPath' => '../typo3conf/ext/' . $temp_extKey . '/');
					} elseif (@is_dir(PATH_typo3 . 'ext/' . $temp_extKey . '/')) {
						$extensions[$temp_extKey] = array('type' => 'G', 'siteRelPath' => TYPO3_mainDir . 'ext/' . $temp_extKey . '/', 'typo3RelPath' => 'ext/' . $temp_extKey . '/');
					} elseif (@is_dir(PATH_typo3 . 'sysext/' . $temp_extKey . '/')) {
						$extensions[$temp_extKey] = array('type' => 'S', 'siteRelPath' => TYPO3_mainDir . 'sysext/' . $temp_extKey . '/', 'typo3RelPath' => 'sysext/' . $temp_extKey . '/');
					}

						// If extension was found, check for reserved filenames:
					if (isset($extensions[$temp_extKey])) {
						foreach ($files as $fName) {
							$temp_filename = PATH_site . $extensions[$temp_extKey]['siteRelPath'] . trim($fName);
							if (is_array($extensions[$temp_extKey]) && @is_file($temp_filename)) {
								$extensions[$temp_extKey][$fName] = $temp_filename;
							}
						}
					}
				}
				unset($extensions['_CACHEFILE']);

					// write cache?
				if ($extensionCacheBehaviour &&
						@is_dir(PATH_typo3 . 'sysext/') &&
								@is_dir(PATH_typo3 . 'ext/')) { // Must also find global and system extension directories to exist, otherwise caching cannot be allowed (since it is most likely a temporary server problem). This might fix a rare, unrepeatable bug where global/system extensions are not loaded resulting in fatal errors if that is cached!
					$wrError = self::cannotCacheFilesWritable($cacheFilePrefix);
					if ($wrError) {
						$GLOBALS['TYPO3_CONF_VARS']['EXT']['extCache'] = 0;
					} else {
							// Write cache files:
						$extensions = self::writeCacheFiles($extensions, $cacheFilePrefix);
					}
				}
			}
		}

		return $extensions;
	}

	/**
	 * Returns the section headers for the compiled cache-files.
	 *
	 * @param	string		$key is the extension key
	 * @param	string		$file is the filename (only informative for comment)
	 * @return	string
	 * @internal
	 */
	public static function _makeIncludeHeader($key, $file) {
		return '<?php
###########################
## EXTENSION: ' . $key . '
## FILE:      ' . $file . '
###########################

$_EXTKEY = \'' . $key . '\';
$_EXTCONF = $GLOBALS[\'TYPO3_CONF_VARS\'][\'EXT\'][\'extConf\'][$_EXTKEY];

?>';
	}

	/**
	 * Returns TRUE if both the localconf and tables cache file exists (with $cacheFilePrefix)
	 * Usage: 2
	 *
	 * @param	string		Prefix of the cache file to check
	 * @return	boolean
	 * @internal
	 */
	public static function isCacheFilesAvailable($cacheFilePrefix) {
		return
				@is_file(PATH_typo3conf . $cacheFilePrefix . '_ext_localconf.php') &&
						@is_file(PATH_typo3conf . $cacheFilePrefix . '_ext_tables.php');
	}

	/**
	 * Returns TRUE if the "localconf.php" file in "typo3conf/" is writable
	 * Usage: 1
	 *
	 * @return	boolean
	 * @internal
	 */
	public static function isLocalconfWritable() {
		return @is_writable(PATH_typo3conf) && @is_writable(PATH_typo3conf . 'localconf.php');
	}

	/**
	 * Returns an error string if typo3conf/ or cache-files with $cacheFilePrefix are NOT writable
	 * Returns FALSE if no problem.
	 * Usage: 1
	 *
	 * @param	string		Prefix of the cache file to check
	 * @return	string
	 * @internal
	 */
	public static function cannotCacheFilesWritable($cacheFilePrefix) {
		$error = array();
		if (!@is_writable(PATH_typo3conf)) {
			$error[] = PATH_typo3conf;
		}
		if (@is_file(PATH_typo3conf . $cacheFilePrefix . '_ext_localconf.php') &&
				!@is_writable(PATH_typo3conf . $cacheFilePrefix . '_ext_localconf.php')) {
			$error[] = PATH_typo3conf . $cacheFilePrefix . '_ext_localconf.php';
		}
		if (@is_file(PATH_typo3conf . $cacheFilePrefix . '_ext_tables.php') &&
				!@is_writable(PATH_typo3conf . $cacheFilePrefix . '_ext_tables.php')) {
			$error[] = PATH_typo3conf . $cacheFilePrefix . '_ext_tables.php';
		}
		return implode(', ', $error);
	}

	/**
	 * Returns an array with the two cache-files (0=>localconf, 1=>tables) from typo3conf/ if they (both) exist. Otherwise FALSE.
	 * Evaluation relies on $GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE']
	 * Usage: 2
	 *
	 * @param string $cacheFilePrefix Cache file prefix to be used (optional)
	 * @return	array
	 * @internal
	 */
	public static function currentCacheFiles($cacheFilePrefix = NULL) {
		if (is_null($cacheFilePrefix)) {
			$cacheFilePrefix = $GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'];
		}

		if ($cacheFilePrefix) {
			$cacheFilePrefixFE = str_replace('temp_CACHED', 'temp_CACHED_FE', $cacheFilePrefix);
			$files = array();
			if (self::isCacheFilesAvailable($cacheFilePrefix)) {
				$files[] = PATH_typo3conf . $cacheFilePrefix . '_ext_localconf.php';
				$files[] = PATH_typo3conf . $cacheFilePrefix . '_ext_tables.php';
			}
			if (self::isCacheFilesAvailable($cacheFilePrefixFE)) {
				$files[] = PATH_typo3conf . $cacheFilePrefixFE . '_ext_localconf.php';
				$files[] = PATH_typo3conf . $cacheFilePrefixFE . '_ext_tables.php';
			}
			if (!empty($files)) {
				return $files;
			}
		}
	}

	/**
	 * Compiles/Creates the two cache-files in typo3conf/ based on $cacheFilePrefix
	 * Returns a array with the key "_CACHEFILE" set to the $cacheFilePrefix value
	 * Usage: 1
	 *
	 * @param	array		Extension information array
	 * @param	string		Prefix for the cache files
	 * @return	array
	 * @internal
	 */
	public static function writeCacheFiles($extensions, $cacheFilePrefix) {
			// Making cache files:
		$extensions['_CACHEFILE'] = $cacheFilePrefix;
		$cFiles = array();
		$cFiles['ext_localconf'] .= '<?php

$GLOBALS[\'TYPO3_LOADED_EXT\'] = unserialize(stripslashes(\'' . addslashes(serialize($extensions)) . '\'));

?>';

		foreach ($extensions as $key => $conf) {
			if (is_array($conf)) {
				if ($conf['ext_localconf.php']) {
					$cFiles['ext_localconf'] .= self::_makeIncludeHeader($key, $conf['ext_localconf.php']);
					$cFiles['ext_localconf'] .= trim(t3lib_div::getUrl($conf['ext_localconf.php']));
				}
				if ($conf['ext_tables.php']) {
					$cFiles['ext_tables'] .= self::_makeIncludeHeader($key, $conf['ext_tables.php']);
					$cFiles['ext_tables'] .= trim(t3lib_div::getUrl($conf['ext_tables.php']));
				}
			}
		}

		$cFiles['ext_localconf'] = "<?php\n" . preg_replace('/<\?php|\?>/is', '', $cFiles['ext_localconf']) . "?>\n";
		$cFiles['ext_tables'] = "<?php\n" . preg_replace('/<\?php|\?>/is', '', $cFiles['ext_tables']) . "?>\n";

		t3lib_div::writeFile(PATH_typo3conf . $cacheFilePrefix . '_ext_localconf.php', $cFiles['ext_localconf']);
		t3lib_div::writeFile(PATH_typo3conf . $cacheFilePrefix . '_ext_tables.php', $cFiles['ext_tables']);

		$extensions = array();
		$extensions['_CACHEFILE'] = $cacheFilePrefix;

		return $extensions;
	}

	/**
	 * Unlink (delete) cache files
	 *
	 * @param string $cacheFilePrefix Cache file prefix to be used (optional)
	 * @return	integer		Number of deleted files.
	 */
	public static function removeCacheFiles($cacheFilePrefix = NULL) {
		$cacheFiles = self::currentCacheFiles($cacheFilePrefix);

		$out = 0;
		if (is_array($cacheFiles)) {
			foreach ($cacheFiles as $cfile) {
				@unlink($cfile);
				clearstatcache();
				$out++;
			}
		}
		return $out;
	}

	/**
	 * Gets the behaviour for caching ext_tables.php and ext_localconf.php files
	 * (see $GLOBALS['TYPO3_CONF_VARS']['EXT']['extCache'] setting in the install tool).
	 *
	 * @param boolean $usePlainValue Whether to use the value as it is without modifications
	 * @return integer
	 */
	public static function getExtensionCacheBehaviour($usePlainValue = FALSE) {
		$extensionCacheBehaviour = intval($GLOBALS['TYPO3_CONF_VARS']['EXT']['extCache']);

			// Caching of extensions is disabled when install tool is used:
		if (!$usePlainValue && defined('TYPO3_enterInstallScript') && TYPO3_enterInstallScript) {
			$extensionCacheBehaviour = 0;
		}

		return $extensionCacheBehaviour;
	}

	/**
	 * Gets the prefix used for the ext_tables.php and ext_localconf.php cached files.
	 *
	 * @return string
	 */
	public static function getCacheFilePrefix() {
		$extensionCacheBehaviour = self::getExtensionCacheBehaviour(TRUE);

		$cacheFileSuffix = (TYPO3_MODE == 'FE' ? '_FE' : '');
		$cacheFilePrefix = 'temp_CACHED' . $cacheFileSuffix;

		if ($extensionCacheBehaviour == 1) {
			$cacheFilePrefix .= '_ps' . substr(t3lib_div::shortMD5(PATH_site . '|' . $GLOBALS['TYPO_VERSION']), 0, 4);
		} elseif ($extensionCacheBehaviour == 2) {
			$cacheFilePrefix .= '_' . t3lib_div::shortMD5(self::getEnabledExtensionList());
		}

		return $cacheFilePrefix;
	}

	/**
	 * Gets the list of enabled extensions for the accordant context (frontend or backend).
	 *
	 * @return string
	 */
	public static function getEnabledExtensionList() {
			// Select mode how to load extensions in order to speed up the FE
		if (TYPO3_MODE == 'FE') {
			if (!($extLoadInContext = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extList_FE'])) {
					// fall back to standard 'extList' if 'extList_FE' is not (yet) set
				$extLoadInContext = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extList'];
			}
		} else {
			$extLoadInContext = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extList'];
		}

		$extensionList = self::getRequiredExtensionList() . ',' . $extLoadInContext;
		$ignoredExtensionList = self::getIgnoredExtensionList();

			// Remove the extensions to be ignored:
		if ($ignoredExtensionList && (defined('TYPO3_enterInstallScript') && TYPO3_enterInstallScript) === FALSE) {
			$extensions = array_diff(
				explode(',', $extensionList),
				explode(',', $ignoredExtensionList)
			);
			$extensionList = implode(',', $extensions);
		}

		return $extensionList;
	}

	/**
	 * Gets the list of required extensions.
	 *
	 * @return string
	 */
	public static function getRequiredExtensionList() {
		$requiredExtensionList = t3lib_div::uniqueList(
			REQUIRED_EXTENSIONS . ',' . $GLOBALS['TYPO3_CONF_VARS']['EXT']['requiredExt']
		);

		return $requiredExtensionList;
	}

	/**
	 * Gets the list of extensions to be ignored (not to be loaded).
	 *
	 * @return string
	 */
	public static function getIgnoredExtensionList() {
		$ignoredExtensionList = t3lib_div::uniqueList(
			$GLOBALS['TYPO3_CONF_VARS']['EXT']['ignoredExt']
		);

		return $ignoredExtensionList;
	}
}

?>
