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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $lang = $this->getLanguageService();
        $lang->includeLLFile('EXT:wizard_sortpages/Resources/Private/Language/locallang.xlf');
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        $out = $this->pObj->doc->header($lang->getLL('wiz_sort'));
        if ($this->getBackendUser()->workspace === 0) {
            $theCode = '';
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
                    $tce->stripslashes_values = 0;
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

            if (!empty($menuItems)) {
                $lines = [];
                $lines[] = '<thead><tr>';
                $lines[] = '<th>' . $lang->getLL('wiz_changeOrder_title') . '</th>';
                $lines[] = '<th>' . $lang->getLL('wiz_changeOrder_subtitle') . '</th>';
                $lines[] = '<th>' . $lang->getLL('wiz_changeOrder_tChange') . '</th>';
                $lines[] = '<th>' . $lang->getLL('wiz_changeOrder_tCreate') . '</th>';
                $lines[] = '</tr></thead>';

                $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
                foreach ($menuItems as $rec) {
                    $m_perms_clause = $this->getBackendUser()->getPagePermsClause(2);
                    // edit permissions for that page!
                    $pRec = BackendUtility::getRecord('pages', $rec['uid'], 'uid', ' AND ' . $m_perms_clause);
                    $lines[] = '<tr><td nowrap="nowrap">' . $iconFactory->getIconForRecord('pages', $rec, Icon::SIZE_SMALL)->render() . (!is_array($pRec) ? '<strong class="text-danger">' . $lang->getLL('wiz_W', true) . '</strong></span> ' : '') . htmlspecialchars(GeneralUtility::fixed_lgd_cs($rec['title'], $GLOBALS['BE_USER']->uc['titleLen'])) . '</td>
					<td nowrap="nowrap">' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($rec['subtitle'], $this->getBackendUser()->uc['titleLen'])) . '</td>
					<td nowrap="nowrap">' . BackendUtility::datetime($rec['tstamp']) . '</td>
					<td nowrap="nowrap">' . BackendUtility::datetime($rec['crdate']) . '</td>
					</tr>';
                }
                $theCode .= '<h2>' . $lang->getLL('wiz_currentPageOrder', true) . '</h2>';
                $theCode .= '<div class="table-fit"><table class="table table-striped table-hover">' . implode('', $lines) . '</table></div>';

                // Menu:
                $lines = [];
                $lines[] = $this->wiz_linkOrder($lang->getLL('wiz_changeOrder_title'), 'title');
                $lines[] = $this->wiz_linkOrder($lang->getLL('wiz_changeOrder_subtitle'), 'subtitle');
                $lines[] = $this->wiz_linkOrder($lang->getLL('wiz_changeOrder_tChange'), 'tstamp');
                $lines[] = $this->wiz_linkOrder($lang->getLL('wiz_changeOrder_tCreate'), 'crdate');
                $lines[] = '';
                $lines[] = $this->wiz_linkOrder($lang->getLL('wiz_changeOrder_REVERSE'), 'REV');
                $theCode .= '<h4>' . $lang->getLL('wiz_changeOrder') . '</h4><p>' . implode(' ', $lines) . '</p>';
            } else {
                $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $lang->getLL('no_subpages'), '', FlashMessage::NOTICE);
                /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $defaultFlashMessageQueue->enqueue($flashMessage);
            }
            // CSH:
            $theCode .= BackendUtility::cshItem('_MOD_web_func', 'tx_wizardsortpages', null, '<span class="btn btn-default btn-sm">|</span>');
            $out .= '<div>' . $theCode . '</div>';
        } else {
            $out .= '<div>Sorry, this function is not available in the current draft workspace!</div>';
        }
        return $out;
    }

    /**
     * Creates a link for the sorting order
     *
     * @param string $title Title of the link
     * @param string $order Field to sort by
     * @return string HTML string
     */
    protected function wiz_linkOrder($title, $order)
    {
        $href = BackendUtility::getModuleUrl('web_func',
            [
                'id' => $GLOBALS['SOBE']->id,
                'sortByField' => $order
            ]
        );
        return '<a class="btn btn-default t3js-modal-trigger" href="' . htmlspecialchars($href) . '" '
            . ' data-severity="warning"'
            . ' data-title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_common.xlf:pleaseConfirm', true) . '"'
            . ' data-button-close-text="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_common.xlf:cancel', true) . '"'
            . ' data-content="' . $this->getLanguageService()->getLL('wiz_changeOrder_msg1', true) . '"'
            . ' >' . htmlspecialchars($title) . '</a>';
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
