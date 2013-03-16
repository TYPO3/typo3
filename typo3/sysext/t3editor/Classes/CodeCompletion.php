<?php
namespace TYPO3\CMS\T3Editor;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Stephan Petzl <spetzl@gmx.at> and Christian Kartnig <office@hahnepeter.de>
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
 * Code completion for t3editor
 *
 * @author Stephan Petzl <spetzl@gmx.at>
 * @author Christian Kartnig <office@hahnepeter.de>
 */
class CodeCompletion {

	/**
	 * @var \TYPO3\CMS\Core\Http\AjaxRequestHandler
	 */
	protected $ajaxObj;

	/**
	 * General processor for AJAX requests.
	 * (called by typo3/ajax.php)
	 *
	 * @param array $params Additional parameters (not used here)
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj The TYPO3AJAX object of this request
	 * @return void
	 * @author Oliver Hader <oliver@typo3.org>
	 */
	public function processAjaxRequest($params, \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj) {
		$this->ajaxObj = $ajaxObj;
		$ajaxIdParts = explode('::', $ajaxObj->getAjaxID(), 2);
		$ajaxMethod = $ajaxIdParts[1];
		$response = array();
		// Process the AJAX requests:
		if ($ajaxMethod == 'loadTemplates') {
			$ajaxObj->setContent($this->loadTemplates(intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pageId'))));
			$ajaxObj->setContentFormat('jsonbody');
		}
	}

	/**
	 * Loads all templates up to a given page id (walking the rootline) and
	 * cleans parts that are not required for the t3editor codecompletion.
	 *
	 * @param integer $pageId ID of the page
	 * @param integer $templateId Currently unused (default: 0)
	 * @return array Cleaned array of TypoScript information
	 * @author Oliver Hader <oliver@typo3.org>
	 */
	protected function loadTemplates($pageId, $templateId = 0) {
		$templates = array();
		// Check whether access is granted (only admin have access to sys_template records):
		if ($GLOBALS['BE_USER']->isAdmin()) {
			// Check whether there is a pageId given:
			if ($pageId) {
				$templates = $this->getMergedTemplates($pageId);
			} else {
				$this->ajaxObj->setError($GLOBALS['LANG']->getLL('pageIDInteger'));
			}
		} else {
			$this->ajaxObj->setError($GLOBALS['LANG']->getLL('noPermission'));
		}
		return $templates;
	}

	/**
	 * Gets merged templates by walking the rootline to a given page id.
	 *
	 * @todo oliver@typo3.org: Refactor this method and comment what's going on there
	 * @param integer $pageId
	 * @param integer $templateId
	 * @return array Setup part of merged template records
	 */
	protected function getMergedTemplates($pageId, $templateId = 0) {
		$result = array();
		/** @var $tsParser \TYPO3\CMS\Core\TypoScript\ExtendedTemplateService */
		$tsParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\ExtendedTemplateService');
		$tsParser->tt_track = 0;
		$tsParser->init();
		// Gets the rootLine
		$page = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		$rootLine = $page->getRootLine($pageId);
		// This generates the constants/config + hierarchy info for the template.
		$tsParser->runThroughTemplates($rootLine);
		// ts-setup & ts-constants of the currently edited template should not be included
		// therefor we have to delete the last template from the stack
		array_pop($tsParser->config);
		array_pop($tsParser->constants);
		$tsParser->linkObjects = TRUE;
		$tsParser->ext_regLinenumbers = FALSE;
		$tsParser->bType = $bType;
		$tsParser->resourceCheck = 1;
		$tsParser->removeFromGetFilePath = PATH_site;
		$tsParser->generateConfig();
		$result = $this->treeWalkCleanup($tsParser->setup);
		return $result;
	}

	/**
	 * Walks through a tree of TypoScript configuration an cleans it up.
	 *
	 * @TODO oliver@typo3.org: Define and comment why this is necessary and exactly happens below
	 * @param array $treeBranch TypoScript configuration or sub branch of it
	 * @return array Cleaned TypoScript branch
	 */
	private function treeWalkCleanup(array $treeBranch) {
		$cleanedTreeBranch = array();
		foreach ($treeBranch as $key => $value) {
			$dotCount = substr_count($key, '.');
			//type definition or value-assignment
			if ($dotCount == 0) {
				if ($value != '') {
					if (strlen($value) > 20) {
						$value = substr($value, 0, 20);
					}
					if (!isset($cleanedTreeBranch[$key])) {
						$cleanedTreeBranch[$key] = array();
					}
					$cleanedTreeBranch[$key]['v'] = $value;
				}
			} elseif ($dotCount == 1) {
				// subtree (definition of properties)
				$subBranch = $this->treeWalkCleanup($value);
				if ($subBranch) {
					$key = str_replace('.', '', $key);
					if (!isset($cleanedTreeBranch[$key])) {
						$cleanedTreeBranch[$key] = array();
					}
					$cleanedTreeBranch[$key]['c'] = $subBranch;
				}
			}
		}
		return $cleanedTreeBranch;
	}

}


?>