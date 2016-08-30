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

use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
     * The type select HTML
     */
    protected $typeSelectHtml = '';

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Main function creating the content for the module.
     *
     * @return string HTML content for the module, actually a "section" made through the parent object in $this->pObj
     */
    public function main()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->getLanguageService()->includeLLFile('EXT:wizard_crpages/Resources/Private/Language/locallang.xlf');
        $theCode = '';
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
                    $dataHandler->stripslashes_values = 0;
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
                $lines = [];
                foreach ($menuItems as $record) {
                    BackendUtility::workspaceOL('pages', $record);
                    if (is_array($record)) {
                        $lines[] = '<span class="text-nowrap" title="' . BackendUtility::titleAttribForPages($record, '', false) . '">' . $this->iconFactory->getIconForRecord('pages', $record, Icon::SIZE_SMALL)->render() . htmlspecialchars(GeneralUtility::fixed_lgd_cs($record['title'], $this->getBackendUser()->uc['titleLen'])) . '</span>';
                    }
                }
                $theCode .= '<div class="form-group"><h4>' . $this->getLanguageService()->getLL('wiz_newPages_currentMenu') . '</h4>' . implode('<br />', $lines) . '</div>';
            } else {
                // Display create form
                $this->typeSelectHtml = $this->getTypeSelectHtml();
                $tableData = [];
                for ($a = 0; $a < 5; $a++) {
                    $tableData[] = $this->getFormLine($a);
                }
                $theCode .= '
					<h4>' . $this->getLanguageService()->getLL('wiz_newPages') . ':</h4>
					<div class="form-group t3js-wizardcrpages-container">
						' . implode(LF, $tableData) . '
					</div>
					<div class="form-group">
						<input class="btn btn-default t3js-wizardcrpages-createnewfields" type="button" value="' . $this->getLanguageService()->getLL('wiz_newPages_addMoreLines') . '" />
					</div>
					<div class="form-group">
						<div class="checkbox">
							<label for="createInListEnd">
								<input type="checkbox" name="createInListEnd" id="createInListEnd" value="1" />
								' . $this->getLanguageService()->getLL('wiz_newPages_listEnd') . '
							</label>
						</div>
						<div class="checkbox">
							<label for="hidePages">
								<input type="checkbox" name="hidePages" id="hidePages" value="1" />
								' . $this->getLanguageService()->getLL('wiz_newPages_hidePages') . '
							</label>
						</div>
						<div class="checkbox">
							<label for="hidePagesInMenus">
								<input type="checkbox" name="hidePagesInMenus" id="hidePagesInMenus" value="1" />
								' . $this->getLanguageService()->getLL('wiz_newPages_hidePagesInMenus') . '
							</label>
						</div>
					</div>
					<div class="form-group">
						<input class="btn btn-default" type="submit" name="create" value="' . $this->getLanguageService()->getLL('wiz_newPages_lCreate') . '" />
						<input class="btn btn-default" type="reset" value="' . $this->getLanguageService()->getLL('wiz_newPages_lReset') . '" />
					</div>';

                $this->getPageRenderer()->loadJquery();
                $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/WizardCrpages/WizardCreatePages');
                // Add inline code
                $inlineJavaScriptCode = 'var tpl = "' . addslashes(str_replace([LF, TAB], ['', ''], $this->getFormLine('#'))) . '", i, line, div, bg, label;';
                $this->getPageRenderer()->addJsInlineCode('wizard_crpages', $inlineJavaScriptCode);
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
        $theCode .= BackendUtility::cshItem('_MOD_web_func', 'tx_wizardcrpages', null, '<span class="btn btn-default btn-sm">|</span>');
        $out = $this->pObj->doc->header($this->getLanguageService()->getLL('wiz_crMany'));
        $out .= '<div>' . $theCode . '</div>';
        return $out;
    }

    /**
     * Return one line in the form
     *
     * @param int|string $index An integer: the line counter for which to create the line. Use "#" to create an template for javascript (used by ExtJS)
     * @return string HTML code for one input line for one new page
     */
    protected function getFormLine($index)
    {
        if (is_numeric($index)) {
            $label = $index + 1;
        } else {
            // used as template for JavaScript
            $index = '{0}';
            $label = '{1}';
        }
        $content = '' .
            '<div class="form-section" id="form-line-' . $index . '">' .
                '<div class="row">' .
                    '<div class="form-group col-sm-6">' .
                        '<label for="page_new_' . $index . '">' .
                            $this->getLanguageService()->getLL('wiz_newPages_page') . ' ' . $label . ':' .
                        '</label>' .
                        '<div class="form-control-wrap">' .
                            '<input class="form-control" type="text" id="page_new_' . $index . '" name="data[pages][NEW' . $index . '][title]" />' .
                        '</div>' .
                    '</div>' .
                    '<div class="form-group col-sm-6">' .
                        '<label>' .
                            $this->getLanguageService()->sL('LLL:EXT:lang/locallang_general.xlf:LGL.type') .
                        '</label>' .
                        '<div class="form-control-wrap">' .
                            '<div class="input-group">' .
                                '<div id="page_new_icon_' . $index . '" class="input-group-addon input-group-icon">' .
                                    $this->iconFactory->getIconForRecord('pages', [], Icon::SIZE_SMALL)->render() .
                                '</div>' .
                                '<select class="form-control form-control-adapt t3js-wizardcrpages-select-doktype" name="data[pages][NEW' . $index . '][doktype]" data-target="#page_new_icon_' . $index . '">' .
                                    $this->typeSelectHtml .
                                '</select>' .
                            '</div>' .
                        '</div>' .
                    '</div>' .
                '</div>' .
            '</div>';
        return $content;
    }

    /**
     * Get type selector
     *
     * @return string
     */
    protected function getTypeSelectHtml()
    {
        $content = '';

        // find all available doktypes for the current user
        $types = $GLOBALS['PAGES_TYPES'];
        unset($types['default']);
        $types = array_keys($types);
        $types[] = PageRepository::DOKTYPE_DEFAULT;
        if (!$this->getBackendUser()->isAdmin() && isset($this->getBackendUser()->groupData['pagetypes_select'])) {
            $types = GeneralUtility::trimExplode(',', $this->getBackendUser()->groupData['pagetypes_select'], true);
        }
        $removeItems = isset($this->pagesTsConfig['doktype.']['removeItems']) ? GeneralUtility::trimExplode(',', $this->pagesTsConfig['doktype.']['removeItems'], true) : [];
        $allowedDoktypes = array_diff($types, $removeItems);

        // fetch all doktypes in the TCA
        $availableDoktypes = $GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'];

        // sort by group and allowedDoktypes
        $groupedData = [];
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

        // render the HTML
        foreach ($groupedData as $groupLabel => $items) {
            $groupContent = '';
            foreach ($items as $item) {
                $label = $this->getLanguageService()->sL($item[0], true);
                $value = $item[1];
                $icon = !empty($item[2]) ? FormEngineUtility::getIconHtml($item[2], $label, $label) : '';
                $groupContent .= '<option value="' . htmlspecialchars($value) . '" data-icon="' . htmlspecialchars($icon) . '">' . $label . '</option>';
            }
            $groupLabel = $this->getLanguageService()->sL($groupLabel, true);
            $content .= '<optgroup label="' . $groupLabel . '">' . $groupContent . '</optgroup>';
        }

        return $content;
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
