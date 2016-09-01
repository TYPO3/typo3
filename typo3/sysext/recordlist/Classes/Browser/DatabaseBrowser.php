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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Recordlist\Tree\View\ElementBrowserPageTreeView;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;

/**
 * Showing a page tree and allows you to browse for records
 */
class DatabaseBrowser extends AbstractElementBrowser implements ElementBrowserInterface, LinkParameterProviderInterface
{
    /**
     * When you click a page title/expand icon to see the content of a certain page, this
     * value will contain the ID of the expanded page.
     * If the value is NOT set by GET parameter, then it will be restored from the module session data.
     *
     * @var NULL|int
     */
    protected $expandPage;

    /**
     * @return void
     */
    protected function initialize()
    {
        parent::initialize();
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/BrowseDatabase');
    }

    /**
     * @return void
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
        $this->setTemporaryDbMounts();

        list(, , , $allowedTables) = explode('|', $this->bparams);
        $backendUser = $this->getBackendUser();

        // Making the browsable pagetree:
        /** @var ElementBrowserPageTreeView $pageTree */
        $pageTree = GeneralUtility::makeInstance(ElementBrowserPageTreeView::class);
        $pageTree->setLinkParameterProvider($this);
        $pageTree->ext_pArrPages = $allowedTables === 'pages';
        $pageTree->ext_showNavTitle = (bool)$backendUser->getTSConfigVal('options.pageTree.showNavTitle');
        $pageTree->ext_showPageId = (bool)$backendUser->getTSConfigVal('options.pageTree.showPageIdWithTitle');
        $pageTree->ext_showPathAboveMounts = (bool)$backendUser->getTSConfigVal('options.pageTree.showPathAboveMounts');
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
        $content = $this->doc->startPage('TBE record selector');
        $content .= $this->doc->getFlashMessages();

        $content .= '

			<!--
				Wrapper table for page tree / record list:
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-EBrecords">
				<tr>';
        if ($withTree) {
            $content .= '<td class="c-wCell" valign="top">'
                . '<h3>' . $this->getLanguageService()->getLL('pageTree', true) . ':</h3>'
                . $this->getTemporaryTreeMountCancelNotice() . $tree . '</td>';
        }
        $content .= '<td class="c-wCell" valign="top">' . $renderedRecordList . '</td>
				</tr>
			</table>
			';

        // Add some space
        $content .= '<br /><br />';

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
            . $this->getLanguageService()->sl('LLL:EXT:lang/locallang_core.xlf:labels.temporaryDBmount', true) . '</a></p>';

        return $link;
    }

    /**
     * @return void
     */
    protected function setTemporaryDbMounts()
    {
        $backendUser = $this->getBackendUser();

        // Clear temporary DB mounts
        $tmpMount = GeneralUtility::_GET('setTempDBmount');
        if (isset($tmpMount)) {
            $backendUser->setAndSaveSessionData('pageTree_temporaryMountPoint', (int)$tmpMount);
        }
        // Set temporary DB mounts
        $alternativeWebmountPoint = (int)$backendUser->getSessionData('pageTree_temporaryMountPoint');
        if ($alternativeWebmountPoint) {
            $alternativeWebmountPoint = GeneralUtility::intExplode(',', $alternativeWebmountPoint);
            $backendUser->setWebmounts($alternativeWebmountPoint);
        } else {
            // Setting alternative browsing mounts (ONLY local to browse_links.php this script so they stay "read-only")
            $alternativeWebmountPoints = trim($backendUser->getTSConfigVal('options.pageTree.altElementBrowserMountPoints'));
            $appendAlternativeWebmountPoints = $backendUser->getTSConfigVal('options.pageTree.altElementBrowserMountPoints.append');
            if ($alternativeWebmountPoints) {
                $alternativeWebmountPoints = GeneralUtility::intExplode(',', $alternativeWebmountPoints);
                $this->getBackendUser()->setWebmounts($alternativeWebmountPoints, $appendAlternativeWebmountPoints);
            }
        }
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
        if ($this->expandPage === null  || $this->expandPage < 0 || !$backendUser->isInWebMount($this->expandPage)) {
            return '';
        }
        // Set array with table names to list:
        if (trim($tables) === '*') {
            $tablesArr = array_keys($GLOBALS['TCA']);
        } else {
            $tablesArr = GeneralUtility::trimExplode(',', $tables, true);
        }

        $out = '<h3>' . $this->getLanguageService()->getLL('selectRecords', true) . ':</h3>';
        // Create the header, showing the current page for which the listing is.
        // Includes link to the page itself, if pages are amount allowed tables.
        $titleLen = (int)$backendUser->uc['titleLen'];
        $mainPageRecord = BackendUtility::getRecordWSOL('pages', $this->expandPage);
        if (is_array($mainPageRecord)) {
            $pText = htmlspecialchars(GeneralUtility::fixed_lgd_cs($mainPageRecord['title'], $titleLen));

            $out .= $this->iconFactory->getIconForRecord('pages', $mainPageRecord, Icon::SIZE_SMALL)->render();
            if (in_array('pages', $tablesArr, true)) {
                $out .= '<span data-uid="' . htmlspecialchars($mainPageRecord['uid']) . '" data-table="pages" data-title="' . htmlspecialchars($mainPageRecord['title']) . '" data-icon="">';
                $out .= '<a href="#" data-close="0">'
                    . $this->iconFactory->getIcon('actions-edit-add', Icon::SIZE_SMALL)->render()
                    . '</a>'
                    . '<a href="#" data-close="1">'
                    . $pText
                    . '</a>';
                $out .= '</span>';
            } else {
                $out .= $pText;
            }
            $out .= '<br />';
        }

        $permsClause = $backendUser->getPagePermsClause(1);
        $pageInfo = BackendUtility::readPageAccess($this->expandPage, $permsClause);

        /** @var ElementBrowserRecordList $dbList */
        $dbList = GeneralUtility::makeInstance(ElementBrowserRecordList::class);
        $dbList->setOverrideUrlParameters($this->getUrlParameters([]));
        $dbList->thisScript = $this->thisScript;
        $dbList->thumbs = false;
        $dbList->localizationView = true;
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
        $pid = isset($values['pid']) ? $values['pid'] : $this->expandPage;
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
