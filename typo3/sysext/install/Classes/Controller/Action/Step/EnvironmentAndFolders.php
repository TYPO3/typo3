<?php
namespace TYPO3\CMS\Install\Controller\Action\Step;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Very first install step:
 * - Needs execution if typo3conf/LocalConfiguration.php does not exist
 * - Renders system environment output
 * - Creates folders like typo3temp, see FolderStructure/DefaultFactory for details
 * - Creates typo3conf/LocalConfiguration.php from factory
 */
class EnvironmentAndFolders extends AbstractStepAction {

	/**
	 * Execute environment and folder step:
	 * - Create main folder structure
	 * - Create typo3conf/LocalConfiguration.php
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	public function execute() {
		/** @var $folderStructureFactory \TYPO3\CMS\Install\FolderStructure\DefaultFactory */
		$folderStructureFactory = $this->objectManager->get('TYPO3\\CMS\\Install\\FolderStructure\\DefaultFactory');
		/** @var $structureFacade \TYPO3\CMS\Install\FolderStructure\StructureFacade */
		$structureFacade = $folderStructureFactory->getStructure();
		$structureFixMessages = $structureFacade->fix();
		/** @var \TYPO3\CMS\Install\Status\StatusUtility $statusUtility */
		$statusUtility = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\StatusUtility');
		$errorsFromStructure = $statusUtility->filterBySeverity($structureFixMessages, 'error');

		if (@is_dir(PATH_typo3conf)) {
			/** @var \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager */
			$configurationManager = $this->objectManager->get('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
			$configurationManager->createLocalConfigurationFromFactoryConfiguration();

			// Create a PackageStates.php with all packages activated marked as "part of factory default"
			if (!file_exists(PATH_typo3conf . 'PackageStates.php')) {
				/** @var \TYPO3\CMS\Core\Package\FailsafePackageManager $packageManager */
				$packageManager = \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->getEarlyInstance('TYPO3\\Flow\\Package\\PackageManager');
				$packages = $packageManager->getAvailablePackages();
				foreach ($packages as $package) {
					/** @var $package \TYPO3\CMS\Core\Package\PackageInterface */
					if ($package instanceof \TYPO3\CMS\Core\Package\PackageInterface
						&& $package->isPartOfFactoryDefault()
					) {
						$packageManager->activatePackage($package->getPackageKey());
					}
				}
				$packageManager->forceSortAndSavePackageStates();
			}

			// Create enable install tool file after typo3conf & LocalConfiguration were created
			/** @var \TYPO3\CMS\Install\Service\EnableFileService $installToolService */
			$installToolService = $this->objectManager->get('TYPO3\\CMS\\Install\\Service\\EnableFileService');
			$installToolService->removeFirstInstallFile();
			$installToolService->createInstallToolEnableFile();
		}

		return $errorsFromStructure;
	}

	/**
	 * Step needs to be executed if LocalConfiguration file does not exist.
	 *
	 * @return boolean
	 */
	public function needsExecution() {
		if (@is_file(PATH_typo3conf . 'LocalConfiguration.php')) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * Executes the step
	 *
	 * @return string Rendered content
	 */
	protected function executeAction() {
		/** @var \TYPO3\CMS\Install\SystemEnvironment\Check $statusCheck */
		$statusCheck = $this->objectManager->get('TYPO3\\CMS\\Install\\SystemEnvironment\\Check');
		$statusObjects = $statusCheck->getStatus();
		/** @var \TYPO3\CMS\Install\Status\StatusUtility $statusUtility */
		$statusUtility = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\StatusUtility');
		$environmentStatus = $statusUtility->sortBySeverity($statusObjects);
		$alerts = $statusUtility->filterBySeverity($statusObjects, 'alert');
		$this->view->assign('alerts', $alerts);
		$this->view->assign('environmentStatus', $environmentStatus);

		/** @var $folderStructureFactory \TYPO3\CMS\Install\FolderStructure\DefaultFactory */
		$folderStructureFactory = $this->objectManager->get('TYPO3\\CMS\\Install\\FolderStructure\\DefaultFactory');
		/** @var $structureFacade \TYPO3\CMS\Install\FolderStructure\StructureFacade */
		$structureFacade = $folderStructureFactory->getStructure();
		$structureMessages = $structureFacade->getStatus();
		/** @var $statusUtility \TYPO3\CMS\Install\Status\StatusUtility */
		$structureErrors = $statusUtility->filterBySeverity($structureMessages, 'error');
		$this->view->assign('structureErrors', $structureErrors);

		if (count($environmentStatus['error']) > 0
			|| count($environmentStatus['warning']) > 0
			|| count($structureErrors) > 0
		) {
			$this->view->assign('errorsOrWarningsFromStatus', TRUE);
		}
		$this->assignSteps();

		return $this->view->render(!empty($alerts));
	}
}
