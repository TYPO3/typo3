<?php

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

namespace TYPO3\CMS\Recordlist\Browser;

use TYPO3\CMS\Backend\RecordList\ElementBrowserRecordList;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Recordlist\View\RecordSearchBoxComponent;

/**
 * Showing a page tree and allows you to browse for records
 * @internal This class is a specific LinkBrowser implementation and is not part of the TYPO3's Core API.
 */
class DatabaseBrowser extends AbstractElementBrowser implements ElementBrowserInterface, LinkParameterProviderInterface
{
    /**
     * When you click a page title/expand icon to see the content of a certain page, this
     * value will contain the ID of the expanded page.
     * If the value is NOT set by GET parameter, then it will be restored from the module session data.
     *
     * @var int|null
     */
    protected $expandPage;
    protected array $modTSconfig = [];

    protected function initialize()
    {
        parent::initialize();
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/BrowseDatabase');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tree/PageBrowser');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ColumnSelectorButton');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/RecordSearch');
    }

    protected function initVariables()
    {
        parent::initVariables();
        $this->expandPage = $this->getRequest()->getParsedBody()['expandPage'] ?? $this->getRequest()->getQueryParams()['expandPage'] ?? null;
    }

    /**
     * Session data for this class can be set from outside with this method.
     *
     * @param mixed[] $data Session data array
     * @return array<int, array|bool> Session data and boolean which indicates that data needs to be stored in session because it's changed
     */
    public function processSessionData($data)
    {
        if ($this->expandPage !== null) {
            $data['expandPage'] = $this->expandPage;
            $store = true;
        } else {
            $this->expandPage = (int)($data['expandPage'] ?? 0);
            $store = false;
        }
        return [$data, $store];
    }

    /**
     * @return string HTML content
     */
    public function render()
    {
        $this->modTSconfig = BackendUtility::getPagesTSconfig((int)$this->expandPage)['mod.']['web_list.'] ?? [];
        [, , , $allowedTables] = explode('|', $this->bparams);

        $withTree = true;
        if ($allowedTables !== '' && $allowedTables !== '*') {
            $tablesArr = GeneralUtility::trimExplode(',', $allowedTables, true);
            $onlyRootLevel = true;
            foreach ($tablesArr as $currentTable) {
                if (isset($GLOBALS['TCA'][$currentTable])) {
                    if (!isset($GLOBALS['TCA'][$currentTable]['ctrl']['rootLevel']) || (int)$GLOBALS['TCA'][$currentTable]['ctrl']['rootLevel'] !== 1) {
                        $onlyRootLevel = false;
                    }
                }
            }
            if ($onlyRootLevel) {
                $withTree = false;
                // page to work on is root
                $this->expandPage = 0;
            }
        }

        $contentOnly = (bool)($this->getRequest()->getQueryParams()['contentOnly'] ?? false);
        $renderedRecordList = $this->renderTableRecords($allowedTables);

        $this->setBodyTagParameters();
        $this->moduleTemplate->setTitle($this->getLanguageService()->getLL('recordSelector'));
        $view = $this->moduleTemplate->getView();
        $view->assignMultiple([
            'treeEnabled' => $withTree,
            'treeType' => 'page',
            'treeActions' => $allowedTables === 'pages' ? ['select'] : [],
            'activePage' => $this->expandPage,
            'initialNavigationWidth' => $this->getBackendUser()->uc['selector']['navigation']['width'] ?? 250,
            'content' => $renderedRecordList,
            'contentOnly' => $contentOnly,
        ]);
        if ($contentOnly) {
            return $view->render();
        }
        return $this->moduleTemplate->renderContent();
    }

    /**
     * This lists all content elements for the given list of tables
     *
     * @param string $tables Comma separated list of tables. Set to "*" if you want all tables.
     * @return string HTML code
     */
    protected function renderTableRecords($tables)
    {
        $backendUser = $this->getBackendUser();
        if ($this->expandPage === null || $this->expandPage < 0 || !$backendUser->isInWebMount($this->expandPage)) {
            return '';
        }
        // Set array with table names to list:
        if (trim($tables) === '*') {
            $tablesArr = array_keys($GLOBALS['TCA']);
        } else {
            $tablesArr = GeneralUtility::trimExplode(',', $tables, true);
        }

        $out = '';
        // Create the header, showing the current page for which the listing is.
        // Includes link to the page itself, if pages are amount allowed tables.
        $titleLen = (int)$backendUser->uc['titleLen'];
        $mainPageRecord = BackendUtility::getRecordWSOL('pages', $this->expandPage);
        if (is_array($mainPageRecord)) {
            $pText = htmlspecialchars(GeneralUtility::fixed_lgd_cs($mainPageRecord['title'], $titleLen));

            $out .= '<p>' . $this->iconFactory->getIconForRecord('pages', $mainPageRecord, Icon::SIZE_SMALL)->render() . '&nbsp;';
            if (in_array('pages', $tablesArr, true)) {
                $out .= '<span data-uid="' . htmlspecialchars($mainPageRecord['uid']) . '" data-table="pages" data-title="' . htmlspecialchars($mainPageRecord['title']) . '">';
                $out .= '<a href="#" data-close="0">'
                    . $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL)->render()
                    . '</a>'
                    . '<a href="#" data-close="1">'
                    . $pText
                    . '</a>';
                $out .= '</span>';
            } else {
                $out .= $pText;
            }
            $out .= '</p>';
        }

        $permsClause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        $pageInfo = BackendUtility::readPageAccess($this->expandPage, $permsClause);

        $dbList = GeneralUtility::makeInstance(ElementBrowserRecordList::class);
        $dbList->setOverrideUrlParameters($this->getUrlParameters([]));
        $dbList->setIsEditable(false);
        $dbList->calcPerms = new Permission($backendUser->calcPerms($pageInfo));
        $dbList->noControlPanels = true;
        $dbList->clickMenuEnabled = false;
        $dbList->displayRecordDownload = false;
        $dbList->tableList = implode(',', $tablesArr);

        // a string like "data[pages][79][storage_pid]"
        [$fieldPointerString] = explode('|', $this->bparams);
        // parts like: data, pages], 79], storage_pid]
        $fieldPointerParts = explode('[', $fieldPointerString);

        $relatingTableName = substr(($fieldPointerParts[1] ?? ''), 0, -1);
        $relatingFieldName = substr(($fieldPointerParts[3] ?? ''), 0, -1);

        if ($relatingTableName && $relatingFieldName) {
            $dbList->setRelatingTableAndField($relatingTableName, $relatingFieldName);
        }

        $selectedTable = GeneralUtility::_GP('table');
        $searchWord = (string)GeneralUtility::_GP('search_field');
        $searchLevels = (int)GeneralUtility::_GP('search_levels');
        $dbList->start(
            $this->expandPage,
            $selectedTable,
            MathUtility::forceIntegerInRange(GeneralUtility::_GP('pointer'), 0, 100000),
            $searchWord,
            $searchLevels
        );

        $dbList->setDispFields();
        $tableList = $dbList->generateList();

        $out .= $this->renderSearchBox($dbList, $searchWord, $searchLevels);

        // Add the HTML for the record list to output variable:
        $out .= $tableList;

        return $out;
    }

    protected function renderSearchBox(ElementBrowserRecordList $dblist, string $searchWord, int $searchLevels): string
    {
        $searchBox = GeneralUtility::makeInstance(RecordSearchBoxComponent::class)
            ->setAllowedSearchLevels((array)($this->modTSconfig['searchLevel.']['items.'] ?? []))
            ->setSearchWord($searchWord)
            ->setSearchLevel($searchLevels)
            ->render($dblist->listURL('', '-1', 'pointer,search_field'));
        return '<div class="pt-2">' . $searchBox . '</div>';
    }

    /**
     * @param array $values Array of values to include into the parameters
     * @return string[] Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $values)
    {
        $pid = $values['pid'] ?? $this->expandPage;
        return [
            'mode' => 'db',
            'expandPage' => $pid,
            'bparams' => $this->bparams,
        ];
    }

    /**
     * @param array $values Values to be checked
     * @return bool Returns TRUE if the given values match the currently selected item
     */
    public function isCurrentlySelectedItem(array $values)
    {
        return false;
    }

    /**
     * Returns the URL of the current script
     *
     * @return string
     */
    public function getScriptUrl()
    {
        return $this->thisScript;
    }
}
