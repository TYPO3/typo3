<?php
namespace TYPO3\CMS\T3Editor\Hook;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2013 Tobias Liebig <mail_typo3@etobi.de>
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
 * Hook for tstemplate info
 *
 * @author Tobias Liebig <mail_typo3@etobi.de>
 */
class TypoScriptTemplateInfoHook {

	/**
	 * @var \TYPO3\CMS\T3Editor\T3Editor
	 */
	protected $t3editor = NULL;

	/**
	 * @var string
	 */
	protected $ajaxSaveType = 'tx_tstemplateinfo';

	/**
	 * @return \TYPO3\CMS\T3Editor\T3Editor
	 */
	protected function getT3editor() {
		if ($this->t3editor == NULL) {
			$this->t3editor = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\T3Editor\\T3Editor')->setMode(\TYPO3\CMS\T3Editor\T3Editor::MODE_TYPOSCRIPT)->setAjaxSaveType($this->ajaxSaveType);
		}
		return $this->t3editor;
	}

	/**
	 * Hook-function: inject t3editor JavaScript code before the page is compiled
	 * called in typo3/template.php:startPage
	 *
	 * @param array $parameters
	 * @param \TYPO3\CMS\Backend\Template\DocumentTemplate $pObj
	 * @return void
	 */
	public function preStartPageHook($parameters, $pObj) {
		// Enable editor in Template-Modul
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('M') === 'web_ts') {
			$t3editor = $this->getT3editor();
			// Insert javascript code in document header
			$pObj->JScode .= $t3editor->getJavascriptCode($pObj);
		}
	}

	/**
	 * Hook-function:
	 * called in typo3/sysext/tstemplate_info/class.tx_tstemplateinfo.php
	 *
	 * @param array $parameters
	 * @param \TYPO3\CMS\TstemplateInfo\Controller\TypoScriptTemplateInformationModuleFunctionController $pObj
	 * @return void
	 */
	public function postOutputProcessingHook($parameters, $pObj) {
		$t3editor = $this->getT3editor();
		if (!$t3editor->isEnabled()) {
			return;
		}
		foreach (array('constants', 'config') as $type) {
			if ($parameters['e'][$type]) {
				$attributes = 'rows="' . $parameters['numberOfRows'] . '" ' . 'wrap="off" ' . $pObj->pObj->doc->formWidthText(48, 'width:98%;height:60%', 'off');
				$title = $GLOBALS['LANG']->getLL('template') . ' ' . htmlspecialchars($parameters['tplRow']['title']) . $GLOBALS['LANG']->getLL('delimiter') . ' ' . $GLOBALS['LANG']->getLL($type);
				$outCode = $t3editor->getCodeEditor('data[' . $type . ']', 'fixed-font enable-tab', '$1', $attributes, $title, array(
					'pageId' => intval($pObj->pObj->id)
				));
				$parameters['theOutput'] = preg_replace('/\\<textarea name="data\\[' . $type . '\\]".*\\>([^\\<]*)\\<\\/textarea\\>/mi', $outCode, $parameters['theOutput']);
			}
		}
	}

	/**
	 * Process saving request like in class.tstemplateinfo.php (TCE processing)
	 *
	 * @return boolean TRUE if successful
	 */
	public function save($parameters, $pObj) {
		$savingsuccess = FALSE;
		if ($parameters['type'] == $this->ajaxSaveType) {
			$pageId = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pageId');
			if (!is_numeric($pageId) || $pageId < 1) {
				return FALSE;
			}
			// If given use the requested template_uid
			// if not, use the first template-record on the page (in this case there should only be one record!)
			$set = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SET');
			$template_uid = $set['templatesOnPage'] ? $set['templatesOnPage'] : 0;
			// Defined global here!
			$tmpl = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\ExtendedTemplateService');
			// Do not log time-performance information
			$tmpl->tt_track = 0;
			$tmpl->init();
			// Get the row of the first VISIBLE template of the page. whereclause like the frontend.
			$tplRow = $tmpl->ext_getFirstTemplate($pageId, $template_uid);
			$existTemplate = is_array($tplRow) ? TRUE : FALSE;
			if ($existTemplate) {
				$saveId = $tplRow['_ORIG_uid'] ? $tplRow['_ORIG_uid'] : $tplRow['uid'];
				// Update template ?
				$POST = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST();
				if ($POST['submit']) {
					// Set the data to be saved
					$recData = array();
					if (is_array($POST['data'])) {
						foreach ($POST['data'] as $field => $val) {
							switch ($field) {
							case 'constants':

							case 'config':

							case 'title':

							case 'sitetitle':

							case 'description':
								$recData['sys_template'][$saveId][$field] = $val;
								break;
							}
						}
					}
					if (count($recData)) {
						// process template row before saving
						require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tstemplate_info') . 'class.tx_tstemplateinfo.php';
						$tstemplateinfo = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_tstemplateinfo');
						/* @var $tstemplateinfo tx_tstemplateinfo */
						// load the MOD_SETTINGS in order to check if the includeTypoScriptFileContent is set
						$tstemplateinfo->pObj->MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData(array('includeTypoScriptFileContent' => TRUE), array(), 'web_ts');
						$recData['sys_template'][$saveId] = $tstemplateinfo->processTemplateRowBeforeSaving($recData['sys_template'][$saveId]);
						// Create new tce-object
						$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
						$tce->stripslashes_values = 0;
						// Initialize
						$tce->start($recData, array());
						// Saved the stuff
						$tce->process_datamap();
						// Clear the cache (note: currently only admin-users can clear the
						// cache in tce_main.php)
						$tce->clear_cacheCmd('all');
						$savingsuccess = TRUE;
					}
				}
			}
		}
		return $savingsuccess;
	}

}


?>