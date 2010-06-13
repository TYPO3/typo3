<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2010 Kasper Skaarhoj (kasper@typo3.com)
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
 * Contains an example DBAL handler class
 *
 * $Id: class.tx_dbal_handler_openoffice.php 28898 2010-01-16 14:32:35Z xperseguers $
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   74: class tx_dbal_handler_xmldb extends tx_dbal_sqlengine
 *   91:     function init($config, &$pObj)
 *  128:     function readDataSource($table)
 *  157:     function saveDataSource($table)
 *  184:     function xmlDB_writeStructure()
 *  193:     function xmlDB_readStructure()
 *
 *              SECTION: SQL admin functions
 *  217:     function admin_get_tables()
 *  242:     function admin_get_fields($tableName)
 *  276:     function admin_get_keys($tableName)
 *  314:     function admin_query($query)
 *
 * TOTAL FUNCTIONS: 9
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */










/**
 * Example DBAL handler class
 * Stores data in an Open Office Calc Spreadsheet
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_dbal
 */
class tx_dbal_handler_openoffice extends tx_dbal_sqlengine {

	var $config = array();
	var $pObj;	// Set from DBAL class.

	var $spreadSheetFiles = '';
	var $unzip;		// Object

	/**
	 * Initialize handler
	 *
	 * @param	array		Configuration from DBAL
	 * @param	object		Parent object
	 * @return	boolean		True on success.
	 */
	function init($config, $pObj) {
		$this->config = $config['config'];

		if (t3lib_extMgm::isLoaded('libunzipped'))	{

				// Include Unzip library:
			require_once(t3lib_extMgm::extPath('libunzipped').'class.tx_libunzipped.php');

				// Find database file:
			$sxc_file = t3lib_div::getFileAbsFileName($this->config['sxc_file']);
			if (@is_file($sxc_file))	{

				// Initialize Unzip object:
				$this->unzip = t3lib_div::makeInstance('tx_libunzipped');
				$this->spreadSheetFiles = $this->unzip->init($sxc_file);

				if (is_array($this->spreadSheetFiles))	{
					return TRUE;
				} else $this->errorStatus = 'Spreadsheet could not be unzipped...?';
			} else $this->errorStatus = 'The Spreadsheet file "'.$sxc_file.'" was not found!';
		} else $this->errorStatus = 'This data handler needs the extension "tx_libunzipped" to be installed!';

		return FALSE;
	}

	/**
	 * Setting table data (overriding function)
	 *
	 * @param	string		Table name
	 * @return	void
	 */
	function readDataSource($table)	{
		if (!is_array($this->spreadSheetFiles))	{
			die('Spreadsheet Data Source FATAL ERROR: No spreadsheet file loaded. Init() must have failed!');
		}

		$this->data[$table] = array();

				// Read content.xml:
		$content_xml = $this->unzip->getFileFromArchive('content.xml');

			// Testing for writing back:
		$content_xml = str_replace('Felt A1','FELT A1',$content_xml);

			// Writing file back (to database)
		$this->unzip->putFileToArchive('content.xml', $content_xml['content']);

			// Writing ZIP content back to zip-archive file:
		$result = $this->unzip->compileZipFile($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] . 'dbtest_output.sxc');

		debug($result);

		exit;
	}

	/**
	 * Saving data source
	 *
	 * @param	string		Table name
	 * @return	boolean		True on success
	 */
	function saveDataSource($table)	{
	}









	/**************************************
	 *
	 * SQL admin functions
	 * (For use in the Install Tool and Extension Manager)
	 *
	 **************************************/

	/**
	 * Returns the list of tables from the database
	 *
	 * @return	array		Tables in an array with tablename as key and an array with status information as value
	 */
	function admin_get_tables()	{

		$whichTables = array();
		return $whichTables;
	}

	/**
	 * Returns information about each field in the $table
	 *
	 * @param	string		Table name
	 * @return	array		Field information in an associative array with fieldname => field row
	 */
	function admin_get_fields($tableName)	{
		return array();
	}

	/**
	 * Returns information about each index key in the $table
	 *
	 * @param	string		Table name
	 * @return	array		Key information in a numeric array
	 */
	function admin_get_keys($tableName)	{
		return array();
	}

	/**
	 * mysql() wrapper function, used by the Install Tool and EM for all queries regarding management of the database!
	 *
	 * @param	string		Query to execute
	 * @return	pointer		Result pointer
	 */
	function admin_query($query)	{

		$parsedQuery = $this->parseSQL($query);
		$table = $parsedQuery['TABLE'];

		if (is_array($parsedQuery))	{
				// Process query based on type:
			switch($parsedQuery['type'])	{
				case 'CREATETABLE':
				break;
				case 'ALTERTABLE':
				break;
				case 'DROPTABLE':
				break;
				default:
					$this->errorStatus = 'Query type "'.$parsedQuery['type'].'" was not supported!';
				break;
			}

		} else $this->errorStatus = 'SQL parse error: '.$parsedQuery;

		return FALSE;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/handlers/class.tx_dbal_handler_openoffice.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dbal/handlers/class.tx_dbal_handler_openoffice.php']);
}

?>
