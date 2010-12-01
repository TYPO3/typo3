<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Xavier Perseguers <typo3@perseguers.ch>
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
 * Fake ADOdb connection factory.
 *
 * $Id: FakeDbConnection.php 40716 2010-12-01 10:49:27Z xperseguers $
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 *
 * @package TYPO3
 * @subpackage dbal
 */
class FakeDbConnection {

	/**
	 * Creates a fake database connection.
	 *
	 * @param ux_t3lib_db $db
	 * @param string $databaseType Type of the database (e.g., 'oracle')
	 * @param string $driver Driver to use (e.g., 'oci8')
	 * @return ADOConnection
	 */
	public static function connect(ux_t3lib_db $db, $driver) {
		// Make sure to have a clean configuration
		$db->clearCachedFieldInfo();
		$db->_call('initInternalVariables');

		include_once(t3lib_extMgm::extPath('adodb') . 'adodb/drivers/adodb-' . $driver . '.inc.php');

		$handlerKey = '_DEFAULT';
		$db->lastHandlerKey = $handlerKey;
		$db->handlerInstance[$handlerKey] = t3lib_div::makeInstance('ADODB_' . $driver);

		// From method handler_init()
		$db->handlerInstance[$handlerKey]->DataDictionary = NewDataDictionary($db->handlerInstance[$handlerKey]);

		// DataDictionary being set, a connectionID may be arbitrarily chosen
		$db->handlerInstance[$handlerKey]->_connectionID = rand(1, 1000);
	}

}

?>