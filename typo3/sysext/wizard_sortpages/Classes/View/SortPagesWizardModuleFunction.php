<?php
namespace TYPO3\CMS\WizardSortpages\View;

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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Creates the "Sort pages" wizard
 */
class SortPagesWizardModuleFunction extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule
{
    /**
     * Main function creating the content for the module.
     *
     * @return string HTML content for the module, actually a "section" made through the parent object in $this->pObj
     */
    public function main()
    {
        $assigns = [];
        $assigns['LLPrefix'] = 'LLL:EXT:wizard_sortpages/Resources/Private/Language/locallang.xlf:';
        $assigns['workspace'] = $this->getBackendUser()->workspace;
        if ($this->getBackendUser()->workspace === 0) {
            // Check if user has modify permissions to
            $sys_pages = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);
            $sortByField = GeneralUtility::_GP('sortByField');
            if ($sortByField) {
                $menuItems = [];
                if ($sortByField === 'title' || $sortByField === 'subtitle' || $sortByField === 'crdate' || $sortByField === 'tstamp') {
                    $menuItems = $sys_pages->getMenu($this->pObj->id, 'uid,pid,title', $sortByField, '', false);
                } elseif ($sortByField === 'REV') {
                    $menuItems = $sys_pages->getMenu($this->pObj->id, 'uid,pid,title', 'sorting', '', false);
                    $menuItems = array_reverse($menuItems);
                }
                if (!empty($menuItems)) {
                    $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                    $menuItems = array_reverse($menuItems);
                    $cmd = [];
                    foreach ($menuItems as $r) {
                        $cmd['pages'][$r['uid']]['move'] = $this->pObj->id;
                    }
                    $tce->start([], $cmd);
                    $tce->process_cmdmap();
                    BackendUtility::setUpdateSignal('updatePageTree');
                }
            }
            $menuItems = $sys_pages->getMenu($this->pObj->id, '*', 'sorting', '', false);
            $assigns['menuItems'] = $menuItems;
            if (!empty($menuItems)) {
                $dataLines = [];
                foreach ($menuItems as $rec) {
                    $m_perms_clause = $this->getBackendUser()->getPagePermsClause(2);
                    // edit permissions for that page!
                    $pRec = BackendUtility::getRecord('pages', $rec['uid'], 'uid', ' AND ' . $m_perms_clause);
                    $line = [];
                    $line['rec'] = $rec;
                    $line['danger'] = !is_array($pRec);
                    $line['title'] = GeneralUtility::fixed_lgd_cs($rec['title'], $GLOBALS['BE_USER']->uc['titleLen']);
                    $line['subtitle'] = GeneralUtility::fixed_lgd_cs($rec['subtitle'], $this->getBackendUser()->uc['titleLen']);
                    $line['tstamp'] = BackendUtility::datetime($rec['tstamp']);
                    $line['crdate'] = BackendUtility::datetime($rec['crdate']);
                    $dataLines[] = $line;
                }
                $assigns['lines'] = $dataLines;

                // Menu:
                $dataLines = [];
                $line = [];
                $line['title'] = 'wiz_changeOrder_title';
                $line['href'] = BackendUtility::getModuleUrl('web_func',
                    [
                        'id' => $GLOBALS['SOBE']->id,
                        'sortByField' => 'title'
                    ]
                );
                $dataLines[] = $line;
                $line['title'] = 'wiz_changeOrder_subtitle';
                $line['href'] = BackendUtility::getModuleUrl('web_func',
                    [
                        'id' => $GLOBALS['SOBE']->id,
                        'sortByField' => 'subtitle'
                    ]
                );
                $dataLines[] = $line;
                $line['title'] = 'wiz_changeOrder_tChange';
                $line['href'] = BackendUtility::getModuleUrl('web_func',
                    [
                        'id' => $GLOBALS['SOBE']->id,
                        'sortByField' => 'tstamp'
                    ]
                );
                $dataLines[] = $line;
                $line['title'] = 'wiz_changeOrder_tCreate';
                $line['href'] = BackendUtility::getModuleUrl('web_func',
                    [
                        'id' => $GLOBALS['SOBE']->id,
                        'sortByField' => 'crdate'
                    ]
                );
                $dataLines[] = $line;
                $line['title'] = 'wiz_changeOrder_REVERSE';
                $line['href'] = BackendUtility::getModuleUrl('web_func',
                    [
                        'id' => $GLOBALS['SOBE']->id,
                        'sortByField' => 'REV'
                    ]
                );
                $dataLines[] = $line;
                $assigns['buttons'] = $dataLines;
            } else {
                $lang = $this->getLanguageService();
                $lang->includeLLFile('EXT:wizard_sortpages/Resources/Private/Language/locallang.xlf');
                $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $lang->getLL('no_subpages'), '', FlashMessage::NOTICE);
                /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $defaultFlashMessageQueue->enqueue($flashMessage);
            }
            // CSH:
            $assigns['cshItem'] = BackendUtility::cshItem('_MOD_web_func', 'tx_wizardsortpages');
        }
        // Rendering of the output via fluid
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:wizard_sortpages/Resources/Private/Templates/SortPagesWizard.html'
        ));
        $view->assignMultiple($assigns);
        return $view->render();
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
