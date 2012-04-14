<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012
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
 * action controller.
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 * @package Extension Manager
 * @subpackage Controller
 */
class Tx_Extensionmanager_Controller_InstallController extends Tx_Extensionmanager_Controller_AbstractController {


	/**
	 * @return array
	 */
	protected function toggleExtensionInstallationStateAction() {
		/** @var $cacheUtility Tx_Extensionmanager_Utility_Cache */
		$cacheUtility = $this->objectManager->get('Tx_Extensionmanager_Utility_Cache');
		/** @var $installUtility Tx_Extensionmanager_Utility_Install */
		$installUtility = $this->objectManager->get('Tx_Extensionmanager_Utility_Install');

		$extension = $this->request->getArgument('extension');
		$installedExtensions = t3lib_extMgm::getInstalledAndLoadedExtensions();

		if (array_key_exists($extension['key'], $installedExtensions)) {
			// uninstall
			$installUtility->uninstall($extension['key']);
		} else {
			// install
			$installUtility->install($extension);
			$cacheUtility->refreshGlobalExtList();
			/** @var $configUtility Tx_Extensionmanager_Utility_Configuration */
			$configUtility = $this->objectManager->get('Tx_Extensionmanager_Utility_Configuration');
			$configUtility->saveDefaultConfiguration($extension['key']);
		}

		$this->redirect('index', 'List');
	}

}

?>
