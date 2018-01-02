<?php
namespace TYPO3\CMS\Info\Controller;

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
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for displaying page information (records, page record properties)
 */
class PageInformationController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule
{

    /** @var array */
    protected $fieldConfiguration = [];

    /**
     * Returns the menu array
     *
     * @return array
     */
    public function modMenu()
    {
        $menu = [
            'pages' => [],
            'depth' => [
                0 => $GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
                1 => $GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
                2 => $GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
                3 => $GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
                4 => $GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
                999 => $GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.depth_infi')
            ]
        ];

        $this->fillFieldConfiguration($this->pObj->id);
        foreach ($this->fieldConfiguration as $key => $item) {
            $menu['pages'][$key] = $item['label'];
        }
        return $menu;
    }

    /**
     * MAIN function for page information display
     *
     * @return string Output HTML for the module.
     */
    public function main()
    {
        $this->fillFieldConfiguration($this->pObj->id);
        $theOutput = '<h1>' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:page_title')) . '</h1>';
        $dblist = GeneralUtility::makeInstance(PageLayoutView::class);
        $dblist->descrTable = '_MOD_web_info';
        $dblist->thumbs = 0;
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $dblist->script = (string)$uriBuilder->buildUriFromRoute('web_info');
        $dblist->showIcon = 0;
        $dblist->setLMargin = 0;
        $dblist->agePrefixes = $GLOBALS['LANG']->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears');
        $dblist->pI_showUser = true;

        if (isset($this->fieldConfiguration[$this->pObj->MOD_SETTINGS['pages']])) {
            $dblist->fieldArray = $this->fieldConfiguration[$this->pObj->MOD_SETTINGS['pages']]['fields'];
        }

        // PAGES:
        $this->pObj->MOD_SETTINGS['pages_levels'] = $this->pObj->MOD_SETTINGS['depth'];
        // ONLY for the sake of dblist module which uses this value.
        $h_func = BackendUtility::getDropdownMenu($this->pObj->id, 'SET[depth]', $this->pObj->MOD_SETTINGS['depth'], $this->pObj->MOD_MENU['depth']);
        $h_func .= BackendUtility::getDropdownMenu($this->pObj->id, 'SET[pages]', $this->pObj->MOD_SETTINGS['pages'], $this->pObj->MOD_MENU['pages']);
        $dblist->start($this->pObj->id, 'pages', 0);
        $dblist->generateList();

        $theOutput .= '<div class="form-inline form-inline-spaced">'
            . $h_func
            . '<div class="form-group">'
            . BackendUtility::cshItem($dblist->descrTable, 'func_' . $this->pObj->MOD_SETTINGS['pages'], null, '<span class="btn btn-default btn-sm">|</span>')
            . '</div>'
            . '</div>'
            . $dblist->HTMLcode;

        // Additional footer content
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/web_info/class.tx_cms_webinfo.php']['drawFooterHook'] ?? [] as $hook) {
            $params = [];
            $theOutput .= GeneralUtility::callUserFunction($hook, $params, $this);
        }
        return $theOutput;
    }

    /**
     * Function, which returns all tables to
     * which the user has access. Also a set of standard tables (pages, sys_filemounts, etc...)
     * are filtered out. So what is left is basically all tables which makes sense to list content from.
     *
     * @return string
     */
    protected function cleanTableNames(): string
    {
        // Get all table names:
        $tableNames = array_flip(array_keys($GLOBALS['TCA']));
        // Unset common names:
        unset($tableNames['pages']);
        unset($tableNames['sys_filemounts']);
        unset($tableNames['sys_action']);
        unset($tableNames['sys_workflows']);
        unset($tableNames['be_users']);
        unset($tableNames['be_groups']);
        $allowedTableNames = [];
        // Traverse table names and set them in allowedTableNames array IF they can be read-accessed by the user.
        if (is_array($tableNames)) {
            foreach ($tableNames as $k => $v) {
                if (!$GLOBALS['TCA'][$k]['ctrl']['hideTable'] && $this->getBackendUser()->check('tables_select', $k)) {
                    $allowedTableNames['table_' . $k] = $k;
                }
            }
        }
        return implode(',', array_keys($allowedTableNames));
    }

    /**
     * Generate configuration for field selection
     *
     * @param int $pageId current page id
     */
    protected function fillFieldConfiguration(int $pageId)
    {
        $modTSconfig = BackendUtility::getModTSconfig($pageId, 'mod.web_info.fieldDefinitions');

        foreach ($modTSconfig['properties'] as $key => $item) {
            $fieldList = str_replace('###ALL_TABLES###', $this->cleanTableNames(), $item['fields']);
            $fields = GeneralUtility::trimExplode(',', $fieldList, true);
            $key = trim($key, '.');
            $this->fieldConfiguration[$key] = [
                'label' => $item['label'] ? $GLOBALS['LANG']->sL($item['label']) : $key,
                'fields' => $fields
            ];
        }
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
