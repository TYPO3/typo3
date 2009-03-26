<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * A multi action controller
 *
 * @package TYPO3
 * @subpackage extbase
 * @version $ID:$
 */
// SK: fill initializeArguments() so it parses the arguments for a given view. We need to discuss how this parsing can be
// SK: done effectively.
class Tx_ExtBase_MVC_Controller_ActionController extends Tx_ExtBase_MVC_Controller_AbstractController {

	/**
	 * @var boolean If initializeView() should be called on an action invocation.
	 */
	protected $initializeView = TRUE;

	/**
	 * @var Tx_ExtBase_MVC_View_AbstractView By default a view with the same name as the current action is provided. Contains NULL if none was found.
	 */
	protected $view = NULL;

	/**
	 * By default a matching view will be resolved. If this property is set, automatic resolving is disabled and the specified object is used instead.
	 * @var string
	 */
	protected $defaultViewObjectName = NULL;

	/**
	 * Pattern after which the view object name is built
	 *
	 * @var string
	 */
	// SK: Decision: Do we support "format"?
	protected $viewObjectNamePattern = 'Tx_@extension_View_@controller@action';

	/**
	 * Name of the action method
	 * @var string
	 */
	protected $actionMethodName = 'indexAction';

	/**
	 * Handles a request. The result output is returned by altering the given response.
	 *
	 * @param Tx_ExtBase_MVC_Request $request The request object
	 * @param Tx_ExtBase_MVC_Response $response The response, modified by this handler
	 * @return void
	 */
	public function processRequest(Tx_ExtBase_MVC_Request $request, Tx_ExtBase_MVC_Response $response) {
		$this->request = $request;
		$this->request->setDispatched(TRUE);
		$this->response = $response;

		$this->actionMethodName = $this->resolveActionMethodName();
		$this->initializeArguments();
		$this->mapRequestArgumentsToLocalArguments();
		if ($this->initializeView) $this->initializeView();
		$this->initializeAction();
		$this->callActionMethod();
	}

	/**
	 * Determines the action method and assures that the method exists.
	 *
	 * @return string The action method name
	 * @throws Tx_ExtBase_Exception_NoSuchAction if the action specified in the request object does not exist (and if there's no default action either).
	 */
	protected function resolveActionMethodName() {
		$actionMethodName = $this->request->getControllerActionName() . 'Action';
		if (!method_exists($this, $actionMethodName)) throw new Tx_ExtBase_Exception_NoSuchAction('An action "' . $actionMethodName . '" does not exist in controller "' . get_class($this) . '".', 1186669086);
		return $actionMethodName;
	}

	/**
	 * Returns TRUE if the given action (a name of an action like 'show'; without
	 * trailing 'Action') should be cached, otherwise it returns FALSE.
	 *
	 * @param string $actionName
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function isCachableAction($actionName) {
		 return !in_array($actionName, $this->nonCachableActions);
	}

	/**
	 * Calls the specified action method and passes the arguments.
	 * If the action returns a string, it is appended to the content in the
	 * response object.
	 *
	 * @param string $actionMethodName Name of the action method
	 * @return void
	 */
	protected function callActionMethod() {
		$actionResult = call_user_func_array(array($this, $this->actionMethodName), array());
		if ($actionResult === NULL && $this->view instanceof Tx_ExtBase_MVC_View_ViewInterface) {
			$this->response->appendContent($this->view->render());
		} elseif (is_string($actionResult) && strlen($actionResult) > 0) {
			$this->response->appendContent($actionResult);
		}
	}

	/**
	 * Prepares a view for the current action and stores it in $this->view.
	 * By default, this method tries to locate a view with a name matching
	 * the current action.
	 *
	 * @return void
	 */
	protected function initializeView() {
		$viewObjectName = ($this->defaultViewObjectName === NULL) ? $this->resolveViewObjectName() : $this->defaultViewObjectName;
		if (!class_exists($viewObjectName)) $viewObjectName = 'Tx_ExtBase_MVC_View_EmptyView';

		$this->view = t3lib_div::makeInstance($viewObjectName);
		$this->view->setRequest($this->request);
	}

	/**
	 * Determines the fully qualified view object name.
	 *
	 * @return string The fully qualified view object name
	 */
	protected function resolveViewObjectName() {
		$possibleViewName = $this->viewObjectNamePattern;
		$extensionName = $this->request->getExtensionName();
		$possibleViewName = str_replace('@extension', $extensionName, $possibleViewName);
		$possibleViewName = str_replace('@controller', $this->request->getControllerName(), $possibleViewName);
		$possibleViewName = str_replace('@action', ucfirst($this->request->getControllerActionName()), $possibleViewName);
		return $possibleViewName;
	}

	/**
	 * Initializes the controller before invoking an action method.
	 *
	 * Override this method to solve tasks which all actions have in
	 * common.
	 *
	 * @return void
	 */
	protected function initializeAction() {
	}

	/**
	 * The default action of this controller.
	 *
	 * This method should always be overridden by the concrete action
	 * controller implementation.
	 *
	 * @return void
	 */
	protected function indexAction() {
		return 'No index action has been implemented yet for this controller.';
	}
}
?>