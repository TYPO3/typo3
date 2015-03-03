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

use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\RequestHandlerInterface;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * General RequestHandler for the TYPO3 Backend. This is used for all backend requests except for CLI, AJAX or
 * calls to mod.php (currently handled by BackendModuleRequestHandler).
 *
 * At first, this request handler serves as a replacement to typo3/init.php. It is called but does not exit
 * so any typical script that is not dispatched, is just running through the handleRequest() method and then
 * calls its own code.
 *
 * However, if a get/post parameter "route" is set, the unified Backend Routing is called and searches for a
 * matching route inside the Router. The corresponding controller / action is called then which returns content.
 *
 * The following get/post parameters are evaluated here:
 *   - route
 *   - token
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
	 * @throws \Exception
	 * @throws \TYPO3\CMS\Core\Exception
	 */
	public function handleRequest() {
		// Only enable routing for typo3/index.php
		$routingEnabled = GeneralUtility::getIndpEnv('SCRIPT_FILENAME') === PATH_typo3 . 'index.php';
		$pathToRoute = (string)GeneralUtility::getIndpEnv('PATH_INFO');

		// Allow the login page to be displayed if routing is not used and on index.php
		if ($routingEnabled && empty($pathToRoute)) {
			define('TYPO3_PROCEED_IF_NO_USER', 1);
			$pathToRoute = '/login';
		}

		$this->boot();

		// Check if the router has the available route and dispatch.
		if ($routingEnabled) {
			$this->dispatchRoute($pathToRoute);
			$this->bootstrap->shutdown();
		}

		// No route found, so the system proceeds in called entrypoint as fallback.
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
	 * Returns the priority - how eager the handler is to actually handle the request.
	 *
	 * @return int The priority of the request handler.
	 */
	public function getPriority() {
		return 50;
	}

	/**
	 * Does the main work for setting up the backend environment for any Backend request
	 *
	 * @return void
	 */
	protected function boot() {
		$this->bootstrap
			->checkLockedBackendAndRedirectOrDie()
			->checkBackendIpOrDie()
			->checkSslBackendAndRedirectIfNeeded()
			->checkValidBrowserOrDie()
			->initializeBackendRouter()
			->loadExtensionTables(TRUE)
			->initializeSpriteManager()
			->initializeBackendUser()
			->initializeBackendAuthentication()
			->initializeLanguageObject()
			->initializeBackendTemplate()
			->endOutputBufferingAndCleanPreviousOutput()
			->initializeOutputCompression()
			->sendHttpHeaders();
	}

	/**
	 * If the request can be handled by the routing framework, dispatch to the correct
	 * controller and action, and then echo the content.
	 *
	 * @param string $pathToRoute
	 *
	 * @throws RouteNotFoundException
	 * @throws \TYPO3\CMS\Core\Exception
	 */
	protected function dispatchRoute($pathToRoute) {
		$route = $this->bootstrap->getEarlyInstance(Routing\Router::class)->match($pathToRoute);
		$routeToken = (string)GeneralUtility::_GP('token');
		$routeName = $route->getOption('_identifier');
		$isPublicRoute = $route->getOption('public') || (defined('TYPO3_PROCEED_IF_NO_USER') && TYPO3_PROCEED_IF_NO_USER > 0);
		if ($isPublicRoute || $this->isValidRequest($routeToken, $routeName)) {
			list($className, $methodName) = $route->getOption('controller');
			// parameters can be used at a later point to define the available request parameters
			$parameters = array();
			echo GeneralUtility::callUserFunction($className . '->' . $methodName, $parameters, $this);
		} else {
			throw new RouteNotFoundException('Invalid request for route "' . $pathToRoute . '"', 1425389455);
		}
	}

	/**
	 * Wrapper method for static form protection utility
	 *
	 * @return \TYPO3\CMS\Core\FormProtection\AbstractFormProtection
	 */
	protected function getFormProtection() {
		return FormProtectionFactory::get();
	}

	/**
	 * Checks if the request token is valid. This is checked to see if the route is really
	 * created by the same instance. Should be called for all routes in the backend except
	 * for the ones that don't require a login.
	 *
	 * @param string $token
	 * @param string $name
	 * @return bool
	 * @see \TYPO3\CMS\Backend\Routing\Generator\UrlGenerator::generate() where the token is generated.
	 */
	protected function isValidRequest($token, $name) {
		return $this->getFormProtection()->validateToken($token, 'route', $name);
	}
}
