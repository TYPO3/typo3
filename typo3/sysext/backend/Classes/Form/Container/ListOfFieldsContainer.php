<?php
namespace TYPO3\CMS\Backend\Form\Container;

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
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Render a given list of field of a TCA table.
 *
 * This is an entry container called from FormEngine to handle a
 * list of specific fields. Access rights are checked here and globalOption array
 * is prepared for further processing of single fields by PaletteAndSingleContainer.
 */
class ListOfFieldsContainer extends AbstractContainer {

	/**
	 * Entry method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$table = $this->globalOptions['table'];
		$row = $this->globalOptions['databaseRow'];
		$list = $this->globalOptions['fieldListToRender'];

		if (!$GLOBALS['TCA'][$table]) {
			return $this->initializeResultArray();
		}

		$languageService = $this->getLanguageService();
		// Load the description content for the table if requested
		if ($GLOBALS['TCA'][$table]['interface']['always_description']) {
			$languageService->loadSingleTableDescription($table);
		}

		// If this is a localized record, stuff data from original record to local registry, will then be given to child elements
		$this->registerDefaultLanguageData($table, $row);

		$list = array_unique(GeneralUtility::trimExplode(',', $list, TRUE));
		$typesFieldConfig = BackendUtility::getTCAtypes($table, $row, 1);
		$finalFieldsConfiguration = array();
		foreach ($list as $singleField) {
			if (!is_array($GLOBALS['TCA'][$table]['columns'][$singleField])) {
				continue;
			}
			if (isset($typesFieldConfig[$singleField]['origString'])) {
				$fieldConfiguration = $this->explodeSingleFieldShowItemConfiguration($typesFieldConfig[$singleField]['origString']);
				// Fields of sub palettes should not be rendered
				$fieldConfiguration['paletteName'] = '';
			} else {
				$fieldConfiguration = array(
					'fieldName' => $singleField,
				);
			}
			$finalFieldsConfiguration[] = implode(';', $fieldConfiguration);
		}

		$options = $this->globalOptions;
		$options['fieldsArray'] = $finalFieldsConfiguration;
		/** @var PaletteAndSingleContainer $paletteAndSingleContainer */
		$paletteAndSingleContainer = GeneralUtility::makeInstance(PaletteAndSingleContainer::class);
		$paletteAndSingleContainer->setGlobalOptions($options);
		return $paletteAndSingleContainer->render();
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

}