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


/**
 * Database select step.
 * This step is only rendered if database is mysql. With dbal,
 * database name is submitted by previous step already.
 */
class DatabaseSelect extends AbstractStepAction {

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
		/** @var $configurationManager \TYPO3\CMS\Core\Configuration\ConfigurationManager */
		$configurationManager = $this->objectManager->get('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
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
						' Either your database name contains special chars (only alphanumeric characters are allowed)' .
						' or your database user probably does not have sufficient permissions to create it.' .
						' Please choose an existing (empty) database or contact administration.'
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
		} elseif ($postValues['type'] === 'existing' && !empty($postValues['existing'])) {
			// Only store database information when it's empty
			$this->databaseConnection->setDatabaseName($postValues['existing']);
			$this->databaseConnection->sql_select_db();
			$existingTables = $this->databaseConnection->admin_get_tables();
			$isInitialInstallation = $configurationManager->getConfigurationValueByPath('SYS/isInitialInstallationInProgress');
			if (!$isInitialInstallation || count($existingTables) === 0) {
				$localConfigurationPathValuePairs['DB/database'] = $postValues['existing'];
			}
		} else {
			/** @var $errorStatus \TYPO3\CMS\Install\Status\ErrorStatus */
			$errorStatus = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
			$errorStatus->setTitle('No Database selected');
			$errorStatus->setMessage('You must select a database.');
			$result[] = $errorStatus;
		}

		if (!empty($localConfigurationPathValuePairs)) {
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
	 * Executes the step
	 *
	 * @return string Rendered content
	 */
	protected function executeAction() {
		/** @var $configurationManager \TYPO3\CMS\Core\Configuration\ConfigurationManager */
		$configurationManager = $this->objectManager->get('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
		$isInitialInstallationInProgress = $configurationManager->getConfigurationValueByPath('SYS/isInitialInstallationInProgress');
		$this->view->assign('databaseList', $this->getDatabaseList($isInitialInstallationInProgress));
		$this->view->assign('isInitialInstallationInProgress', $isInitialInstallationInProgress);
		$this->assignSteps();
		return $this->view->render();
	}

	/**
	 * Returns list of available databases (with access-check based on username/password)
	 *
	 * @param boolean $initialInstallation TRUE if first installation is in progress, FALSE if upgrading or usual access
	 * @return array List of available databases
	 */
	protected function getDatabaseList($initialInstallation) {
		$this->initializeDatabaseConnection();
		$databaseArray = $this->databaseConnection->admin_get_dbs();
		// Remove mysql organizational tables from database list
		$reservedDatabaseNames = array('mysql', 'information_schema', 'performance_schema');
		$allPossibleDatabases = array_diff($databaseArray, $reservedDatabaseNames);

		// If we are upgrading we show *all* databases the user has access to
		if ($initialInstallation === FALSE) {
			return $allPossibleDatabases;
		} else {
			// In first installation we show all databases but disable not empty ones (with tables)
			$databases = array();
			foreach ($allPossibleDatabases as $database) {
				$this->databaseConnection->setDatabaseName($database);
				$this->databaseConnection->sql_select_db();
				$existingTables = $this->databaseConnection->admin_get_tables();
				$databases[] = array(
					'name' => $database,
					'tables' => count($existingTables),
				);
			}
			return $databases;
		}
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
		$this->databaseConnection->setDatabaseSocket($GLOBALS['TYPO3_CONF_VARS']['DB']['socket']);
		$this->databaseConnection->sql_pconnect();
	}
}
