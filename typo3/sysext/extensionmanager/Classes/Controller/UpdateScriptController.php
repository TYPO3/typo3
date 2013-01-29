<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Francois Suter, <francois.suter@typo3.org>
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
 * @author Francois Suter <francois.suter@typo3.org>
 */
class UpdateScriptController extends \TYPO3\CMS\Extensionmanager\Controller\AbstractController {

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\UpdateScriptRepository
	 * @inject
	 */
	protected $updateScriptRepository;

	/**
	 * Show the content of the update script (if any).
	 *
	 * @param array $extension Extension information, must contain at least the key
	 * @return void
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 */
	public function showAction(array $extension) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Request for update script', 'extensionmanager', 0, (is_array($extension)) ? $extension : FALSE);
		if (!array_key_exists('key', $extension)) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException(
				'Extension key not found.',
				1359206803
			);
		}
		// Get all the path information for the extension while preserving the key
		$fullExtensionProperties = array_merge($GLOBALS['TYPO3_LOADED_EXT'][$extension['key']], $extension);
		$this->view
			->assign('update', $this->updateScriptRepository->findByExtension($fullExtensionProperties))
			->assign('extension', $fullExtensionProperties);
	}
}


?>