<?php
namespace TYPO3\CMS\Backend\View;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Backend layout for CMS
 */
class BackendLayoutView implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var BackendLayout\DataProviderCollection
     */
    protected $dataProviderCollection;

    /**
     * @var array
     */
    protected $selectedCombinedIdentifier = [];

    /**
     * @var array
     */
    protected $selectedBackendLayout = [];

    /**
     * Creates this object and initializes data providers.
     */
    public function __construct()
    {
        $this->initializeDataProviderCollection();
    }

    /**
     * Initializes data providers
     */
    protected function initializeDataProviderCollection()
    {
        /** @var $dataProviderCollection BackendLayout\DataProviderCollection */
        $dataProviderCollection = GeneralUtility::makeInstance(
            BackendLayout\DataProviderCollection::class
        );

        $dataProviderCollection->add(
            'default',
            \TYPO3\CMS\Backend\View\BackendLayout\DefaultDataProvider::class
        );

        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider'])) {
            $dataProviders = (array)$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider'];
            foreach ($dataProviders as $identifier => $className) {
                $dataProviderCollection->add($identifier, $className);
            }
        }

        $this->setDataProviderCollection($dataProviderCollection);
    }

    /**
     * @param BackendLayout\DataProviderCollection $dataProviderCollection
     */
    public function setDataProviderCollection(BackendLayout\DataProviderCollection $dataProviderCollection)
    {
        $this->dataProviderCollection = $dataProviderCollection;
    }

    /**
     * @return BackendLayout\DataProviderCollection
     */
    public function getDataProviderCollection()
    {
        return $this->dataProviderCollection;
    }

    /**
     * Gets backend layout items to be shown in the forms engine.
     * This method is called as "itemsProcFunc" with the accordant context
     * for pages.backend_layout and pages.backend_layout_next_level.
     *
     * @param array $parameters
     */
    public function addBackendLayoutItems(array $parameters)
    {
        $pageId = $this->determinePageId($parameters['table'], $parameters['row']);
        $pageTsConfig = (array)BackendUtility::getPagesTSconfig($pageId);
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

                if (in_array($combinedIdentifier, $identifiersToBeExcluded, true)) {
                    continue;
                }

                $parameters['items'][] = [
                    $this->getLanguageService()->sL($backendLayout->getTitle()),
                    $combinedIdentifier,
                    $backendLayout->getIconPath(),
                ];
            }
        }
    }

    /**
     * Determines the page id for a given record of a database table.
     *
     * @param string $tableName
     * @param array $data
     * @return int|bool Returns page id or false on error
     */
    protected function determinePageId($tableName, array $data)
    {
        if (strpos($data['uid'], 'NEW') === 0) {
            // negative uid_pid values of content elements indicate that the element
            // has been inserted after an existing element so there is no pid to get
            // the backendLayout for and we have to get that first
            if ($data['pid'] < 0) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($tableName);
                $queryBuilder->getRestrictions()
                    ->removeAll();
                $pageId = $queryBuilder
                    ->select('pid')
                    ->from($tableName)
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter(abs($data['pid']), \PDO::PARAM_INT)
                        )
                    )
                    ->execute()
                    ->fetchColumn();
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
     * @param int $pageId
     * @return bool|string Identifier of the backend layout to be used, or FALSE if none
     */
    public function getSelectedCombinedIdentifier($pageId)
    {
        if (!isset($this->selectedCombinedIdentifier[$pageId])) {
            $page = $this->getPage($pageId);
            $this->selectedCombinedIdentifier[$pageId] = (string)$page['backend_layout'];

            if ($this->selectedCombinedIdentifier[$pageId] === '-1') {
                // If it is set to "none" - don't use any
                $this->selectedCombinedIdentifier[$pageId] = false;
            } elseif ($this->selectedCombinedIdentifier[$pageId] === '' || $this->selectedCombinedIdentifier[$pageId] === '0') {
                // If it not set check the root-line for a layout on next level and use this
                // (root-line starts with current page and has page "0" at the end)
                $rootLine = $this->getRootLine($pageId);
                // Remove first and last element (current and root page)
                array_shift($rootLine);
                array_pop($rootLine);
                foreach ($rootLine as $rootLinePage) {
                    $this->selectedCombinedIdentifier[$pageId] = (string)$rootLinePage['backend_layout_next_level'];
                    if ($this->selectedCombinedIdentifier[$pageId] === '-1') {
                        // If layout for "next level" is set to "none" - don't use any and stop searching
                        $this->selectedCombinedIdentifier[$pageId] = false;
                        break;
                    }
                    if ($this->selectedCombinedIdentifier[$pageId] !== '' && $this->selectedCombinedIdentifier[$pageId] !== '0') {
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
    protected function getIdentifiersToBeExcluded(array $pageTSconfig)
    {
        $identifiersToBeExcluded = [];

        if (ArrayUtility::isValidPath($pageTSconfig, 'options./backendLayout./exclude')) {
            $identifiersToBeExcluded = GeneralUtility::trimExplode(
                ',',
                ArrayUtility::getValueByPath($pageTSconfig, 'options./backendLayout./exclude'),
                true
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
     */
    public function colPosListItemProcFunc(array $parameters)
    {
        $pageId = $this->determinePageId($parameters['table'], $parameters['row']);

        if ($pageId !== false) {
            $parameters['items'] = $this->addColPosListLayoutItems($pageId, $parameters['items']);
        }
    }

    /**
     * Adds items to a colpos list
     *
     * @param int $pageId
     * @param array $items
     * @return array
     */
    protected function addColPosListLayoutItems($pageId, $items)
    {
        $layout = $this->getSelectedBackendLayout($pageId);
        if ($layout && $layout['__items']) {
            $items = $layout['__items'];
        }
        return $items;
    }

    /**
     * Gets the list of available columns for a given page id
     *
     * @param int $id
     * @return array $tcaItems
     */
    public function getColPosListItemsParsed($id)
    {
        $tsConfig = BackendUtility::getModTSconfig($id, 'TCEFORM.tt_content.colPos');
        $tcaConfig = $GLOBALS['TCA']['tt_content']['columns']['colPos']['config'];
        $tcaItems = $tcaConfig['items'];
        $tcaItems = $this->addItems($tcaItems, $tsConfig['properties']['addItems.']);
        if (isset($tcaConfig['itemsProcFunc']) && $tcaConfig['itemsProcFunc']) {
            $tcaItems = $this->addColPosListLayoutItems($id, $tcaItems);
        }
        foreach (GeneralUtility::trimExplode(',', $tsConfig['properties']['removeItems'], true) as $removeId) {
            foreach ($tcaItems as $key => $item) {
                if ($item[1] == $removeId) {
                    unset($tcaItems[$key]);
                }
            }
        }
        return $tcaItems;
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
    protected function addItems($items, $iArray)
    {
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
                $items[] = [$languageService->sL($label), $value, $icon];
            }
        }
        return $items;
    }

    /**
     * Gets the selected backend layout
     *
     * @param int $pageId
     * @return array|null $backendLayout
     */
    public function getSelectedBackendLayout($pageId)
    {
        if (isset($this->selectedBackendLayout[$pageId])) {
            return $this->selectedBackendLayout[$pageId];
        }
        $backendLayoutData = null;

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
            $parser = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::class);
            /** @var \TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher $conditionMatcher */
            $conditionMatcher = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher::class);
            $parser->parse($parser->checkIncludeLines($backendLayout->getConfiguration()), $conditionMatcher);

            $backendLayoutData = [];
            $backendLayoutData['config'] = $backendLayout->getConfiguration();
            $backendLayoutData['__config'] = $parser->setup;
            $backendLayoutData['__items'] = [];
            $backendLayoutData['__colPosList'] = [];

            // create items and colPosList
            if (!empty($backendLayoutData['__config']['backend_layout.']['rows.'])) {
                foreach ($backendLayoutData['__config']['backend_layout.']['rows.'] as $row) {
                    if (!empty($row['columns.'])) {
                        foreach ($row['columns.'] as $column) {
                            if (!isset($column['colPos'])) {
                                continue;
                            }
                            $backendLayoutData['__items'][] = [
                                $this->getColumnName($column),
                                $column['colPos'],
                                null
                            ];
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
    public static function getDefaultColumnLayout()
    {
        return '
		backend_layout {
			colCount = 4
			rowCount = 1
			rows {
				1 {
					columns {
						1 {
							name = LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos.I.0
							colPos = 1
						}
						2 {
							name = LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos.I.1
							colPos = 0
						}
						3 {
							name = LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos.I.2
							colPos = 2
						}
						4 {
							name = LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos.I.3
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
     * @param int $pageId
     * @return array|null
     */
    protected function getPage($pageId)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $page = $queryBuilder
            ->select('uid', 'pid', 'backend_layout')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
        BackendUtility::workspaceOL('pages', $page);

        return $page;
    }

    /**
     * Gets the page root-line.
     *
     * @param int $pageId
     * @return array
     */
    protected function getRootLine($pageId)
    {
        return BackendUtility::BEgetRootLine($pageId, '', true);
    }

    /**
     * @return BackendLayout\DataProviderContext
     */
    protected function createDataProviderContext()
    {
        return GeneralUtility::makeInstance(BackendLayout\DataProviderContext::class);
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Get column name from colPos item structure
     *
     * @param array $column
     * @return string
     */
    protected function getColumnName($column)
    {
        $columnName = $column['name'];

        if (GeneralUtility::isFirstPartOfStr($columnName, 'LLL:')) {
            $columnName = $this->getLanguageService()->sL($columnName);
        }

        return $columnName;
    }
}
