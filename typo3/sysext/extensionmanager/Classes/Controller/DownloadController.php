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
class Tx_Extensionmanager_Controller_DownloadController extends Tx_Extensionmanager_Controller_AbstractController {

	/**
	 * @var Tx_Extensionmanager_Domain_Repository_ExtensionRepository
	 */
	protected $extensionRepository;

	/**
	 * Dependency injection of the Extension Repository
	 * @param Tx_Extensionmanager_Domain_Repository_ExtensionRepository $extensionRepository
	 * @return void
	-	 */
	public function injectExtensionRepository(Tx_Extensionmanager_Domain_Repository_ExtensionRepository $extensionRepository) {
		$this->extensionRepository = $extensionRepository;
	}

	public function terExtensionDownloadAction() {
		if(!$this->request->hasArgument('extension')) {
			throw new Exception('Required argument extension not set.', 1334433342);
		}
		$extensionUid = $this->request->getArgument('extension');
		$extension = $this->extensionRepository->findByUid(intval($extensionUid));
		/** @var $repositoryHelper Tx_Extensionmanager_Utility_Repository_Helper */
		$repositoryHelper = $this->objectManager->get('Tx_Extensionmanager_Utility_Repository_Helper');
		/** @var $terConnection Tx_Extensionmanager_Utility_Connection_Ter */
		$terConnection = $this->objectManager->get('Tx_Extensionmanager_Utility_Connection_Ter');
		$mirrorUrl = $repositoryHelper->getMirrors()->getMirrorUrl();
		//$fetchedData = $terConnection->fetchExtension($extension->getExtensionKey(), $extension->getVersion(), $extension->getMd5hash(), $mirrorUrl);
	}

}