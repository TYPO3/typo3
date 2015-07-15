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

/**
 * General RequestHandler for the TYPO3 Backend. This is used for all Backend requests except for CLI
 * or AJAX calls. Unlike all other RequestHandlers in the TYPO3 CMS Core, the actual logic for choosing
 * the controller is still done inside places like each single file.
 * This RequestHandler here serves solely to check and set up all requirements needed for a TYPO3 Backend.
 * This class might be changed in the future.
 */
class RequestHandler implements RequestHandlerInterface {

	/**
	 * Instance of the current TYPO3 bootstrap
	 * @var Bootstrap
	 */
	protected $bootstrap;

	/**
	 * Constructor handing over the bootstrap and the original request
	 *
	 * @param Bootstrap $bootstrap
	 */
	public function __construct(Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;
	}

	/**
	 * Handles any backend request
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 * @return NULL|\Psr\Http\Message\ResponseInterface
	 */
	public function handleRequest(\Psr\Http\Message\ServerRequestInterface $request) {
		// enable dispatching via Request/Response logic only for typo3/index.php currently
		$path = substr($request->getUri()->getPath(), strlen(GeneralUtility::getIndpEnv('TYPO3_SITE_PATH')));
		$routingEnabled = ($path === TYPO3_mainDir . 'index.php' || $path === TYPO3_mainDir);

		// Evaluate the constant for skipping the BE user check for the bootstrap
		if (defined('TYPO3_PROCEED_IF_NO_USER') && TYPO3_PROCEED_IF_NO_USER) {
			$proceedIfNoUserIsLoggedIn = TRUE;
		} else {
			$proceedIfNoUserIsLoggedIn = FALSE;
		}

		$this->bootstrap
			->checkLockedBackendAndRedirectOrDie()
			->checkBackendIpOrDie()
			->checkSslBackendAndRedirectIfNeeded()
			->loadExtensionTables(TRUE)
			->initializeSpriteManager()
			->initializeBackendUser()
			->initializeBackendAuthentication($proceedIfNoUserIsLoggedIn)
			->initializeLanguageObject()
			->initializeBackendTemplate()
			->endOutputBufferingAndCleanPreviousOutput()
			->initializeOutputCompression()
			->sendHttpHeaders();

		if ($routingEnabled) {
			return $this->dispatch($request);
		}
		return NULL;
	}

	/**
	 * This request handler can handle any backend request (but not CLI).
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface|\TYPO3\CMS\Core\Console\Request $request
	 * @return bool If the request is not a CLI script, TRUE otherwise FALSE
	 */
	public function canHandleRequest(\Psr\Http\Message\ServerRequestInterface $request) {
		return (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_BE && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI));
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request.
	 *
	 * @return int The priority of the request handler.
	 */
	public function getPriority() {
		return 50;
	}

	/**
	 * Dispatch the request to the appropriate controller, will go to a proper dispatcher/router class in the future
	 *
	 * @internal
	 * @param \Psr\Http\Message\RequestInterface $request
	 * @return NULL|\Psr\Http\Message\ResponseInterface
	 */
	protected function dispatch($request) {
		$controller = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Controller\LoginController::class);
		if ($controller instanceof \TYPO3\CMS\Core\Http\ControllerInterface) {
			return $controller->processRequest($request);
		}
		return NULL;
	}
}
