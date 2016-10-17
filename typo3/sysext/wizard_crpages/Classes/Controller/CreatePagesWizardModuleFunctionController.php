<?php
namespace TYPO3\CMS\WizardCrpages\Controller;

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
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Creates the "Create pages" wizard
 */
class CreatePagesWizardModuleFunctionController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule
{
    /**
     * Complete tsConfig
     *
     * @var array
     */
    protected $tsConfig = [];

    /**
     * Part of tsConfig with TCEFORM.pages. settings
     *
     * @var array
     */
    protected $pagesTsConfig = [];

    /**
     * Main function creating the content for the module.
     *
     * @return string HTML content for the module, actually a "section" made through the parent object in $this->pObj
     */
    public function main()
    {
        $this->getLanguageService()->includeLLFile('EXT:wizard_crpages/Resources/Private/Language/locallang.xlf');
        $assigns = [];
        $assigns['LLPrefix'] = 'LLL:EXT:wizard_crpages/Resources/Private/Language/locallang.xlf:';
        $this->tsConfig = BackendUtility::getPagesTSconfig($this->pObj->id);
        $this->pagesTsConfig = isset($this->tsConfig['TCEFORM.']['pages.']) ? $this->tsConfig['TCEFORM.']['pages.'] : [];

        // Create new pages here?
        $pageRecord = BackendUtility::getRecord('pages', $this->pObj->id, 'uid', ' AND ' . $this->getBackendUser()->getPagePermsClause(8));
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $menuItems = $pageRepository->getMenu($this->pObj->id, '*', 'sorting', '', false);
        if (is_array($pageRecord)) {
            $data = GeneralUtility::_GP('data');
            if (is_array($data['pages'])) {
                if (GeneralUtility::_GP('createInListEnd')) {
                    $endI = end($menuItems);
                    $thePid = -(int)$endI['uid'];
                    if (!$thePid) {
                        $thePid = $this->pObj->id;
                    }
                } else {
                    $thePid = $this->pObj->id;
                }
                $firstRecord = true;
                $previousIdentifier = '';
                foreach ($data['pages'] as $identifier => $dat) {
                    if (!trim($dat['title'])) {
                        unset($data['pages'][$identifier]);
                    } else {
                        $data['pages'][$identifier]['hidden'] = GeneralUtility::_GP('hidePages') ? 1 : 0;
                        $data['pages'][$identifier]['nav_hide'] = GeneralUtility::_GP('hidePagesInMenus') ? 1 : 0;
                        if ($firstRecord) {
                            $firstRecord = false;
                            $data['pages'][$identifier]['pid'] = $thePid;
                        } else {
                            $data['pages'][$identifier]['pid'] = '-' . $previousIdentifier;
                        }
                        $previousIdentifier = $identifier;
                    }
                }
                if (!empty($data['pages'])) {
                    reset($data);
                    $dataHandler = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                    // set default TCA values specific for the user
                    $TCAdefaultOverride = $this->getBackendUser()->getTSConfigProp('TCAdefaults');
                    if (is_array($TCAdefaultOverride)) {
                        $dataHandler->setDefaultsFromUserTS($TCAdefaultOverride);
                    }
                    $dataHandler->start($data, []);
                    $dataHandler->process_datamap();
                    BackendUtility::setUpdateSignal('updatePageTree');
                    $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, '', $this->getLanguageService()->getLL('wiz_newPages_create'));
                } else {
                    $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, '', $this->getLanguageService()->getLL('wiz_newPages_noCreate'), FlashMessage::ERROR);
                }
                /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $defaultFlashMessageQueue->enqueue($flashMessage);
                // Display result:
                $menuItems = $pageRepository->getMenu($this->pObj->id, '*', 'sorting', '', false);
                $pageLines = [];
                foreach ($menuItems as $record) {
                    BackendUtility::workspaceOL('pages', $record);
                    if (is_array($record)) {
                        $line = [];
                        $line['titleAttribute'] = BackendUtility::titleAttribForPages($record, '', false);
                        $line['title'] = GeneralUtility::fixed_lgd_cs($record['title'], $this->getBackendUser()->uc['titleLen']);
                        $line['record'] = $record;
                        $pageLines[] = $line;
                    }
                }
                $assigns['pages'] = $pageLines;
            } else {
                // Display create form
                $assigns['typeSelect'] = $this->getTypeSelectData();
            }
        } else {
            $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, '', $this->getLanguageService()->getLL('wiz_newPages_errorMsg1'), FlashMessage::ERROR);
            /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
        // CSH
        $assigns['cshItem'] = BackendUtility::cshItem('_MOD_web_func', 'tx_wizardcrpages');

        // Rendering of the output via fluid
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:wizard_crpages/Resources/Private/Templates/CreatePagesWizard.html'
        ));
        $view->assignMultiple($assigns);
        $out = $view->render();

        return $out;
    }

    /**
     * Get type selector data
     *
     * @return string
     */
    protected function getTypeSelectData()
    {
        // find all available doktypes for the current user
        $types = $GLOBALS['PAGES_TYPES'];
        unset($types['default']);
        $types = array_keys($types);
        $types[] = PageRepository::DOKTYPE_DEFAULT;
        $types[] = PageRepository::DOKTYPE_LINK;
        $types[] = PageRepository::DOKTYPE_SHORTCUT;
        $types[] = PageRepository::DOKTYPE_MOUNTPOINT;
        $types[] = PageRepository::DOKTYPE_SPACER;

        if (!$this->getBackendUser()->isAdmin() && isset($this->getBackendUser()->groupData['pagetypes_select'])) {
            $types = GeneralUtility::trimExplode(',', $this->getBackendUser()->groupData['pagetypes_select'], true);
        }
        $removeItems = isset($this->pagesTsConfig['doktype.']['removeItems']) ? GeneralUtility::trimExplode(',', $this->pagesTsConfig['doktype.']['removeItems'], true) : [];
        $allowedDoktypes = array_diff($types, $removeItems);

        // fetch all doktypes in the TCA
        $availableDoktypes = $GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'];

        // sort by group and allowedDoktypes
        $groupedData = [];
        $groupLabel = '';
        foreach ($availableDoktypes as $doktypeData) {
            // if it is a group, save the group label for the children underneath
            if ($doktypeData[1] == '--div--') {
                $groupLabel = $doktypeData[0];
            } else {
                if (in_array($doktypeData[1], $allowedDoktypes)) {
                    $groupedData[$groupLabel][] = $doktypeData;
                }
            }
        }

        return $groupedData;
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
