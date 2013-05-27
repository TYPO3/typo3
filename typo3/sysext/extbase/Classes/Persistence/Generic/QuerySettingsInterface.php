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
 * A query settings interface. This interface is NOT part of the FLOW3 API.
 */
interface QuerySettingsInterface {

	/**
	 * Sets the flag if the storage page should be respected for the query.
	 *
	 * @param boolean $respectStoragePage If TRUE the storage page ID will be determined and the statement will be extended accordingly.
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface instance of $this to allow method chaining
	 * @api
	 */
	public function setRespectStoragePage($respectStoragePage);

	/**
	 * Returns the state, if the storage page should be respected for the query.
	 *
	 * @return boolean TRUE, if the storage page should be respected; otherwise FALSE.
	 */
	public function getRespectStoragePage();

	/**
	 * Sets the pid(s) of the storage page(s) that should be respected for the query.
	 *
	 * @param array $storagePageIds If TRUE the storage page ID will be determined and the statement will be extended accordingly.
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface instance of $this to allow method chaining
	 * @api
	 */
	public function setStoragePageIds(array $storagePageIds);

	/**
	 * Returns the pid(s) of the storage page(s) that should be respected for the query.
	 *
	 * @return array list of integers that each represent a storage page id
	 */
	public function getStoragePageIds();

	/**
	 * Sets the flag if a  and language overlay should be performed.
	 *
	 * @param boolean $respectSysLanguage TRUE if a  and language overlay should be performed.
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface instance of $this to allow method chaining
	 * @api
	 */
	public function setRespectSysLanguage($respectSysLanguage);

	/**
	 * Returns the state, if a  and language overlay should be performed.
	 *
	 * @return boolean TRUE, if a  and language overlay should be performed; otherwise FALSE.
	 */
	public function getRespectSysLanguage();

	/**
	 * Sets the language uid for the language overlay.
	 *
	 * @param integer $sysLanguageUid language uid for the language overlay
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface instance of $this to allow method chaining
	 * @api
	 */
	public function setSysLanguageUid($sysLanguageUid);

	/**
	 * Returns the language uid for the language overlay
	 *
	 * @return integer language uid for the language overlay
	 */
	public function getSysLanguageUid();

	/**
	 * Sets a flag indicating whether all or some enable fields should be ignored. If TRUE, all enable fields are ignored.
	 * If--in addition to this--enableFieldsToBeIgnored is set, only fields specified there are ignored. If FALSE, all
	 * enable fields are taken into account, regardless of the enableFieldsToBeIgnored setting.
	 *
	 * @param boolean $ignoreEnableFields
	 * @see setEnableFieldsToBeIgnored()
	 * @api
	 */
	public function setIgnoreEnableFields($ignoreEnableFields);

	/**
	 * The returned value indicates whether all or some enable fields should be ignored.
	 *
	 * If TRUE, all enable fields are ignored. If--in addition to this--enableFieldsToBeIgnored is set, only fields specified there are ignored.
	 * If FALSE, all enable fields are taken into account, regardless of the enableFieldsToBeIgnored setting.
	 *
	 * @return boolean
	 * @see getEnableFieldsToBeIgnored()
	 */
	public function getIgnoreEnableFields();

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
	public function setEnableFieldsToBeIgnored($enableFieldsToBeIgnored);

	/**
	 * An array of column names in the enable columns array (array keys in $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']),
	 * to be ignored while building the query statement.
	 *
	 * @return array
	 * @see getIgnoreEnableFields()
	 */
	public function getEnableFieldsToBeIgnored();

	/**
	 * Sets the flag if the query should return objects that are deleted.
	 *
	 * @param boolean $includeDeleted
	 * @return void
	 * @api
	 */
	public function setIncludeDeleted($includeDeleted);

	/**
	 * Returns if the query should return objects that are deleted.
	 *
	 * @return boolean
	 */
	public function getIncludeDeleted();

	/**
	 * Sets the state, if the QueryResult should be returned unmapped.
	 *
	 * @param boolean $returnRawQueryResult TRUE, if the QueryResult should be returned unmapped; otherwise FALSE.
	 * @return void
	 */
	public function setReturnRawQueryResult($returnRawQueryResult);

	/**
	 * Returns the state, if the QueryResult should be returned unmapped.
	 *
	 * @return boolean TRUE, if the QueryResult should be returned unmapped; otherwise FALSE.
	 */
	public function getReturnRawQueryResult();
}

?>