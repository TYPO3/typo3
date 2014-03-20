<?php
namespace TYPO3\CMS\Install\Controller\Action\Tool;

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
 * Handle update wizards
 */
class UpgradeWizard extends Action\AbstractAction {

	/**
	 * There are tables and fields missing in the database
	 *
	 * @var bool
	 */
	protected $needsInitialUpdateDatabaseSchema = FALSE;

	/**
	 * Executes the tool
	 *
	 * @return string Rendered content
	 */
	protected function executeAction() {
		// ext_localconf, db and ext_tables must be loaded for the upgrade wizards
		$this->loadExtLocalconfDatabaseAndExtTables();

		// To make sure initialUpdateDatabaseSchema is first wizard, it is added here instead of ext_localconf.php
		$initialUpdateDatabaseSchemaUpdateObject = $this->getUpgradeObjectInstance('TYPO3\\CMS\\Install\\Updates\\InitialDatabaseSchemaUpdate', 'initialUpdateDatabaseSchema');
		if ($initialUpdateDatabaseSchemaUpdateObject->shouldRenderWizard()) {
			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'] = array_merge(
				array('initialUpdateDatabaseSchema' => 'TYPO3\\CMS\\Install\\Updates\\InitialDatabaseSchemaUpdate'),
				$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']
			);
			$this->needsInitialUpdateDatabaseSchema = TRUE;
		}

		// To make sure finalUpdateDatabaseSchema is last wizard, it is added here instead of ext_localconf.php
		$finalUpdateDatabaseSchemaUpdateObject = $this->getUpgradeObjectInstance('TYPO3\\CMS\\Install\\Updates\\FinalDatabaseSchemaUpdate', 'finalUpdateDatabaseSchema');
		if ($finalUpdateDatabaseSchemaUpdateObject->shouldRenderWizard()) {
			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['finalUpdateDatabaseSchema'] = 'TYPO3\\CMS\\Install\\Updates\\FinalDatabaseSchemaUpdate';
		}

		// Perform silent cache framework table upgrades
		$this->silentCacheFrameworkTableSchemaMigration();

		$actionMessages = array();

		if (isset($this->postValues['set']['getUserInput'])) {
			$actionMessages[] = $this->getUserInputForUpgradeWizard();
			$this->view->assign('updateAction', 'getUserInput');
		} elseif (isset($this->postValues['set']['performUpdate'])) {
			$actionMessages[] = $this->performUpdate();
			$this->view->assign('updateAction', 'performUpdate');
		} else {
			$actionMessages[] = $this->listUpdates();
			$this->view->assign('updateAction', 'listUpdates');
		}

		$this->view->assign('actionMessages', $actionMessages);

		return $this->view->render();
	}

	/**
	 * List of available updates
	 *
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	protected function listUpdates() {
		if (empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'])) {
			/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
			$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\WarningStatus');
			$message->setTitle('No update wizards registered');
			return $message;
		}

		$availableUpdates = array();
		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'] as $identifier => $className) {
			$updateObject = $this->getUpgradeObjectInstance($className, $identifier);
			if ($updateObject->shouldRenderWizard()) {
				// $explanation is changed by reference in upgrade objects!
				$explanation = '';
				$updateObject->checkForUpdate($explanation);
				$availableUpdates[$identifier] = array(
					'identifier' => $identifier,
					'title' => $updateObject->getTitle(),
					'explanation' => $explanation,
					'renderNext' => FALSE,
				);
				if ($identifier === 'initialUpdateDatabaseSchema') {
					$availableUpdates['initialUpdateDatabaseSchema']['renderNext'] = $this->needsInitialUpdateDatabaseSchema;
				} elseif ($identifier === 'finalUpdateDatabaseSchema') {
					// Okay to check here because finalUpdateDatabaseSchema is last element in array
					$availableUpdates['finalUpdateDatabaseSchema']['renderNext'] = count($availableUpdates) === 1;
				} elseif (!$this->needsInitialUpdateDatabaseSchema && $updateObject->shouldRenderNextButton()) {
					// There are upgrade wizards that only show text and don't want to be executed
					$availableUpdates[$identifier]['renderNext'] = TRUE;
				}
			}
		}

		$this->view->assign('availableUpdates', $availableUpdates);

		/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
		$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\OkStatus');
		$message->setTitle('Show available update wizards');
		return $message;
	}

	/**
	 * Get user input of update wizard
	 *
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	protected function getUserInputForUpgradeWizard() {
		$wizardIdentifier = $this->postValues['values']['identifier'];

		$className = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][$wizardIdentifier];
		$updateObject = $this->getUpgradeObjectInstance($className, $wizardIdentifier);
		$wizardHtml = '';
		if (method_exists($updateObject, 'getUserInput')) {
			$wizardHtml = $updateObject->getUserInput('install[values][' . $wizardIdentifier . ']');
		}

		$upgradeWizardData = array(
			'identifier' => $wizardIdentifier,
			'title' => $updateObject->getTitle(),
			'wizardHtml' => $wizardHtml,
		);

		$this->view->assign('upgradeWizardData', $upgradeWizardData);

		/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
		$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\OkStatus');
		$message->setTitle('Show wizard options');
		return $message;
	}

	/**
	 * Perform update of a specific wizard
	 *
	 * @throws \TYPO3\CMS\Install\Exception
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	protected function performUpdate() {
		$this->getDatabaseConnection()->store_lastBuiltQuery = TRUE;

		$wizardIdentifier = $this->postValues['values']['identifier'];
		$className = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][$wizardIdentifier];
		$updateObject = $this->getUpgradeObjectInstance($className, $wizardIdentifier);

		$wizardData = array(
			'identifier' => $wizardIdentifier,
			'title' => $updateObject->getTitle(),
		);

		// $wizardInputErrorMessage is given as reference to wizard object!
		$wizardInputErrorMessage = '';
		if (method_exists($updateObject, 'checkUserInput') && !$updateObject->checkUserInput($wizardInputErrorMessage)) {
			/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
			$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
			$message->setTitle('Input parameter broken');
			$message->setMessage($wizardInputErrorMessage ?: 'Something went wrong!');
			$wizardData['wizardInputBroken'] = TRUE;
		} else {
			if (!method_exists($updateObject, 'performUpdate')) {
				throw new \TYPO3\CMS\Install\Exception(
					'No performUpdate method in update wizard with identifier ' . $wizardIdentifier,
					1371035200
				);
			}

			// Both variables are used by reference in performUpdate()
			$customOutput = '';
			$databaseQueries = array();
			$performResult = $updateObject->performUpdate($databaseQueries, $customOutput);

			if ($performResult) {
				/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
				$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\OkStatus');
				$message->setTitle('Update successful');
			} else {
				/** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
				$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
				$message->setTitle('Update failed!');
				if ($customOutput) {
					$message->setMessage($customOutput);
				}
			}

			if ($this->postValues['values']['showDatabaseQueries'] == 1) {
				$wizardData['queries'] = $databaseQueries;
			}
		}

		$this->view->assign('wizardData', $wizardData);

		$this->getDatabaseConnection()->store_lastBuiltQuery = FALSE;

		// Next update wizard, if available
		$nextUpgradeWizard = $this->getNextUpgradeWizardInstance($updateObject);
		$nextUpgradeWizardIdentifier = '';
		if ($nextUpgradeWizard) {
			$nextUpgradeWizardIdentifier = $nextUpgradeWizard->getIdentifier();
		}
		$this->view->assign('nextUpgradeWizardIdentifier', $nextUpgradeWizardIdentifier);

		return $message;
	}

	/**
	 * Creates instance of an upgrade object, setting the pObj, versionNumber and userInput
	 *
	 * @param string $className The class name
	 * @param string $identifier The identifier of upgrade object - needed to fetch user input
	 * @return object Newly instantiated upgrade object
	 */
	protected function getUpgradeObjectInstance($className, $identifier) {
		$formValues = $this->postValues;
		$updateObject = GeneralUtility::getUserObj($className);
		$updateObject->setIdentifier($identifier);
		$updateObject->versionNumber = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version);
		$updateObject->pObj = $this;
		$updateObject->userInput = $formValues['values'][$identifier];
		return $updateObject;
	}

	/**
	 * Returns the next upgrade wizard object
	 * Used to show the link/button to the next upgrade wizard
	 *
	 * @param object $currentObj Current update wizard object
	 * @return mixed Upgrade wizard instance or FALSE
	 */
	protected function getNextUpgradeWizardInstance($currentObj) {
		$isPreviousRecord = TRUE;
		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'] as $identifier => $className) {
			// Find the current update wizard, and then start validating the next ones
			if ($currentObj->getIdentifier() == $identifier) {
				$isPreviousRecord = FALSE;
				// For the updateDatabaseSchema-wizards verify they do not have to be executed again
				if ($identifier !== 'initialUpdateDatabaseSchema' && $identifier !== 'finalUpdateDatabaseSchema') {
					continue;
				}
			}
			if (!$isPreviousRecord) {
				$nextUpgradeWizard = $this->getUpgradeObjectInstance($className, $identifier);
				if ($nextUpgradeWizard->shouldRenderWizard()) {
					return $nextUpgradeWizard;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Force creation / update of caching framework tables that are needed by some update wizards
	 *
	 * @TODO: See also the other remarks on this topic in the abstract class, this whole area needs improvements
	 * @return void
	 */
	protected function silentCacheFrameworkTableSchemaMigration() {
		/** @var $sqlHandler \TYPO3\CMS\Install\Service\SqlSchemaMigrationService */
		$sqlHandler = $this->objectManager->get('TYPO3\\CMS\\Install\\Service\\SqlSchemaMigrationService');

		/** @var \TYPO3\CMS\Install\Service\CachingFrameworkDatabaseSchemaService $cachingFrameworkDatabaseSchemaService */
		$cachingFrameworkDatabaseSchemaService = $this->objectManager->get('TYPO3\\CMS\\Install\\Service\\CachingFrameworkDatabaseSchemaService');
		$expectedSchemaString = $cachingFrameworkDatabaseSchemaService->getCachingFrameworkRequiredDatabaseSchema();
		$cleanedExpectedSchemaString = implode(LF, $sqlHandler->getStatementArray($expectedSchemaString, TRUE, '^CREATE TABLE '));
		$neededTableDefinition = $sqlHandler->getFieldDefinitions_fileContent($cleanedExpectedSchemaString);
		$currentTableDefinition = $sqlHandler->getFieldDefinitions_database();
		$updateTableDefinition = $sqlHandler->getDatabaseExtra($neededTableDefinition, $currentTableDefinition);
		$updateStatements = $sqlHandler->getUpdateSuggestions($updateTableDefinition);
		if (isset($updateStatements['create_table']) && count($updateStatements['create_table']) > 0) {
			$sqlHandler->performUpdateQueries($updateStatements['create_table'], $updateStatements['create_table']);
		}
		if (isset($updateStatements['add']) && count($updateStatements['add']) > 0) {
			$sqlHandler->performUpdateQueries($updateStatements['add'], $updateStatements['add']);
		}
		if (isset($updateStatements['change']) && count($updateStatements['change']) > 0) {
			$sqlHandler->performUpdateQueries($updateStatements['change'], $updateStatements['change']);
		}
	}

	/**
	 * Overwrite getDatabase method of abstract!
	 *
	 * Returns $GLOBALS['TYPO3_DB'] directly, since this global is instantiated properly in update wizards
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}
