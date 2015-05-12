<?php
namespace TYPO3\CMS\Backend\Form\Utility;

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

use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Backend\Form\DataPreprocessor;

/**
 * This is a static, internal and intermediate helper class for various
 * FormEngine related tasks.
 *
 * This class was introduced to help disentangling FormEngine and
 * its sub classes. It MUST NOT be used in other extensions and will
 * change or vanish without further notice.
 *
 * @internal
 * @todo: These helpers are target to be dropped if further FormEngine refactoring is done
 */
class FormEngineUtility {

	/**
	 * Whitelist that allows TCA field configuration to be overridden by TSconfig
	 *
	 * @see overrideFieldConf()
	 * @var array
	 */
	static protected $allowOverrideMatrix = array(
		'input' => array('size', 'max', 'readOnly'),
		'text' => array('cols', 'rows', 'wrap', 'readOnly'),
		'check' => array('cols', 'showIfRTE', 'readOnly'),
		'select' => array('size', 'autoSizeMax', 'maxitems', 'minitems', 'readOnly', 'treeConfig'),
		'group' => array('size', 'autoSizeMax', 'max_size', 'show_thumbs', 'maxitems', 'minitems', 'disable_controls', 'readOnly'),
		'inline' => array('appearance', 'behaviour', 'foreign_label', 'foreign_selector', 'foreign_unique', 'maxitems', 'minitems', 'size', 'autoSizeMax', 'symmetric_label', 'readOnly'),
	);

	/**
	 * @var array Cache of getLanguageIcon()
	 */
	static protected $cachedLanguageFlag = array();

	/**
	 * Overrides the TCA field configuration by TSconfig settings.
	 *
	 * Example TSconfig: TCEform.<table>.<field>.config.appearance.useSortable = 1
	 * This overrides the setting in $GLOBALS['TCA'][<table>]['columns'][<field>]['config']['appearance']['useSortable'].
	 *
	 * @param array $fieldConfig $GLOBALS['TCA'] field configuration
	 * @param array $TSconfig TSconfig
	 * @return array Changed TCA field configuration
	 * @internal
	 */
	static public function overrideFieldConf($fieldConfig, $TSconfig) {
		if (is_array($TSconfig)) {
			$TSconfig = GeneralUtility::removeDotsFromTS($TSconfig);
			$type = $fieldConfig['type'];
			if (is_array($TSconfig['config']) && is_array(static::$allowOverrideMatrix[$type])) {
				// Check if the keys in TSconfig['config'] are allowed to override TCA field config:
				foreach ($TSconfig['config'] as $key => $_) {
					if (!in_array($key, static::$allowOverrideMatrix[$type], TRUE)) {
						unset($TSconfig['config'][$key]);
					}
				}
				// Override $GLOBALS['TCA'] field config by remaining TSconfig['config']:
				if (count($TSconfig['config'])) {
					ArrayUtility::mergeRecursiveWithOverrule($fieldConfig, $TSconfig['config']);
				}
			}
		}
		return $fieldConfig;
	}

	/**
	 * Initializes language icons etc.
	 *
	 * @param string $table Table name
	 * @param array $row Record
	 * @param string $sys_language_uid Sys language uid OR ISO language code prefixed with "v", eg. "vDA
	 * @return string
	 * @internal
	 */
	static public function getLanguageIcon($table, $row, $sys_language_uid) {
		$mainKey = $table . ':' . $row['uid'];
		if (!isset(static::$cachedLanguageFlag[$mainKey])) {
			BackendUtility::fixVersioningPid($table, $row);
			list($tscPID) = BackendUtility::getTSCpidCached($table, $row['uid'], $row['pid']);
			/** @var $t8Tools TranslationConfigurationProvider */
			$t8Tools = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
			static::$cachedLanguageFlag[$mainKey] = $t8Tools->getSystemLanguages($tscPID);
		}
		// Convert sys_language_uid to sys_language_uid if input was in fact a string (ISO code expected then)
		if (!MathUtility::canBeInterpretedAsInteger($sys_language_uid)) {
			foreach (static::$cachedLanguageFlag[$mainKey] as $rUid => $cD) {
				if ('v' . $cD['ISOcode'] === $sys_language_uid) {
					$sys_language_uid = $rUid;
				}
			}
		}
		$out = '';
		if (static::$cachedLanguageFlag[$mainKey][$sys_language_uid]['flagIcon'] && static::$cachedLanguageFlag[$mainKey][$sys_language_uid]['flagIcon'] != 'empty-empty') {
			$out .= IconUtility::getSpriteIcon(static::$cachedLanguageFlag[$mainKey][$sys_language_uid]['flagIcon']);
			$out .= '&nbsp;';
		} elseif (static::$cachedLanguageFlag[$mainKey][$sys_language_uid]['title']) {
			$out .= '[' . static::$cachedLanguageFlag[$mainKey][$sys_language_uid]['title'] . ']';
			$out .= '&nbsp;';
		}
		return $out;
	}

	/**
	 * Returns TSconfig for given table and row
	 *
	 * @param string $table The table name
	 * @param array $row The table row - Must at least contain the "uid" value, even if "NEW..." string.
	 *                   The "pid" field is important as well, negative values will be interpreted as pointing to a record from the same table.
	 * @param string $field Optionally specify the field name as well. In that case the TSconfig for this field is returned.
	 * @return mixed The TSconfig values - probably in an array
	 * @internal
	 */
	static public function getTSconfigForTableRow($table, $row, $field = '') {
		static $cache;
		if (is_null($cache)) {
			$cache = array();
		}
		$cacheIdentifier = $table . ':' . $row['uid'];
		if (!isset($cache[$cacheIdentifier])) {
			$cache[$cacheIdentifier] = BackendUtility::getTCEFORM_TSconfig($table, $row);
		}
		if ($field) {
			return $cache[$cacheIdentifier][$field];
		}
		return $cache[$cacheIdentifier];
	}

	/**
	 * Extracting values from a value/label list (as made by transferData class)
	 *
	 * @param array $itemFormElValue Values in an array
	 * @return array Input string exploded with comma and for each value only the label part is set in the array. Keys are numeric
	 * @internal
	 */
	static public function extractValuesOnlyFromValueLabelList($itemFormElValue) {
		// Get values of selected items:
		$itemArray = GeneralUtility::trimExplode(',', $itemFormElValue, TRUE);
		foreach ($itemArray as $tk => $tv) {
			$tvP = explode('|', $tv, 2);
			$tvP[0] = rawurldecode($tvP[0]);
			$itemArray[$tk] = $tvP[0];
		}
		return $itemArray;
	}

	/**
	 * Get icon (for example for selector boxes)
	 *
	 * @param string $icon Icon reference
	 * @return array Array with two values; the icon file reference, the icon file information array (getimagesize())
	 * @internal
	 */
	static public function getIcon($icon) {
		$selIconInfo = FALSE;
		if (substr($icon, 0, 4) == 'EXT:') {
			$file = GeneralUtility::getFileAbsFileName($icon);
			if ($file) {
				$file = PathUtility::stripPathSitePrefix($file);
				$selIconFile = '../' . $file;
				$selIconInfo = @getimagesize((PATH_site . $file));
			} else {
				$selIconFile = '';
			}
		} elseif (substr($icon, 0, 3) == '../') {
			$selIconFile = GeneralUtility::resolveBackPath($icon);
			if (is_file(PATH_site . GeneralUtility::resolveBackPath(substr($icon, 3)))) {
				$selIconInfo = getimagesize((PATH_site . GeneralUtility::resolveBackPath(substr($icon, 3))));
			}
		} elseif (substr($icon, 0, 4) == 'ext/' || substr($icon, 0, 7) == 'sysext/') {
			$selIconFile = $icon;
			if (is_file(PATH_typo3 . $icon)) {
				$selIconInfo = getimagesize(PATH_typo3 . $icon);
			}
		} else {
			$selIconFile = IconUtility::skinImg('', 'gfx/' . $icon, '', 1);
			$iconPath = $selIconFile;
			if (is_file(PATH_typo3 . $iconPath)) {
				$selIconInfo = getimagesize(PATH_typo3 . $iconPath);
			}
		}
		if ($selIconInfo === FALSE) {
			// Unset to empty string if icon is not available
			$selIconFile = '';
		}
		return array($selIconFile, $selIconInfo);
	}

	/**
	 * Renders the $icon, supports a filename for skinImg or sprite-icon-name
	 *
	 * @param string $icon The icon passed, could be a file-reference or a sprite Icon name
	 * @param string $alt Alt attribute of the icon returned
	 * @param string $title Title attribute of the icon return
	 * @return string A tag representing to show the asked icon
	 * @internal
	 */
	static public function getIconHtml($icon, $alt = '', $title = '') {
		$iconArray = static::getIcon($icon);
		if (!empty($iconArray[0]) && is_file(GeneralUtility::resolveBackPath(PATH_typo3 . PATH_typo3_mod . $iconArray[0]))) {
			return '<img src="' . $iconArray[0] . '" alt="' . $alt . '" ' . ($title ? 'title="' . $title . '"' : '') . ' />';
		} else {
			return IconUtility::getSpriteIcon($icon, array('alt' => $alt, 'title' => $title));
		}
	}

	/**
	 * Creates style attribute content for option tags in a selector box, primarily setting
	 * it up to show the icon of an element as background image (works in mozilla)
	 *
	 * @param string $iconString Icon string for option item
	 * @return string Style attribute content, if any
	 * @internal
	 */
	static public function optionTagStyle($iconString) {
		if (!$iconString) {
			return '';
		}
		list($selIconFile, $selIconInfo) = static::getIcon($iconString);
		if (empty($selIconFile)) {
			// Skip background style if image is unavailable
			return '';
		}
		$padLeft = $selIconInfo[0] + 4;
		if ($padLeft >= 18 && $padLeft <= 24) {
			// In order to get the same padding for all option tags even if icon sizes differ a little,
			// set it to 22 if it was between 18 and 24 pixels
			$padLeft = 22;
		}
		$padTop = MathUtility::forceIntegerInRange(($selIconInfo[1] - 12) / 2, 0);
		$styleAttr = 'background: #fff url(' . $selIconFile . ') 0% 50% no-repeat; height: '
			. MathUtility::forceIntegerInRange(($selIconInfo[1] + 2 - $padTop), 0)
			. 'px; padding-top: ' . $padTop . 'px; padding-left: ' . $padLeft . 'px;';
		return $styleAttr;
	}

	/**
	 * Initialize item array (for checkbox, selectorbox, radio buttons)
	 * Will resolve the label value.
	 *
	 * @param array $fieldValue The "columns" array for the field (from TCA)
	 * @return array An array of arrays with three elements; label, value, icon
	 * @internal
	 */
	static public function initItemArray($fieldValue) {
		$languageService = static::getLanguageService();
		$items = array();
		if (is_array($fieldValue['config']['items'])) {
			foreach ($fieldValue['config']['items'] as $itemValue) {
				$items[] = array($languageService->sL($itemValue[0]), $itemValue[1], $itemValue[2]);
			}
		}
		return $items;
	}

	/**
	 * Merges items into an item-array, optionally with an icon
	 * example:
	 * TCEFORM.pages.doktype.addItems.13 = My Label
	 * TCEFORM.pages.doktype.addItems.13.icon = EXT:t3skin/icons/gfx/i/pages.gif
	 *
	 * @param array $items The existing item array
	 * @param array $iArray An array of items to add. NOTICE: The keys are mapped to values, and the values and mapped to be labels. No possibility of adding an icon.
	 * @return array The updated $item array
	 * @internal
	 */
	static public function addItems($items, $iArray) {
		$languageService = static::getLanguageService();
		if (is_array($iArray)) {
			foreach ($iArray as $value => $label) {
				// if the label is an array (that means it is a subelement
				// like "34.icon = mylabel.png", skip it (see its usage below)
				if (is_array($label)) {
					continue;
				}
				// check if the value "34 = mylabel" also has a "34.icon = myimage.png"
				if (isset($iArray[$value . '.']) && $iArray[$value . '.']['icon']) {
					$icon = $iArray[$value . '.']['icon'];
				} else {
					$icon = '';
				}
				$items[] = array($languageService->sL($label), $value, $icon);
			}
		}
		return $items;
	}

	/**
	 * Collects the items for a select field by reading the configured
	 * select items from the configuration and / or by collecting them
	 * from a foreign table.
	 *
	 * @param string $table The table name of the record
	 * @param string $fieldName The select field name
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $PA An array with additional configuration options.
	 * @return array
	 */
	static public function getSelectItems($table, $fieldName, array $row, array $PA) {
		$config = $PA['fieldConf']['config'];

		// Getting the selector box items from the system
		$selectItems = FormEngineUtility::addSelectOptionsToItemArray(
			FormEngineUtility::initItemArray($PA['fieldConf']),
			$PA['fieldConf'],
			FormEngineUtility::getTSconfigForTableRow($table, $row),
			$fieldName
		);

		// Possibly filter some items:
		$selectItems = ArrayUtility::keepItemsInArray(
			$selectItems,
			$PA['fieldTSConfig']['keepItems'],
			function ($value) {
				return $value[1];
			}
		);

		// Possibly add some items:
		$selectItems = FormEngineUtility::addItems($selectItems, $PA['fieldTSConfig']['addItems.']);

		// Process items by a user function:
		if (isset($config['itemsProcFunc']) && $config['itemsProcFunc']) {
			$dataPreprocessor = GeneralUtility::makeInstance(DataPreprocessor::class);
			$selectItems = $dataPreprocessor->procItems($selectItems, $PA['fieldTSConfig']['itemsProcFunc.'], $config, $table, $row, $fieldName);
		}

		// Possibly remove some items:
		$removeItems = GeneralUtility::trimExplode(',', $PA['fieldTSConfig']['removeItems'], TRUE);
		foreach ($selectItems as $selectItemIndex => $selectItem) {

			// Checking languages and authMode:
			$languageDeny = FALSE;
			$beUserAuth = static::getBackendUserAuthentication();
			if (
				!empty($GLOBALS['TCA'][$table]['ctrl']['languageField'])
				&& $GLOBALS['TCA'][$table]['ctrl']['languageField'] === $fieldName
				&& !$beUserAuth->checkLanguageAccess($selectItem[1])
			) {
				$languageDeny = TRUE;
			}

			$authModeDeny = FALSE;
			if (
				($config['type'] === 'select')
				&& $config['authMode']
				&& !$beUserAuth->checkAuthMode($table, $fieldName, $selectItem[1], $config['authMode'])
			) {
				$authModeDeny = TRUE;
			}

			if (in_array($selectItem[1], $removeItems) || $languageDeny || $authModeDeny) {
				unset($selectItems[$selectItemIndex]);
			} elseif (isset($PA['fieldTSConfig']['altLabels.'][$selectItem[1]])) {
				$selectItems[$selectItemIndex][0] = htmlspecialchars(static::getLanguageService()->sL($PA['fieldTSConfig']['altLabels.'][$selectItem[1]]));
			}

			// Removing doktypes with no access:
			if (($table === 'pages' || $table === 'pages_language_overlay') && $fieldName === 'doktype') {
				if (!($beUserAuth->isAdmin() || GeneralUtility::inList($beUserAuth->groupData['pagetypes_select'], $selectItem[1]))) {
					unset($selectItems[$selectItemIndex]);
				}
			}
		}

		return $selectItems;
	}

	/**
	 * Add selector box items of more exotic kinds.
	 *
	 * @param array $items The array of items (label,value,icon)
	 * @param array $fieldValue The "columns" array for the field (from TCA)
	 * @param array $TSconfig TSconfig for the table/row
	 * @param string $field The fieldname
	 * @return array The $items array modified.
	 * @internal
	 */
	static public function addSelectOptionsToItemArray($items, $fieldValue, $TSconfig, $field) {
		$languageService = static::getLanguageService();

		// Values from foreign tables:
		if ($fieldValue['config']['foreign_table']) {
			$items = static::foreignTable($items, $fieldValue, $TSconfig, $field);
			if ($fieldValue['config']['neg_foreign_table']) {
				$items = static::foreignTable($items, $fieldValue, $TSconfig, $field, 1);
			}
		}

		// Values from a file folder:
		if ($fieldValue['config']['fileFolder']) {
			$fileFolder = GeneralUtility::getFileAbsFileName($fieldValue['config']['fileFolder']);
			if (@is_dir($fileFolder)) {
				// Configurations:
				$extList = $fieldValue['config']['fileFolder_extList'];
				$recursivityLevels = isset($fieldValue['config']['fileFolder_recursions'])
					? MathUtility::forceIntegerInRange($fieldValue['config']['fileFolder_recursions'], 0, 99)
					: 99;
				// Get files:
				$fileFolder = rtrim($fileFolder, '/') . '/';
				$fileArr = GeneralUtility::getAllFilesAndFoldersInPath(array(), $fileFolder, $extList, 0, $recursivityLevels);
				$fileArr = GeneralUtility::removePrefixPathFromList($fileArr, $fileFolder);
				foreach ($fileArr as $fileRef) {
					$fI = pathinfo($fileRef);
					$icon = GeneralUtility::inList('gif,png,jpeg,jpg', strtolower($fI['extension']))
						? '../' . PathUtility::stripPathSitePrefix($fileFolder) . $fileRef
						: '';
					$items[] = array(
						$fileRef,
						$fileRef,
						$icon
					);
				}
			}
		}

		// If 'special' is configured:
		if ($fieldValue['config']['special']) {
			switch ($fieldValue['config']['special']) {
				case 'tables':
					foreach ($GLOBALS['TCA'] as $theTableNames => $_) {
						if (!$GLOBALS['TCA'][$theTableNames]['ctrl']['adminOnly']) {
							// Icon:
							$icon = IconUtility::mapRecordTypeToSpriteIconName($theTableNames, array());
							// Add help text
							$helpText = array();
							$languageService->loadSingleTableDescription($theTableNames);
							$helpTextArray = $GLOBALS['TCA_DESCR'][$theTableNames]['columns'][''];
							if (!empty($helpTextArray['description'])) {
								$helpText['description'] = $helpTextArray['description'];
							}
							// Item configuration:
							$items[] = array(
								$languageService->sL($GLOBALS['TCA'][$theTableNames]['ctrl']['title']),
								$theTableNames,
								$icon,
								$helpText
							);
						}
					}
					break;
				case 'pagetypes':
					$theTypes = $GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'];
					foreach ($theTypes as $theTypeArrays) {
						// Icon:
						// @todo: typo here
						$icon = 'empty-emtpy';
						if ($theTypeArrays[1] != '--div--') {
							$icon = IconUtility::mapRecordTypeToSpriteIconName('pages', array('doktype' => $theTypeArrays[1]));
						}
						// Item configuration:
						$items[] = array(
							$languageService->sL($theTypeArrays[0]),
							$theTypeArrays[1],
							$icon
						);
					}
					break;
				case 'exclude':
					$theTypes = BackendUtility::getExcludeFields();
					foreach ($theTypes as $theTypeArrays) {
						list($theTable, $theFullField) = explode(':', $theTypeArrays[1]);
						// If the field comes from a FlexForm, the syntax is more complex
						$theFieldParts = explode(';', $theFullField);
						$theField = array_pop($theFieldParts);
						// Add header if not yet set for table:
						if (!array_key_exists($theTable, $items)) {
							$icon = IconUtility::mapRecordTypeToSpriteIconName($theTable, array());
							$items[$theTable] = array(
								$languageService->sL($GLOBALS['TCA'][$theTable]['ctrl']['title']),
								'--div--',
								$icon
							);
						}
						// Add help text
						$helpText = array();
						$languageService->loadSingleTableDescription($theTable);
						$helpTextArray = $GLOBALS['TCA_DESCR'][$theTable]['columns'][$theFullField];
						if (!empty($helpTextArray['description'])) {
							$helpText['description'] = $helpTextArray['description'];
						}
						// Item configuration:
						$items[] = array(
							rtrim($languageService->sL($GLOBALS['TCA'][$theTable]['columns'][$theField]['label']), ':') . ' (' . $theField . ')',
							$theTypeArrays[1],
							'empty-empty',
							$helpText
						);
					}
					break;
				case 'explicitValues':
					$theTypes = BackendUtility::getExplicitAuthFieldValues();
					// Icons:
					$icons = array(
						'ALLOW' => 'status-status-permission-granted',
						'DENY' => 'status-status-permission-denied'
					);
					// Traverse types:
					foreach ($theTypes as $tableFieldKey => $theTypeArrays) {
						if (is_array($theTypeArrays['items'])) {
							// Add header:
							$items[] = array(
								$theTypeArrays['tableFieldLabel'],
								'--div--'
							);
							// Traverse options for this field:
							foreach ($theTypeArrays['items'] as $itemValue => $itemContent) {
								// Add item to be selected:
								$items[] = array(
									'[' . $itemContent[2] . '] ' . $itemContent[1],
									$tableFieldKey . ':' . preg_replace('/[:|,]/', '', $itemValue) . ':' . $itemContent[0],
									$icons[$itemContent[0]]
								);
							}
						}
					}
					break;
				case 'languages':
					$items = array_merge($items, BackendUtility::getSystemLanguages());
					break;
				case 'custom':
					// Initialize:
					$customOptions = $GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions'];
					if (is_array($customOptions)) {
						foreach ($customOptions as $coKey => $coValue) {
							if (is_array($coValue['items'])) {
								// Add header:
								$items[] = array(
									$languageService->sL($coValue['header']),
									'--div--'
								);
								// Traverse items:
								foreach ($coValue['items'] as $itemKey => $itemCfg) {
									// Icon:
									if ($itemCfg[1]) {
										list($icon) = FormEngineUtility::getIcon($itemCfg[1]);
									} else {
										$icon = 'empty-empty';
									}
									// Add help text
									$helpText = array();
									if (!empty($itemCfg[2])) {
										$helpText['description'] = $languageService->sL($itemCfg[2]);
									}
									// Add item to be selected:
									$items[] = array(
										$languageService->sL($itemCfg[0]),
										$coKey . ':' . preg_replace('/[:|,]/', '', $itemKey),
										$icon,
										$helpText
									);
								}
							}
						}
					}
					break;
				case 'modListGroup':

				case 'modListUser':
					$loadModules = GeneralUtility::makeInstance(ModuleLoader::class);
					$loadModules->load($GLOBALS['TBE_MODULES']);
					$modList = $fieldValue['config']['special'] == 'modListUser' ? $loadModules->modListUser : $loadModules->modListGroup;
					if (is_array($modList)) {
						foreach ($modList as $theMod) {
							// Icon:
							$icon = $languageService->moduleLabels['tabs_images'][$theMod . '_tab'];
							if ($icon) {
								$icon = '../' . PathUtility::stripPathSitePrefix($icon);
							}
							// Add help text
							$helpText = array(
								'title' => $languageService->moduleLabels['labels'][$theMod . '_tablabel'],
								'description' => $languageService->moduleLabels['labels'][$theMod . '_tabdescr']
							);

							$label = '';
							// Add label for main module:
							$pp = explode('_', $theMod);
							if (count($pp) > 1) {
								$label .= $languageService->moduleLabels['tabs'][($pp[0] . '_tab')] . '>';
							}
							// Add modules own label now:
							$label .= $languageService->moduleLabels['tabs'][$theMod . '_tab'];

							// Item configuration:
							$items[] = array($label, $theMod, $icon, $helpText);
						}
					}
					break;
			}
		}

		return $items;
	}

	/**
	 * Extracts FlexForm parts of a form element name like
	 * data[table][uid][field][sDEF][lDEF][FlexForm][vDEF]
	 * Helper method used in inline
	 *
	 * @param string $formElementName The form element name
	 * @return array|NULL
	 * @internal
	 */
	static public function extractFlexFormParts($formElementName) {
		$flexFormParts = NULL;

		$matches = array();

		if (preg_match('#^data(?:\[[^]]+\]){3}(\[data\](?:\[[^]]+\]){4,})$#', $formElementName, $matches)) {
			$flexFormParts = GeneralUtility::trimExplode(
				'][',
				trim($matches[1], '[]')
			);
		}

		return $flexFormParts;
	}

	/**
	 * Get inlineFirstPid from a given objectId string
	 *
	 * @param string $domObjectId The id attribute of an element
	 * @return integer|NULL Pid or null
	 * @internal
	 */
	static public function getInlineFirstPidFromDomObjectId($domObjectId) {
		// Substitute FlexForm addition and make parsing a bit easier
		$domObjectId = str_replace('---', ':', $domObjectId);
		// The starting pattern of an object identifier (e.g. "data-<firstPidValue>-<anything>)
		$pattern = '/^data' . '-' . '(.+?)' . '-' . '(.+)$/';
		if (preg_match($pattern, $domObjectId, $match)) {
			return $match[1];
		}
		return NULL;
	}

	/**
	 * Adds / adapts some general options of main TCA config for inline usage
	 *
	 * @param array $config TCA field configuration
	 * @return array Modified configuration
	 * @internal
	 */
	static public function mergeInlineConfiguration($config) {
		// Init appearance if not set:
		if (!isset($config['appearance']) || !is_array($config['appearance'])) {
			$config['appearance'] = array();
		}
		// Set the position/appearance of the "Create new record" link:
		if (
			isset($config['foreign_selector'])
			&& $config['foreign_selector']
			&& (!isset($config['appearance']['useCombination']) || !$config['appearance']['useCombination'])
		) {
			$config['appearance']['levelLinksPosition'] = 'none';
		} elseif (
			!isset($config['appearance']['levelLinksPosition'])
			|| !in_array($config['appearance']['levelLinksPosition'], array('top', 'bottom', 'both', 'none'))
		) {
			$config['appearance']['levelLinksPosition'] = 'top';
		}
		// Defines which controls should be shown in header of each record:
		$enabledControls = array(
			'info' => TRUE,
			'new' => TRUE,
			'dragdrop' => TRUE,
			'sort' => TRUE,
			'hide' => TRUE,
			'delete' => TRUE,
			'localize' => TRUE
		);
		if (isset($config['appearance']['enabledControls']) && is_array($config['appearance']['enabledControls'])) {
			$config['appearance']['enabledControls'] = array_merge($enabledControls, $config['appearance']['enabledControls']);
		} else {
			$config['appearance']['enabledControls'] = $enabledControls;
		}
		return $config;
	}

	/**
	 * Determine the configuration and the type of a record selector.
	 * This is a helper method for inline / IRRE handling
	 *
	 * @param array $conf TCA configuration of the parent(!) field
	 * @param string $field Field name
	 * @return array Associative array with the keys 'PA' and 'type', both are FALSE if the selector was not valid.
	 * @internal
	 */
	static public function getInlinePossibleRecordsSelectorConfig($conf, $field = '') {
		$foreign_table = $conf['foreign_table'];
		$foreign_selector = $conf['foreign_selector'];
		$PA = FALSE;
		$type = FALSE;
		$table = FALSE;
		$selector = FALSE;
		if ($field) {
			$PA = array();
			$PA['fieldConf'] = $GLOBALS['TCA'][$foreign_table]['columns'][$field];
			if ($PA['fieldConf'] && $conf['foreign_selector_fieldTcaOverride']) {
				ArrayUtility::mergeRecursiveWithOverrule($PA['fieldConf'], $conf['foreign_selector_fieldTcaOverride']);
			}
			$PA['fieldTSConfig'] = FormEngineUtility::getTSconfigForTableRow($foreign_table, array(), $field);
			$config = $PA['fieldConf']['config'];
			// Determine type of Selector:
			$type = static::getInlinePossibleRecordsSelectorType($config);
			// Return table on this level:
			$table = $type === 'select' ? $config['foreign_table'] : $config['allowed'];
			// Return type of the selector if foreign_selector is defined and points to the same field as in $field:
			if ($foreign_selector && $foreign_selector == $field && $type) {
				$selector = $type;
			}
		}
		return array(
			'PA' => $PA,
			'type' => $type,
			'table' => $table,
			'selector' => $selector
		);
	}

	/**
	 * Determine the type of a record selector, e.g. select or group/db.
	 *
	 * @param array $config TCE configuration of the selector
	 * @return mixed The type of the selector, 'select' or 'groupdb' - FALSE not valid
	 * @internal
	 */
	static protected function getInlinePossibleRecordsSelectorType($config) {
		$type = FALSE;
		if ($config['type'] === 'select') {
			$type = 'select';
		} elseif ($config['type'] === 'group' && $config['internal_type'] === 'db') {
			$type = 'groupdb';
		}
		return $type;
	}

	/**
	 * Update expanded/collapsed states on new inline records if any.
	 *
	 * @param array $uc The uc array to be processed and saved (by reference)
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $tce Instance of FormEngine that saved data before
	 * @return void
	 * @internal
	 */
	static public function updateInlineView(&$uc, $tce) {
		$backendUser = static::getBackendUserAuthentication();
		if (isset($uc['inlineView']) && is_array($uc['inlineView'])) {
			$inlineView = (array)unserialize($backendUser->uc['inlineView']);
			foreach ($uc['inlineView'] as $topTable => $topRecords) {
				foreach ($topRecords as $topUid => $childElements) {
					foreach ($childElements as $childTable => $childRecords) {
						$uids = array_keys($tce->substNEWwithIDs_table, $childTable);
						if (!empty($uids)) {
							$newExpandedChildren = array();
							foreach ($childRecords as $childUid => $state) {
								if ($state && in_array($childUid, $uids)) {
									$newChildUid = $tce->substNEWwithIDs[$childUid];
									$newExpandedChildren[] = $newChildUid;
								}
							}
							// Add new expanded child records to UC (if any):
							if (!empty($newExpandedChildren)) {
								$inlineViewCurrent = &$inlineView[$topTable][$topUid][$childTable];
								if (is_array($inlineViewCurrent)) {
									$inlineViewCurrent = array_unique(array_merge($inlineViewCurrent, $newExpandedChildren));
								} else {
									$inlineViewCurrent = $newExpandedChildren;
								}
							}
						}
					}
				}
			}
			$backendUser->uc['inlineView'] = serialize($inlineView);
			$backendUser->writeUC();
		}
	}

	/**
	 * Gets an array with the uids of related records out of a list of items.
	 * This list could contain more information than required. This methods just
	 * extracts the uids.
	 *
	 * @param string $itemList The list of related child records
	 * @return array An array with uids
	 * @internal
	 */
	static public function getInlineRelatedRecordsUidArray($itemList) {
		$itemArray = GeneralUtility::trimExplode(',', $itemList, TRUE);
		// Perform modification of the selected items array:
		foreach ($itemArray as &$value) {
			$parts = explode('|', $value, 2);
			$value = $parts[0];
		}
		unset($value);
		return $itemArray;
	}

	/**
	 * Adds records from a foreign table (for selector boxes). Helper for addSelectOptionsToItemArray()
	 *
	 * @param array $items The array of items (label,value,icon)
	 * @param array $fieldValue The 'columns' array for the field (from TCA)
	 * @param array $TSconfig TSconfig for the table/row
	 * @param string $field The fieldname
	 * @param bool $pFFlag If set, then we are fetching the 'neg_' foreign tables.
	 * @return array The $items array modified.
	 * @internal
	 */
	static protected function foreignTable($items, $fieldValue, $TSconfig, $field, $pFFlag = FALSE) {
		$languageService = static::getLanguageService();
		$db = static::getDatabaseConnection();

		// Init:
		$pF = $pFFlag ? 'neg_' : '';
		$f_table = $fieldValue['config'][$pF . 'foreign_table'];
		$uidPre = $pFFlag ? '-' : '';
		// Exec query:
		$res = BackendUtility::exec_foreign_table_where_query($fieldValue, $field, $TSconfig, $pF);
		// Perform error test
		if ($db->sql_error()) {
			$msg = htmlspecialchars($db->sql_error());
			$msg .= '<br />' . LF;
			$msg .= $languageService->sL('LLL:EXT:lang/locallang_core.xlf:error.database_schema_mismatch');
			$msgTitle = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:error.database_schema_mismatch_title');
			/** @var $flashMessage FlashMessage */
			$flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $msg, $msgTitle, FlashMessage::ERROR, TRUE);
			/** @var $flashMessageService FlashMessageService */
			$flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
			/** @var $defaultFlashMessageQueue FlashMessageQueue */
			$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
			$defaultFlashMessageQueue->enqueue($flashMessage);
			return array();
		}
		// Get label prefix.
		$lPrefix = $languageService->sL($fieldValue['config'][$pF . 'foreign_table_prefix']);
		// Get icon field + path if any:
		$iField = $GLOBALS['TCA'][$f_table]['ctrl']['selicon_field'];
		$iPath = trim($GLOBALS['TCA'][$f_table]['ctrl']['selicon_field_path']);
		// Traverse the selected rows to add them:
		while ($row = $db->sql_fetch_assoc($res)) {
			BackendUtility::workspaceOL($f_table, $row);
			if (is_array($row)) {
				// Prepare the icon if available:
				if ($iField && $iPath && $row[$iField]) {
					$iParts = GeneralUtility::trimExplode(',', $row[$iField], TRUE);
					$icon = '../' . $iPath . '/' . trim($iParts[0]);
				} elseif (GeneralUtility::inList('singlebox,checkbox', $fieldValue['config']['renderMode'])) {
					$icon = IconUtility::mapRecordTypeToSpriteIconName($f_table, $row);
				} else {
					$icon = '';
				}
				// Add the item:
				$items[] = array(
					$lPrefix . htmlspecialchars(BackendUtility::getRecordTitle($f_table, $row)),
					$uidPre . $row['uid'],
					$icon
				);
			}
		}
		$db->sql_free_result($res);
		return $items;
	}

	/**
	 * @return LanguageService
	 */
	static protected function  getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * @return DatabaseConnection
	 */
	static protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * @return BackendUserAuthentication
	 */
	static protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

}
