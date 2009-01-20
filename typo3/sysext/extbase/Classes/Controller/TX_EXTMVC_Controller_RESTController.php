<?php
declare(ENCODING = 'utf-8');

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

/**
 * An action controller for RESTful web services
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RESTController extends TX_EXTMVC_Controller_ActionController {

	/**
	 * @var TX_EXTMVC_Web_Request The current request
	 */
	protected $request;

	/**
	 * @var TX_EXTMVC_Web_Response The response which will be returned by this action controller
	 */
	protected $response;

	/**
	 * Handles a web request. The result output is returned by altering the given response.
	 *
	 * Note that this REST controller only supports web requests!
	 *
	 * @param TX_EXTMVC_Web_Request $request The request object
	 * @param TX_EXTMVC_Web_Response $response The response, modified by this handler
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequest(TX_EXTMVC_Request $request, TX_EXTMVC_Response $response) {
		if (!($request instanceof TX_EXTMVC_Web_Request) || !($response instanceof TX_EXTMVC_Web_Response)) {
			throw new TX_EXTMVC_Exception_InvalidRequestType('This RESTController only supports web requests.', 1226665171);
		}
		parent::processRequest($request, $response);
	}

	/**
	 * Determines the name of the requested action and calls the action method accordingly.
	 * This implementation analyzes the HTTP request and chooses a matching REST-style action.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws TX_EXTMVC_Exception_NoSuchAction if the action specified in the request object does not exist (and if there's no default action either).
	 */
	protected function callActionMethod() {
		if ($this->arguments['id']->isValid() === FALSE) $this->throwStatus(400);

		if ($this->request->getControllerActionName() == 'index') {
			$actionName = 'index';
			switch ($this->request->getMethod()) {
				case F3_FLOW3_Utility_Environment::REQUEST_METHOD_GET :
					$actionName = ($this->arguments['id']->getValue() === NULL) ? 'list' : 'show';
				break;
				case F3_FLOW3_Utility_Environment::REQUEST_METHOD_POST :
					$actionName = 'create';
				break;
				case F3_FLOW3_Utility_Environment::REQUEST_METHOD_PUT :
					if ($this->arguments['id']->getValue() === NULL) $this->throwStatus(400, NULL, 'Invalid identifier');
					$actionName = 'update';
				break;
				case F3_FLOW3_Utility_Environment::REQUEST_METHOD_DELETE :
					$actionName = 'delete';
				break;
			}
			$this->request->setControllerActionName($actionName);
		}
		parent::callActionMethod();
	}

	/**
	 * Initializes (registers / defines) arguments of this controller.
	 *
	 * Override this method to add arguments which can later be accessed
	 * by the action methods.
	 *
	 * NOTE: If you override this method, don't forget to call the parent
	 * method as well (or define the identifier own your own).
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeArguments() {
		$this->arguments->addNewArgument('id', 'UUID');
	}
}
?>