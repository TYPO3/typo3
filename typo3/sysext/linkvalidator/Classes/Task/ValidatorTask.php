<?php
namespace TYPO3\CMS\Linkvalidator\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 - 2013 Michael Miousse (michael.miousse@infoglobe.ca)
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
 * This class provides Scheduler plugin implementation
 *
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 */
class ValidatorTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

	/**
	 * @var integer
	 */
	protected $sleepTime;

	/**
	 * @var integer
	 */
	protected $sleepAfterFinish;

	/**
	 * @var integer
	 */
	protected $countInARun;

	/**
	 * Total number of broken links
	 *
	 * @var integer
	 */
	protected $totalBrokenLink = 0;

	/**
	 * Total number of broken links from the last run
	 *
	 * @var integer
	 */
	protected $oldTotalBrokenLink = 0;

	/**
	 * Mail template fetched from the given template file
	 *
	 * @var string
	 */
	protected $templateMail;

	/**
	 * specific TSconfig for this task.
	 *
	 * @var array
	 */
	protected $configuration = array();

	/**
	 * Shows if number of result was different from the result of the last check
	 *
	 * @var boolean
	 */
	protected $isDifferentToLastRun;

	/**
	 * Template to be used for the email
	 *
	 * @var string
	 */
	protected $emailTemplateFile;

	/**
	 * Level of pages the task should check
	 *
	 * @var integer
	 */
	protected $depth;

	/**
	 * UID of the start page for this task
	 *
	 * @var integer
	 */
	protected $page;

	/**
	 * Email address to which an email report is sent
	 *
	 * @var string
	 */
	protected $email;

	/**
	 * Only send an email, if new broken links were found
	 *
	 * @var boolean
	 */
	protected $emailOnBrokenLinkOnly;

	/**
	 * Get the value of the protected property email
	 *
	 * @return string Email address to which an email report is sent
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * Set the value of the private property email.
	 *
	 * @param string $email Email address to which an email report is sent
	 * @return void
	 */
	public function setEmail($email) {
		$this->email = $email;
	}

	/**
	 * Get the value of the protected property emailOnBrokenLinkOnly
	 *
	 * @return boolean Whether to send an email, if new broken links were found
	 */
	public function getEmailOnBrokenLinkOnly() {
		return $this->emailOnBrokenLinkOnly;
	}

	/**
	 * Set the value of the private property emailOnBrokenLinkOnly
	 *
	 * @param boolean $emailOnBrokenLinkOnly Only send an email, if new broken links were found
	 * @return void
	 */
	public function setEmailOnBrokenLinkOnly($emailOnBrokenLinkOnly) {
		$this->emailOnBrokenLinkOnly = $emailOnBrokenLinkOnly;
	}

	/**
	 * Get the value of the protected property page
	 *
	 * @return integer UID of the start page for this task
	 */
	public function getPage() {
		return $this->page;
	}

	/**
	 * Set the value of the private property page
	 *
	 * @param integer $page UID of the start page for this task.
	 * @return void
	 */
	public function setPage($page) {
		$this->page = $page;
	}

	/**
	 * Get the value of the protected property depth
	 *
	 * @return integer Level of pages the task should check
	 */
	public function getDepth() {
		return $this->depth;
	}

	/**
	 * Set the value of the private property depth
	 *
	 * @param integer $depth Level of pages the task should check
	 * @return void
	 */
	public function setDepth($depth) {
		$this->depth = $depth;
	}

	/**
	 * Get the value of the protected property emailTemplateFile
	 *
	 * @return string Template to be used for the email
	 */
	public function getEmailTemplateFile() {
		return $this->emailTemplateFile;
	}

	/**
	 * Set the value of the private property emailTemplateFile
	 *
	 * @param string $emailTemplateFile Template to be used for the email
	 * @return void
	 */
	public function setEmailTemplateFile($emailTemplateFile) {
		$this->emailTemplateFile = $emailTemplateFile;
	}

	/**
	 * Get the value of the protected property configuration
	 *
	 * @return array specific TSconfig for this task
	 */
	public function getConfiguration() {
		return $this->configuration;
	}

	/**
	 * Set the value of the private property configuration
	 *
	 * @param array $configuration specific TSconfig for this task
	 * @return void
	 */
	public function setConfiguration($configuration) {
		$this->configuration = $configuration;
	}

	/**
	 * Function execute from the Scheduler
	 *
	 * @return boolean TRUE on successful execution, FALSE on error
	 * @throws \InvalidArgumentException if the email template file can not be read
	 */
	public function execute() {
		$this->setCliArguments();
		$successfullyExecuted = TRUE;
		if (!file_exists(($file = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->emailTemplateFile)))
			&& !empty($this->email)
		) {
			if ($this->emailTemplateFile === 'EXT:linkvalidator/res/mailtemplate.html') {
				// Update the default email template file path
				$this->emailTemplateFile = 'EXT:linkvalidator/Resources/Private/Templates/mailtemplate.html';
				$this->save();
			} else {
				throw new \InvalidArgumentException(
					$GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.error.invalidEmailTemplateFile'),
					'1295476972'
				);
			}
		}
		$htmlFile = \TYPO3\CMS\Core\Utility\GeneralUtility::getURL($file);
		$this->templateMail = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($htmlFile, '###REPORT_TEMPLATE###');
		// The array to put the content into
		$html = array();
		$pageSections = '';
		$this->isDifferentToLastRun = FALSE;
		$pageList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->page, 1);
		$modTs = $this->loadModTsConfig($this->page);
		if (is_array($pageList)) {
			foreach ($pageList as $page) {
				$pageSections .= $this->checkPageLinks($page);
			}
		}
		if ($this->totalBrokenLink != $this->oldTotalBrokenLink) {
			$this->isDifferentToLastRun = TRUE;
		}
		if ($this->totalBrokenLink > 0 && (!$this->emailOnBrokenLinkOnly || $this->isDifferentToLastRun) && !empty($this->email)) {
			$successfullyExecuted = $this->reportEmail($pageSections, $modTs);
		}
		return $successfullyExecuted;
	}

	/**
	 * Validate all links for a page based on the task configuration
	 *
	 * @param integer $page Uid of the page to parse
	 * @return string $pageSections Content of page section
	 */
	protected function checkPageLinks($page) {
		$page = intval($page);
		$pageSections = '';
		$pageIds = '';
		$oldLinkCounts = array();
		$modTs = $this->loadModTsConfig($page);
		$searchFields = $this->getSearchField($modTs);
		$linkTypes = $this->getLinkTypes($modTs);
		/** @var $processor \TYPO3\CMS\Linkvalidator\LinkAnalyzer */
		$processor = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Linkvalidator\\LinkAnalyzer');
		if ($page === 0) {
			$rootLineHidden = FALSE;
		} else {
			$pageRow = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'pages', 'uid=' . $page);
			$rootLineHidden = $processor->getRootLineIsHidden($pageRow);
		}
		if (!$rootLineHidden || $modTs['checkhidden'] == 1) {
			$pageIds = $processor->extGetTreeList($page, $this->depth, 0, '1=1', $modTs['checkhidden']);
			if (isset($pageRow) && $pageRow['hidden'] == 0 || $modTs['checkhidden'] == 1) {
				// \TYPO3\CMS\Linkvalidator\LinkAnalyzer->extGetTreeList() always adds trailing comma
				$pageIds .= $page;
			}
		}
		if (!empty($pageIds)) {
			$processor->init($searchFields, $pageIds);
			if (!empty($this->email)) {
				$oldLinkCounts = $processor->getLinkCounts($page);
				$this->oldTotalBrokenLink += $oldLinkCounts['brokenlinkCount'];
			}
			$processor->getLinkStatistics($linkTypes, $modTs['checkhidden']);
			if (!empty($this->email)) {
				$linkCounts = $processor->getLinkCounts($page);
				$this->totalBrokenLink += $linkCounts['brokenlinkCount'];
				$pageSections = $this->buildMail($page, $pageIds, $linkCounts, $oldLinkCounts);
			}
		}
		return $pageSections;
	}

	/**
	 * Get the linkvalidator modTSconfig for a page
	 *
	 * @param integer $page Uid of the page
	 * @return array $modTsConfig mod.linkvalidator TSconfig array
	 * @throws \Exception
	 */
	protected function loadModTsConfig($page) {
		$modTs = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig($page, 'mod.linkvalidator');
		$parseObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
		$parseObj->parse($this->configuration);
		if (count($parseObj->errors) > 0) {
			$parseErrorMessage = $GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.error.invalidTSconfig') . '<br />';
			foreach ($parseObj->errors as $errorInfo) {
				$parseErrorMessage .= $errorInfo[0] . '<br />';
			}
			throw new \Exception($parseErrorMessage, '1295476989');
		}
		$tsConfig = $parseObj->setup;
		$modTs = $modTs['properties'];
		$overrideTs = $tsConfig['mod.']['tx_linkvalidator.'];
		if (is_array($overrideTs)) {
			$modTs = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($modTs, $overrideTs);
		}
		return $modTs;
	}

	/**
	 * Get the list of fields to parse in modTSconfig
	 *
	 * @param array $modTS mod.linkvalidator TSconfig array
	 * @return array $searchFields List of fields
	 */
	protected function getSearchField(array $modTS) {
		// Get the searchFields from TypoScript
		foreach ($modTS['searchFields.'] as $table => $fieldList) {
			$fields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $fieldList);
			foreach ($fields as $field) {
				$searchFields[$table][] = $field;
			}
		}
		return isset($searchFields) ? $searchFields : array();
	}

	/**
	 * Get the list of linkTypes to parse in modTSconfig
	 *
	 * @param array $modTS mod.linkvalidator TSconfig array
	 * @return array $linkTypes list of link types
	 */
	protected function getLinkTypes(array $modTS) {
		$linkTypes = array();
		$typesTmp = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $modTS['linktypes'], 1);
		if (is_array($typesTmp)) {
			if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']) && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $type => $value) {
					if (in_array($type, $typesTmp)) {
						$linkTypes[$type] = 1;
					}
				}
			}
		}
		return $linkTypes;
	}

	/**
	 * Build and send warning email when new broken links were found
	 *
	 * @param string $pageSections Content of page section
	 * @param array $modTsConfig TSconfig array
	 * @return boolean TRUE if mail was sent, FALSE if or not
	 * @throws \Exception if required modTsConfig settings are missing
	 */
	protected function reportEmail($pageSections, array $modTsConfig) {
		$content = \TYPO3\CMS\Core\Html\HtmlParser::substituteSubpart($this->templateMail, '###PAGE_SECTION###', $pageSections);
		/** @var array $markerArray */
		$markerArray = array();
		/** @var array $validEmailList */
		$validEmailList = array();
		/** @var boolean $sendEmail */
		$sendEmail = TRUE;
		$markerArray['totalBrokenLink'] = $this->totalBrokenLink;
		$markerArray['totalBrokenLink_old'] = $this->oldTotalBrokenLink;
		// Hook
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['reportEmailMarkers'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['reportEmailMarkers'] as $userFunc) {
				$params = array(
					'pObj' => &$this,
					'markerArray' => $markerArray
				);
				$newMarkers = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($userFunc, $params, $this);
				if (is_array($newMarkers)) {
					$markerArray = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge($markerArray, $newMarkers);
				}
				unset($params);
			}
		}
		$content = \TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray($content, $markerArray, '###|###', TRUE, TRUE);
		/** @var $mail \TYPO3\CMS\Core\Mail\MailMessage */
		$mail = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MailMessage');
		if (empty($modTsConfig['mail.']['fromemail'])) {
			$modTsConfig['mail.']['fromemail'] = \TYPO3\CMS\Core\Utility\MailUtility::getSystemFromAddress();
		}
		if (empty($modTsConfig['mail.']['fromname'])) {
			$modTsConfig['mail.']['fromname'] = \TYPO3\CMS\Core\Utility\MailUtility::getSystemFromName();
		}
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($modTsConfig['mail.']['fromemail'])) {
			$mail->setFrom(array($modTsConfig['mail.']['fromemail'] => $modTsConfig['mail.']['fromname']));
		} else {
			throw new \Exception($GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.error.invalidFromEmail'), '1295476760');
		}
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($modTsConfig['mail.']['replytoemail'])) {
			$mail->setReplyTo(array($modTsConfig['mail.']['replytoemail'] => $modTsConfig['mail.']['replytoname']));
		}
		if (!empty($modTsConfig['mail.']['subject'])) {
			$mail->setSubject($modTsConfig['mail.']['subject']);
		} else {
			throw new \Exception($GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.error.noSubject'), '1295476808');
		}
		if (!empty($this->email)) {
			$emailList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->email);
			foreach ($emailList as $emailAdd) {
				if (!\TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($emailAdd)) {
					throw new \Exception($GLOBALS['LANG']->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.error.invalidToEmail'), '1295476821');
				} else {
					$validEmailList[] = $emailAdd;
				}
			}
		}
		if (is_array($validEmailList) && !empty($validEmailList)) {
			$mail->setTo($this->email);
		} else {
			$sendEmail = FALSE;
		}
		if ($sendEmail) {
			$mail->setBody($content, 'text/html');
			$mail->send();
		}
		return $sendEmail;
	}

	/**
	 * Build the mail content
	 *
	 * @param integer $curPage Id of the current page
	 * @param string $pageList List of pages id
	 * @param array $markerArray Array of markers
	 * @param array $oldBrokenLink Marker array with the number of link found
	 * @return string Content of the mail
	 */
	protected function buildMail($curPage, $pageList, array $markerArray, array $oldBrokenLink) {
		$pageSectionHtml = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($this->templateMail, '###PAGE_SECTION###');
		// Hook
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['buildMailMarkers'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['buildMailMarkers'] as $userFunc) {
				$params = array(
					'curPage' => $curPage,
					'pageList' => $pageList,
					'markerArray' => $markerArray,
					'oldBrokenLink' => $oldBrokenLink,
					'pObj' => &$this
				);
				$newMarkers = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($userFunc, $params, $this);
				if (is_array($newMarkers)) {
					$markerArray = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge($markerArray, $newMarkers);
				}
				unset($params);
			}
		}
		if (is_array($markerArray)) {
			foreach ($markerArray as $markerKey => $markerValue) {
				if (empty($oldBrokenLink[$markerKey])) {
					$oldBrokenLink[$markerKey] = 0;
				}
				if ($markerValue != $oldBrokenLink[$markerKey]) {
					$this->isDifferentToLastRun = TRUE;
				}
				$markerArray[$markerKey . '_old'] = $oldBrokenLink[$markerKey];
			}
		}
		$markerArray['title'] = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle(
			'pages',
			\TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $curPage)
		);
		$content = '';
		if ($markerArray['brokenlinkCount'] > 0) {
			$content = \TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray($pageSectionHtml, $markerArray, '###|###', TRUE, TRUE);
		}
		return $content;
	}

	/**
	 * Simulate cli call with setting the required options to the $_SERVER['argv']
	 *
	 * @return void
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
?>