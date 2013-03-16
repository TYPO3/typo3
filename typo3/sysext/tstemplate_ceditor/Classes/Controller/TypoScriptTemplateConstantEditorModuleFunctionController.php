<?php
namespace TYPO3\CMS\TstemplateCeditor\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * TypoScript Constant editor
 *
 * Module Include-file
 *
 * localconf-variables:
 * $TYPO3_CONF_VARS['MODS']['web_ts']['onlineResourceDir'] = 'fileadmin/fonts/';		// This is the path (must be in "fileadmin/" !!) where the web_ts/constant-editor submodule fetches online resources. Put fonts (ttf) and standard images here!
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class TypoScriptTemplateConstantEditorModuleFunctionController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {

	/**
	 * Initialize editor
	 *
	 * @param integer $pageId
	 * @param integer $template_uid
	 * @return integer
	 * @todo Define visibility
	 */
	public function initialize_editor($pageId, $template_uid = 0) {
		// Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
		global $tmpl, $tplRow, $theConstants;
		$tmpl = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\ExtendedTemplateService');
		// Defined global here!
		$tmpl->tt_track = 0;
		// Do not log time-performance information
		$tmpl->init();
		$tmpl->ext_localGfxPrefix = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tstemplate_ceditor');
		$tmpl->ext_localWebGfxPrefix = $GLOBALS['BACK_PATH'] . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('tstemplate_ceditor');
		// Get the row of the first VISIBLE template of the page. whereclause like the frontend.
		$tplRow = $tmpl->ext_getFirstTemplate($pageId, $template_uid);
		// IF there was a template...
		if (is_array($tplRow)) {
			// Gets the rootLine
			$sys_page = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
			$rootLine = $sys_page->getRootLine($pageId);
			// This generates the constants/config + hierarchy info for the template.
			$tmpl->runThroughTemplates($rootLine, $template_uid);
			// The editable constants are returned in an array.
			$theConstants = $tmpl->generateConfig_constants();
			// The returned constants are sorted in categories, that goes into the $tmpl->categories array
			$tmpl->ext_categorizeEditableConstants($theConstants);
			// This array will contain key=[expanded constantname], value=linenumber in template. (after edit_divider, if any)
			$tmpl->ext_regObjectPositions($tplRow['constants']);
			return 1;
		}
	}

	/**
	 * Display example
	 *
	 * @param string $theOutput
	 * @return string
	 * @todo Define visibility
	 */
	public function displayExample($theOutput) {
		global $tmpl;
		if ($tmpl->helpConfig['imagetag'] || $tmpl->helpConfig['description'] || $tmpl->helpConfig['header']) {
			$theOutput .= $this->pObj->doc->spacer(30);
			$theOutput .= $this->pObj->doc->section($tmpl->helpConfig['header'], '<div align="center">' . $tmpl->helpConfig['imagetag'] . '</div><BR>' . ($tmpl->helpConfig['description'] ? implode(explode('//', $tmpl->helpConfig['description']), '<BR>') . '<BR>' : '') . ($tmpl->helpConfig['bulletlist'] ? '<ul><li>' . implode(explode('//', $tmpl->helpConfig['bulletlist']), '<li>') . '</ul>' : '<BR>'));
		}
		return $theOutput;
	}

	/**
	 * Main
	 *
	 * @return string
	 * @todo Define visibility
	 */
	public function main() {
		global $TYPO3_CONF_VARS;
		global $tmpl, $tplRow, $theConstants;
		$GLOBALS['LANG']->includeLLFile('EXT:tstemplate_ceditor/locallang.xlf');
		$theOutput = '';
		// Create extension template
		$this->pObj->createTemplate($this->pObj->id);
		// Checking for more than one template an if, set a menu...
		$manyTemplatesMenu = $this->pObj->templateMenu();
		$template_uid = 0;
		if ($manyTemplatesMenu) {
			$template_uid = $this->pObj->MOD_SETTINGS['templatesOnPage'];
		}
		// BUGBUG: Should we check if the user may at all read and write template-records???
		// initialize
		$existTemplate = $this->initialize_editor($this->pObj->id, $template_uid);
		if ($existTemplate) {
			$saveId = $tplRow['_ORIG_uid'] ? $tplRow['_ORIG_uid'] : $tplRow['uid'];
			// Update template ?
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('submit') || \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('submit_x')) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('submit_y'))) {
				$tmpl->changed = 0;
				$tmpl->ext_procesInput(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST(), array(), $theConstants, $tplRow);
				if ($tmpl->changed) {
					// Set the data to be saved
					$recData = array();
					$recData['sys_template'][$saveId]['constants'] = implode($tmpl->raw, LF);
					// Create new  tce-object
					$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
					$tce->stripslashes_values = 0;
					$tce->start($recData, array());
					$tce->process_datamap();
					// Clear the cache (note: currently only admin-users can clear the cache in tce_main.php)
					$tce->clear_cacheCmd('all');
					// re-read the template ...
					$this->initialize_editor($this->pObj->id, $template_uid);
				}
			}
			// Resetting the menu (start). I wonder if this in any way is a violation of the menu-system. Haven't checked. But need to do it here, because the menu is dependent on the categories available.
			$this->pObj->MOD_MENU['constant_editor_cat'] = $tmpl->ext_getCategoryLabelArray();
			$this->pObj->MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData($this->pObj->MOD_MENU, \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SET'), $this->pObj->MCONF['name']);
			// Resetting the menu (stop)
			$content = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('sys_template', $tplRow) . '<strong>' . $this->pObj->linkWrapTemplateTitle($tplRow['title'], 'constants') . '</strong>' . htmlspecialchars((trim($tplRow['sitetitle']) ? ' (' . $tplRow['sitetitle'] . ')' : ''));
			$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('editConstants', TRUE), $content, FALSE, TRUE);
			if ($manyTemplatesMenu) {
				$theOutput .= $this->pObj->doc->section('', $manyTemplatesMenu);
			}
			$theOutput .= $this->pObj->doc->spacer(10);
			if (count($this->pObj->MOD_MENU['constant_editor_cat'])) {
				$menu = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu($this->pObj->id, 'SET[constant_editor_cat]', $this->pObj->MOD_SETTINGS['constant_editor_cat'], $this->pObj->MOD_MENU['constant_editor_cat']);
				$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('category', TRUE), '<NOBR>' . $menu . '</NOBR>', FALSE);
			} else {
				$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('noConstants', TRUE), $GLOBALS['LANG']->getLL('noConstantsDescription', TRUE), FALSE, FALSE, 1);
			}
			$theOutput .= $this->pObj->doc->spacer(15);
			// Category and constant editor config:
			$category = $this->pObj->MOD_SETTINGS['constant_editor_cat'];
			$tmpl->ext_getTSCE_config($category);
			$printFields = trim($tmpl->ext_printFields($theConstants, $category));
			if ($printFields) {
				$theOutput .= $this->pObj->doc->section('', $printFields);
			}
			if ($BE_USER_modOptions['properties']['constantEditor.']['example'] != 'top') {
				$theOutput = $this->displayExample($theOutput);
			}
		} else {
			$theOutput .= $this->pObj->noTemplate(1);
		}
		return $theOutput;
	}

}

?>