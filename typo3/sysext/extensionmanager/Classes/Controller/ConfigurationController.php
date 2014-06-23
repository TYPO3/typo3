<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

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

use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;

/**
 * Controller for configuration related actions.
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class ConfigurationController extends AbstractController {

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ConfigurationItemRepository
	 * @inject
	 */
	protected $configurationItemRepository;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
	 * @inject
	 */
	protected $extensionRepository;

	/**
	 * Show the extension configuration form. The whole form field handling is done
	 * in the corresponding view helper
	 *
	 * @param array $extension Extension information, must contain at least the key
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 * @return void
	 */
	public function showConfigurationFormAction(array $extension) {
		if (!array_key_exists('key', $extension)) {
			throw new ExtensionManagerException(
				'Extension key not found.',
				1359206803
			);
		}
		$configuration = $this->configurationItemRepository->findByExtensionKey($extension['key']);
		if ($configuration) {
			$this->view
				->assign('configuration', $configuration)
				->assign('extension', $extension);
		} else {
			/** @var Extension $extension */
			$extension = $this->extensionRepository->findOneByCurrentVersionByExtensionKey($extension['key']);
			// Extension has no configuration and is a distribution
			if ($extension->getCategory() === Extension::DISTRIBUTION_CATEGORY) {
				$this->redirect('welcome', 'Distribution', NULL, array('extension' => $extension->getUid()));
			}
			throw new ExtensionManagerException('The extension ' . htmlspecialchars($extension['key']) . ' has no configuration.');
		}
	}

	/**
	 * Save configuration and redirects back to form
	 * or to the welcome page of a distribution
	 *
	 * @param array $config The new extension configuration
	 * @param string $extensionKey The extension key
	 * @return void
	 */
	public function saveAction(array $config, $extensionKey) {
		$this->saveConfiguration($config, $extensionKey);
		/** @var Extension $extension */
		$extension = $this->extensionRepository->findOneByCurrentVersionByExtensionKey($extensionKey);
		// Different handling for distribution installation
		if ($extension instanceof Extension &&
			$extension->getCategory() === Extension::DISTRIBUTION_CATEGORY
		) {
			$this->redirect('welcome', 'Distribution', NULL, array('extension' => $extension->getUid()));
		} else {
			$this->redirect('showConfigurationForm', NULL, NULL, array('extension' => array('key' => $extensionKey)));
		}
	}

	/**
	 * Saves new configuration and redirects back to list
	 *
	 * @param array $config
	 * @param string $extensionKey
	 * @return void
	 */
	public function saveAndCloseAction(array $config, $extensionKey) {
		$this->saveConfiguration($config, $extensionKey);
		$this->redirect('index', 'List');
	}


	/**
	 * Emits a signal after the configuration file was written
	 *
	 * @param array $newConfiguration
	 */
	protected function emitAfterExtensionConfigurationWriteSignal(array $newConfiguration) {
		$this->signalSlotDispatcher->dispatch(__CLASS__, 'afterExtensionConfigurationWrite', array($newConfiguration, $this));
	}

	/**
	 * Merge and save new configuration
	 *
	 * @param array $config
	 * @param $extensionKey
	 * @return void
	 */
	protected function saveConfiguration(array $config, $extensionKey) {
		/** @var $configurationUtility \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility */
		$configurationUtility = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\ConfigurationUtility');
		$newConfiguration = $configurationUtility->getCurrentConfiguration($extensionKey);
		\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($newConfiguration, $config);
		$configurationUtility->writeConfiguration(
			$configurationUtility->convertValuedToNestedConfiguration($newConfiguration),
			$extensionKey
		);
		$this->emitAfterExtensionConfigurationWriteSignal($newConfiguration);
	}

}
