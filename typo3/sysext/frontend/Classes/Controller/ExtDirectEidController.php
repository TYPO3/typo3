<?php
namespace TYPO3\CMS\Frontend\Controller;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Stefan Galinski <stefan.galinski@gmail.com>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * eID controller for ExtDirect
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
class ExtDirectEidController {

	/**
	 * Ajax Instance
	 *
	 * @var \TYPO3\CMS\Core\Http\AjaxRequestHandler
	 */
	protected $ajaxObjext = NULL;

	/**
	 * Routes the given eID action to the related ExtDirect method with the necessary
	 * ajax object.
	 *
	 * @return void
	 */
	public function routeAction() {
		\TYPO3\CMS\Frontend\Utility\EidUtility::initLanguage();
		$ajaxID = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('action');
		$ajaxScript = $GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['ExtDirect::' . $ajaxID];
		$this->ajaxObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Http\\AjaxRequestHandler', 'ExtDirect::' . $ajaxID);
		$parameters = array();
		\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($ajaxScript, $parameters, $this->ajaxObject, FALSE, TRUE);
	}

	/**
	 * Returns TRUE if the associated action in _GET is allowed.
	 *
	 * @return boolean
	 */
	public function actionIsAllowed() {
		if (!in_array(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('action'), array('route', 'getAPI'))) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Renders/Echoes the ajax output
	 *
	 * @return void
	 */
	public function render() {
		$this->ajaxObject->render();
	}

}


?>