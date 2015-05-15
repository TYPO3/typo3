<?php
namespace TYPO3\CMS\Backend;

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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Base class for all AJAX-related calls for the TYPO3 Backend run through typo3/ajax.php.
 * Before doing the basic BE-related set up of this request (see the additional calls on $this->bootstrap inside
 * handleRequest()), some AJAX-calls can be made without a valid user, which is determined here.
 *
 * Due to legacy reasons, the actual logic is in EXT:core/Http/AjaxRequestHandler which will eventually
 * be moved into this class.
 * In the future, the logic for "TYPO3_PROCEED_IF_NO_USER" will be moved in here as well.
 */
class AjaxRequestHandler implements RequestHandlerInterface {

	/**
	 * Instance of the current TYPO3 bootstrap
	 * @var Bootstrap
	 */
	protected $bootstrap;

	/**
	 * Constructor handing over the bootstrap
	 *
	 * @param Bootstrap $bootstrap
	 */
	public function __construct(Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;
	}

	/**
	 * Handles any AJAX request in the TYPO3 Backend
	 *
	 * @return void
	 */
	public function handleRequest() {

		// This is a list of requests that don't necessarily need a valid BE user
		$noUserAjaxIDs = array(
			'BackendLogin::login',
			'BackendLogin::logout',
			'BackendLogin::refreshLogin',
			'BackendLogin::isTimedOut',
			'BackendLogin::getRsaPublicKey',
		);

		// First get the ajaxID
		$ajaxID = isset($_POST['ajaxID']) ? $_POST['ajaxID'] : $_GET['ajaxID'];
		if (isset($ajaxID)) {
			$ajaxID = (string)stripslashes($ajaxID);
		}

		// If we're trying to do an ajax login, don't require a user.
		if (in_array($ajaxID, $noUserAjaxIDs)) {
			define('TYPO3_PROCEED_IF_NO_USER', 2);
		}

		$GLOBALS['ajaxID'] = $ajaxID;
		$this->bootstrap
			->checkLockedBackendAndRedirectOrDie()
			->checkBackendIpOrDie()
			->checkSslBackendAndRedirectIfNeeded()
			->checkValidBrowserOrDie()
			->loadExtensionTables(TRUE)
			->initializeSpriteManager()
			->initializeBackendUser()
			->initializeBackendAuthentication()
			->initializeLanguageObject()
			->initializeBackendTemplate()
			->endOutputBufferingAndCleanPreviousOutput()
			->initializeOutputCompression()
			->sendHttpHeaders();

		// Finding the script path from the registry
		$ajaxRegistryEntry = isset($GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX'][$ajaxID]) ? $GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX'][$ajaxID] : NULL;
		$ajaxScript = NULL;
		$csrfTokenCheck = FALSE;
		if ($ajaxRegistryEntry !== NULL) {
			if (is_array($ajaxRegistryEntry)) {
				if (isset($ajaxRegistryEntry['callbackMethod'])) {
					$ajaxScript = $ajaxRegistryEntry['callbackMethod'];
					$csrfTokenCheck = $ajaxRegistryEntry['csrfTokenCheck'];
				}
			} else {
				// @deprecated since 6.2 will be removed two versions later
				$ajaxScript = $ajaxRegistryEntry;
			}
		}

		// Instantiating the AJAX object
		$ajaxObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Http\AjaxRequestHandler::class, $ajaxID);
		$ajaxParams = array();

		// Evaluating the arguments and calling the AJAX method/function
		if (empty($ajaxID)) {
			$ajaxObj->setError('No valid ajaxID parameter given.');
		} elseif (empty($ajaxScript)) {
			$ajaxObj->setError('No backend function registered for ajaxID "' . $ajaxID . '".');
		} else {
			$success = TRUE;
			$tokenIsValid = TRUE;
			if ($csrfTokenCheck) {
				$tokenIsValid = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get()->validateToken(GeneralUtility::_GP('ajaxToken'), 'ajaxCall', $ajaxID);
			}
			if ($tokenIsValid) {
				// Cleanup global variable space
				unset($csrfTokenCheck, $ajaxRegistryEntry, $tokenIsValid, $success);
				$success = GeneralUtility::callUserFunction($ajaxScript, $ajaxParams, $ajaxObj, FALSE, TRUE);
			} else {
				$ajaxObj->setError('Invalid CSRF token detected for ajaxID "' . $ajaxID . '"!');
			}
			if ($success === FALSE) {
				$ajaxObj->setError('Registered backend function for ajaxID "' . $ajaxID . '" was not found.');
			}
		}

		// Outputting the content (and setting the X-JSON-Header)
		$ajaxObj->render();
	}

	/**
	 * This request handler can handle any backend request coming from ajax.php
	 *
	 * @return bool If the request is an AJAX backend request, TRUE otherwise FALSE
	 */
	public function canHandleRequest() {
		return TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX;
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the request.
	 *
	 * @return int The priority of the request handler.
	 */
	public function getPriority() {
		return 80;
	}
}
