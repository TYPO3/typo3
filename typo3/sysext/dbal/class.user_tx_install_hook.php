<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Xavier Perseguers <typo3@perseguers.ch>
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
 * Hook for TYPO3 installer.
 *
 * $Id$
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 *
 * @package TYPO3
 * @subpackage dbal
 */
class user_tx_install_hook {

	protected $templateFilePath = 'res/Templates/';

	/**
	 * Hooks into Installer to let a non-MySQL database to be configured.
	 * 
	 * @param array $markers
	 * @param integer $step
	 * @param tx_install $instObj
	 * @return void
	 */
	public function executeStepOutput(array &$markers, $step, tx_install $instObj) {
		switch ($step) {
			case 2:
				$this->createConnectionForm(t3lib_div::_GET('driver'), $markers, $instObj);
				break;
			case 3:
				$this->createDatabaseForm($markers, $instObj);
				break;
		}
	}

	/**
	 * Hooks into Installer to modify lines to be written to localconf.php.
	 * 
	 * @param array $lines
	 * @param integer $step
	 * @param tx_install $instObj
	 * @return void
	 */
	public function executeLocalconf(array &$lines, $step, tx_install $instObj) {
		switch ($step) {
			case 3:
			case 4:
				$driver = $instObj->INSTALL['localconf.php']['typo_db_driver'];
				if (!$driver) {
					break;
				}
				$driverConfig = '';
				switch ($driver) {
					case 'oci8':
						$driverConfig = '\'driverOptions\' => array(' .
							'\'connectSID\' => ' . ($instObj->INSTALL['localconf.php']['typo_db_type'] === 'sid' ? 'TRUE' : 'FALSE') .
						')' ;
						break;
					case 'mssql':
					case 'odbc_mssql':
						$driverConfig = '\'useNameQuote\' => TRUE';
						break;
				}
				$config = 'array(' .
					'\'_DEFAULT\' => array(' .
						'\'type\' => \'adodb\',' .
						'\'config\' => array(' .
							'\'driver\' => \'' . $driver . '\',' .
							$driverConfig .
						')' .
					')' .
				');';
				$lines[] = '$TYPO3_CONF_VARS[\'EXTCONF\'][\'dbal\'][\'handlerCfg\'] = ' . $config;
				break;
		}
	}

	/**
	 * Creates a specialized form to configure the DBMS connection.
	 * 
	 * @param string $driver
	 * @param array $markers
	 * @param tx_install $instObj
	 * @return void
	 */
	protected function createConnectionForm($driver, array &$markers, tx_install $instObj) {
			// Get the template file
		$templateFile = @file_get_contents(
			t3lib_extMgm::extPath('dbal') . $this->templateFilePath . 'install.html'
		);
			// Get the template part from the file
		$template = t3lib_parsehtml::getSubpart(
			$templateFile, '###TEMPLATE###'
		);

			// Get the subpart for the connection form
		$formSubPart = t3lib_parsehtml::getSubpart(
			$template, '###CONNECTION_FORM###'
		);
		$driverTemplate = t3lib_parsehtml::getSubpart(
			$formSubPart, '###DATABASE_DRIVER###'
		);
		$driverSubPart = $instObj->prepareDatabaseDrivers($driverTemplate);
		$formSubPart = t3lib_parsehtml::substituteSubpart(
			$formSubPart,
			'###DATABASE_DRIVER###',
			$driverSubPart
		);

			// Get the subpart related to selected database driver
		if ($driver === '' || $driver === 'mysql' || $driver === 'mysqli') {
			$driverOptionsSubPart = t3lib_parsehtml::getSubpart(
				$template, '###DRIVER_MYSQL###'
			);
		} else {
			$driverOptionsSubPart = t3lib_parsehtml::getSubpart(
				$template, '###DRIVER_' . t3lib_div::strtoupper($driver) . '###'
			);
			if ($driverOptionsSubPart === '') {
				$driverOptionsSubPart = t3lib_parsehtml::getSubpart(
					$template, '###DRIVER_DEFAULT###'
				);
			}
		}

			// Define driver-specific markers
		$driverMarkers = array();
		switch ($driver) {
			case 'mssql':
				$driverMarkers = array(
					'labelUsername' => 'Username',
					'username' => TYPO3_db_username,
					'labelPassword' => 'Password',
					'password' => TYPO3_db_password,
					'labelHost' => 'Host',
					'host' => TYPO3_db_host ? TYPO3_db_host : 'windows',
				);
				$nextStep = $instObj->step + 1;
				break;
			case 'odbc_mssql':
				$driverMarkers = array(
					'labelUsername' => 'Username',
					'username' => TYPO3_db_username,
					'labelPassword' => 'Password',
					'password' => TYPO3_db_password,
					'labelHost' => 'Host',
					'host' => TYPO3_db_host ? TYPO3_db_host : 'windows',
					'database' => 'dummy_string',
				);
				$nextStep = $instObj->step + 2;
				break;
			case 'oci8':
				$driverMarkers = array(
					'labelUsername' => 'Username',
					'username' => TYPO3_db_username,
					'labelPassword' => 'Password',
					'password' => TYPO3_db_password,
					'labelHost' => 'Host',
					'host' => TYPO3_db_host ? TYPO3_db_host : 'localhost',
					'labelType' => 'Type',
					'labelSID' => 'SID',
					'labelServiceName' => 'Service Name',
					'labelDatabase' => 'Name',
					'database' => TYPO3_db,
				);
				$nextStep = $instObj->step + 2;
				break;
			default:
				$driverMarkers = array(
					'labelUsername' => 'Username',
					'username' => TYPO3_db_username,
					'labelPassword' => 'Password',
					'password' => TYPO3_db_password,
					'labelHost' => 'Host',
					'host' => TYPO3_db_host ? TYPO3_db_host : 'localhost',
					'labelDatabase' => 'Database',
					'database' => TYPO3_db,
				);
				$nextStep = $instObj->step + 1;
				break;
		}

			// Add header marker for main template
		$markers['header'] = 'Connect to your database host';
			// Define the markers content for the subpart
		$subPartMarkers = array(
			'step' => $nextStep,
			'action' => htmlspecialchars($instObj->action),
			'encryptionKey' => $instObj->createEncryptionKey(),
			'branch' => TYPO3_branch,
			'driver_options' => $driverOptionsSubPart,
			'continue' => 'Continue'
		);
		$subPartMarkers = array_merge($subPartMarkers, $driverMarkers);

			// Add step marker for main template
		$markers['step'] = t3lib_parsehtml::substituteMarkerArray(
			$formSubPart,
			$subPartMarkers,
			'###|###',
			1,
			1
		);
	}

	/**
	 * Creates a specialized form to configure the database.
	 * 
	 * @param array $markers
	 * @param tx_install $instObj
	 */
	protected function createDatabaseForm(array &$markers, tx_install $instObj) {
		$error_missingConnect = '
			<p class="typo3-message message-error">
				<strong>
					There is no connection to the database!
				</strong>
				<br />
				(Username: <em>' . TYPO3_db_username . '</em>,
				Host: <em>' . TYPO3_db_host . '</em>,
				Using Password: YES)
				<br />
				Go to Step 1 and enter a proper username/password!
			</p>
		';

			// Add header marker for main template
		$markers['header'] = 'Select database';
			// There should be a database host connection at this point
		if ($result = $GLOBALS['TYPO3_DB']->sql_pconnect(
			TYPO3_db_host, TYPO3_db_username, TYPO3_db_password
		)) {
				// Get the template file
			$templateFile = @file_get_contents(
				t3lib_extMgm::extPath('dbal') . $this->templateFilePath . 'install.html'
			);
				// Get the template part from the file
			$template = t3lib_parsehtml::getSubpart(
				$templateFile, '###TEMPLATE###'
			);
				// Get the subpart for the database choice step
			$formSubPart = t3lib_parsehtml::getSubpart(
				$template, '###DATABASE_FORM###'
			);
				// Get the subpart for the database options
			$step3DatabaseOptionsSubPart = t3lib_parsehtml::getSubpart(
				$formSubPart, '###DATABASEOPTIONS###'
			);

			$dbArr = $instObj->getDatabaseList();
			$dbIncluded = FALSE;
			foreach ($dbArr as $dbname) {
					// Define the markers content for database options
				$step3DatabaseOptionMarkers = array(
					'databaseValue' => htmlspecialchars($dbname),
					'databaseSelected' => ($dbname === TYPO3_db) ? 'selected="selected"' : '',
					'databaseName' => htmlspecialchars($dbname)
				);
					// Add the option HTML to an array
				$step3DatabaseOptions[] = t3lib_parsehtml::substituteMarkerArray(
					$step3DatabaseOptionsSubPart,
					$step3DatabaseOptionMarkers,
					'###|###',
					1,
					1
				);
				if ($dbname === TYPO3_db) {
					$dbIncluded = TRUE;
				}
			}
			if (!$dbIncluded && TYPO3_db) {
					// // Define the markers content when no access
				$step3DatabaseOptionMarkers = array(
					'databaseValue' => htmlspecialchars(TYPO3_db),
					'databaseSelected' => 'selected="selected"',
					'databaseName' => htmlspecialchars(TYPO3_db) . ' (NO ACCESS!)'
				);
					// Add the option HTML to an array
				$step3DatabaseOptions[] = t3lib_parsehtml::substituteMarkerArray(
					$step3DatabaseOptionsSubPart,
					$step3DatabaseOptionMarkers,
					'###|###',
					1,
					1
				);
			}
				// Substitute the subpart for the database options
			$content = t3lib_parsehtml::substituteSubpart(
				$formSubPart,
				'###DATABASEOPTIONS###',
				implode(chr(10), $step3DatabaseOptions)
			);
				// Define the markers content
			$step3SubPartMarkers = array(
				'step' => $instObj->step + 1,
				'action' => htmlspecialchars($instObj->action),
				'llOption2' => 'Select an EMPTY existing database:',
				'llRemark2' => 'All tables used by TYPO3 will be overwritten in step 3.',
				'continue' => 'Continue'
			);
				// Add step marker for main template
			$markers['step'] = t3lib_parsehtml::substituteMarkerArray(
				$content,
				$step3SubPartMarkers,
				'###|###',
				1,
				1
			);
		} else {
				// Add step marker for main template when no connection
			$markers['step'] = $error_missingConnect;
		}
	}

}

?>