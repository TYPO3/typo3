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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Query settings. This class is NOT part of the FLOW3 API.
 * It reflects the settings unique to TYPO3 CMS.
 *
 * @api
 */
class Typo3QuerySettings implements QuerySettingsInterface {

	/**
	 * Flag if the storage page should be respected for the query.
	 *
	 * @var bool
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
	 * @var bool
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
	 * @var bool
	 */
	protected $includeDeleted = FALSE;

	/**
	 * Flag if the sys_language_uid should be respected (default is TRUE).
	 *
	 * @var bool
	 */
	protected $respectSysLanguage = TRUE;

	/**
	 * Representing sys_language_overlay only valid for current context
	 *
	 * @var mixed
	 */
	protected $languageOverlayMode = TRUE;

	/**
	 * Representing sys_language_mode only valid for current context
	 *
	 * @var string
	 */
	protected $languageMode = NULL;

	/**
	 * Represensting sys_language_uid only valid for current context
	 *
	 * @var int
	 */
	protected $languageUid = 0;

	/**
	 * Flag if the the query result should be returned as raw QueryResult.
	 *
	 * @var bool
	 * @deprecated since Extbase 6.2, will be removed two versions later
	 */
	protected $returnRawQueryResult = FALSE;

	/**
	 * Flag whether the query should use a prepared statement
	 *
	 * @var bool
	 */
	protected $usePreparedStatement = FALSE;

	/**
	 * Flag whether the query should be cached using the caching framework
	 *
	 * @var bool
	 */
	protected $useQueryCache = TRUE;

	/**
	 * As long as we use a feature flag ignoreAllEnableFieldsInBe to determine the default behavior, the
	 * initializeObject is responsible for handling that.
	 */
	public function initializeObject() {
		/** @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
		$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		/** @var $configurationManager \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface */
		$configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
		if (TYPO3_MODE === 'BE' && $configurationManager->isFeatureEnabled('ignoreAllEnableFieldsInBe')) {
			$this->setIgnoreEnableFields(TRUE);
		}

		// TYPO3 CMS language defaults
		$this->setLanguageUid(0);
		$this->setLanguageMode(NULL);
		$this->setLanguageOverlayMode(FALSE);

		// Set correct language uid for frontend handling
		if (isset($GLOBALS['TSFE']) && is_object($GLOBALS['TSFE'])) {
			$this->setLanguageUid((int)$GLOBALS['TSFE']->sys_language_content);
			$this->setLanguageOverlayMode($GLOBALS['TSFE']->sys_language_contentOL ?: FALSE);
			$this->setLanguageMode($GLOBALS['TSFE']->sys_language_mode ?: NULL);
		} elseif ((int)GeneralUtility::_GP('L')) {
			// Set language from 'L' parameter
			$this->setLanguageUid((int)GeneralUtility::_GP('L'));
		}
	}

	/**
	 * Sets the flag if the storage page should be respected for the query.
	 *
	 * @param bool $respectStoragePage If TRUE the storage page ID will be determined and the statement will be extended accordingly.
	 * @return QuerySettingsInterface
	 * @api
	 */
	public function setRespectStoragePage($respectStoragePage) {
		$this->respectStoragePage = $respectStoragePage;
		return $this;
	}

	/**
	 * Returns the state, if the storage page should be respected for the query.
	 *
	 * @return bool TRUE, if the storage page should be respected; otherwise FALSE.
	 */
	public function getRespectStoragePage() {
		return $this->respectStoragePage;
	}

	/**
	 * Sets the pid(s) of the storage page(s) that should be respected for the query.
	 *
	 * @param array $storagePageIds If given the storage page IDs will be determined and the statement will be extended accordingly.
	 * @return QuerySettingsInterface
	 * @api
	 */
	public function setStoragePageIds(array $storagePageIds) {
		$this->storagePageIds = $storagePageIds;
		return $this;
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
	 * @param bool $respectSysLanguage TRUE if TYPO3 language settings are to be applied
	 * @return QuerySettingsInterface
	 * @api
	 */
	public function setRespectSysLanguage($respectSysLanguage) {
		$this->respectSysLanguage = $respectSysLanguage;
		return $this;
	}

	/**
	 * @return bool TRUE if TYPO3 language settings are to be applied
	 */
	public function getRespectSysLanguage() {
		return $this->respectSysLanguage;
	}

	/**
	 * @param mixed $languageOverlayMode TRUE, FALSE or "hideNonTranslated"
	 * @return QuerySettingsInterface instance of $this to allow method chaining
	 * @api
	 */
	public function setLanguageOverlayMode($languageOverlayMode = FALSE) {
		$this->languageOverlayMode = $languageOverlayMode;
		return $this;
	}

	/**
	 * @return mixed TRUE, FALSE or "hideNonTranslated"
	 */
	public function getLanguageOverlayMode() {
		return $this->languageOverlayMode;
	}

	/**
	 * @param string $languageMode NULL, "content_fallback", "strict" or "ignore"
	 * @return QuerySettingsInterface instance of $this to allow method chaining
	 * @api
	 */
	public function setLanguageMode($languageMode = '') {
		$this->languageMode = $languageMode;
		return $this;
	}

	/**
	 * @return string NULL, "content_fallback", "strict" or "ignore"
	 */
	public function getLanguageMode() {
		return $this->languageMode;
	}

	/**
	 * @param int $languageUid
	 * @return QuerySettingsInterface instance of $this to allow method chaining
	 * @api
	 */
	public function setLanguageUid($languageUid) {
		$this->languageUid = $languageUid;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getLanguageUid() {
		return $this->languageUid;
	}

	/**
	 * Sets the language uid for the language overlay.
	 *
	 * @param int $sysLanguageUid language uid for the language overlay
	 * @return QuerySettingsInterface instance of $this to allow method chaining
	 * @deprecated since Extbase 6.2, will be removed two versions later. Use setLanguageUid() instead.
	 */
	public function setSysLanguageUid($sysLanguageUid) {
		GeneralUtility::logDeprecatedFunction();
		return $this->setLanguageUid($sysLanguageUid);
	}

	/**
	 * Returns the language uid for the language overlay
	 *
	 * @return int language uid for the language overlay
	 * @deprecated since Extbase 6.2, will be removed two versions later. Use getLanguageUid() instead.
	 */
	public function getSysLanguageUid() {
		GeneralUtility::logDeprecatedFunction();
		return $this->getLanguageUid();
	}

	/**
	 * Sets a flag indicating whether all or some enable fields should be ignored. If TRUE, all enable fields are ignored.
	 * If--in addition to this--enableFieldsToBeIgnored is set, only fields specified there are ignored. If FALSE, all
	 * enable fields are taken into account, regardless of the enableFieldsToBeIgnored setting.
	 *
	 * @param bool $ignoreEnableFields
	 * @return QuerySettingsInterface
	 * @see setEnableFieldsToBeIgnored()
	 * @api
	 */
	public function setIgnoreEnableFields($ignoreEnableFields) {
		$this->ignoreEnableFields = $ignoreEnableFields;
		return $this;
	}

	/**
	 * The returned value indicates whether all or some enable fields should be ignored.
	 *
	 * If TRUE, all enable fields are ignored. If--in addition to this--enableFieldsToBeIgnored is set, only fields specified there are ignored.
	 * If FALSE, all enable fields are taken into account, regardless of the enableFieldsToBeIgnored setting.
	 *
	 * @return bool
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
	 * @return QuerySettingsInterface
	 * @see setIgnoreEnableFields()
	 * @api
	 */
	public function setEnableFieldsToBeIgnored($enableFieldsToBeIgnored) {
		$this->enableFieldsToBeIgnored = $enableFieldsToBeIgnored;
		return $this;
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
	 * @param bool $includeDeleted
	 * @return QuerySettingsInterface
	 * @api
	 */
	public function setIncludeDeleted($includeDeleted) {
		$this->includeDeleted = $includeDeleted;
		return $this;
	}

	/**
	 * Returns if the query should return objects that are deleted.
	 *
	 * @return bool
	 */
	public function getIncludeDeleted() {
		return $this->includeDeleted;
	}

	/**
	 * Sets the state, if the QueryResult should be returned unmapped.
	 *
	 * @param bool $returnRawQueryResult TRUE, if the QueryResult should be returned unmapped; otherwise FALSE.
	 * @return QuerySettingsInterface
	 * @deprecated since Extbase 6.2, will be removed two versions later. Please use argument in query->execute() instead.
	 */
	public function setReturnRawQueryResult($returnRawQueryResult) {
		GeneralUtility::logDeprecatedFunction();
		$this->returnRawQueryResult = $returnRawQueryResult;
		return $this;
	}

	/**
	 * Returns the state, if the QueryResult should be returned unmapped.
	 *
	 * @return bool TRUE, if the QueryResult should be returned unmapped; otherwise FALSE.
	 * @deprecated since Extbase 6.2, will be removed two versions later. Please use argument in query->execute() instead.
	 */
	public function getReturnRawQueryResult() {
		// We do not log this call intentionally, otherwise the deprecation log would be filled up
		return $this->returnRawQueryResult;
	}

	/**
	 * @param bool $usePreparedStatement
	 * @return QuerySettingsInterface
	 */
	public function usePreparedStatement($usePreparedStatement) {
		$this->usePreparedStatement = $usePreparedStatement;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getUsePreparedStatement() {
		return (bool)$this->usePreparedStatement;
	}

	/**
	 * @param bool $useQueryCache
	 * @return QuerySettingsInterface
	 */
	public function useQueryCache($useQueryCache) {
		$this->useQueryCache = $useQueryCache;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getUseQueryCache() {
		return (bool)$this->useQueryCache;
	}
}
