<?php
namespace TYPO3\CMS\Tstemplate\Controller;

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

use TYPO3\CMS\Backend\Module\AbstractFunctionModule;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * TypoScript Constant editor
 *
 * Module Include-file
 *
 * $GLOBALS['TYPO3_CONF_VARS']['MODS']['web_ts']['onlineResourceDir'] = 'fileadmin/fonts/';
 * // This is the path (must be in "fileadmin/" !!) where the web_ts/constant-editor submodule fetches online resources.
 * Put fonts (ttf) and standard images here!
 */
class TypoScriptTemplateConstantEditorModuleFunctionController extends AbstractFunctionModule {

	/**
	 * @var TypoScriptTemplateModuleController
	 */
	public $pObj;

	/**
	 * Initialize editor
	 *
	 * Initializes the module.
	 * Done in this function because we may need to re-initialize if data is submitted!
	 *
	 * @param int $pageId
	 * @param int $template_uid
	 * @return bool
	 */
	public function initialize_editor($pageId, $template_uid = 0) {
		$templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);
		$GLOBALS['tmpl'] = $templateService;

		// Do not log time-performance information
		$templateService->tt_track = FALSE;

		$templateService->init();
		$templateService->ext_localGfxPrefix = ExtensionManagementUtility::extPath('tstemplate');
		$templateService->ext_localWebGfxPrefix = ExtensionManagementUtility::extRelPath('tstemplate') . 'Resources/Public/';

		// Get the row of the first VISIBLE template of the page. whereclause like the frontend.
		$GLOBALS['tplRow'] = $templateService->ext_getFirstTemplate($pageId, $template_uid);
		// IF there was a template...
		if (is_array($GLOBALS['tplRow'])) {
			// Gets the rootLine
			$sys_page = GeneralUtility::makeInstance(PageRepository::class);
			$rootLine = $sys_page->getRootLine($pageId);
			// This generates the constants/config + hierarchy info for the template.
			$templateService->runThroughTemplates($rootLine, $template_uid);
			// The editable constants are returned in an array.
			$GLOBALS['theConstants'] = $templateService->generateConfig_constants();
			// The returned constants are sorted in categories, that goes into the $tmpl->categories array
			$templateService->ext_categorizeEditableConstants($GLOBALS['theConstants']);
			// This array will contain key=[expanded constant name], value=line number in template. (after edit_divider, if any)
			$templateService->ext_regObjectPositions($GLOBALS['tplRow']['constants']);
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Display example
	 *
	 * @param string $theOutput
	 * @return string
	 */
	public function displayExample($theOutput) {
		$templateService = $this->getExtendedTemplateService();
		if ($templateService->helpConfig['imagetag'] || $templateService->helpConfig['description'] || $templateService->helpConfig['header']) {
			$theOutput .= $this->pObj->doc->spacer(30);
			$theOutput .= $this->pObj->doc->section($templateService->helpConfig['header'], '<div align="center">' . $templateService->helpConfig['imagetag'] . '</div><BR>' . ($templateService->helpConfig['description'] ? implode(explode('//', $templateService->helpConfig['description']), '<BR>') . '<BR>' : '') . ($templateService->helpConfig['bulletlist'] ? '<ul><li>' . implode(explode('//', $templateService->helpConfig['bulletlist']), '<li>') . '</ul>' : '<BR>'));
		}
		return $theOutput;
	}

	/**
	 * Main
	 *
	 * @return string
	 */
	public function main() {
		$lang = $this->getLanguageService();

		$lang->includeLLFile('EXT:tstemplate/Resources/Private/Language/locallang_ceditor.xlf');
		$theOutput = '';
		// Create extension template
		$this->pObj->createTemplate($this->pObj->id);
		// Checking for more than one template an if, set a menu...
		$manyTemplatesMenu = $this->pObj->templateMenu();
		$template_uid = 0;
		if ($manyTemplatesMenu) {
			$template_uid = $this->pObj->MOD_SETTINGS['templatesOnPage'];
		}

		// initialize
		$existTemplate = $this->initialize_editor($this->pObj->id, $template_uid);
		if ($existTemplate) {
			$templateService = $this->getExtendedTemplateService();
			$tplRow = $this->getTemplateRow();
			$theConstants = $this->getConstants();

			$this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Tstemplate/ConstantEditor');
			$saveId = $tplRow['_ORIG_uid'] ? $tplRow['_ORIG_uid'] : $tplRow['uid'];
			// Update template ?
			if (GeneralUtility::_POST('submit') ) {
				$templateService->changed = 0;
				$templateService->ext_procesInput(GeneralUtility::_POST(), array(), $theConstants, $tplRow);
				if ($templateService->changed) {
					// Set the data to be saved
					$recData = array();
					$recData['sys_template'][$saveId]['constants'] = implode($templateService->raw, LF);
					// Create new  tce-object
					$tce = GeneralUtility::makeInstance(DataHandler::class);
					$tce->stripslashes_values = FALSE;
					$tce->start($recData, array());
					$tce->process_datamap();
					// Clear the cache (note: currently only admin-users can clear the cache in tce_main.php)
					$tce->clear_cacheCmd('all');
					// re-read the template ...
					$this->initialize_editor($this->pObj->id, $template_uid);
					// re-read the constants as they have changed
					$theConstants = $this->getConstants();
				}
			}
			// Resetting the menu (start). I wonder if this in any way is a violation of the menu-system. Haven't checked. But need to do it here, because the menu is dependent on the categories available.
			$this->pObj->MOD_MENU['constant_editor_cat'] = $templateService->ext_getCategoryLabelArray();
			$this->pObj->MOD_SETTINGS = BackendUtility::getModuleData($this->pObj->MOD_MENU, GeneralUtility::_GP('SET'), $this->pObj->MCONF['name']);
			// Resetting the menu (stop)
			$content = IconUtility::getSpriteIconForRecord('sys_template', $tplRow) . '<strong>' . $this->pObj->linkWrapTemplateTitle($tplRow['title'], 'constants') . '</strong>' . htmlspecialchars((trim($tplRow['sitetitle']) ? ' (' . $tplRow['sitetitle'] . ')' : ''));
			$theOutput .= $this->pObj->doc->section($lang->getLL('editConstants', TRUE), $content, FALSE, TRUE);
			if ($manyTemplatesMenu) {
				$theOutput .= $this->pObj->doc->section('', $manyTemplatesMenu);
			}
			$theOutput .= $this->pObj->doc->spacer(10);
			if (!empty($this->pObj->MOD_MENU['constant_editor_cat'])) {
				$menu = '<div class="form-inline form-inline-spaced">';
				$menu .= BackendUtility::getDropdownMenu($this->pObj->id, 'SET[constant_editor_cat]', $this->pObj->MOD_SETTINGS['constant_editor_cat'], $this->pObj->MOD_MENU['constant_editor_cat']);
				$menu .= '</div>';
				$theOutput .= $this->pObj->doc->section($lang->getLL('category', TRUE), '<span class="text-nowrap">' . $menu . '</span>', FALSE);
			} else {
				$theOutput .= $this->pObj->doc->section($lang->getLL('noConstants', TRUE), $lang->getLL('noConstantsDescription', TRUE), FALSE, FALSE, 1);
			}
			$theOutput .= $this->pObj->doc->spacer(15);
			// Category and constant editor config:
			$category = $this->pObj->MOD_SETTINGS['constant_editor_cat'];
			$templateService->ext_getTSCE_config($category);

			$printFields = trim($templateService->ext_printFields($theConstants, $category));
			if ($printFields) {
				$theOutput .= $this->pObj->doc->section('', $printFields);
			}
			$BE_USER_modOptions = BackendUtility::getModTSconfig(0, 'mod.' . $this->pObj->MCONF['name']);
			if ($BE_USER_modOptions['properties']['constantEditor.']['example'] != 'top') {
				$theOutput = $this->displayExample($theOutput);
			}
		} else {
			$theOutput .= $this->pObj->noTemplate(1);
		}
		return $theOutput;
	}

	/**
	 * @return ExtendedTemplateService
	 */
	protected function getExtendedTemplateService() {
		return $GLOBALS['tmpl'];
	}

	/**
	 * @return array
	 */
	protected function getTemplateRow() {
		return $GLOBALS['tplRow'];
	}

	/**
	 * @return array
	 */
	protected function getConstants() {
		return $GLOBALS['theConstants'];
	}

}
