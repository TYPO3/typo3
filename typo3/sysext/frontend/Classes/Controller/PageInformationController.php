<?php
namespace TYPO3\CMS\Frontend\Controller;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for displaying page information (records, page record properties)
 */
class PageInformationController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule
{
    /**
     * Returns the menu array
     *
     * @return array
     */
    public function modMenu()
    {
        return [
            'pages' => [
                0 => $GLOBALS['LANG']->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_webinfo.xlf:pages_0'),
                2 => $GLOBALS['LANG']->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_webinfo.xlf:pages_2'),
                1 => $GLOBALS['LANG']->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_webinfo.xlf:pages_1')
            ],
            'depth' => [
                0 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_0'),
                1 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_1'),
                2 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_2'),
                3 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_3'),
                4 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_4'),
                999 => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_infi')
            ]
        ];
    }

    /**
     * MAIN function for page information display
     *
     * @return string Output HTML for the module.
     */
    public function main()
    {
        $theOutput = $this->pObj->doc->header($this->getLanguageService()->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_webinfo.xlf:page_title'));
        $dblist = GeneralUtility::makeInstance(PageLayoutView::class);
        $dblist->descrTable = '_MOD_web_info';
        $dblist->thumbs = 0;
        $dblist->script = BackendUtility::getModuleUrl('web_info');
        $dblist->showIcon = 0;
        $dblist->setLMargin = 0;
        $dblist->agePrefixes = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears');
        $dblist->pI_showUser = 1;
        // PAGES:
        $this->pObj->MOD_SETTINGS['pages_levels'] = $this->pObj->MOD_SETTINGS['depth'];
        // ONLY for the sake of dblist module which uses this value.
        $h_func = BackendUtility::getDropdownMenu($this->pObj->id, 'SET[depth]', $this->pObj->MOD_SETTINGS['depth'], $this->pObj->MOD_MENU['depth']);
        $h_func .= BackendUtility::getDropdownMenu($this->pObj->id, 'SET[pages]', $this->pObj->MOD_SETTINGS['pages'], $this->pObj->MOD_MENU['pages']);
        $dblist->start($this->pObj->id, 'pages', 0);
        $dblist->generateList();
        // CSH
        $theOutput .= '<div>';
        $theOutput .= BackendUtility::cshItem($dblist->descrTable, 'pagetree_overview', null, '<span class="btn btn-default btn-sm">|</span><br />')
            . '<div class="form-inline form-inline-spaced">'
            . $h_func
            . '</div>'
            . $dblist->HTMLcode;
        $theOutput .= '</div>';
        // Additional footer content
        $footerContentHook = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/web_info/class.tx_cms_webinfo.php']['drawFooterHook'];
        if (is_array($footerContentHook)) {
            foreach ($footerContentHook as $hook) {
                $params = [];
                $theOutput .= GeneralUtility::callUserFunction($hook, $params, $this);
            }
        }
        return $theOutput;
    }
}
