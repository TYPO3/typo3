<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Controller;

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
 * @package FLOW3
 * @subpackage MVC
 * @version $Id: F3_FLOW3_MVC_Controller_DefaultController.php 1749 2009-01-15 15:06:30Z k-fish $
 */

/**
 * A Special Case of a Controller: If no controller could be resolved or no
 * controller has been specified in the request, this controller is chosen.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id: F3_FLOW3_MVC_Controller_DefaultController.php 1749 2009-01-15 15:06:30Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DefaultController extends \F3\FLOW3\MVC\Controller\RequestHandlingController {

	/**
	 * @var \F3\FLOW3\MVC\View\DefaultView
	 */
	protected $defaultView;

	/**
	 * Injects the default view
	 *
	 * @param \F3\FLOW3\MVC\View\DefaultView $defaultView The default view
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectDefaultView(\F3\FLOW3\MVC\View\DefaultView $defaultView) {
		$this->defaultView = $defaultView;
	}

	/**
	 * Processes a generic request and returns a response
	 *
	 * @param \F3\FLOW3\MVC\Request $request: The request
	 * @param \F3\FLOW3\MVC\Response $response: The response
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequest(\F3\FLOW3\MVC\Request $request, \F3\FLOW3\MVC\Response $response) {
		$request->setDispatched(TRUE);
		switch (get_class($request)) {
			case 'F3\FLOW3\MVC\Web\Request' :
				$this->processWebRequest($request, $response);
				break;
			default :
				$response->setContent(
					"\nWelcome to FLOW3!\n\n" .
					"This is the default view of the FLOW3 MVC object. You see this message because no \n" .
					"other view is available. Please refer to the Developer's Guide for more information \n" .
					"how to create and configure one.\n\n" .
					"Have fun! The FLOW3 Development Team\n"
				);
		}
	}

	/**
	 * Processes a web request and returns a response
	 *
	 * @param \F3\FLOW3\MVC\Web\Request $request: The request
	 * @param \F3\FLOW3\MVC\Web\Response $response: The response
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function processWebRequest(\F3\FLOW3\MVC\Web\Request $request, \F3\FLOW3\MVC\Web\Response $response) {
		$this->defaultView->setRequest($request);
		$response->setContent($this->defaultView->render());
	}

}

?>