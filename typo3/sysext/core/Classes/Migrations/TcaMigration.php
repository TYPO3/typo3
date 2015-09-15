<?php
namespace TYPO3\CMS\Core\Migrations;

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
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Migrate TCA from old to new syntax. Used in bootstrap and Flex Form Data Structures.
 *
 * @internal Class and API may change any time.
 */
class TcaMigration {

	/**
	 * Accumulate migration messages
	 *
	 * @var array
	 */
	protected $messages = array();

	/**
	 * Migrate old TCA to new TCA.
	 *
	 * See unit tests for details.
	 *
	 * @param array $tca
	 * @return array
	 */
	public function migrate(array $tca) {
		$tca = $this->migrateT3editorWizardToRenderTypeT3editorIfNotEnabledByTypeConfig($tca);
		$tca = $this->migrateSpecialConfigurationAndRemoveShowItemStylePointerConfig($tca);
		$tca = $this->migrateT3editorWizardWithEnabledByTypeConfigToColumnsOverrides($tca);
		$tca = $this->migrateShowItemAdditionalPaletteToOwnPalette($tca);
		$tca = $this->migrateIconsForFormFieldWizardsToNewLocation($tca);
		$tca = $this->migrateExtAndSysextPathToEXTPath($tca);
		$tca = $this->migrateIconsInOptionTags($tca);
		$tca = $this->migrateIconfileRelativePathOrFilenameOnlyToExtReference($tca);
		$tca = $this->migrateSelectFieldRenderType($tca);
		// @todo: if showitem/defaultExtras wizards[xy] is migrated to columnsOverrides here, enableByTypeConfig could be dropped
		return $tca;
	}

	/**
	 * Get messages of migrated fields. Can be used for deprecation messages after migrate() was called.
	 *
	 * @return array Migration messages
	 */
	public function getMessages() {
		return $this->messages;
	}

	/**
	 * Migrate type=text field with t3editor wizard to renderType=t3editor without this wizard
	 *
	 * @param array $tca Incoming TCA
	 * @return array Migrated TCA
	 */
	protected function migrateT3editorWizardToRenderTypeT3editorIfNotEnabledByTypeConfig(array $tca) {
		$newTca = $tca;
		foreach ($tca as $table => $tableDefinition) {
			if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
				continue;
			}
			foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
				if (
					!empty($fieldConfig['config']['type']) // type is set
					&& trim($fieldConfig['config']['type']) === 'text' // to "text"
					&& isset($fieldConfig['config']['wizards'])
					&& is_array($fieldConfig['config']['wizards']) // and there are wizards
				) {
					foreach ($fieldConfig['config']['wizards'] as $wizardName => $wizardConfig) {
						if (
							!empty($wizardConfig['userFunc']) // a userFunc is defined
							&& trim($wizardConfig['userFunc']) === 'TYPO3\\CMS\\T3editor\\FormWizard->main' // and set to FormWizard
							&& (
								!isset($wizardConfig['enableByTypeConfig']) // and enableByTypeConfig is not set
								|| (isset($wizardConfig['enableByTypeConfig']) && !$wizardConfig['enableByTypeConfig'])  // or set, but not enabled
							)
						) {
							// Set renderType from text to t3editor
							$newTca[$table]['columns'][$fieldName]['config']['renderType'] = 't3editor';
							// Unset this wizard definition
							unset($newTca[$table]['columns'][$fieldName]['config']['wizards'][$wizardName]);
							// Move format parameter
							if (!empty($wizardConfig['params']['format'])) {
								$newTca[$table]['columns'][$fieldName]['config']['format'] = $wizardConfig['params']['format'];
							}
							$this->messages[] = 'Migrated t3editor wizard in TCA of table "' . $table . '" field "' . $fieldName . '" to a renderType definition.';
						}
					}
					// If no wizard is left after migration, unset the whole sub array
					if (empty($newTca[$table]['columns'][$fieldName]['config']['wizards'])) {
						unset($newTca[$table]['columns'][$fieldName]['config']['wizards']);
					}
				}
			}
		}
		return $newTca;
	}

	/**
	 * Remove "style pointer", the 5th parameter from "types" "showitem" configuration.
	 * Move "specConf", 4th parameter from "tyes" "showitem" to "types" "columnsOverrides.
	 *
	 * @param array $tca Incoming TCA
	 * @return array Modified TCA
	 */
	protected function migrateSpecialConfigurationAndRemoveShowItemStylePointerConfig(array $tca) {
		$newTca = $tca;
		foreach ($tca as $table => $tableDefinition) {
			if (!isset($tableDefinition['types']) || !is_array($tableDefinition['types'])) {
				continue;
			}
			foreach ($tableDefinition['types'] as $typeName => $typeArray) {
				if (!is_string($typeArray['showitem']) || strpos($typeArray['showitem'], ';') === FALSE) {
					// Continue directly if no semicolon is found
					continue;
				}
				$itemList = GeneralUtility::trimExplode(',', $typeArray['showitem'], TRUE);
				$newFieldStrings = array();
				foreach ($itemList as $fieldString) {
					$fieldString = rtrim($fieldString, ';');
					// Unpack the field definition, migrate and remove as much as possible
					// Keep empty parameters in trimExplode here (third parameter FALSE), so position is not changed
					$fieldArray = GeneralUtility::trimExplode(';', $fieldString);
					$fieldArray = array(
						'fieldName' => isset($fieldArray[0]) ? $fieldArray[0] : '',
						'fieldLabel' => isset($fieldArray[1]) ? $fieldArray[1] : NULL,
						'paletteName' => isset($fieldArray[2]) ? $fieldArray[2] : NULL,
						'fieldExtra' => isset($fieldArray[3]) ? $fieldArray[3] : NULL,
					);
					$fieldName = $fieldArray['fieldName'];
					if (!empty($fieldArray['fieldExtra'])) {
						// Move fieldExtra "specConf" to columnsOverrides "defaultExtras"
						if (!isset($newTca[$table]['types'][$typeName]['columnsOverrides'])) {
							$newTca[$table]['types'][$typeName]['columnsOverrides'] = array();
						}
						if (!isset($newTca[$table]['types'][$typeName]['columnsOverrides'][$fieldArray['fieldName']])) {
							$newTca[$table]['types'][$typeName]['columnsOverrides'][$fieldArray['fieldName']] = array();
						}
						// Merge with given defaultExtras from columns.
						// They will be the first part of the string, so if "specConf" from types changes the same settings,
						// those will override settings from defaultExtras of columns
						$newDefaultExtras = array();
						if (!empty($tca[$table]['columns'][$fieldArray['fieldName']]['defaultExtras'])) {
							$newDefaultExtras[] = $tca[$table]['columns'][$fieldArray['fieldName']]['defaultExtras'];
						}
						$newDefaultExtras[] = $fieldArray['fieldExtra'];
						$newTca[$table]['types'][$typeName]['columnsOverrides'][$fieldArray['fieldName']]['defaultExtras'] = implode(':', $newDefaultExtras);
					}
					unset($fieldArray['fieldExtra']);
					if (count($fieldArray) === 3 && empty($fieldArray['paletteName'])) {
						unset($fieldArray['paletteName']);
					}
					if (count($fieldArray) === 2 && empty($fieldArray['fieldLabel'])) {
						unset($fieldArray['fieldLabel']);
					}
					if (count($fieldArray) === 1 && empty($fieldArray['fieldName'])) {
						// The field may vanish if nothing is left
						unset($fieldArray['fieldName']);
					}
					$newFieldString = implode(';', $fieldArray);
					if ($newFieldString !== $fieldString) {
						$this->messages[] = 'Changed showitem string of TCA table "' . $table . '" type "' . $typeName . '" due to changed field "' . $fieldName . '".';
					}
					if (!empty($newFieldString)) {
						$newFieldStrings[] = $newFieldString;
					}
				}
				$newTca[$table]['types'][$typeName]['showitem'] = implode(',', $newFieldStrings);
			}
		}
		return $newTca;
	}

	/**
	 * Migrate type=text field with t3editor wizard that is "enableByTypeConfig" to columnsOverrides
	 * with renderType=t3editor
	 *
	 * @param array $tca Incoming TCA
	 * @return array Migrated TCA
	 */
	protected function migrateT3editorWizardWithEnabledByTypeConfigToColumnsOverrides(array $tca) {
		$newTca = $tca;
		foreach ($tca as $table => $tableDefinition) {
			if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
				continue;
			}
			foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
				if (
					!empty($fieldConfig['config']['type']) // type is set
					&& trim($fieldConfig['config']['type']) === 'text' // to "text"
					&& isset($fieldConfig['config']['wizards'])
					&& is_array($fieldConfig['config']['wizards']) // and there are wizards
				) {
					foreach ($fieldConfig['config']['wizards'] as $wizardName => $wizardConfig) {
						if (
							!empty($wizardConfig['userFunc']) // a userFunc is defined
							&& trim($wizardConfig['userFunc']) === 'TYPO3\CMS\T3editor\FormWizard->main' // and set to FormWizard
							&& !empty($wizardConfig['enableByTypeConfig']) // and enableByTypeConfig is enabled
						) {
							// Remove this wizard
							unset($newTca[$table]['columns'][$fieldName]['config']['wizards'][$wizardName]);
							// Find configured types that use this wizard
							if (!isset($tableDefinition['types']) || !is_array($tableDefinition['types'])) {
								// No type definition at all ... continue directly
								continue;
							}
							foreach ($tableDefinition['types'] as $typeName => $typeArray) {
								if (
									empty($typeArray['columnsOverrides'][$fieldName]['defaultExtras'])
									|| strpos($typeArray['columnsOverrides'][$fieldName]['defaultExtras'], $wizardName) === FALSE
								) {
									// Continue directly if this wizard is not enabled for given type
									continue;
								}
								$defaultExtras = $typeArray['columnsOverrides'][$fieldName]['defaultExtras'];
								$defaultExtrasArray = GeneralUtility::trimExplode(':', $defaultExtras, TRUE);
								$newDefaultExtrasArray = array();
								foreach ($defaultExtrasArray as $fieldExtraField) {
									// There might be multiple enabled wizards separated by | ... split them
									if (substr($fieldExtraField, 0, 8) === 'wizards[') {
										$enabledWizards = substr($fieldExtraField, 8, strlen($fieldExtraField) - 8); // Cut off "wizards[
										$enabledWizards = substr($enabledWizards, 0, strlen($enabledWizards) - 1);
										$enabledWizardsArray = GeneralUtility::trimExplode('|', $enabledWizards, TRUE);
										$newEnabledWizardsArray = array();
										foreach ($enabledWizardsArray as $enabledWizardName) {
											if ($enabledWizardName === $wizardName) {
												// Found a columnsOverrides configuration that has this wizard enabled
												// Force renderType = t3editor
												$newTca[$table]['types'][$typeName]['columnsOverrides'][$fieldName]['config']['renderType'] = 't3editor';
												// Transfer format option if given
												if (!empty($wizardConfig['params']['format'])) {
													$newTca[$table]['types'][$typeName]['columnsOverrides'][$fieldName]['config']['format'] = $wizardConfig['params']['format'];
												}
												$this->messages[] = 'Migrated t3editor wizard in TCA of table "' . $table . '" field "' . $fieldName
													. '" to a renderType definition with columnsOverrides in type "' . $typeName . '".';
											} else {
												// Some other enabled wizard
												$newEnabledWizardsArray[] = $enabledWizardName;
											}
										}
										if (!empty($newEnabledWizardsArray)) {
											$newDefaultExtrasArray[] = 'wizards[' . implode('|', $newEnabledWizardsArray) . ']';
										}
									} else {
										$newDefaultExtrasArray[] = $fieldExtraField;
									}
								}
								if (!empty($newDefaultExtrasArray)) {
									$newTca[$table]['types'][$typeName]['columnsOverrides'][$fieldName]['defaultExtras'] = implode(':', $newDefaultExtrasArray);
								} else {
									unset($newTca[$table]['types'][$typeName]['columnsOverrides'][$fieldName]['defaultExtras']);
								}
							}
						}
					}
					// If no wizard is left after migration, unset the whole sub array
					if (empty($newTca[$table]['columns'][$fieldName]['config']['wizards'])) {
						unset($newTca[$table]['columns'][$fieldName]['config']['wizards']);
					}
				}
			}
		}
		return $newTca;
	}

	/**
	 * Migrate types showitem 'aField;aLabel;aPalette' to 'afield;aLabel, --palette--;;aPalette'
	 *
	 * Old showitem can have a syntax like:
	 * fieldName;aLabel;aPalette
	 * This way, the palette with name "aPalette" is rendered after fieldName.
	 * The migration parses this to a syntax like:
	 * fieldName;aLabel, --palette--;;paletteName
	 *
	 * @param array $tca Incoming TCA
	 * @return array Migrated TCA
	 */
	protected function migrateShowItemAdditionalPaletteToOwnPalette(array $tca) {
		$newTca = $tca;
		foreach ($tca as $table => $tableDefinition) {
			if (!isset($tableDefinition['types']) || !is_array($tableDefinition['types'])) {
				continue;
			}
			foreach ($tableDefinition['types'] as $typeName => $typeArray) {
				if (
					!isset($typeArray['showitem'])
					|| !is_string($typeArray['showitem'])
					|| strpos($typeArray['showitem'], ';') === FALSE // no field parameters
				) {
					continue;
				}
				$itemList = GeneralUtility::trimExplode(',', $typeArray['showitem'], TRUE);
				$newFieldStrings = array();
				foreach ($itemList as $fieldString) {
					$fieldArray = GeneralUtility::trimExplode(';', $fieldString);
					$fieldArray = array(
						'fieldName' => isset($fieldArray[0]) ? $fieldArray[0] : '',
						'fieldLabel' => isset($fieldArray[1]) ? $fieldArray[1] : NULL,
						'paletteName' => isset($fieldArray[2]) ? $fieldArray[2] : NULL,
					);
					if ($fieldArray['fieldName'] !== '--palette--' && $fieldArray['paletteName'] !== NULL) {
						if ($fieldArray['fieldLabel']) {
							$fieldString = $fieldArray['fieldName'] . ';' . $fieldArray['fieldLabel'];
						} else {
							$fieldString = $fieldArray['fieldName'];
						}
						$paletteString = '--palette--;;' . $fieldArray['paletteName'];
						$this->messages[] = 'Migrated TCA table "' . $table . '" showitem field of type "' . $typeName . '": Moved additional palette'
							. ' with name "' . $fieldArray['paletteName'] . '" as 3rd argument of field "' . $fieldArray['fieldName']
							. '" to an own palette. The result of this part is: "' . $fieldString . ', ' . $paletteString . '"';
						$newFieldStrings[] = $fieldString;
						$newFieldStrings[] = $paletteString;
					} else {
						$newFieldStrings[] = $fieldString;
					}
				}
				$newTca[$table]['types'][$typeName]['showitem'] = implode(',', $newFieldStrings);
			}
		}
		return $newTca;
	}

	/**
	 * Migrate core icons for form field wizard to new location
	 *
	 * add.gif => EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_add.gif
	 * link_popup.gif => EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif
	 * wizard_rte2.gif => EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_rte.gif
	 * wizard_table.gif => EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif
	 * edit2.gif => EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_edit.gif
	 * list.gif => EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_list.gif
	 * wizard_forms.gif => EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_forms.gif
	 *
	 * @param array $tca Incoming TCA
	 * @return array Migrated TCA
	 */
	protected function migrateIconsForFormFieldWizardsToNewLocation($tca) {
		$newTca = $tca;

		$oldFileNames = array(
			'add.gif',
			'link_popup.gif',
			'wizard_rte2.gif',
			'wizard_table.gif',
			'edit2.gif',
			'list.gif',
			'wizard_forms.gif',
		);

		$newFileLocations = array(
			'add.gif' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_add.gif',
			'link_popup.gif' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif',
			'wizard_rte2.gif' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_rte.gif',
			'wizard_table.gif' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif',
			'edit2.gif' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_edit.gif',
			'list.gif' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_list.gif',
			'wizard_forms.gif' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_forms.gif',
		);

		foreach ($tca as $table => $tableDefinition) {
			if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
				continue;
			}
			foreach ($tableDefinition['columns'] as $fieldName => $fieldConfig) {
				if (
					isset($fieldConfig['config']['wizards'])
					&& is_array($fieldConfig['config']['wizards']) // and there are wizards
				) {
					foreach ($fieldConfig['config']['wizards'] as $wizardName => $wizardConfig) {
						if (!is_array($wizardConfig)) {
							continue;
						}

						foreach ($wizardConfig as $option => $value) {
							if ($option === 'icon' && in_array($value, $oldFileNames, TRUE)) {
								$newTca[$table]['columns'][$fieldName]['config']['wizards'][$wizardName]['icon'] = $newFileLocations[$value];
								$this->messages[] = 'Migrated icon path of wizard "' . $wizardName . '" in field "' . $fieldName . '" from TCA table "' . $table . '". New path is: ' . $newFileLocations[$value];
							}
						}
					}
				}
			}
		}

		return $newTca;
	}

	/**
	 * Migrate file reference which starts with ext/ or sysext/ to EXT:
	 *
	 * @param array $tca Incoming TCA
	 * @return array Migrated TCA
	 */
	protected function migrateExtAndSysextPathToEXTPath($tca) {
		foreach ($tca as $table => &$tableDefinition) {
			if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
				continue;
			}
			foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
				if (
					!empty($fieldConfig['config']['type']) // type is set
					&& trim($fieldConfig['config']['type']) === 'select' // to "select"
					&& isset($fieldConfig['config']['items'])
					&& is_array($fieldConfig['config']['items']) // and there are items
				) {
					foreach ($fieldConfig['config']['items'] as &$itemConfig) {
						// more then two values? then the third entry is the image path
						if (!empty($itemConfig[2])) {
							$tcaPath = implode('.', [$table, 'columns', $fieldName, 'config', 'items']);
							$pathParts = GeneralUtility::trimExplode('/', $itemConfig[2]);
							// remove first element (ext or sysext)
							array_shift($pathParts);
							$path = implode('/', $pathParts);
							// If the path starts with ext/ or sysext/ migrate it
							if (
								StringUtility::beginsWith($itemConfig[2], 'ext/')
								|| StringUtility::beginsWith($itemConfig[2], 'sysext/')
							) {
								$this->messages[] = '[' . $tcaPath . '] ext/ or sysext/ within the path (' . $path . ') in items array is deprecated, use EXT: reference';
								$itemConfig[2] = 'EXT:' . $path;
							} elseif (StringUtility::beginsWith($itemConfig[2], 'i/')) {
								$this->messages[] = '[' . $tcaPath . '] i/ within the path (' . $path . ') in items array is deprecated, use EXT: reference';
								$itemConfig[2] = 'EXT:t3skin/icons/gfx/' . $itemConfig[2];
							}
						}
					}
				}
			}
		}
		return $tca;
	}

	/**
	 * Migrate "iconsInOptionTags" for "select" TCA fields
	 *
	 * @param array $tca Incoming TCA
	 * @return array Migrated TCA
	 */
	protected function migrateIconsInOptionTags($tca) {
		$newTca = $tca;

		foreach ($newTca as $table => &$tableDefinition) {
			if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
				continue;
			}
			foreach ($tableDefinition['columns'] as $fieldName => &$fieldConfig) {
				if (isset($fieldConfig['config']['iconsInOptionTags'])) {
					unset($fieldConfig['config']['iconsInOptionTags']);
					$this->messages[] = 'Configuration option "iconsInOptionTags" was removed from field "' . $fieldName . '" in TCA table "' . $table . '"';
				}
			}
		}

		return $newTca;
	}

	/**
	 * Migrate "iconfile" references which starts with ../ to EXT: and consisting of filename only to absolute paths in EXT:t3skin
	 *
	 * @param array $tca Incoming TCA
	 * @return array Migrated TCA
	 */
	protected function migrateIconfileRelativePathOrFilenameOnlyToExtReference($tca) {
		foreach ($tca as $table => &$tableDefinition) {
			if (!isset($tableDefinition['ctrl']) || !is_array($tableDefinition['ctrl'])) {
				continue;
			}
			if (!isset($tableDefinition['ctrl']['iconfile'])) {
				continue;
			}
			if (StringUtility::beginsWith($tableDefinition['ctrl']['iconfile'], '../typo3conf/ext/')) {
				$tableDefinition['ctrl']['iconfile'] = str_replace('../typo3conf/ext/', 'EXT:', $tableDefinition['ctrl']['iconfile']);
				$tcaPath = implode('.', [$table, 'ctrl', 'iconfile']);
				$this->messages[] = '[' . $tcaPath . '] relative path to ../typo3conf/ext/ is deprecated, use EXT: instead';
			} elseif (strpos($tableDefinition['ctrl']['iconfile'], '/') === FALSE) {
				$tableDefinition['ctrl']['iconfile'] = 'EXT:t3skin/icons/gfx/i/' . $tableDefinition['ctrl']['iconfile'];
				$tcaPath = implode('.', [$table, 'ctrl', 'iconfile']);
				$this->messages[] = '[' . $tcaPath . '] filename only is deprecated, use EXT: or absolute reference instead';
			}
		}
		return $tca;
	}

	/**
	 * Migrate "type=select" with "renderMode=[tree|singlebox|checkbox]" to "renderType=[selectTree|selectSingleBox|selectCheckBox]".
	 * This migration also take care of "maxitems" settings and set "renderType=[selectSingle|selectMultipleSideBySide]" if no other
	 * renderType is already set.
	 *
	 * @param array $tca
	 * @return array
	 */
	public function migrateSelectFieldRenderType(array $tca) {
		$newTca = $tca;

		foreach ($newTca as $table => &$tableDefinition) {

			if (empty($tableDefinition['columns'])) {
				continue;
			}

			foreach ($tableDefinition['columns'] as $columnName => &$columnDefinition) {
				// Only handle select fields.
				if (empty($columnDefinition['config']['type']) || $columnDefinition['config']['type'] !== 'select') {
					continue;
				}
				// Do not handle field where the render type is set.
				if (!empty($columnDefinition['config']['renderType'])) {
					continue;
				}

				$tableColumnInfo = 'table "' . $table . '" and column "' . $columnName . '"';
				$this->messages[] = 'Using select fields without the "renderType" setting is deprecated in ' . $tableColumnInfo;

				$columnConfig = &$columnDefinition['config'];
				if (!empty($columnConfig['renderMode'])) {
					$this->messages[] = 'The "renderMode" setting for select fields is deprecated. Please use "renderType" instead in ' . $tableColumnInfo;
					switch ($columnConfig['renderMode']) {
						case 'tree':
							$columnConfig['renderType'] = 'selectTree';
							break;
						case 'singlebox':
							$columnConfig['renderType'] = 'selectSingleBox';
							break;
						case 'checkbox':
							$columnConfig['renderType'] = 'selectCheckBox';
							break;
						default:
							$this->messages[] = 'The render mode ' . $columnConfig['renderMode'] . ' is invalid for the select field in ' . $tableColumnInfo;
					}
					continue;
				}

				$maxItems = !empty($columnConfig['maxitems']) ? (int)$columnConfig['maxitems'] : 1;
				if ($maxItems <= 1) {
					$columnConfig['renderType'] = 'selectSingle';
				} else {
					$columnConfig['renderType'] = 'selectMultipleSideBySide';
				}
			}
		}

		return $newTca;
	}
}
