<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2007 Thomas Hempel (thomas@work.de)
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

require_once(PATH_t3lib.'class.t3lib_install.php');

/**
 * $Id$
 *
 * @author	Thomas Hempel	<thomas@work.de>
 * @author	Ingo Renner	<ingo@typo3.org>
 */ 
class tx_install_module_database extends tx_install_module_base	{
	/*
	 * API FUNCTIONS
	 */	
	/**
	 * This is the main method
	 */
	public function main()	{
	}
	
	/*
	 * CHECK FUNCTIONS
	 */
	
	/**
	 * Checks if the database can be connected.
	 *
	 * @param string $host
	 * @param string $username
	 * @param string $password
	 * @return boolean
	 */
	public function checkDatabaseConnect($host = TYPO3_db_host, $username = TYPO3_db_username, $password = TYPO3_db_password)	{
		if (!$GLOBALS['TYPO3_DB']->sql_pconnect($host, $username, $password))	{
			$this->addError('LLL:msg_database_error_cantconnect');
			return false;
		}
		return true;
	}
	
	public function checkSelectDatabase($host = TYPO3_db_host, $username = TYPO3_db_username, $password = TYPO3_db_password, $db = TYPO3_db)	{
		if (!$this->checkDatabaseConnect($host, $username, $password))	return false;
		if (!$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db))	{
			$this->addError(sprintf($this->get_LL('msg_database_error_cantselectdb'), TYPO3_db));
			return false;
		}
		return true;
	}

	
	/*
	 * USER FUNCTIONS 
	 */
	
	/**
	 * Returns the list of available databases (with access-check based on username/password)
	 *
	 * @return	[type]		...
	 */
	public function getDatabaseList()	{
		$dbArr  = array();
		$dbList = array();
		$localconfCache = $this->basicsObject->getLocalconfCache();
		
		if ($GLOBALS['TYPO3_DB']->sql_pconnect(
			$localconfCache['db']['typo_db_host'],
			$localconfCache['db']['typo_db_username'],
			$localconfCache['db']['typo_db_password'])
		)	{
			$dbArr = $GLOBALS['TYPO3_DB']->admin_get_dbs();
			
			foreach ($dbArr as $dbName)	{
				$dbList[$dbName] = $dbName;
			}
		}
		
		return $dbList;
	}
	
	/*
	 * INSTALLER STEP METHODS
	 */
	
	/**
	 * Returns form config for database connection data
	 *
	 * @param 	static fields for form (hidden fields)	
	 * @return 	string
	 */
	public function databaseConnectionData()	{
			// get all options for this step
		
		$elements = array(
			'advanced' => array (
				$this->pObj->getViewObject()->renderOption('typo_db_host', $GLOBALS['MCA']['database']['options']['typo_db_host'])
			),
			'normal' => array (
				$this->pObj->getViewObject('typo_db_username', $GLOBALS['MCA']['database']['options']['typo_db_username'])->renderOption(),
				$this->pObj->getViewObject('typo_db_password', $GLOBALS['MCA']['database']['options']['typo_db_password'])->renderOption()
			)
		);
		
		return $elements;
	}
	
	public function connectDatabaseProcess($staticFields)	{
			// try to connect with the given values
		$connectResult = $this->checkDatabaseConnect($this->env['typo_db_host'], $this->env['typo_db_username'], $this->env['typo_db_password']);
		if(!$connectResult) {
			return false;
		}
		
			// if connection was sucessfull, write to localconf cache aand save it to file
		$this->basicsObject->addDbDataToLocalconf(array(
			'typo_db_host'     => $this->env['typo_db_host'],
			'typo_db_username' => $this->env['typo_db_username'],
			'typo_db_password' => $this->env['typo_db_password']
		));
		
		if (!$this->basicsObject->saveLocalconf())	return false;
		
		return true;
	}
	
	/**
	 * Provides the form for selecting or creating the database
	 */
	public function createDatabase($staticFields)	{
		global $MCA;
			
			// get all options for this step
		$options = array (
			'typo_db' => $MCA['database']['options']['typo_db'],
			'typo_db_new' => $MCA['database']['options']['typo_db_new'],
		);
		
		$formConfig = array (
			'type' => 'form',
			'value' => array (
				'options' => array (
					'name' => 'form_createDatabase',
					'submit' => $this->get_LL('label_next_step'),
				),
				'hidden' => $staticFields
			)
		);
		
		foreach ($options as $optionName => $optionConfig)	{
			$elementConfig = $this->pObj->getViewObject()->renderOption($optionName, $optionConfig);
			if ($elementConfig !== false)	{
				$formConfig['value']['elements'][] = $elementConfig;
			} 
		}
		
		return $this->pObj->getViewObject()->render($formConfig);
	}
	
	
	/**
	 * Does the processing of creating the database
	 */
	public function createDatabaseProcess($staticFields)	{
			// check if a database was selected or if a new databse was filled in
		if (empty($this->env['typo_db']) && empty($this->env['typo_db_new']))	{
			$this->addError('LLL:msg_database_warning_selectdb');
			return false;
		}
		
			// set an existing database or try to create a new one. If a new name was entered, try to create new one
		if (!empty($this->env['typo_db_new']))	{
				// check name
			if (!ereg('[^[:alnum:]_-]',	$this->env['typo_db_new']))	{
					// try to create the database
				if ($this->checkDatabaseConnect())	{
					$GLOBALS['TYPO3_DB']->admin_query('CREATE DATABASE '.$this->env['typo_db_new'].' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
					$res = $GLOBALS['TYPO3_DB']->admin_query('SHOW DATABASES');
					$tables = array();
					while($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res)) {
						$tables[] = $row;
					}
					
					if(t3lib_div::inArray($tables, $this->env['typo_db_new'])) {
							// db created -> write name to localconf
						$this->basicsObject->addDbDataToLocalconf(array('typo_db' => $this->env['typo_db_new']));
						if($this->basicsObject->saveLocalconf()) {
							return true;
						}
					} else $this->addError(sprintf($this->get_LL('msg_database_error_couldnotcreate'), $this->env['typo_db_new']), WARNING, 'fields', 'typo_db_new');
				}
			} else $this->addError(sprintf($this->get_LL('msg_database_warning_invalidname'), $this->env['typo_db_new']), WARNING, 'fields', 'typo_db_new');
				// if we reach this point, something went wrong
			return false;
		} else {
			$this->basicsObject->addDbDataToLocalconf(array('typo_db' => $this->env['typo_db']));
			if (!$this->basicsObject->saveLocalconf()) {
				return false;
			}
		}
		
		return true;
	}
	
	
	/**
	 * Provides the form for initial database import.
	 */
	public function createTables($staticFields)	{
		$sFiles = t3lib_div::getFilesInDir(PATH_typo3conf,'sql',1,1);
		
			// Check if default database scheme "database.sql" already exists, otherwise create it
		if (!strstr(implode(',', $sFiles).',', '/database.sql,'))	{
			array_unshift($sFiles, $this->get_LL('label_database_defaulttables'));
		}
		
		$elements = array();
		foreach ($sFiles as $f)	{
			if ($f == $this->get_LL('label_database_defaulttables'))	{
				$key = 'CURRENT_TABLES+STATIC';
			} else {
				$key = htmlspecialchars($f);
			}

			$elements['import|'.$key] = htmlspecialchars(basename($f));
		}
		
		$formConfig = array (
			'type' => 'form',
			'value' => array (
				'options' => array (
					'name' => 'form_createTables',
					'submit' => $this->get_LL('label_next_step'),
				),
				'hidden' => $staticFields,
				'elements' => array (
					array (
						'type' => 'formelement',
						'value' => array (
							'label' => 'label_selectdump',
							'elementType' => 'selectbox',
							'options' => array (
								'name' => 'L',
								'elements' => $elements
							)
						)
					)
				)
			)
		);
		
		return $this->pObj->getViewObject()->render($formConfig);
	}
	
	/**
	 * Does the processing for initial database import
	 */
	public function createTablesProcess($staticFields)	{
		if (!$GLOBALS['TYPO3_DB']->sql_pconnect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password))	{
			$this->addError('LLL:msg_database_error_cantconnect', CRITICAL);
			return false;
		}
		
		if (!$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db))	{
			$this->addError(sprintf($this->get_LL('LLL:msg_database_error_cantselectdb'), TYPO3_db), CRITICAL);
			return false;
		}
		
		$tblFileContent = t3lib_div::getUrl(PATH_t3lib.'stddb/tables.sql');

		reset($GLOBALS['TYPO3_LOADED_EXT']);
		foreach ($GLOBALS['TYPO3_LOADED_EXT'] as $loadedExtConf)	{
			if (is_array($loadedExtConf) && $loadedExtConf['ext_tables.sql'])	{
				$tblFileContent.= chr(10).chr(10).chr(10).chr(10).t3lib_div::getUrl($loadedExtConf['ext_tables.sql']);
			}
		}
		
		$t3lib_install = t3lib_div::makeInstance('t3lib_install');
		$statements = $t3lib_install->getStatementArray($tblFileContent, 1);
		
		list($statements_table, $insertCount) = $t3lib_install->getCreateTables($statements, 1);
		
			// create tables and count them
		$createCount = 0;
		foreach ($statements as $tableName => $query)	{
			$res = $GLOBALS['TYPO3_DB']->admin_query($query);
			$createCount++;
		}

			// Make a database comparison because some tables that are defined twice have not been created at this point. This applies to the "pages.*" fields defined in sysext/cms/ext_tables.sql for example.
		$fileContent = implode(chr(10), $t3lib_install->getStatementArray($tblFileContent,1,'^CREATE TABLE '));
		$FDfile = $t3lib_install->getFieldDefinitions_sqlContent($fileContent);
		$FDdb = $t3lib_install->getFieldDefinitions_database();
		$diff = $t3lib_install->getDatabaseExtra($FDfile, $FDdb);
		$update_statements = $t3lib_install->getUpdateSuggestions($diff);
		if (is_array($update_statements['add']))	{
			foreach ($update_statements['add'] as $statement)	{
				$res = $GLOBALS['TYPO3_DB']->admin_query($statement);
			}
		}
		
		return true;
	}
	
	/**
	 * Provides a form for creating a new admin user
	 */
	public function createAdmin($staticFields)	{
		$formConfig = array (
			'type' => 'form',
			'value' => array (
				'options' => array (
					'name' => 'form_getlanguage',
					'submit' => $this->get_LL('label_next_step'),
				),
				'hidden' => $staticFields,
				'elements' => array (
					array (
						'type' => 'formelement',
						'value' => array (
							'label' => 'label_admin_username',
							'elementType' => 'input',
							'options' => array (
								'name' => 'createadmin_username',
							)
						)
					),
					array (
						'type' => 'formelement',
						'value' => array (
							'label' => 'label_admin_password',
							'elementType' => 'password',
							'renderTwice' => true,
							'options' => array (
								'name' => 'createadmin_password'
							)
						)
					)
				)
			)
		);
		
		return $this->pObj->getViewObject()->render($formConfig);
	}
	
	/**
	 * Processes the request for a new admin user. This might become obsolete in the next days!
	 *
	 * @param unknown_type $staticFields
	 * @return unknown
	 */
	public function createAdminProcess($staticFields)	{
		if (ereg('[^[:alnum:]_-]', $this->env['createadmin_username']))	{
			$this->addError(sprintf($this->get_LL('msg_warning_invalidusername'), $this->env['createadmin_username']), FATAL, 'fields', 'createadmin_username');
			return false;
		}
		
		if (empty($this->env['createadmin_username'])) {
			$this->addError($this->get_LL('msg_warning_emptyusername'), FATAL, 'fields', 'createadmin_username');
			return false;
		}
		
		if ($this->env['createadmin_password1'] != $this->env['createadmin_password2'])	{
			$this->addError($this->get_LL('msg_warning_passwordmatch'), FATAL, 'fields', 'createadmin_password1');
			return false;
		}
		
		if (empty($this->env['createadmin_password1']))	{
			$viewObj = $this->addError($this->get_LL('msg_warning_emptypassword'), FATAL, 'fields', 'createadmin_password1');
			return false;
		}
		
			// input data is OK ...
		
		if (!$this->basicsObject->executeMethod(array('database', 'checkSelectDatabase'))) {
			return false;
		}
		
			// connected to database ...
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'be_users', 'username='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->env['createadmin_username'], 'be_users'));
		if($GLOBALS['TYPO3_DB']->sql_num_rows($res) > 0) {
			$this->addError(sprintf($this->get_LL('msg_warning_usernameexists'), $this->env['createadmin_username']), FATAL, 'fields', 'createadmin_username');
			return false;
		}
		
			// no user with the entered name exist ... create
		
		$insertFields = array(
			'username'       => strtolower($this->env['createadmin_username']),
			'password'       => md5($this->env['createadmin_password1']),
			'admin'          => 1,
			'uc'             => '',
			'fileoper_perms' => 7,
			'tstamp'         => time(),
			'crdate'         => time()
		);
									
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('be_users', $insertFields);

		return true;
	}
	
	/**
	 * Compares the current database with a given SQL file. If the argument is null it uses the
	 * default file t3lib/stddb/tables.sql.
	 *
	 * @param	string	$sqlFile: The file that is used for comparision; NULL for default file
	 * @return	HTML-Code or false if an error occured
	 */
	public function analyzeCompareFile($sqlFile = NULL)	{
			// Load default SQL file if none is given
		if (is_null($sqlFile))	{
			$sqlFile = PATH_t3lib.'stddb/tables.sql';
		}
		$tblFileContent = t3lib_div::getUrl($sqlFile);
		
			// return an error if the given file was not found
		if (!$tblFileContent)	{
			$this->addError(sprintf($this->get_LL('msg_database_error_filenotfound'), $sqlFile), FATAL);
			return false;
		}

			// Add all SQL statements from all loaded extensions
		reset($GLOBALS['TYPO3_LOADED_EXT']);
		foreach ($GLOBALS['TYPO3_LOADED_EXT'] as $loadedExtConf)	{
			if (is_array($loadedExtConf) && $loadedExtConf['ext_tables.sql'])	{
				$tblFileContent .= chr(10).chr(10).chr(10).chr(10).t3lib_div::getUrl($loadedExtConf['ext_tables.sql']);
			}
		}

			// Get an instance of the t3lib_install class
		$t3lib_install = t3lib_div::makeInstance('t3lib_install');
		
			// Transform string of SQL statements into an array
		$statements = $t3lib_install->getStatementArray($tblFileContent, 1);

			// Get all the statements indexed for each table
		list($statements_table, $insertCount) = $t3lib_install->getCreateTables($statements, 1);

			// Get all create table statements
		$fileContent = implode($t3lib_install->getStatementArray($tblFileContent, 1, '^CREATE TABLE '), chr(10));
			// Get field definitions for each table and make sure they are clean
		$FDfile = $t3lib_install->getFieldDefinitions_sqlContent($fileContent);
		
		if (!count($FDfile))	{
			$this->addError(sprintf($this->get_LL('msg_analyze_error_nocreatedefinitions'), PATH_t3lib.'stddb/tables.sql'), FATAL);
			return false;
		}
		
		$FDdb = $t3lib_install->getFieldDefinitions_database();
		$diff = $t3lib_install->getDatabaseExtra($FDfile, $FDdb);
		$update_statements = $t3lib_install->getUpdateSuggestions($diff);
		$diff = $t3lib_install->getDatabaseExtra($FDdb, $FDfile);
		$remove_statements = $t3lib_install->getUpdateSuggestions($diff,'remove');
		
			// Updating database...
		if ($this->env['action'] == 'performUpdate')	{
			/*
			 * Here the script has to perform the update of the database. The code is pasted from old install class.
			 */
			$t3lib_install->performUpdateQueries($update_statements['add'], $this->env);
			$t3lib_install->performUpdateQueries($update_statements['change'], $this->env);
			$t3lib_install->performUpdateQueries($remove_statements['change'], $this->env);
			$t3lib_install->performUpdateQueries($remove_statements['drop'], $this->env);

			$t3lib_install->performUpdateQueries($update_statements['create_table'], $this->env);
			$t3lib_install->performUpdateQueries($remove_statements['change_table'], $this->env);
			$t3lib_install->performUpdateQueries($remove_statements['drop_table'], $this->env);
		
				// Init again / first time depending...
			$FDdb = $t3lib_install->getFieldDefinitions_database();
			$diff = $t3lib_install->getDatabaseExtra($FDfile, $FDdb);
			$update_statements = $t3lib_install->getUpdateSuggestions($diff);
			$diff = $t3lib_install->getDatabaseExtra($FDdb, $FDfile);
			$remove_statements = $t3lib_install->getUpdateSuggestions($diff,'remove');
		}

			// render form and / or message depending on result of DB compare
		if ($remove_statements || $update_statements)	{
			$formContent = $this->get_LL('msg_database_updateneeded').'<br />'.$this->generateUpdateDatabaseForm($update_statements, $remove_statements);
		} else {
			$formContent = $this->get_LL('msg_database_noupdateneeded');
		}
		
		return $formContent;
	}
	
	/**
	 * Generates the form for selecting actions that can be performed after a comparison. 
	 *
	 * @param	array	$arr_update: All actions concerning database updates
	 * @param	array	$arr_remove: All actions concerning database removales
	 * @return	HTML with complete formcode
	 */
	private function generateUpdateDatabaseForm($arr_update, $arr_remove)	{
		$content = '';
		
			// get elements for various states
		$elements = array();	
		
			// Fields
		$elements = array_merge($elements, $this->generateUpdateDatabaseForm_checkboxes($arr_update['add'], 'Add fields'));
		$elements = array_merge($elements, $this->generateUpdateDatabaseForm_checkboxes($arr_update['change'], 'Changing fields', (t3lib_extMgm::isLoaded('dbal') ? false : true), $arr_update['change_currentValue']));
		$elements = array_merge($elements, $this->generateUpdateDatabaseForm_checkboxes($arr_remove['change'], 'Remove unused fields (rename with prefix)'));
		$elements = array_merge($elements, $this->generateUpdateDatabaseForm_checkboxes($arr_remove['drop'], 'Drop fields (really!)'));

			// Tables
		$elements = array_merge($elements, $this->generateUpdateDatabaseForm_checkboxes($arr_update['create_table'], 'Add tables'));
		$elements = array_merge($elements, $this->generateUpdateDatabaseForm_checkboxes($arr_remove['change_table'], 'Removing tables (rename with prefix)', $this->setAllCheckBoxesByDefault));
		$elements = array_merge($elements, $this->generateUpdateDatabaseForm_checkboxes($arr_remove['drop_table'], 'Drop tables (really!)', $this->setAllCheckBoxesByDefault));

			// prepare config for rendering
		$formConfig = array (
			'type' => 'form',
			'value' => array (
				'options' => array (
					'name' => 'form_analyzeCompareFile',
					'id' => 'form_analyzeCompareFile',
					'submit' => $this->get_LL('label_writechanges'),
					'ajax' => true,
					'action' => 'sendMethodForm(\'form_analyzeCompareFile\', \'database\', \'analyzeCompareFile\', displayMethodResult)',
				),
				'hidden' => array (
					'action' => 'performUpdate',
					'target' => 'analyze_compareFile_result'
				),
				'elements' => $elements
			)
		);
		
			// render the form in viewObj
		$content = $this->pObj->getViewObject()->render($formConfig);
			
		return $content;
	}
	
	private function generateUpdateDatabaseForm_checkboxes($data, $label, $checked = true, $currentValue = array())	{
		$result = array();
		
		if (is_array($data))	{
			$result[] = array(
				'type' => 'formelement',
				'value' => array (
					'elementType' => 'fieldset',
					'label' => $label
				)
			);
			
			foreach ($data as $key => $statement)	{
				$result[] = array(
					'type' => 'formelement',
					
					'value' => array (
						'elementType' => 'checkbox',
						'label' => nl2br(htmlspecialchars($statement)).'<br /><br />'.((empty($currentValue[$key]) ? '' : '<em>Current value: '.$currentValue[$key].'</em>')),
						'label_align' => 'right',
						'options' => array (
							'name' => $key,
							'default' => $checked,
							'id' => $key
						)
					)
				);
			}
		}
		
		return $result;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/modules/database/class.tx_install_database.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/modules/database/class.tx_install_database.php']);
}
?>
