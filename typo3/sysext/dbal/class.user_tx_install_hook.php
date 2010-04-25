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
	public function execute(array &$markers, $step, tx_install $instObj) {
		switch ($step) {
			case 2:
				$this->createConnectionForm(t3lib_div::_GET('driver'), $markers, $instObj);
				break;
			case 3:
				t3lib_div::debug(t3lib_div::_POST, 'POST');
				die();
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
					'labelDatabase' => 'Database',
					'database' => TYPO3_db,  
				);
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
				break;
		}

			// Add header marker for main template
		$markers['header'] = 'Connect to your database host';
			// Define the markers content for the subpart
		$subPartMarkers = array(
			'step' => $instObj->step + 1,
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
}

?>