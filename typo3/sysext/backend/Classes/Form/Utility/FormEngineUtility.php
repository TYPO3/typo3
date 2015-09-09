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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

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
				if (!empty($TSconfig['config'])) {
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
				if (\TYPO3\CMS\Core\Utility\StringUtility::endsWith($icon, '.svg')) {
					$selIconInfo = TRUE;
				} else {
					$selIconInfo = getimagesize((PATH_site . GeneralUtility::resolveBackPath(substr($icon, 3))));
				}
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
		if (!empty($iconArray[0]) && is_file(GeneralUtility::resolveBackPath(PATH_typo3 . $iconArray[0]))) {
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
	 * @return int|NULL Pid or null
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
	 * Compatibility layer for methods not in FormEngine scope.
	 *
	 * databaseRow was a flat array with single elements in select and group fields as comma separated list.
	 * With new data handling in FormEngine, this is now an array of element values. There are however "old"
	 * methods that still expect the flat array.
	 * This method implodes the array again to fake the old behavior of a database row before it is given
	 * to those methods.
	 *
	 * @param array $row Incoming array
	 * @return array Flat array
	 * @internal
	 */
	static public function databaseRowCompatibility(array $row) {
		$newRow = [];
		foreach ($row as $fieldName => $fieldValue) {
			if (!is_array($fieldValue)) {
				$newRow[$fieldName] = $fieldValue;
			} else {
				$newElementValue = [];
				foreach ($fieldValue as $itemNumber => $itemValue) {
					if (is_array($itemValue) && array_key_exists(1, $itemValue)) {
						$newElementValue[] = $itemValue[1];
					} else {
						$newElementValue[] = $itemValue;
					}
				}
				$newRow[$fieldName] = implode(',', $newElementValue);
			}
		}
		return $newRow;
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
