<?php
namespace TYPO3\CMS\Backend\Http;

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
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ServerRequestInterface;

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
	 * List of requests that don't need a valid BE user
	 * @var array
	 */
	protected $publicAjaxIds = array(
		'BackendLogin::login',
		'BackendLogin::logout',
		'BackendLogin::refreshLogin',
		'BackendLogin::isTimedOut',
		'BackendLogin::getChallenge',
		'BackendLogin::getRsaPublicKey',
		'RsaEncryption::getRsaPublicKey'
	);

	/**
	 * Constructor handing over the bootstrap and the original request
	 *
	 * @param Bootstrap $bootstrap
	 */
	public function __construct(Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;
	}

	/**
	 * Handles any AJAX request in the TYPO3 Backend
	 *
	 * @param ServerRequestInterface $request
	 * @return NULL|\Psr\Http\Message\ResponseInterface
	 */
	public function handleRequest(ServerRequestInterface $request) {
		// First get the ajaxID
		$ajaxID = isset($request->getParsedBody()['ajaxID']) ? $request->getParsedBody()['ajaxID'] : $request->getQueryParams()['ajaxID'];

		// used for backwards-compatibility
		$GLOBALS['ajaxID'] = $ajaxID;
		$this->boot($ajaxID);

		// Finding the script path from the registry
		$ajaxRegistryEntry = isset($GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX'][$ajaxID]) ? $GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX'][$ajaxID] : NULL;
		$ajaxScript = NULL;
		$csrfTokenCheck = FALSE;
		if ($ajaxRegistryEntry !== NULL && is_array($ajaxRegistryEntry) && isset($ajaxRegistryEntry['callbackMethod'])) {
			$ajaxScript = $ajaxRegistryEntry['callbackMethod'];
			$csrfTokenCheck = $ajaxRegistryEntry['csrfTokenCheck'];
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
				$ajaxToken = $request->getParsedBody()['ajaxToken'] ?: $request->getQueryParams()['ajaxToken'];
				$tokenIsValid = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get()->validateToken($ajaxToken, 'ajaxCall', $ajaxID);
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

		return NULL;
	}

	/**
	 * This request handler can handle any backend request coming from ajax.php
	 *
	 * @param ServerRequestInterface $request
	 * @return bool If the request is an AJAX backend request, TRUE otherwise FALSE
	 */
	public function canHandleRequest(ServerRequestInterface $request) {
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

	/**
	 * Start the Backend bootstrap part
	 *
	 * @param string $ajaxId Contains the string of the ajaxId used
	 */
	protected function boot($ajaxId) {
		// If we're trying to do an ajax login, don't require a user
		$proceedIfNoUserIsLoggedIn = in_array($ajaxId, $this->publicAjaxIds, TRUE);

		$this->bootstrap
			->checkLockedBackendAndRedirectOrDie($proceedIfNoUserIsLoggedIn)
			->checkBackendIpOrDie()
			->checkSslBackendAndRedirectIfNeeded()
			->checkValidBrowserOrDie()
			->loadExtensionTables(TRUE)
			->initializeSpriteManager()
			->initializeBackendUser()
			->initializeBackendAuthentication($proceedIfNoUserIsLoggedIn)
			->initializeLanguageObject()
			->initializeBackendTemplate()
			->endOutputBufferingAndCleanPreviousOutput()
			->initializeOutputCompression()
			->sendHttpHeaders();
	}
}
