<?php
namespace TYPO3\CMS\Install\Controller;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Install step controller, dispatcher class of step actions.
 */
class StepController extends AbstractController {

	/**
	 * @var array List of valid action names that need authentication. Order is important!
	 */
	protected $authenticationActions = array(
		'environmentAndFolders',
		'databaseConnect',
		'databaseSelect',
		'databaseData',
		'defaultConfiguration',
	);

	/**
	 * Index action acts a a dispatcher to different steps
	 *
	 * Warning: Order of these methods is security relevant and interferes with different access
	 * conditions (new/existing installation). See the single method comments for details.
	 *
	 * @throws Exception
	 * @return void
	 */
	public function execute() {
		$this->loadBaseExtensions();
		$this->initializeObjectManager();

		$this->outputInstallToolNotEnabledMessageIfNeeded();
		$this->migrateLocalconfToLocalConfigurationIfNeeded();
		$this->outputInstallToolPasswordNotSetMessageIfNeeded();
		$this->migrateExtensionListToPackageStatesFile();
		$this->executeOrOutputFirstInstallStepIfNeeded();
		$this->executeSilentConfigurationUpgradesIfNeeded();
		$this->initializeSession();
		$this->checkSessionToken();
		$this->checkSessionLifetime();
		$this->loginIfRequested();
		$this->outputLoginFormIfNotAuthorized();
		$this->executeSpecificStep();
		$this->outputSpecificStep();
		$this->redirectToTool();
	}

	/**
	 * Execute a step action if requested. If executed, a redirect is done, so
	 * the next request will render step one again if needed or initiate a
	 * request to test the next step.
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function executeSpecificStep() {
		$action = $this->getAction();
		$postValues = $this->getPostValues();
		if ($action && isset($postValues['set']) && $postValues['set'] === 'execute') {
			$stepAction = $this->getActionInstance($action);
			$stepAction->setAction($action);
			$stepAction->setToken($this->generateTokenForAction($action));
			$stepAction->setPostValues($this->getPostValues());
			$messages = $stepAction->execute();
			$this->addSessionMessages($messages);
			$this->redirect();
		}
	}

	/**
	 * Render a specific step. Fallback to first step if none is given.
	 * The according step is instantiated and 'needsExecution' is called. If
	 * it needs execution, the step will be rendered, otherwise a redirect
	 * to test the next step is initiated.
	 *
	 * @return void
	 */
	protected function outputSpecificStep() {
		$action = $this->getAction();
		if ($action === '') {
			// First step action
			list($action) = $this->authenticationActions;
		}
		$stepAction = $this->getActionInstance($action);
		$stepAction->setAction($action);
		$stepAction->setController('step');
		$stepAction->setToken($this->generateTokenForAction($action));
		$stepAction->setPostValues($this->getPostValues());

		$needsExecution = TRUE;
		try {
			// needsExecution() may throw a RedirectException to communicate that it changed
			// configuration parameters and need an application reload.
			$needsExecution = $stepAction->needsExecution();
		} catch (Exception\RedirectException $e) {
			$this->redirect();
		}

		if ($needsExecution) {
			if ($this->isInitialInstallationInProgress()) {
				$currentStep = (array_search($action, $this->authenticationActions) + 1);
				$totalSteps = count($this->authenticationActions);
				$stepAction->setStepsCounter($currentStep, $totalSteps);
			}
			$stepAction->setMessages($this->session->getMessagesAndFlush());
			$this->output($stepAction->handle());
		} else {
			// Redirect to next step if there are any
			$currentPosition = array_keys($this->authenticationActions, $action, TRUE);
			$nextAction = array_slice($this->authenticationActions, $currentPosition[0] + 1, 1);
			if (!empty($nextAction)) {
				$this->redirect('', $nextAction[0]);
			}
		}
	}

	/**
	 * Instantiate a specific action class
	 *
	 * @param string $action Action to instantiate
	 * @throws Exception
	 * @return \TYPO3\CMS\Install\Controller\Action\Step\StepInterface
	 */
	protected function getActionInstance($action) {
		$this->validateAuthenticationAction($action);
		$actionClass = ucfirst($action);
		/** @var \TYPO3\CMS\Install\Controller\Action\Step\StepInterface $stepAction */
		$stepAction = $this->objectManager->get('TYPO3\\CMS\\Install\\Controller\\Action\\Step\\' . $actionClass);
		if (!($stepAction instanceof Action\Step\StepInterface)) {
			throw new Exception(
				$action . ' does non implement StepInterface',
				1371303903
			);
		}
		return $stepAction;
	}

	/**
	 * If the last step was reached and none needs execution, a redirect
	 * to call the tool controller is initiated.
	 *
	 * @return void
	 */
	protected function redirectToTool() {
		$this->redirect('tool');
	}

	/**
	 * Migrate localconf.php to LocalConfiguration if needed. This is done early in
	 * install tool to ease further handling.
	 *
	 * If typo3conf and typo3conf/localconf.php exist, but no typo3conf/LocalConfiguration,
	 * create LocalConfiguration.php / AdditionalConfiguration.php from localconf.php
	 * Might throw exception if typo3conf directory is not writable.
	 *
	 * @return void
	 */
	protected function migrateLocalconfToLocalConfigurationIfNeeded() {
		/** @var \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager */
		$configurationManager = $this->objectManager->get('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');

		$localConfigurationFileLocation = $configurationManager->getLocalConfigurationFileLocation();
		$localConfigurationFileExists = is_file($localConfigurationFileLocation);
		$localConfFileLocation = PATH_typo3conf . 'localconf.php';
		$localConfFileExists = is_file($localConfFileLocation);

		if (is_dir(PATH_typo3conf) && $localConfFileExists && !$localConfigurationFileExists) {
			$localConfContent = file($localConfFileLocation);

			// Line array for the three categories: localConfiguration, db settings, additionalConfiguration
			$typo3ConfigurationVariables = array();
			$typo3DatabaseVariables = array();
			$additionalConfiguration = array();
			foreach ($localConfContent as $line) {
				$line = trim($line);
				$matches = array();
				// Convert extList to array
				if (
					preg_match('/^\\$TYPO3_CONF_VARS\\[\'EXT\'\\]\\[\'extList\'\\] *={1} *\'(.+)\';{1}/', $line, $matches) === 1
					|| preg_match('/^\\$GLOBALS\\[\'TYPO3_CONF_VARS\'\\]\\[\'EXT\'\\]\\[\'extList\'\\] *={1} *\'(.+)\';{1}/', $line, $matches) === 1
				) {
					$extListAsArray = GeneralUtility::trimExplode(',', $matches[1], TRUE);
					$typo3ConfigurationVariables[] = '$TYPO3_CONF_VARS[\'EXT\'][\'extListArray\'] = ' . var_export($extListAsArray, TRUE) . ';';
				} elseif (
					preg_match('/^\\$TYPO3_CONF_VARS.+;{1}/', $line, $matches) === 1
				) {
					$typo3ConfigurationVariables[] = $matches[0];
				} elseif (
					preg_match('/^\\$GLOBALS\\[\'TYPO3_CONF_VARS\'\\].+;{1}/', $line, $matches) === 1
				) {
					$lineWithoutGlobals = str_replace('$GLOBALS[\'TYPO3_CONF_VARS\']', '$TYPO3_CONF_VARS', $matches[0]);
					$typo3ConfigurationVariables[] = $lineWithoutGlobals;
				} elseif (
					preg_match('/^\\$typo_db.+;{1}/', $line, $matches) === 1
				) {
					eval($matches[0]);
					if (isset($typo_db_host)) {
						$typo3DatabaseVariables['host'] = $typo_db_host;
					} elseif (isset($typo_db)) {
						$typo3DatabaseVariables['database'] = $typo_db;
					} elseif (isset($typo_db_username)) {
						$typo3DatabaseVariables['username'] = $typo_db_username;
					} elseif (isset($typo_db_password)) {
						$typo3DatabaseVariables['password'] = $typo_db_password;
					} elseif (isset($typo_db_extTableDef_script)) {
						$typo3DatabaseVariables['extTablesDefinitionScript'] = $typo_db_extTableDef_script;
					}
					unset($typo_db_host, $typo_db, $typo_db_username, $typo_db_password, $typo_db_extTableDef_script);
				} elseif (
					strlen($line) > 0 && preg_match('/^\\/\\/.+|^#.+|^<\\?php$|^<\\?$|^\\?>$/', $line, $matches) === 0
				) {
					$additionalConfiguration[] = $line;
				}
			}

			// Build new TYPO3_CONF_VARS array
			$TYPO3_CONF_VARS = NULL;
			// Issue #39434: Combining next two lines into one triggers a weird issue in some PHP versions
			$evalData = implode(LF, $typo3ConfigurationVariables);
			eval($evalData);

			// Add db settings to array
			$TYPO3_CONF_VARS['DB'] = $typo3DatabaseVariables;
			$TYPO3_CONF_VARS = \TYPO3\CMS\Core\Utility\ArrayUtility::sortByKeyRecursive($TYPO3_CONF_VARS);

			// Write out new LocalConfiguration file
			$configurationManager->writeLocalConfiguration($TYPO3_CONF_VARS);

			// Write out new AdditionalConfiguration file
			if (sizeof($additionalConfiguration) > 0) {
				$configurationManager->writeAdditionalConfiguration($additionalConfiguration);
			} else {
				@unlink($configurationManager->getAdditionalConfigurationFileLocation());
			}

			// Move localconf.php to localconf.obsolete.php
			rename($localConfFileLocation, PATH_site . 'typo3conf/localconf.obsolete.php');

			// Perform a reload to self, so bootstrap now uses new LocalConfiguration.php
			$this->redirect();
		}
	}

	/**
	 * Create PackageStates.php if missing and LocalConfiguration exists.
	 *
	 * This typically happens during upgrading from 6.1 or lower, all valid packages
	 * from old EXT/extListArray will be marked active.
	 *
	 * It is also fired if PackageStates.php is deleted on a running 6.2 instance,
	 * all packages marked as "part of minimal system" are activated in this case.
	 *
	 * The step installer creates typo3conf/, LocalConfiguration and PackageStates in
	 * one call, so an "installation in progress" does not trigger creation of
	 * PackageStates here.
	 *
	 * @throws \Exception
	 * @return void
	 */
	protected function migrateExtensionListToPackageStatesFile() {
		/** @var \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager */
		$configurationManager = $this->objectManager->get('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
		$localConfigurationFileLocation = $configurationManager->getLocalConfigurationFileLocation();
		$localConfigurationFileExists = is_file($localConfigurationFileLocation);
		$packageStatesFilePath = PATH_typo3conf . 'PackageStates.php';
		$localConfigurationBackupFilePath = preg_replace(
			'/\\.php$/',
			'.beforePackageStatesMigration.php',
			$configurationManager->getLocalConfigurationFileLocation()
		);

		if (file_exists($packageStatesFilePath)
			|| (is_dir(PATH_typo3conf) && !$localConfigurationFileExists)
			|| !is_dir(PATH_typo3conf)
		) {
			return;
		}

		try {
			/** @var \TYPO3\CMS\Core\Package\FailsafePackageManager $packageManager */
			$packageManager = \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->getEarlyInstance('TYPO3\\Flow\\Package\\PackageManager');

			// Activate all packages required for a minimal usable system
			$packages = $packageManager->getAvailablePackages();
			foreach ($packages as $package) {
				/** @var $package \TYPO3\CMS\Core\Package\PackageInterface */
				if ($package instanceof \TYPO3\CMS\Core\Package\PackageInterface
					&& $package->isPartOfMinimalUsableSystem()
				) {
					$packageManager->activatePackage($package->getPackageKey());
				}
			}

			// Activate all packages from LocalConfiguration EXT/extListArray if there is such an entry during upgrading.
			$extensionsFromExtListArray = array();
			try {
				$extensionsFromExtListArray = $configurationManager->getLocalConfigurationValueByPath('EXT/extListArray');
			} catch (\RuntimeException $exception) {
			}
			foreach ($extensionsFromExtListArray as $loadedExtension) {
				try {
					$packageManager->activatePackage($loadedExtension);
				} catch (\TYPO3\Flow\Package\Exception\UnknownPackageException $exception) {
					// Skip unavailable packages silently
				}
			}

			// Backup LocalConfiguration.php
			copy(
				$configurationManager->getLocalConfigurationFileLocation(),
				$localConfigurationBackupFilePath
			);

			$packageManager->forceSortAndSavePackageStates();

			// Perform a reload to self, so bootstrap now uses new PackageStates.php
			$this->redirect();
		} catch (\Exception $exception) {
			if (file_exists($packageStatesFilePath)) {
				unlink($packageStatesFilePath);
			}
			if (file_exists($localConfigurationBackupFilePath)) {
				unlink($localConfigurationBackupFilePath);
			}
			throw $exception;
		}
	}

	/**
	 * The first install step has a special standing and needs separate handling:
	 * At this point no directory exists (no typo3conf, no typo3temp), so we can
	 * not start the session handling (that stores the install tool session within typo3temp).
	 * This also means, we can not start the token handling for CSRF protection. This
	 * is no real problem, since no local configuration or other security relevant
	 * information was created yet.
	 *
	 * So, if no typo3conf directory exists yet, the first step is just rendered, or
	 * executed if called so. After that, a redirect is initiated to proceed with
	 * other tasks.
	 *
	 * @return void
	 */
	protected function executeOrOutputFirstInstallStepIfNeeded() {
		$postValues = $this->getPostValues();

		$wasExecuted = FALSE;
		$errorMessagesFromExecute = array();
		if (isset($postValues['action'])
			&& $postValues['action'] === 'environmentAndFolders'
		) {
			/** @var \TYPO3\CMS\Install\Controller\Action\Step\StepInterface $action */
			$action = $this->objectManager->get('TYPO3\\CMS\\Install\\Controller\\Action\\Step\\EnvironmentAndFolders');
			$errorMessagesFromExecute = $action->execute();
			$wasExecuted = TRUE;
		}

		/** @var \TYPO3\CMS\Install\Controller\Action\Step\StepInterface $action */
		$action = $this->objectManager->get('TYPO3\\CMS\\Install\\Controller\\Action\\Step\\EnvironmentAndFolders');

		$needsExecution = TRUE;
		try {
			// needsExecution() may throw a RedirectException to communicate that it changed
			// configuration parameters and need an application reload.
			$needsExecution = $action->needsExecution();
		} catch (Exception\RedirectException $e) {
			$this->redirect();
		}

		$testReflection = new \ReflectionMethod(get_class($this), __FUNCTION__);
		if (!@is_dir(PATH_typo3conf)
			|| $needsExecution
			|| $testReflection->getDocComment() === FALSE
		) {
			/** @var \TYPO3\CMS\Install\Controller\Action\Step\StepInterface $action */
			$action = $this->objectManager->get('TYPO3\\CMS\\Install\\Controller\\Action\\Step\\EnvironmentAndFolders');
			if ($this->isInitialInstallationInProgress()) {
				$currentStep = (array_search('environmentAndFolders', $this->authenticationActions) + 1);
				$totalSteps = count($this->authenticationActions);
				$action->setStepsCounter($currentStep, $totalSteps);
			}
			$action->setController('step');
			$action->setAction('environmentAndFolders');
			if (count($errorMessagesFromExecute) > 0) {
				$action->setMessages($errorMessagesFromExecute);
			}
			$this->output($action->handle());
		}

		if ($wasExecuted) {
			$this->redirect();
		}
	}

	/**
	 * Call silent upgrade class, redirect to self if configuration was changed.
	 *
	 * @return void
	 */
	protected function executeSilentConfigurationUpgradesIfNeeded() {
		/** @var \TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService $upgradeService */
		$upgradeService = $this->objectManager->get(
			'TYPO3\\CMS\\Install\\Service\\SilentConfigurationUpgradeService'
		);
		try {
			$upgradeService->execute();
		} catch (Exception\RedirectException $e) {
			$this->redirect();
		}
	}
}
