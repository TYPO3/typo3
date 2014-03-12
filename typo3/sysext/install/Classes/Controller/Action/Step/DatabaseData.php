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
 * Populate base tables, insert admin user, set install tool password
 */
class DatabaseData extends AbstractStepAction {

	/**
	 * Import tables and data, create admin user, create install tool password
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	public function execute() {
		$result = array();

		/** @var \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager */
		$configurationManager = $this->objectManager->get('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');

		$postValues = $this->postValues['values'];

		$username = strlen($postValues['username']) ? $postValues['username'] : 'admin';

		// Check password and return early if not good enough
		$password = $postValues['password'];
		if (strlen($password) < 8) {
			$errorStatus = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
			$errorStatus->setTitle('Administrator password not secure enough!');
			$errorStatus->setMessage(
				'You are setting an important password here! It gives an attacker full control over your instance if cracked.' .
				' It should be strong (include lower and upper case characters, special characters and numbers) and must be at least eight characters long.'
			);
			$result[] = $errorStatus;
			return $result;
		}

		// Set site name
		if (!empty($postValues['sitename'])) {
			$configurationManager->setLocalConfigurationValueByPath('SYS/sitename', $postValues['sitename']);
		}

		$this->importDatabaseData();

		// Insert admin user
		$hashedPassword = $this->getHashedPassword($password);
		$adminUserFields = array(
			'username' => $username,
			'password' => $hashedPassword,
			'admin' => 1,
			'tstamp' => $GLOBALS['EXEC_TIME'],
			'crdate' => $GLOBALS['EXEC_TIME']
		);
		$this->getDatabaseConnection()->exec_INSERTquery('be_users', $adminUserFields);

		// Set password as install tool password
		$configurationManager->setLocalConfigurationValueByPath('BE/installToolPassword', $hashedPassword);

		return $result;
	}

	/**
	 * Step needs to be executed if there are no tables in database
	 *
	 * @return boolean
	 */
	public function needsExecution() {
		$result = FALSE;
		$existingTables = $this->getDatabaseConnection()->admin_get_tables();
		if (count($existingTables) === 0) {
			$result = TRUE;
		}
		return $result;
	}

	/**
	 * Executes the step
	 *
	 * @return string Rendered content
	 */
	protected function executeAction() {
		$this->assignSteps();
		return $this->view->render();
	}

	/**
	 * Create tables and import static rows
	 *
	 * @return void
	 */
	protected function importDatabaseData() {
		// Will load ext_localconf and ext_tables. This is pretty safe here since we are
		// in first install (database empty), so it is very likely that no extension is loaded
		// that could trigger a fatal at this point.
		$this->loadExtLocalconfDatabaseAndExtTables();

		// Import database data
		$database = $this->getDatabaseConnection();
		/** @var \TYPO3\CMS\Install\Service\SqlSchemaMigrationService $schemaMigrationService */
		$schemaMigrationService = $this->objectManager->get('TYPO3\\CMS\\Install\\Service\\SqlSchemaMigrationService');
		/** @var \TYPO3\CMS\Install\Service\SqlExpectedSchemaService $expectedSchemaService */
		$expectedSchemaService = $this->objectManager->get('TYPO3\\CMS\\Install\\Service\\SqlExpectedSchemaService');

		// Raw concatenated ext_tables.sql and friends string
		$expectedSchemaString = $expectedSchemaService->getTablesDefinitionString(TRUE);
		$statements = $schemaMigrationService->getStatementArray($expectedSchemaString, TRUE);
		list($_, $insertCount) = $schemaMigrationService->getCreateTables($statements, TRUE);

		$fieldDefinitionsFile = $schemaMigrationService->getFieldDefinitions_fileContent($expectedSchemaString);
		$fieldDefinitionsDatabase = $schemaMigrationService->getFieldDefinitions_database();
		$difference = $schemaMigrationService->getDatabaseExtra($fieldDefinitionsFile, $fieldDefinitionsDatabase);
		$updateStatements = $schemaMigrationService->getUpdateSuggestions($difference);

		$schemaMigrationService->performUpdateQueries($updateStatements['add'], $updateStatements['add']);
		$schemaMigrationService->performUpdateQueries($updateStatements['change'], $updateStatements['change']);
		$schemaMigrationService->performUpdateQueries($updateStatements['create_table'], $updateStatements['create_table']);

		foreach ($insertCount as $table => $count) {
			$insertStatements = $schemaMigrationService->getTableInsertStatements($statements, $table);
			foreach ($insertStatements as $insertQuery) {
				$insertQuery = rtrim($insertQuery, ';');
				$database->admin_query($insertQuery);
			}
		}
	}
}
