<?php
namespace TYPO3\CMS\Recordlist\Browser;

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

use TYPO3\CMS\Backend\RecordList\ElementBrowserRecordList;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Recordlist\Tree\View\ElementBrowserPageTreeView;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;

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

    /**
     */
    protected function initialize()
    {
        parent::initialize();
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/BrowseDatabase');
    }

    /**
     */
    protected function initVariables()
    {
        parent::initVariables();
        $this->expandPage = GeneralUtility::_GP('expandPage');
    }

    /**
     * Session data for this class can be set from outside with this method.
     *
     * @param mixed[] $data Session data array
     * @return array[] Session data and boolean which indicates that data needs to be stored in session because it's changed
     */
    public function processSessionData($data)
    {
        if ($this->expandPage !== null) {
            $data['expandPage'] = $this->expandPage;
            $store = true;
        } else {
            $this->expandPage = (int)$data['expandPage'];
            $store = false;
        }
        return [$data, $store];
    }

    /**
     * @return string HTML content
     */
    public function render()
    {
        $userTsConfig = $this->getBackendUser()->getTSConfig();

        $this->setTemporaryDbMounts();
        list(, , , $allowedTables) = explode('|', $this->bparams);

        // Making the browsable pagetree:
        $pageTree = GeneralUtility::makeInstance(ElementBrowserPageTreeView::class);
        $pageTree->setLinkParameterProvider($this);
        $pageTree->ext_pArrPages = $allowedTables === 'pages';
        $pageTree->ext_showNavTitle = (bool)($userTsConfig['options.']['pageTree.']['showNavTitle'] ?? false);
        $pageTree->ext_showPageId = (bool)($userTsConfig['options.']['pageTree.']['showPageIdWithTitle'] ?? false);
        $pageTree->ext_showPathAboveMounts = (bool)($userTsConfig['options.']['pageTree.']['showPathAboveMounts'] ?? false);
        $pageTree->addField('nav_title');
        $tree = $pageTree->getBrowsableTree();

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

        $renderedRecordList = $this->renderTableRecords($allowedTables);

        $this->initDocumentTemplate();
        $content = $this->doc->startPage(htmlspecialchars($this->getLanguageService()->getLL('recordSelector')));

        // Putting the parts together, side by side:
        $markup = [];
        $markup[] = '<!-- Wrapper table for folder tree / filelist: -->';
        $markup[] = '<div class="element-browser">';
        $markup[] = '   <div class="element-browser-panel element-browser-main">';
        if ($withTree) {
            $markup[] = '   <div class="element-browser-main-sidebar">';
            $markup[] = '       <div class="element-browser-body">';
            $markup[] = '           ' . $this->getTemporaryTreeMountCancelNotice();
            $markup[] = '           ' . $tree;
            $markup[] = '       </div>';
            $markup[] = '   </div>';
        }
        $markup[] = '       <div class="element-browser-main-content">';
        $markup[] = '           <div class="element-browser-body">';
        $markup[] = '               ' . $this->doc->getFlashMessages();
        $markup[] = '               ' . $renderedRecordList;
        $markup[] = '           </div>';
        $markup[] = '       </div>';
        $markup[] = '   </div>';
        $markup[] = '</div>';
        $content .= implode('', $markup);

        // Ending page, returning content:
        $content .= $this->doc->endPage();
        return $this->doc->insertStylesAndJS($content);
    }

    /**
     * Check if a temporary tree mount is set and return a cancel button
     *
     * @return string HTML code
     */
    protected function getTemporaryTreeMountCancelNotice()
    {
        if ((int)$this->getBackendUser()->getSessionData('pageTree_temporaryMountPoint') === 0) {
            return '';
        }
        $link = '<p><a href="' . htmlspecialchars(GeneralUtility::linkThisScript(['setTempDBmount' => 0])) . '" class="btn btn-primary">'
            . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.temporaryDBmount')) . '</a></p>';

        return $link;
    }

    /**
     * If the current Backend User has set a temporary DB mount, it is stored to her/his UC.
     */
    protected function setTemporaryDbMounts()
    {
        $backendUser = $this->getBackendUser();

        // Clear temporary DB mounts
        $tmpMount = GeneralUtility::_GET('setTempDBmount');
        if (isset($tmpMount)) {
            $backendUser->setAndSaveSessionData('pageTree_temporaryMountPoint', (int)$tmpMount);
        }

        $backendUser->initializeWebmountsForElementBrowser();
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
                $out .= '<span data-uid="' . htmlspecialchars($mainPageRecord['uid']) . '" data-table="pages" data-title="' . htmlspecialchars($mainPageRecord['title']) . '" data-icon="">';
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

        /** @var ElementBrowserRecordList $dbList */
        $dbList = GeneralUtility::makeInstance(ElementBrowserRecordList::class);
        $dbList->setOverrideUrlParameters($this->getUrlParameters([]));
        $dbList->thisScript = $this->thisScript;
        $dbList->thumbs = false;
        $dbList->setIsEditable(false);
        $dbList->calcPerms = $backendUser->calcPerms($pageInfo);
        $dbList->noControlPanels = true;
        $dbList->clickMenuEnabled = false;
        $dbList->tableList = implode(',', $tablesArr);

        // a string like "data[pages][79][storage_pid]"
        list($fieldPointerString) = explode('|', $this->bparams);
        // parts like: data, pages], 79], storage_pid]
        $fieldPointerParts = explode('[', $fieldPointerString);
        $relatingTableName = substr($fieldPointerParts[1], 0, -1);
        $relatingFieldName = substr($fieldPointerParts[3], 0, -1);
        if ($relatingTableName && $relatingFieldName) {
            $dbList->setRelatingTableAndField($relatingTableName, $relatingFieldName);
        }

        $dbList->start(
            $this->expandPage,
            GeneralUtility::_GP('table'),
            MathUtility::forceIntegerInRange(GeneralUtility::_GP('pointer'), 0, 100000),
            GeneralUtility::_GP('search_field'),
            GeneralUtility::_GP('search_levels'),
            GeneralUtility::_GP('showLimit')
        );

        $dbList->setDispFields();
        $dbList->generateList();

        $out .= $dbList->getSearchBox();

        // Add the HTML for the record list to output variable:
        $out .= $dbList->HTMLcode;

        // Add support for fieldselectbox in singleTableMode
        if ($dbList->table) {
            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/FieldSelectBox');
            $out .= $dbList->fieldSelectBox($dbList->table);
        }

        return $out;
    }

    /**
     * @return string[] Array of body-tag attributes
     */
    protected function getBodyTagAttributes()
    {
        return [
            'data-mode' => 'db'
        ];
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
            'bparams' => $this->bparams
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
