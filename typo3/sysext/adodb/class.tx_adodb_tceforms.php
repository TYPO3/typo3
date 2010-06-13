<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2010 Robert Lemke (robert@typo3.org)
*  (c) 2006-2010 Karsten Dambekalns (karsten@typo3.org)
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
 * @author	Robert Lemke <robert@typo3.org>
 * @author	Karsten Dambekalns <karsten@typo3.org>
 */
class tx_adodb_tceforms {

	function itemsProcFunc_dbtype(&$params, $pObj) {
		if (is_callable('sybase_get_last_message')) $params['items'][] = array ('Sybase', 'sybase');
		if (is_callable('odbc_error')) $params['items'][] = array ('ODBC', 'odbc');
		if (is_callable('mysql_error')) $params['items'][] = array ('MySQL', 'mysql');
		if (is_callable('mssql_connect')) $params['items'][] = array ('MSSQL', 'mssql');
		if (is_callable('ocilogon')) $params['items'][] = array ('Oracle', 'oci8');
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/adodb/class.tx_adodb_tceforms.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/adodb/class.tx_adodb_tceforms.php']);
}

?>