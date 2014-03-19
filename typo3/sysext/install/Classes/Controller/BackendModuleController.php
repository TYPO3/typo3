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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
 * Backend module controller
 *
 * Embeds in backend an only shows the 'enable install tool button' or redirects
 * to step installer if install tool is enabled.
 *
 * This is a classic extbase module that does not interfere with the other code
 * within the install tool.
 */
class BackendModuleController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var \TYPO3\CMS\Install\Service\EnableFileService
	 * @inject
	 */
	protected $enableFileService;

	/**
	 * @var \TYPO3\CMS\Core\FormProtection\AbstractFormProtection
	 */
	protected $formProtection;

	/**
	 * Set formprotection property
	 */
	public function initializeAction() {
		$this->formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
	}

	/**
	 * Index action shows install tool / step installer or redirect to action to enable install tool
	 *
	 * @return void
	 */
	public function indexAction() {
		if ($this->enableFileService->checkInstallToolEnableFile()) {
			$this->redirect('sysext/install/Start/Install.php?install[context]=backend');
		} else {
			$this->forward('showEnableInstallToolButton');
		}
	}

	/**
	 * Show enable install tool
	 *
	 * @return void
	 */
	public function showEnableInstallToolButtonAction() {
		$token = $this->formProtection->generateToken('installTool');
		$this->view->assign('installToolEnableToken', $token);
	}

	/**
	 * Enable the install tool
	 *
	 * @param string $installToolEnableToken
	 * @throws \RuntimeException
	 */
	public function enableInstallToolAction($installToolEnableToken) {
		if (!$this->formProtection->validateToken($installToolEnableToken, 'installTool')) {
			throw new \RuntimeException('Given form token was not valid', 1369161225);
		}
		$this->enableFileService->createInstallToolEnableFile();
		$this->forward('index');
	}

	/**
	 * Redirect to specified URI
	 *
	 * @param string $uri
	 */
	protected function redirect($uri) {
		\TYPO3\CMS\Core\Utility\HttpUtility::redirect($uri);
	}
}
