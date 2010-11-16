<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * A query settings interface. This interface is NOT part of the FLOW3 API.
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: QuerySettingsInterface.php 1729 2009-11-25 21:37:20Z stucki $
 */
interface Tx_Extbase_Persistence_QuerySettingsInterface {

	/**
	 * Sets the flag if the storage page should be respected for the query.
	 *
	 * @param boolean $respectStoragePage If TRUE the storage page ID will be determined and the statement will be extended accordingly.
	 * @return Tx_Extbase_Persistence_QuerySettingsInterface instance of $this to allow method chaining
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
	 * @param array $respectStoragePage If TRUE the storage page ID will be determined and the statement will be extended accordingly.
	 * @return Tx_Extbase_Persistence_QuerySettingsInterface instance of $this to allow method chaining
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
	 * @param boolean $respectEnableFields TRUE if a  and language overlay should be performed.
	 * @return Tx_Extbase_Persistence_QuerySettingsInterface instance of $this to allow method chaining
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
	 * Sets the flag if the visibility in the frontend should be respected.
	 *
	 * @param boolean $respectEnableFields TRUE if the visibility in the frontend should be respected. If TRUE, the "enable fields" of TYPO3 will be added to the query statement.
	 * @return Tx_Extbase_Persistence_QuerySettingsInterface instance of $this to allow method chaining
	 * @api
	 */
	public function setRespectEnableFields($respectEnableFields);

	/**
	 * Returns the state, if the visibility settings for the frontend should be respected for the query.
	 *
	 * @return boolean TRUE, if the visibility settings for the frontend should should be respected; otherwise FALSE.
	 */
	public function getRespectEnableFields();

	/**
	 * Sets the state, if the QueryResult should be returned unmapped.
	 *
	 * @var boolean $returnRawQueryResult TRUE, if the QueryResult should be returned unmapped; otherwise FALSE.
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