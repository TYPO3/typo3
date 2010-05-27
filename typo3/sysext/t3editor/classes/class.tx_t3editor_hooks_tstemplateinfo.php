<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Tobias Liebig <mail_typo3@etobi.de>
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

require_once(t3lib_extMgm::extPath('t3editor', 'classes/class.tx_t3editor.php'));

class tx_t3editor_hooks_tstemplateinfo {

	/**
	 *
	 * @var tx_t3editor
	 */
	protected $t3editor = NULL;

	/**
	 *
	 * @return tx_t3editor
	 */
	protected function getT3editor() {
		if ($this->t3editor == NULL) {
			$this->t3editor = t3lib_div::makeInstance('tx_t3editor')
				->setMode(tx_t3editor::MODE_TYPOSCRIPT);
		}
		return $this->t3editor;
	}

	/**
	 * Hook-function: inject t3editor JavaScript code before the page is compiled
	 * called in typo3/template.php:startPage
	 *
	 * @param array $parameters
	 * @param template $pObj
	 */
	public function preStartPageHook($parameters, $pObj) {
		// enable editor in Template-Modul
		if (preg_match('/sysext\/tstemplate\/ts\/index\.php/', $_SERVER['SCRIPT_NAME'])) {

			$t3editor = $this->getT3editor();

			// insert javascript code in document header
			$pObj->JScode .= $t3editor->getJavascriptCode($pObj);
			$pObj->loadJavascriptLib(t3lib_extmgm::extRelPath('t3editor') . 'res/jslib/tx_tstemplateinfo/tx_tstemplateinfo.js');
		}
	}


	/**
	 * Hook-function:
	 * called in typo3/sysext/tstemplate_info/class.tx_tstemplateinfo.php
	 *
	 * @param array $parameters
	 * @param tx_tstemplateinfo $pObj
	 */
	public function postOutputProcessingHook($parameters, $pObj) {
		$t3editor = $this->getT3editor();

		if (!$t3editor->isEnabled()) {
			return;
		}

		foreach (array('constants', 'config') as $type) {
			if ($parameters['e'][$type]) {
				$attributes = 'rows="' . $parameters['numberOfRows'] . '" ' .
					'wrap="off" ' .
					$pObj->pObj->doc->formWidthText(48, 'width:98%;height:60%', 'off');

				$title = $GLOBALS['LANG']->getLL('template') . ' ' .
					htmlspecialchars($parameters['tplRow']['title']) .
					$GLOBALS['LANG']->getLL('delimiter') . ' ' .
					$GLOBALS['LANG']->getLL($type);

				$outCode = $t3editor->getCodeEditor(
					'data[' . $type . ']',
					'fixed-font enable-tab',
					'$1', // will be replaced with the actual code later, see preg_replace below
					$attributes,
					$title,
					array(
						'pageId' => intval($pObj->pObj->id)
					)
				);
				$parameters['theOutput'] = preg_replace(
					'/\<textarea name="data\[' . $type . '\]".*\>([^\<]*)\<\/textarea\>/mi',
					$outCode,
					$parameters['theOutput']
				);
			}
		}
	}


	/**
	 * Process saving request like in class.tstemplateinfo.php (TCE processing)
	 *
	 * @return boolean true if successful
	 */
	public function save($parameters, $pObj) {
		$savingsuccess = false;
		if ($parameters['type'] == 'tx_tstemplateinfo') {

			$pageId = t3lib_div::_GP('pageId');
			if (!is_numeric($pageId) || $pageId < 1) {
				return false;
			}

			// if given use the requested template_uid
			// if not, use the first template-record on the page (in this case there should only be one record!)
			$set = t3lib_div::_GP('SET');
			$template_uid = $set['templatesOnPage'] ? $set['templatesOnPage'] : 0;

			$tmpl = t3lib_div::makeInstance('t3lib_tsparser_ext');	// Defined global here!
			$tmpl->tt_track = 0;	// Do not log time-performance information
			$tmpl->init();

			// Get the row of the first VISIBLE template of the page. whereclause like the frontend.
			$tplRow = $tmpl->ext_getFirstTemplate($pageId, $template_uid);
			$existTemplate = (is_array($tplRow) ? true : false);

			if ($existTemplate)	{
				$saveId = ($tplRow['_ORIG_uid'] ? $tplRow['_ORIG_uid'] : $tplRow['uid']);

				// Update template ?
				$POST = t3lib_div::_POST();

				if ($POST['submit']) {
					require_once(PATH_t3lib . 'class.t3lib_tcemain.php');

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
						// Create new tce-object
						$tce = t3lib_div::makeInstance('t3lib_TCEmain');
						$tce->stripslashes_values = 0;

						// Initialize
						$tce->start($recData, array());

						// Saved the stuff
						$tce->process_datamap();

						// Clear the cache (note: currently only admin-users can clear the
						// cache in tce_main.php)
						$tce->clear_cacheCmd('all');

						$savingsuccess = true;
					}
				}
			}
		}
		return $savingsuccess;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3editor/classes/class.tx_t3editor_hooks_tstemplateinfo.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3editor/classes/class.tx_t3editor_hooks_tstemplateinfo.php']);
}

?>