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

/**
 * General RequestHandler for the TYPO3 Backend. This is used for all Backend requests except for CLI
 * or AJAX calls. Unlike all other RequestHandlers in the TYPO3 CMS Core, the actual logic for choosing
 * the controller is still done inside places like mod.php and each single file.
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
	 * Constructor handing over the bootstrap
	 *
	 * @param Bootstrap $bootstrap
	 */
	public function __construct(Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;
	}

	/**
	 * Handles any backend request
	 *
	 * @return void
	 */
	public function handleRequest() {
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

	/**
	 * This request handler can handle any backend request (but not CLI).
	 *
	 * @return bool If the request is not a CLI script, TRUE otherwise FALSE
	 */
	public function canHandleRequest() {
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
}
