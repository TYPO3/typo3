<?php
namespace TYPO3\CMS\Frontend\Controller;

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
	protected $ajaxObject = NULL;

	/**
	 * Routes the given eID action to the related ExtDirect method with the necessary
	 * ajax object.
	 *
	 * @return void
	 */
	public function routeAction() {
		\TYPO3\CMS\Frontend\Utility\EidUtility::initLanguage();
		$ajaxID = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('action');
		$ajaxScript = $GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['ExtDirect::' . $ajaxID]['callbackMethod'];
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
