<?php
namespace TYPO3\CMS\Backend\View\LoginForm;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Weiske <cweiske@cweiske.de>
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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Controller\LoginController;

/**
 * TYPO3 backend Login form with username and password
 *
 * @author Christian Weiske <cweiske@cweiske.de>
 */
class Password {

	/**
	 * GPvar: preset username
	 */
	protected $u;

	/**
	 * GPvar: preset password
	 */
	protected $p;

	public function __construct()
	{
		// Grabbing preset username and password, for security reasons this feature only works if SSL is used
		if (GeneralUtility::getIndpEnv('TYPO3_SSL')) {
			$this->u = GeneralUtility::_GP('u');
			$this->p = GeneralUtility::_GP('p');
		}
	}
	/**
	 * Render password login form
	 *
	 * @param array  $params    Array with keys "forms", "labels" and "conf"
	 * @param object $loginCtrl Login controller object
	 *
	 * @return void
	 */
	public function render($params, LoginController $loginCtrl) {
		$view = GeneralUtility::makeInstance('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
		$view->setTemplatePathAndFilename(
			GeneralUtility::getFileAbsFileName($params['conf']['template'])
		);

		$view->assign('username', $this->u);
		$view->assign('password', $this->p);

		$params['forms']['password']  = $view->render();
		$params['labels']['password'] = $GLOBALS['LANG']->sL(
			'LLL:EXT:lang/locallang_login.xlf:labels.switchToDefault'
		);
	}
}
?>
