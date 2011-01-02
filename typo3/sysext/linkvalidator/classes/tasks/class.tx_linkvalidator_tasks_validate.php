<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2010 Michael Miousse (michael.miousse@infoglobe.ca)
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * This class provides Scheduler plugin implementation.
 *
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 * @package TYPO3
 * @subpackage linkvalidator
 */
class tx_linkvalidator_tasks_Validate extends tx_scheduler_Task {

	/**
	 * @var integer
	 */
	public $sleepTime;

	/**
	 * @var integer
	 */
	public $sleepAfterFinish;

	/**
	 * @var integer
	 */
	public $countInARun;

	/**
	 * @var integer
	 */
	public $totalBrokenLink = 0;

	/**
	 * @var integer
	 */
	public $oldTotalBrokenLink = 0;


	/**
	 * Function executed from the Scheduler.
	 *
	 * @return	void
	 */
	public function execute() {
		$this->setCliArguments();

		$file = t3lib_div::getFileAbsFileName($this->emailfile);
		$htmlFile = t3lib_div::getURL($file);
		$this->templateMail = t3lib_parsehtml::getSubpart($htmlFile, '###REPORT_TEMPLATE###');

			// The array to put the content into
		$html = array();
		$pageSections = '';
		$this->dif = FALSE;
		$pageList = t3lib_div::trimExplode(',', $this->page, 1);
		if (is_array($pageList)) {
			foreach ($pageList as $page) {
				$modTS = t3lib_BEfunc::getModTSconfig($page, 'mod.linkvalidator');
				$parseObj = t3lib_div::makeInstance('t3lib_TSparser');
				$parseObj->parse($this->configuration);
				$TSconfig = $parseObj->setup;
				$modTS = $modTS['properties'];
				$overrideTs = $TSconfig['mod.']['tx_linkvalidator.'];
				if (is_array($overrideTs)) {
					$modTS = t3lib_div::array_merge_recursive_overrule($modTS, $overrideTs);
				}

					// get the searchFields from TCA
				foreach ($GLOBALS['TCA'] as $tablename => $table) {
					if (!empty($table['columns'])) {
						foreach ($table['columns'] as $columnname => $column) {
							if ($column['config']['type'] == 'text' || $column['config']['type'] == 'input') {
								if (!empty($column['config']['softref']) && (stripos($column['config']['softref'], "typolink")
										!== FALSE || stripos($column['config']['softref'], "url") !== FALSE)) {

									$searchFields[$tablename][] = $columnname;
								}
							}
						}
					}
				}

					// get the searchFields from TypoScript
				foreach ($modTS['searchFields.'] as $table => $fieldList) {
					$fields = t3lib_div::trimExplode(',', $fieldList);
					foreach ($fields as $field) {
						if (is_array($searchFields[$table])) {
							if (array_search($field, $searchFields[$table]) === FALSE) {
								$searchFields[$table][] = $field;
							}
						}
					}
				}
				$linktypes = t3lib_div::trimExplode(',', $modTS['linktypes'], 1);
				if (is_array($linktypes)) {
					if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])
							&& is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
						foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $key => $value) {
							if (in_array($key, $linktypes)) {
								$array[$key] = 1;
							}
						}
					}
				}
				$pageIds = $this->extGetTreeList($page, $this->depth, 0, '1=1');
				$pageIds .= $page;
				$processing = t3lib_div::makeInstance('tx_linkvalidator_processing');
				$processing->init($searchFields, $pageIds);
				if (!empty($this->email)) {
					$oldLinkCounts = $processing->getLinkCounts($page);
					$this->oldTotalBrokenLink += $oldLinkCounts['brokenlinkCount'];
				}

				$processing->getLinkStatistics($array, $modTS['checkhidden']);

				if (!empty($this->email)) {
					$linkCounts = $processing->getLinkCounts($page);
					$this->totalBrokenLink += $linkCounts['brokenlinkCount'];
					$pageSections .= $this->buildMail($page, $pageIds, $linkCounts, $oldLinkCounts);
				}

			}
		}
		if ($this->totalBrokenLink != $this->oldTotalBrokenLink) {
			$this->dif = TRUE;
		}
		if ($this->totalBrokenLink > 0
			&& (!$this->emailonbrokenlinkonly || $this->dif)
			&& !empty($this->email)
		) {
			$this->reportEmail($pageSections, $modTS);
		}
		return TRUE;
	}


	/**
	 * Build and send warning email when new broken links were found.
	 *
	 * @param	string		$pageSections: Content of page section
	 * @param	string		$modTS: TSconfig array
	 * @return	bool		Mail sent or not
	 */
	function reportEmail($pageSections, $modTS) {
		$content = t3lib_parsehtml::substituteSubpart($this->templateMail, '###PAGE_SECTION###', $pageSections);
		$markerArray = array();
		$markerArray['totalBrokenLink'] = $this->totalBrokenLink;
		$markerArray['totalBrokenLink_old'] = $this->oldTotalBrokenLink;
		$content = t3lib_parsehtml::substituteMarkerArray($content, $markerArray, '###|###', TRUE, TRUE);

		$Typo3_htmlmail = t3lib_div::makeInstance('t3lib_htmlmail');
		$Typo3_htmlmail->start();
		$Typo3_htmlmail->useBase64();

		$convObj = t3lib_div::makeInstance('t3lib_cs');

		$charset = $convObj->parse_charset($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] ? $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] : 'utf-8');
		$Typo3_htmlmail->subject = $convObj->conv($modTS['mail.']['subject'], $charset, $modTS['mail.']['encoding'], 0);
		$Typo3_htmlmail->from_email = $modTS['mail.']['fromemail'];
		$Typo3_htmlmail->from_name = $modTS['mail.']['fromname'];
		$Typo3_htmlmail->replyto_email = $modTS['mail.']['replytoemail'];
		$Typo3_htmlmail->replyto_name = $modTS['mail.']['replytoname'];

		//$Typo3_htmlmail->addPlain($mcontent);
		$Typo3_htmlmail->setHTML($Typo3_htmlmail->encodeMsg($convObj->conv($content, $charset, $modTS['mail.']['encoding'], 0)));

		$Typo3_htmlmail->setHeaders();
		$Typo3_htmlmail->setContent();
		$Typo3_htmlmail->setRecipient($this->email);

		return $Typo3_htmlmail->sendtheMail();
	}


	/**
	 * Build the mail content.
	 *
	 * @param	int			$curPage: id of the current page
	 * @param	string		$pageList: list of pages id
	 * @param	array		$markerArray: array of markers
	 * @param	array		$oldBrokenLink: markerarray with the number of link found
	 * @return	string		Content of the mail
	 */
	function buildMail($curPage, $pageList, $markerArray, $oldBrokenLink) {
		$pageSectionHTML = t3lib_parsehtml::getSubpart($this->templateMail, '###PAGE_SECTION###');

		if (is_array($markerArray)) {
			foreach ($markerArray as $markerKey => $markerValue) {
				if (empty($oldBrokenLink[$markerKey])) {
					$oldBrokenLink[$markerKey] = 0;
				}
				if ($markerValue != $oldBrokenLink[$markerKey]) {
					$this->dif = TRUE;
				}
				$markerArray[$markerKey . '_old'] = $oldBrokenLink[$markerKey];
			}
		}
		$markerArray['title'] = t3lib_BEfunc::getRecordTitle('pages', t3lib_BEfunc::getRecord('pages', $curPage));

		$content = '';
		if ($markerArray['brokenlinkCount'] > 0) {
			$content = t3lib_parsehtml::substituteMarkerArray($pageSectionHTML, $markerArray, '###|###', TRUE, TRUE);
		}
		return $content;
	}


	/**
	 * Calls t3lib_tsfeBeUserAuth::extGetTreeList.
	 * Although this duplicates the function t3lib_tsfeBeUserAuth::extGetTreeList
	 * this is necessary to create the object that is used recursively by the original function.
	 *
	 * Generates a list of Page uids from $id. List does not include $id itself.
	 * The only pages excluded from the list are deleted pages.
	 *
	 *							  level in the tree to start collecting uid's. Zero means
	 *							  'start right away', 1 = 'next level and out'
	 *
	 * @param	integer		Start page id
	 * @param	integer		Depth to traverse down the page tree.
	 * @param	integer		$begin is an optional integer that determines at which
	 * @param	string		Perms clause
	 * @return	string		Returns the list with a comma in the end (if any pages selected!)
	 */
	function extGetTreeList($id, $depth, $begin = 0, $perms_clause) {
		return t3lib_tsfeBeUserAuth::extGetTreeList($id, $depth, $begin, $perms_clause);
	}


	/**
	 * Simulate cli call with setting the required options to the $_SERVER['argv']
	 *
	 * @return	void
	 * @access protected
	 */
	protected function setCliArguments() {
		$_SERVER['argv'] = array(
			$_SERVER['argv'][0],
			'tx_link_scheduler_link',
			'0',
			'-ss',
			'--sleepTime',
			$this->sleepTime,
			'--sleepAfterFinish',
			$this->sleepAfterFinish,
			'--countInARun',
			$this->countInARun
		);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/linkvalidator/classes/tasks/class.tx_linkvalidator_tasks_validate.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/linkvalidator/classes/tasks/class.tx_linkvalidator_tasks_validate.php']);
}
?>