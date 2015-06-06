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
							$this->messages[] = 'Migrated t3editor wizard in TCA of table ' . $table . ' field ' . $fieldName . ' to a renderType definition.';
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
												$this->messages[] = 'Migrated t3editor wizard in TCA of table ' . $table . ' field ' . $fieldName
													. ' to a renderType definition with columnsOverrides in type ' . $typeName . '.';
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

}
