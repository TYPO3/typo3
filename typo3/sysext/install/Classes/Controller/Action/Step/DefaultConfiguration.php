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

use TYPO3\CMS\Install\Service\EnableFileService;

/**
 * Set production defaults
 */
class DefaultConfiguration extends AbstractStepAction {

	/**
	 * Set defaults of auto configuration, mark installation as completed
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	public function execute() {
		/** @var \TYPO3\CMS\Install\Configuration\FeatureManager $featureManager */
		$featureManager = $this->objectManager->get('TYPO3\\CMS\\Install\\Configuration\\FeatureManager');
		// Get best matching configuration presets
		$configurationValues = $featureManager->getBestMatchingConfigurationForAllFeatures();

		// let the admin user redirect to the distributions page on first login
		if (isset($this->postValues['values']['loaddistributions'])) {

			// update the admin backend user to show the distribution management on login
			$adminUserFirstLogin = array('startModuleOnFirstLogin' => 'tools_ExtensionmanagerExtensionmanager->tx_extensionmanager_tools_extensionmanagerextensionmanager%5Baction%5D=distributions&tx_extensionmanager_tools_extensionmanagerextensionmanager%5Bcontroller%5D=List');
			$this->getDatabaseConnection()->exec_UPDATEquery(
					'be_users',
					'admin=1',
					array('uc' => serialize($adminUserFirstLogin))
			);
		}

		// Setting SYS/isInitialInstallationInProgress to FALSE marks this instance installation as complete
		$configurationValues['SYS/isInitialInstallationInProgress'] = FALSE;

		/** @var $configurationManager \TYPO3\CMS\Core\Configuration\ConfigurationManager */
		$configurationManager = $this->objectManager->get('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
		$configurationManager->setLocalConfigurationValuesByPathValuePairs($configurationValues);

		/** @var \TYPO3\CMS\Install\Service\SessionService $session */
		$session = $this->objectManager->get('TYPO3\\CMS\\Install\\Service\\SessionService');
		$session->destroySession();

		/** @var $formProtection \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection */
		$formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get(
			'TYPO3\\CMS\\Core\\FormProtection\\InstallToolFormProtection'
		);
		$formProtection->clean();

		if (!EnableFileService::isInstallToolEnableFilePermanent()) {
			EnableFileService::removeInstallToolEnableFile();
		}

		\TYPO3\CMS\Core\Utility\HttpUtility::redirect('../../../index.php', \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_303);
	}

	/**
	 * Step needs to be executed if 'isInitialInstallationInProgress' is set to TRUE in LocalConfiguration
	 *
	 * @return boolean
	 */
	public function needsExecution() {
		$result = FALSE;
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['isInitialInstallationInProgress'])
			&& $GLOBALS['TYPO3_CONF_VARS']['SYS']['isInitialInstallationInProgress'] === TRUE
		) {
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
}
