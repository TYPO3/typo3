<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 * Query settings. This class is NOT part of the FLOW3 API.
 * It reflects the settings unique to TYPO3 4.x.
 *
 * @api
 */
class Typo3QuerySettings implements \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface {

	/**
	 * Flag if the storage page should be respected for the query.
	 *
	 * @var boolean
	 */
	protected $respectStoragePage = TRUE;

	/**
	 * the pid(s) of the storage page(s) that should be respected for the query.
	 *
	 * @var array
	 */
	protected $storagePageIds = array();

	/**
	 * A flag indicating whether all or some enable fields should be ignored. If TRUE, all enable fields are ignored.
	 * If--in addition to this--enableFieldsToBeIgnored is set, only fields specified there are ignored. If FALSE, all
	 * enable fields are taken into account, regardless of the enableFieldsToBeIgnored setting.
	 *
	 * @var boolean
	 */
	protected $ignoreEnableFields = FALSE;

	/**
	 * An array of column names in the enable columns array (array keys in $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']),
	 * to be ignored while building the query statement
	 *
	 * @var array
	 */
	protected $enableFieldsToBeIgnored = array();

	/**
	 * Flag whether deleted records should be included in the result set.
	 *
	 * @var boolean
	 */
	protected $includeDeleted = FALSE;

	/**
	 * Flag if the sys_language_uid should be respected (default is TRUE).
	 *
	 * @var boolean
	 */
	protected $respectSysLanguage = TRUE;

	/**
	 * The language uid for the language overlay.
	 *
	 * @var integer
	 */
	protected $sysLanguageUid = 0;

	/**
	 * Flag if the the query result should be returned as raw QueryResult.
	 *
	 * @var boolean
	 */
	protected $returnRawQueryResult = FALSE;

	/**
	 * As long as we use a feature flag ignoreAllEnableFieldsInBe to determine the default behavior, the
	 * initializeObject is responsible for handling that.
	 */
	public function initializeObject() {
		/** @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		/** @var $configurationManager \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface */
		$configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
		if (TYPO3_MODE === 'BE' && $configurationManager->isFeatureEnabled('ignoreAllEnableFieldsInBe')) {
			$this->setIgnoreEnableFields(TRUE);
		}

		// Set correct language uid for frontend handling
		if (isset($GLOBALS['TSFE']) && is_object($GLOBALS['TSFE'])) {
			$this->setSysLanguageUid($GLOBALS['TSFE']->sys_language_content);
		} elseif (intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('L'))) {
			// Set language from 'L' parameter
			$this->setSysLanguageUid(intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('L')));
		}
	}

	/**
	 * Sets the flag if the storage page should be respected for the query.
	 *
	 * @param boolean $respectStoragePage If TRUE the storage page ID will be determined and the statement will be extended accordingly.
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface (fluent interface)
	 * @api
	 */
	public function setRespectStoragePage($respectStoragePage) {
		$this->respectStoragePage = $respectStoragePage;
		return $this;
	}

	/**
	 * Returns the state, if the storage page should be respected for the query.
	 *
	 * @return boolean TRUE, if the storage page should be respected; otherwise FALSE.
	 */
	public function getRespectStoragePage() {
		return $this->respectStoragePage;
	}

	/**
	 * Sets the pid(s) of the storage page(s) that should be respected for the query.
	 *
	 * @param array $storagePageIds If TRUE the storage page ID will be determined and the statement will be extended accordingly.
	 * @return void
	 * @api
	 */
	public function setStoragePageIds(array $storagePageIds) {
		$this->storagePageIds = $storagePageIds;
	}

	/**
	 * Returns the pid(s) of the storage page(s) that should be respected for the query.
	 *
	 * @return array list of integers that each represent a storage page id
	 */
	public function getStoragePageIds() {
		return $this->storagePageIds;
	}

	/**
	 * Sets the flag if a  and language overlay should be performed.
	 *
	 * @param boolean $respectSysLanguage TRUE if a  and language overlay should be performed.
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface (fluent interface)
	 * @api
	 */
	public function setRespectSysLanguage($respectSysLanguage) {
		$this->respectSysLanguage = $respectSysLanguage;
		return $this;
	}

	/**
	 * Returns the state, if a  and language overlay should be performed.
	 *
	 * @return boolean TRUE, if a  and language overlay should be performed; otherwise FALSE.
	 */
	public function getRespectSysLanguage() {
		return $this->respectSysLanguage;
	}

	/**
	 * Sets the language uid for the language overlay.
	 *
	 * @param integer $sysLanguageUid language uid for the language overlay
	 * @return void
	 * @api
	 */
	public function setSysLanguageUid($sysLanguageUid) {
		$this->sysLanguageUid = $sysLanguageUid;
	}

	/**
	 * Returns the language uid for the language overlay
	 *
	 * @return integer language uid for the language overlay
	 */
	public function getSysLanguageUid() {
		return $this->sysLanguageUid;
	}

	/**
	 * Sets the flag if the visibility in the frontend should be respected.
	 *
	 * @param boolean $respectEnableFields TRUE if the visibility in the frontend should be respected. If TRUE, the "enable fields" of TYPO3 will be added to the query statement.
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface (fluent interface)
	 * @deprecated since Extbase 6.0, will be removed in Extbase 6.2. Use setIgnoreEnableFields() and setEnableFieldsToBeIgnored() instead.
	 * @see setIgnoreEnableFields()
	 * @see setEnableFieldsToBeIgnored()
	 * @api
	 */
	public function setRespectEnableFields($respectEnableFields) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		$this->setIgnoreEnableFields(!$respectEnableFields);
		$this->setIncludeDeleted(!$respectEnableFields);
		return $this;
	}

	/**
	 * Returns the state, if the visibility settings for the frontend should be respected for the query.
	 *
	 * @return boolean TRUE, if the visibility settings for the frontend should should be respected; otherwise FALSE.
	 * @deprecated since Extbase 6.0, will be removed in Extbase 6.2. Use getIgnoreEnableFields() and getEnableFieldsToBeIgnored() instead.
	 * @see getIgnoreEnableFields()
	 * @see getEnableFieldsToBeIgnored()
	 */
	public function getRespectEnableFields() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return !($this->getIgnoreEnableFields() && $this->getIncludeDeleted());
	}

	/**
	 * Sets a flag indicating whether all or some enable fields should be ignored. If TRUE, all enable fields are ignored.
	 * If--in addition to this--enableFieldsToBeIgnored is set, only fields specified there are ignored. If FALSE, all
	 * enable fields are taken into account, regardless of the enableFieldsToBeIgnored setting.
	 *
	 * @param boolean $ignoreEnableFields
	 * @see setEnableFieldsToBeIgnored()
	 * @api
	 */
	public function setIgnoreEnableFields($ignoreEnableFields) {
		$this->ignoreEnableFields = $ignoreEnableFields;
	}

	/**
	 * The returned value indicates whether all or some enable fields should be ignored.
	 *
	 * If TRUE, all enable fields are ignored. If--in addition to this--enableFieldsToBeIgnored is set, only fields specified there are ignored.
	 * If FALSE, all enable fields are taken into account, regardless of the enableFieldsToBeIgnored setting.
	 *
	 * @return boolean
	 * @see getEnableFieldsToBeIgnored()
	 */
	public function getIgnoreEnableFields() {
		return $this->ignoreEnableFields;
	}

	/**
	 * An array of column names in the enable columns array (array keys in $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']),
	 * to be ignored while building the query statement. Adding a column name here effectively switches off filtering
	 * by this column. This setting is only taken into account if $this->ignoreEnableFields = TRUE.
	 *
	 * @param array $enableFieldsToBeIgnored
	 * @return void
	 * @see setIgnoreEnableFields()
	 * @api
	 */
	public function setEnableFieldsToBeIgnored($enableFieldsToBeIgnored) {
		$this->enableFieldsToBeIgnored = $enableFieldsToBeIgnored;
	}

	/**
	 * An array of column names in the enable columns array (array keys in $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']),
	 * to be ignored while building the query statement.
	 *
	 * @return array
	 * @see getIgnoreEnableFields()
	 */
	public function getEnableFieldsToBeIgnored() {
		return $this->enableFieldsToBeIgnored;
	}

	/**
	 * Sets the flag if the query should return objects that are deleted.
	 *
	 * @param boolean $includeDeleted
	 * @return void
	 * @api
	 */
	public function setIncludeDeleted($includeDeleted) {
		$this->includeDeleted = $includeDeleted;
	}

	/**
	 * Returns if the query should return objects that are deleted.
	 *
	 * @return boolean
	 */
	public function getIncludeDeleted() {
		return $this->includeDeleted;
	}

	/**
	 * Sets the state, if the QueryResult should be returned unmapped.
	 *
	 * @param boolean $returnRawQueryResult TRUE, if the QueryResult should be returned unmapped; otherwise FALSE.
	 * @return void
	 */
	public function setReturnRawQueryResult($returnRawQueryResult) {
		$this->returnRawQueryResult = $returnRawQueryResult;
	}

	/**
	 * Returns the state, if the QueryResult should be returned unmapped.
	 *
	 * @return boolean TRUE, if the QueryResult should be returned unmapped; otherwise FALSE.
	 */
	public function getReturnRawQueryResult() {
		return $this->returnRawQueryResult;
	}
}

?>