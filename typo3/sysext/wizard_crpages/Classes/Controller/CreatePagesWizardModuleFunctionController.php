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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Creates the "Create pages" wizard
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class CreatePagesWizardModuleFunctionController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {

	/**
	 * Complete tsConfig
	 *
	 * @var array
	 */
	protected $tsConfig = array();

	/**
	 * Part of tsConfig with TCEFORM.pages. settings
	 *
	 * @var array
	 */
	protected $pagesTsConfig = array();

	/**
	 * @var string
	 */
	protected $backPath = '';

	/**
	 * The type select HTML
	 */
	protected $typeSelectHtml = '';

	/**
	 * Main function creating the content for the module.
	 *
	 * @return string HTML content for the module, actually a "section" made through the parent object in $this->pObj
	 */
	public function main() {
		$this->getLanguageService()->includeLLFile('EXT:wizard_crpages/locallang.xlf');
		$theCode = '';
		$this->tsConfig = BackendUtility::getPagesTSconfig($this->pObj->id);
		$this->pagesTsConfig = isset($this->tsConfig['TCEFORM.']['pages.']) ? $this->tsConfig['TCEFORM.']['pages.'] : array();
		$this->backPath = $GLOBALS['BACK_PATH'];

		// Create new pages here?
		$pageRecord = BackendUtility::getRecord('pages', $this->pObj->id, 'uid', ' AND ' . $this->getBackendUser()->getPagePermsClause(8));
		$pageRepository = GeneralUtility::makeInstance(PageRepository::class);
		$menuItems = $pageRepository->getMenu($this->pObj->id, '*', 'sorting', '', FALSE);
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
				$firstRecord = TRUE;
				$previousIdentifier = '';
				foreach ($data['pages'] as $identifier => $dat) {
					if (!trim($dat['title'])) {
						unset($data['pages'][$identifier]);
					} else {
						$data['pages'][$identifier]['hidden'] = GeneralUtility::_GP('hidePages') ? 1 : 0;
						$data['pages'][$identifier]['nav_hide'] = GeneralUtility::_GP('hidePagesInMenus') ? 1 : 0;
						if ($firstRecord) {
							$firstRecord = FALSE;
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
					$dataHandler->start($data, array());
					$dataHandler->process_datamap();
					BackendUtility::setUpdateSignal('updatePageTree');
					$flashMessage = GeneralUtility::makeInstance(FlashMessage::class, '', $this->getLanguageService()->getLL('wiz_newPages_create'));
				} else {
					$flashMessage = GeneralUtility::makeInstance(FlashMessage::class, '', $this->getLanguageService()->getLL('wiz_newPages_noCreate'), FlashMessage::ERROR);
				}
				$theCode .= $flashMessage->render();
				// Display result:
				$menuItems = $pageRepository->getMenu($this->pObj->id, '*', 'sorting', '', FALSE);
				$lines = array();
				foreach ($menuItems as $record) {
					BackendUtility::workspaceOL('pages', $record);
					if (is_array($record)) {
						$lines[] = '<nobr>' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', $record, array('title' => BackendUtility::titleAttribForPages($record, '', FALSE))) . htmlspecialchars(GeneralUtility::fixed_lgd_cs($record['title'], $this->getBackendUser()->uc['titleLen'])) . '</nobr>';
					}
				}
				$theCode .= '<h4>' . $this->getLanguageService()->getLL('wiz_newPages_currentMenu') . '</h4>' . implode('<br />', $lines);
			} else {
				// Display create form
				$this->typeSelectHtml = $this->getTypeSelectHtml();
				$lines = array();
				$tableData = array();
				for ($a = 0; $a < 9; $a++) {
					$tableData[] = $this->getFormLine($a);
				}
				$lines[] = '<div class="table-fit"><table id="formFieldContainer" class="table table-striped table-hover"><tbody id="formFieldContainerBody">' . implode(LF, $tableData) . '</tbody></table></div>';
				$theCode .= '<h4>' . $this->getLanguageService()->getLL('wiz_newPages') . ':</h4>' . implode('', $lines) . '<input class="btn btn-default" type="button" id="createNewFormFields" value="' . $this->getLanguageService()->getLL('wiz_newPages_addMoreLines') . '" />';
				$theCode .= '<div class="checkbox"><label for="createInListEnd"><input type="checkbox" name="createInListEnd" id="createInListEnd" value="1" />' . $this->getLanguageService()->getLL('wiz_newPages_listEnd') . '</label></div>';
				$theCode .= '<div class="checkbox"><label for="hidePages"><input type="checkbox" name="hidePages" id="hidePages" value="1" />' . $this->getLanguageService()->getLL('wiz_newPages_hidePages') . '</label></div>';
				$theCode .= '<div class="checkbox"><label for="hidePagesInMenus"><input type="checkbox" name="hidePagesInMenus" id="hidePagesInMenus" value="1" />' . $this->getLanguageService()->getLL('wiz_newPages_hidePagesInMenus') . '</label></div>';
				$theCode .= '<input class="btn btn-default" type="submit" name="create" value="' . $this->getLanguageService()->getLL('wiz_newPages_lCreate') . '" /> <input class="btn btn-default" type="reset" value="' . $this->getLanguageService()->getLL('wiz_newPages_lReset') . '" />';

				/** @var \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer */
				$pageRenderer = $GLOBALS['TBE_TEMPLATE']->getPageRenderer();
				$pageRenderer->loadJquery();
				$pageRenderer->loadRequireJsModule('TYPO3/CMS/WizardCrpages/WizardCreatePages');
				// Add inline code
				$inlineJavaScriptCode = 'var tpl = "' . addslashes(str_replace(array(LF, TAB), array('', ''), $this->getFormLine('#'))) . '", i, line, div, bg, label;';
				$pageRenderer->addJsInlineCode('wizard_crpages', $inlineJavaScriptCode);
			}
		} else {
			$theCode .= GeneralUtility::makeInstance(FlashMessage::class, '', $this->getLanguageService()->getLL('wiz_newPages_errorMsg1'), FlashMessage::ERROR)->render();
		}
		// CSH
		$theCode .= BackendUtility::cshItem('_MOD_web_func', 'tx_wizardcrpages', NULL, '<div class="t3-help">|</div>');
		$out = $this->pObj->doc->header($this->getLanguageService()->getLL('wiz_crMany'));
		$out .= $this->pObj->doc->section('', $theCode, FALSE, TRUE);
		return $out;
	}

	/**
	 * Return one line in the form
	 *
	 * @param int|string $index An integer: the line counter for which to create the line. Use "#" to create an template for javascript (used by ExtJS)
	 * @return string HTML code for one input line for one new page
	 */
	protected function getFormLine($index) {
		if (is_numeric($index)) {
			$label = $index + 1;
		} else {
			// used as template for JavaScript
			$index = '{0}';
			$label = '{1}';
		}
		$content = '<label for="page_new_' . $index . '"> ' . $this->getLanguageService()->getLL('wiz_newPages_page') . ' ' . $label;
		$content .= ':&nbsp;</label>';
		// Title
		$content .= '<input type="text" id="page_new_' . $index . '" name="data[pages][NEW' . $index . '][title]"' . $this->pObj->doc->formWidth(35) . ' />&nbsp';
		// type selector
		$content .= '<span>' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_general.xlf:LGL.type') . '</span>';
		$content .= '<select class="select icon-select" name="data[pages][NEW' . $index . '][doktype]" style="background: url(&quot;' . $this->backPath . 'sysext/t3skin/icons/gfx/i/pages.gif&quot;) no-repeat scroll 0% 50% rgb(255, 255, 255); padding: 1px 1px 1px 24px;">';
		$content .= $this->typeSelectHtml;
		$content .= '</select>';
		return '<tr id="form-line-' . $index . '"><td>' . $content . '</td></tr>';
	}

	/**
	 * Get type selector
	 *
	 * @return string
	 */
	protected function getTypeSelectHtml() {
		$content = '';

		// find all available doktypes for the current user
		$types = $GLOBALS['PAGES_TYPES'];
		unset($types['default']);
		$types = array_keys($types);
		$types[] = PageRepository::DOKTYPE_DEFAULT;
		if (!$this->getBackendUser()->isAdmin() && isset($this->getBackendUser()->groupData['pagetypes_select'])) {
			$types = GeneralUtility::trimExplode(',', $this->getBackendUser()->groupData['pagetypes_select'], TRUE);
		}
		$removeItems = isset($this->pagesTsConfig['doktype.']['removeItems']) ? GeneralUtility::trimExplode(',', $this->pagesTsConfig['doktype.']['removeItems'], TRUE) : array();
		$allowedDoktypes = array_diff($types, $removeItems);

		// fetch all doktypes in the TCA
		$availableDoktypes = $GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'];

		// sort by group and allowedDoktypes
		$groupedData = array();
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
				$label = $this->getLanguageService()->sL($item[0], TRUE);
				$value = $item[1];
				$icon = ($item[2] ? $this->getStyleAttributeWithIcon($item[2]) : '');
				$groupContent .= '<option ' . $icon . ' value="' . htmlspecialchars($value) . '">' . $label . '</option>';
			}
			$groupLabel = $this->getLanguageService()->sL($groupLabel, TRUE);
			$content .= '<optgroup class="c-divider" label="' . $groupLabel . '">' . $groupContent . '</optgroup>';
		}

		return $content;
	}

	/**
	 * Returns the style attribute for a select element with the icon in it
	 *
	 * @param string $src
	 * @return string
	 */
	protected function getStyleAttributeWithIcon($src) {
		$icon = \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, 'gfx/' . $src, '', 1);
		return 'style="background: #fff url(&quot;' . $icon. '&quot;) 0% 50% no-repeat; height: 16px; padding-top: 2px; padding-left: 22px;"';
	}

	/**
	 * Returns LanguageService
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * Returns the current BE user.
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}
}
