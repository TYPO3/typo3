<?php

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Controller/TX_EXTMVC_Controller_AbstractController.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Exception/TX_EXTMVC_Exception_StopUncachedAction.php');

/**
 * A multi action controller
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_Controller_ActionController extends TX_EXTMVC_Controller_AbstractController {

	/**
	 * @var boolean If initializeView() should be called on an action invocation.
	 */
	protected $initializeView = TRUE;

	/**
	 * @var TX_EXTMVC_View_AbstractView By default a view with the same name as the current action is provided. Contains NULL if none was found.
	 */
	protected $view = NULL;

	/**
	 * By default a matching view will be resolved. If this property is set, automatic resolving is disabled and the specified object is used instead.
	 * @var string
	 */
	protected $viewObjectName = NULL;

	/**
	 * Pattern after which the view object name is built
	 *
	 * @var string
	 */
	protected $viewObjectNamePattern = 'TX_@extension_View_@controller@action';

	/**
	 * Name of the action method
	 * @var string
	 */
	protected $actionMethodName = 'indexAction';
	
	/**
	 * Actions that schould not be cached (changes the invocated dispatcher to a USER_INT cObject)
	 * @var array
	 */
	protected $nonCachableActions = array();

	/**
	 * Handles a request. The result output is returned by altering the given response.
	 *
	 * @param TX_EXTMVC_Request $request The request object
	 * @param TX_EXTMVC_Response $response The response, modified by this handler
	 * @return void
	 */
	public function processRequest(TX_EXTMVC_Request $request, TX_EXTMVC_Response $response) {
		$this->request = $request;
		$this->request->setDispatched(TRUE);
		$this->response = $response;

		$this->actionMethodName = $this->resolveActionMethodName();
		$this->initializeArguments();
		$this->mapRequestArgumentsToLocalArguments();
		if ($this->initializeView) $this->initializeView();
		$this->initializeAction();

		$this->callActionMethod();
		if (in_array($this->request->getControllerActionName(), $this->nonCachableActions)) {
			throw new TX_EXTMVC_Exception_StopUncachedAction();
		}
	}

	/**
	 * Determines the action method and assures that the method exists.
	 *
	 * @return string The action method name
	 * @throws TX_EXTMVC_Exception_NoSuchAction if the action specified in the request object does not exist (and if there's no default action either).
	 */
	protected function resolveActionMethodName() {
		$actionMethodName = $this->request->getControllerActionName() . 'Action';
		if (!method_exists($this, $actionMethodName)) throw new TX_EXTMVC_Exception_NoSuchAction('An action "' . $actionMethodName . '" does not exist in controller "' . get_class($this) . '".', 1186669086);
		return $actionMethodName;
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
		$preparedArguments = array();
		// foreach ($this->arguments as $argument) {
		// 	$preparedArguments[] = $argument->getValue();
		// }

		$actionResult = call_user_func_array(array($this, $this->actionMethodName), $preparedArguments);
		if ($actionResult === NULL && $this->view instanceof TX_EXTMVC_View_ViewInterface) {
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
		// TODO Reslove View Object name
		$viewObjectName = ($this->viewObjectName === NULL) ? $this->resolveViewObjectName() : $this->viewObjectName;
		if ($viewObjectName === FALSE) $viewObjectName = 'TX_EXTMVC_View_EmptyView';

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
		$extensionKey = $this->request->getControllerExtensionKey();
		$possibleViewName = str_replace('@extension', $extensionKey, $possibleViewName);		
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