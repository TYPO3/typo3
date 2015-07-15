<?php
namespace TYPO3\CMS\Frontend\Controller;

/*
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

use TYPO3\CMS\Frontend\Utility\EidUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Http\AjaxRequestHandler;

/**
 * eID controller for ExtDirect
 */
class ExtDirectEidController {

	/**
	 * Ajax Instance
	 *
	 * @var AjaxRequestHandler
	 */
	protected $ajaxObject = NULL;

	/**
	 * Routes the given eID action to the related ExtDirect method with the necessary
	 * ajax object.
	 *
	 * @return void
	 */
	public function routeAction() {
		EidUtility::initLanguage();
		$ajaxID = GeneralUtility::_GP('action');
		$ajaxScript = $GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['ExtDirect::' . $ajaxID]['callbackMethod'];
		$this->ajaxObject = GeneralUtility::makeInstance(AjaxRequestHandler::class, 'ExtDirect::' . $ajaxID);
		$parameters = array();
		GeneralUtility::callUserFunction($ajaxScript, $parameters, $this->ajaxObject, FALSE, TRUE);
	}

	/**
	 * Returns TRUE if the associated action in _GET is allowed.
	 *
	 * @return bool
	 */
	public function actionIsAllowed() {
		if (!in_array(GeneralUtility::_GP('action'), array('route', 'getAPI'))) {
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
