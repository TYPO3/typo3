<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog, <typo3@susannemoog.de>
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
 * Controller for configuration related actions.
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 * @package Extension Manager
 * @subpackage Controller
 */
class Tx_Extensionmanager_Controller_ConfigurationController extends Tx_Extensionmanager_Controller_AbstractController {

	/**
	 * @var Tx_Extensionmanager_Domain_Repository_ConfigurationItemRepository
	 */
	protected $configurationItemRepository;

	/**
	 * @param Tx_Extensionmanager_Domain_Repository_ConfigurationItemRepository $configurationItemRepository
	 * @return void
	 */
	public function injectConfigurationItemRepository(
		Tx_Extensionmanager_Domain_Repository_ConfigurationItemRepository $configurationItemRepository
	) {
		$this->configurationItemRepository = $configurationItemRepository;
	}

	/**
	 * Show the extension configuration form. The whole form field handling is done
	 * in the corresponding view helper
	 *
	 * @return void
	 */
	public function showConfigurationFormAction() {
		$extension = $this->request->getArgument('extension');
		$extension = array_merge($extension, $GLOBALS['TYPO3_LOADED_EXT'][$extension['key']]);
		$configuration = $this->configurationItemRepository->findByExtension($extension);
		$this->view
			->assign('configuration', $configuration)
			->assign('extension', $extension);
	}

	/**
	 * Save configuration to file
	 * Merges existing with new configuration.
	 *
	 * @param array $config The new extension configuration
	 * @param string $extensionKey The extension key
	 * @return void
	 */
	public function saveAction(array $config, $extensionKey) {
		/** @var $configurationUtility Tx_Extensionmanager_Utility_Configuration */
		$configurationUtility = $this->objectManager->get('Tx_Extensionmanager_Utility_Configuration');
		$currentFullConfiguration = $configurationUtility->getCurrentConfiguration($extensionKey);
		$newConfiguration = t3lib_div::array_merge_recursive_overrule($currentFullConfiguration, $config);

		$configurationUtility->writeConfiguration(
			$configurationUtility->convertValuedToNestedConfiguration($newConfiguration),
			$extensionKey
		);
		$this->redirect('showConfigurationForm', NULL, NULL, array('extension' => array('key' => $extensionKey)));
	}


}
?>