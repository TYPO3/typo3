<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Set or initialize configuration for inline fields in TCA
 */
class TcaInlineConfiguration extends AbstractItemProvider implements FormDataProviderInterface {

	/**
	 * Find all inline fields and force proper configuration
	 *
	 * @param array $result
	 * @return array
	 * @throws \UnexpectedValueException If inline configuration is broken
	 */
	public function addData(array $result) {
		foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
			if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'inline') {
				continue;
			}

			// Throw if an inline field without foreign_table is set
			if (!isset($fieldConfig['config']['foreign_table'])) {
				throw new \UnexpectedValueException(
					'Inline field ' . $fieldName . ' of table ' . $result['tableName'] . ' must have a foreign_table config',
					1443793404
				);
			}

			$result = $this->initializeMinMaxItems($result, $fieldName);
			$result = $this->initializeLocalizationMode($result, $fieldName);
			$result = $this->initializeAppearance($result, $fieldName);
		}
		return $result;
	}

	/**
	 * Set and validate minitems and maxitems in config
	 *
	 * @param array $result Result array
	 * @param string $fieldName Current handle field name
	 * @return array Modified item array
	 * @return array
	 */
	protected function initializeMinMaxItems(array $result, $fieldName) {
		$config = $result['processedTca']['columns'][$fieldName]['config'];

		$minItems = 0;
		if (isset($config['minitems'])) {
			$minItems = MathUtility::forceIntegerInRange($config['minitems'], 0);
		}
		$result['processedTca']['columns'][$fieldName]['config']['minitems'] = $minItems;

		$maxItems = 100000;
		if (isset($config['maxitems'])) {
			$maxItems = MathUtility::forceIntegerInRange($config['maxitems'], 1);
		}
		$result['processedTca']['columns'][$fieldName]['config']['maxitems'] = $maxItems;

		return $result;
	}

	/**
	 * Set appearance configuration
	 *
	 * @param array $result Result array
	 * @param string $fieldName Current handle field name
	 * @return array Modified item array
	 * @return array
	 */
	protected function initializeAppearance(array $result, $fieldName) {
		$config = $result['processedTca']['columns'][$fieldName]['config'];
		if (!isset($config['appearance']) || !is_array($config['appearance'])) {
			// Init appearance if not set
			$config['appearance'] = [];
		}
		// Set the position/appearance of the "Create new record" link
		if (isset($config['foreign_selector']) && $config['foreign_selector']
			&& (!isset($config['appearance']['useCombination']) || !$config['appearance']['useCombination'])
		) {
			$config['appearance']['levelLinksPosition'] = 'none';
		} elseif (!isset($config['appearance']['levelLinksPosition'])
			|| !in_array($config['appearance']['levelLinksPosition'], array('top', 'bottom', 'both', 'none'), TRUE)
		) {
			$config['appearance']['levelLinksPosition'] = 'top';
		}
		$config['appearance']['showPossibleLocalizationRecords']
			= isset($config['appearance']['showPossibleLocalizationRecords']) && $config['appearance']['showPossibleLocalizationRecords'];
		$config['appearance']['showRemovedLocalizationRecords']
			= isset($config['appearance']['showRemovedLocalizationRecords']) && $config['appearance']['showRemovedLocalizationRecords'];
		// Defines which controls should be shown in header of each record
		$enabledControls = [
			'info' => TRUE,
			'new' => TRUE,
			'dragdrop' => TRUE,
			'sort' => TRUE,
			'hide' => TRUE,
			'delete' => TRUE,
			'localize' => TRUE
		];
		if (isset($config['appearance']['enabledControls']) && is_array($config['appearance']['enabledControls'])) {
			$config['appearance']['enabledControls'] = array_merge($enabledControls, $config['appearance']['enabledControls']);
		} else {
			$config['appearance']['enabledControls'] = $enabledControls;
		}
		$result['processedTca']['columns'][$fieldName]['config'] = $config;

		return $result;
	}

	/**
	 * Set localization mode. This will end up with localizationMode to be set to either 'select', 'keep'
	 * or 'none' if the handled record is a localized record.
	 *
	 * @see TcaInline for a detailed explanation on the meaning of these modes.
	 *
	 * @param array $result Result array
	 * @param string $fieldName Current handle field name
	 * @return array Modified item array
	 * @throws \UnexpectedValueException If localizationMode configuration is broken
	 */
	protected function initializeLocalizationMode(array $result, $fieldName) {
		if ($result['defaultLanguageRow'] === NULL) {
			// Currently handled parent is a localized row if a former provider added the "default" row
			// If handled record is not localized, set localizationMode to 'none' and return
			$result['processedTca']['columns'][$fieldName]['config']['behaviour']['localizationMode'] = 'none';
			return $result;
		}

		$childTableName = $result['processedTca']['columns'][$fieldName]['config']['foreign_table'];
		$parentConfig = $result['processedTca']['columns'][$fieldName]['config'];

		$isChildTableLocalizable = FALSE;
		// @todo: Direct $globals access here, but no good idea yet how to get rid of this
		if (isset($GLOBALS['TCA'][$childTableName]['ctrl']) && is_array($GLOBALS['TCA'][$childTableName]['ctrl'])
			&& isset($GLOBALS['TCA'][$childTableName]['ctrl']['languageField'])
			&& $GLOBALS['TCA'][$childTableName]['ctrl']['languageField']
			&& isset($GLOBALS['TCA'][$childTableName]['ctrl']['transOrigPointerField'])
			&& $GLOBALS['TCA'][$childTableName]['ctrl']['transOrigPointerField']
		) {
			$isChildTableLocalizable = TRUE;
		}

		$mode = NULL;

		if (isset($parentConfig['behaviour']['localizationMode'])) {
			// Use explicit set mode, but validate before use
			// Use  mode if set, but throw if not set to either 'select' or 'keep'
			if ($parentConfig['behaviour']['localizationMode'] !== 'keep' && $parentConfig['behaviour']['localizationMode'] !== 'select') {
				throw new \UnexpectedValueException(
					'localizationMode of table ' . $result['tableName'] . ' field ' . $fieldName . ' is not valid, set to either \'keep\' or \'select\'',
					1443829370
				);
			}
			// Throw if is set to select, but child can not be localized
			if ($parentConfig['behaviour']['localizationMode'] === 'select' && !$isChildTableLocalizable) {
				throw new \UnexpectedValueException(
					'Wrong configuration: localizationMode of table ' . $result['tableName'] . ' field ' . $fieldName . ' is set to \'select\', but table is not localizable.',
					1443944274
				);
			}
			$mode = $parentConfig['behaviour']['localizationMode'];
		} else {
			// Not set explicitly -> use "none"
			$mode = 'none';
			if ($isChildTableLocalizable) {
				// Except if child is localizable, then use "select"
				$mode = 'select';
			}
		}

		$result['processedTca']['columns'][$fieldName]['config']['behaviour']['localizationMode'] = $mode;
		return $result;
	}

}
