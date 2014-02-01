<?php
namespace TYPO3\CMS\Backend\View;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 GridView Team
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Backend layout for CMS
 *
 * @author GridView Team
 */
class BackendLayoutView implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var BackendLayout\DataProviderCollection
	 */
	protected $dataProviderCollection;

	/**
	 * @var array
	 */
	protected $selectedCombinedIdentifier = array();

	/**
	 * @var array
	 */
	protected $selectedBackendLayout = array();

	/**
	 * Creates this object and initializes data providers.
	 */
	public function __construct() {
		$this->initializeDataProviderCollection();
	}

	/**
	 * Initializes data providers
	 *
	 * @return void
	 */
	protected function initializeDataProviderCollection() {
		/** @var $dataProviderCollection BackendLayout\DataProviderCollection */
		$dataProviderCollection = GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Backend\\View\\BackendLayout\\DataProviderCollection'
		);

		$dataProviderCollection->add(
			'default',
			'TYPO3\\CMS\\Backend\\View\\BackendLayout\\DefaultDataProvider'
		);

		if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider'])) {
			$dataProviders = (array) $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider'];
			foreach ($dataProviders as $identifier => $className) {
				$dataProviderCollection->add($identifier, $className);
			}
		}

		$this->setDataProviderCollection($dataProviderCollection);
	}

	/**
	 * @param BackendLayout\DataProviderCollection $dataProviderCollection
	 */
	public function setDataProviderCollection(BackendLayout\DataProviderCollection $dataProviderCollection) {
		$this->dataProviderCollection = $dataProviderCollection;
	}

	/**
	 * @return BackendLayout\DataProviderCollection
	 */
	public function getDataProviderCollection() {
		return $this->dataProviderCollection;
	}

	/**
	 * Gets backend layout items to be shown in the forms engine.
	 * This method is called as "itemsProcFunc" with the accordant context
	 * for pages.backend_layout and pages.backend_layout_next_level.
	 *
	 * @param array $parameters
	 */
	public function addBackendLayoutItems(array $parameters) {
		$pageId = $this->determinePageId($parameters['table'], $parameters['row']);
		$pageTsConfig = (array) BackendUtility::getPagesTSconfig($pageId);
		$identifiersToBeExcluded = $this->getIdentifiersToBeExcluded($pageTsConfig);

		$dataProviderContext = $this->createDataProviderContext()
			->setPageId($pageId)
			->setData($parameters['row'])
			->setTableName($parameters['table'])
			->setFieldName($parameters['field'])
			->setPageTsConfig($pageTsConfig);

		$backendLayoutCollections = $this->getDataProviderCollection()->getBackendLayoutCollections($dataProviderContext);
		foreach ($backendLayoutCollections as $backendLayoutCollection) {
			$combinedIdentifierPrefix = '';
			if ($backendLayoutCollection->getIdentifier() !== 'default') {
				$combinedIdentifierPrefix = $backendLayoutCollection->getIdentifier() . '__';
			}

			foreach ($backendLayoutCollection->getAll() as $backendLayout) {
				$combinedIdentifier = $combinedIdentifierPrefix . $backendLayout->getIdentifier();

				if (in_array($combinedIdentifier, $identifiersToBeExcluded, TRUE)) {
					continue;
				}

				$parameters['items'][] = array(
					$this->getLanguageService()->sL($backendLayout->getTitle()),
					$combinedIdentifier,
					$backendLayout->getIconPath(),
				);
			}
		}
	}

	/**
	 * Determines the page id for a given record of a database table.
	 *
	 * @param string $tableName
	 * @param array $data
	 * @return NULL|integer
	 */
	protected function determinePageId($tableName, array $data) {
		$pageId = NULL;

		if (strpos($data['uid'], 'NEW') === 0) {
			// negative uid_pid values of content elements indicate that the element has been inserted after an existing element
			// so there is no pid to get the backendLayout for and we have to get that first
			if ($data['pid'] < 0) {
				$existingElement = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
					'pid', $tableName, 'uid='  . abs($data['pid'])
				);
				if ($existingElement !== NULL) {
					$pageId = $existingElement['pid'];
				}
			} else {
				$pageId = $data['pid'];
			}
		} elseif ($tableName === 'pages') {
			$pageId = $data['uid'];
		} else {
			$pageId = $data['pid'];
		}

		return $pageId;
	}

	/**
	 * Returns the backend layout which should be used for this page.
	 *
	 * @param integer $pageId
	 * @return boolean|string Identifier of the backend layout to be used, or FALSE if none
	 */
	public function getSelectedCombinedIdentifier($pageId) {
		if (!isset($this->selectedCombinedIdentifier[$pageId])) {
			$page = $this->getPage($pageId);
			$this->selectedCombinedIdentifier[$pageId] = (string) $page['backend_layout'];

			if ($this->selectedCombinedIdentifier[$pageId] === '-1') {
				// If it is set to "none" - don't use any
				$this->selectedCombinedIdentifier[$pageId] = FALSE;
			} elseif ($this->selectedCombinedIdentifier[$pageId] === '' || $this->selectedCombinedIdentifier[$pageId] === '0') {
				// If it not set check the root-line for a layout on next level and use this
				// (root-line starts with current page and has page "0" at the end)
				$rootLine = $this->getRootLine($pageId);
				// Remove first and last element (current and root page)
				array_shift($rootLine);
				array_pop($rootLine);
				foreach ($rootLine as $rootLinePage) {
					$this->selectedCombinedIdentifier[$pageId] = (string) $rootLinePage['backend_layout_next_level'];
					if ($this->selectedCombinedIdentifier[$pageId] === '-1') {
						// If layout for "next level" is set to "none" - don't use any and stop searching
						$this->selectedCombinedIdentifier[$pageId] = FALSE;
						break;
					} elseif ($this->selectedCombinedIdentifier[$pageId] !== '' && $this->selectedCombinedIdentifier[$pageId] !== '0') {
						// Stop searching if a layout for "next level" is set
						break;
					}
				}
			}
		}
		// If it is set to a positive value use this
		return $this->selectedCombinedIdentifier[$pageId];
	}

	/**
	 * Gets backend layout identifiers to be excluded
	 *
	 * @param array $pageTSconfig
	 * @return array
	 */
	protected function getIdentifiersToBeExcluded(array $pageTSconfig) {
		$identifiersToBeExcluded = array();

		if (ArrayUtility::isValidPath($pageTSconfig, 'options./backendLayout./exclude')) {
			$identifiersToBeExcluded = GeneralUtility::trimExplode(
				',',
				ArrayUtility::getValueByPath($pageTSconfig, 'options./backendLayout./exclude'),
				TRUE
			);
		}

		return $identifiersToBeExcluded;
	}

	/**
	 * Gets colPos items to be shown in the forms engine.
	 * This method is called as "itemsProcFunc" with the accordant context
	 * for tt_content.colPos.
	 *
	 * @param array $parameters
	 * @return void
	 */
	public function colPosListItemProcFunc(array $parameters) {
		$pageId = $this->determinePageId($parameters['table'], $parameters['row']);

		if ($pageId !== NULL) {
			$parameters['items'] = $this->addColPosListLayoutItems($pageId, $parameters['items']);
		}
	}

	/**
	 * Adds items to a colpos list
	 *
	 * @param integer $pageId
	 * @param array $items
	 * @return array
	 */
	protected function addColPosListLayoutItems($pageId, $items) {
		$layout = $this->getSelectedBackendLayout($pageId);
		if ($layout && $layout['__items']) {
			$items = $layout['__items'];
		}
		return $items;
	}

	/**
	 * Gets the list of available columns for a given page id
	 *
	 * @param integer $id
	 * @return array $tcaItems
	 */
	public function getColPosListItemsParsed($id) {
		$tsConfig = BackendUtility::getModTSconfig($id, 'TCEFORM.tt_content.colPos');
		$tcaConfig = $GLOBALS['TCA']['tt_content']['columns']['colPos']['config'];
		/** @var $tceForms \TYPO3\CMS\Backend\Form\FormEngine */
		$tceForms = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\FormEngine');
		$tcaItems = $tcaConfig['items'];
		$tcaItems = $tceForms->addItems($tcaItems, $tsConfig['properties']['addItems.']);
		if (isset($tcaConfig['itemsProcFunc']) && $tcaConfig['itemsProcFunc']) {
			$tcaItems = $this->addColPosListLayoutItems($id, $tcaItems);
		}
		foreach (GeneralUtility::trimExplode(',', $tsConfig['properties']['removeItems'], TRUE) as $removeId) {
			foreach ($tcaItems as $key => $item) {
				if ($item[1] == $removeId) {
					unset($tcaItems[$key]);
				}
			}
		}
		return $tcaItems;
	}

	/**
	 * Gets the selected backend layout
	 *
	 * @param integer $pageId
	 * @return array|NULL $backendLayout
	 */
	public function getSelectedBackendLayout($pageId) {
		if (isset($this->selectedBackendLayout[$pageId])) {
			return $this->selectedBackendLayout[$pageId];
		}
		$backendLayoutData = NULL;

		$selectedCombinedIdentifier = $this->getSelectedCombinedIdentifier($pageId);
		// If no backend layout is selected, use default
		if (empty($selectedCombinedIdentifier)) {
			$selectedCombinedIdentifier = 'default';
		}

		$backendLayout = $this->getDataProviderCollection()->getBackendLayout($selectedCombinedIdentifier, $pageId);
		// If backend layout is not found available anymore, use default
		if (is_null($backendLayout)) {
			$selectedCombinedIdentifier = 'default';
			$backendLayout = $this->getDataProviderCollection()->getBackendLayout($selectedCombinedIdentifier, $pageId);
		}

		if (!empty($backendLayout)) {
			/** @var $parser \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser */
			$parser = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
			/** @var \TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher $conditionMatcher */
			$conditionMatcher = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Configuration\\TypoScript\\ConditionMatching\\ConditionMatcher');
			$parser->parse($parser->checkIncludeLines($backendLayout->getConfiguration()), $conditionMatcher);

			$backendLayoutData = array();
			$backendLayoutData['config'] = $backendLayout->getConfiguration();
			$backendLayoutData['__config'] = $parser->setup;
			$backendLayoutData['__items'] = array();
			$backendLayoutData['__colPosList'] = array();

			// create items and colPosList
			if (!empty($backendLayoutData['__config']['backend_layout.']['rows.'])) {
				foreach ($backendLayoutData['__config']['backend_layout.']['rows.'] as $row) {
					if (!empty($row['columns.'])) {
						foreach ($row['columns.'] as $column) {
							$backendLayoutData['__items'][] = array(
								GeneralUtility::isFirstPartOfStr($column['name'], 'LLL:') ? $this->getLanguageService()->sL($column['name']) : $column['name'],
								$column['colPos'],
								NULL
							);
							$backendLayoutData['__colPosList'][] = $column['colPos'];
						}
					}
				}
			}

			$this->selectedBackendLayout[$pageId] = $backendLayoutData;
		}

		return $backendLayoutData;
	}

	/**
	 * Get default columns layout
	 *
	 * @return string Default four column layout
	 * @static
	 */
	static public function getDefaultColumnLayout() {
		return '
		backend_layout {
			colCount = 4
			rowCount = 1
			rows {
				1 {
					columns {
						1 {
							name = LLL:EXT:cms/locallang_ttc.xlf:colPos.I.0
							colPos = 1
						}
						2 {
							name = LLL:EXT:cms/locallang_ttc.xlf:colPos.I.1
							colPos = 0
						}
						3 {
							name = LLL:EXT:cms/locallang_ttc.xlf:colPos.I.2
							colPos = 2
						}
						4 {
							name = LLL:EXT:cms/locallang_ttc.xlf:colPos.I.3
							colPos = 3
						}
					}
				}
			}
		}
		';
	}

	/**
	 * Gets a page record.
	 *
	 * @param integer $pageId
	 * @return NULL|array
	 */
	protected function getPage($pageId) {
		$page = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
			'uid, pid, backend_layout',
			'pages',
			'uid=' . (int)$pageId
		);
		BackendUtility::workspaceOL('pages', $page);
		return $page;
	}

	/**
	 * Gets the page root-line.
	 *
	 * @param integer $pageId
	 * @return array
	 */
	protected function getRootLine($pageId) {
		return BackendUtility::BEgetRootLine($pageId, '', TRUE);
	}

	/**
	 * @return BackendLayout\DataProviderContext
	 */
	protected function createDataProviderContext() {
		return GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Backend\\View\\BackendLayout\\DataProviderContext'
		);
	}

	/**
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

}
