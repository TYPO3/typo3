<?php
namespace TYPO3\CMS\Install\Controller\Action\Step;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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

use TYPO3\CMS\Install\Controller\Action;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Database select step.
 * This step is only rendered if database is mysql. With dbal,
 * database name is submitted by previous step already.
 */
class DatabaseSelect extends Action\AbstractAction implements StepInterface {

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseConnection = NULL;

	/**
	 * Create database if needed, save selected db name in configuration
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	public function execute() {
		$result = array();
		$this->initializeDatabaseConnection();
		$postValues = $this->postValues['values'];
		$localConfigurationPathValuePairs = array();
		if ($postValues['type'] === 'new') {
			$newDatabaseName = $postValues['new'];
			if (strlen($newDatabaseName) <= 50) {
				$createDatabaseResult = $this->databaseConnection->admin_query('CREATE DATABASE ' . $newDatabaseName . ' CHARACTER SET utf8');
				if ($createDatabaseResult) {
					$localConfigurationPathValuePairs['DB/database'] = $newDatabaseName;
				} else {
					/** @var $errorStatus \TYPO3\CMS\Install\Status\ErrorStatus */
					$errorStatus = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
					$errorStatus->setTitle('Unable to create database');
					$errorStatus->setMessage(
						'Database with name ' . $newDatabaseName . ' could not be created.' .
						' Your database user probably has no sufficient permissions to do so. Please choose an existing (empty)' .
						' database or contact administration.'
					);
					$result[] = $errorStatus;
				}
			} else {
				/** @var $errorStatus \TYPO3\CMS\Install\Status\ErrorStatus */
				$errorStatus = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
				$errorStatus->setTitle('Database name not valid');
				$errorStatus->setMessage('Given database name must be shorter than fifty characters.');
				$result[] = $errorStatus;
			}
		} elseif ($postValues['type'] === 'existing') {
			$localConfigurationPathValuePairs['DB/database'] = $postValues['existing'];
		}

		if (!empty($localConfigurationPathValuePairs)) {
			/** @var $configurationManager \TYPO3\CMS\Core\Configuration\ConfigurationManager */
			$configurationManager = $this->objectManager->get('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
			$configurationManager->setLocalConfigurationValuesByPathValuePairs($localConfigurationPathValuePairs);
		}

		return $result;
	}

	/**
	 * Step needs to be executed if database is not set or can
	 * not be selected.
	 *
	 * @return boolean
	 */
	public function needsExecution() {
		$this->initializeDatabaseConnection();
		$result = TRUE;
		if (strlen($GLOBALS['TYPO3_CONF_VARS']['DB']['database']) > 0) {
			$this->databaseConnection->setDatabaseName($GLOBALS['TYPO3_CONF_VARS']['DB']['database']);
			try {
				$selectResult = $this->databaseConnection->sql_select_db();
				if ($selectResult === TRUE) {
					$result = FALSE;
				}
			} catch (\RuntimeException $e) {
			}
		}
		return $result;
	}

	/**
	 * Render this step
	 *
	 * @return string
	 */
	public function handle() {
		$this->initialize();
		$this->view->assign('databaseList', $this->getDatabaseList());
		return $this->view->render();
	}

	/**
	 * Returns list of available databases (with access-check based on username/password)
	 *
	 * @return array List of available databases
	 */
	protected function getDatabaseList() {
		$this->initializeDatabaseConnection();
		$databaseArray = $this->databaseConnection->admin_get_dbs();
		// Remove mysql organizational tables from database list
		$reservedDatabaseNames = array('mysql', 'information_schema', 'performance_schema');
		$allPossibleDatabases = array_diff($databaseArray, $reservedDatabaseNames);
		$databasesWithoutTables = array();
		foreach ($allPossibleDatabases as $database) {
			$this->databaseConnection->setDatabaseName($database);
			$this->databaseConnection->sql_select_db();
			$existingTables = $this->databaseConnection->admin_get_tables();
			if (count($existingTables) === 0) {
				$databasesWithoutTables[] = $database;
			}
		}
		return $databasesWithoutTables;
	}

	/**
	 * Initialize database connection
	 *
	 * @return void
	 */
	protected function initializeDatabaseConnection() {
		$this->databaseConnection = $this->objectManager->get('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
		$this->databaseConnection->setDatabaseUsername($GLOBALS['TYPO3_CONF_VARS']['DB']['username']);
		$this->databaseConnection->setDatabasePassword($GLOBALS['TYPO3_CONF_VARS']['DB']['password']);
		$this->databaseConnection->setDatabaseHost($GLOBALS['TYPO3_CONF_VARS']['DB']['host']);
		$this->databaseConnection->setDatabasePort($GLOBALS['TYPO3_CONF_VARS']['DB']['port']);
		$this->databaseConnection->sql_pconnect();
	}
}
?>