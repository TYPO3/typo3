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
 * A Special Case of a Controller: If no controller could be resolved or no
 * controller has been specified in the request, this controller is chosen.
 *
 * @package TYPO3
 * @subpackage extbase
 * @version $ID:$
 */
class Tx_ExtBase_MVC_Controller_DefaultController extends Tx_ExtBase_MVC_Controller_ActionController {

	/**
	 * @var Tx_ExtBase_MVC_View_DefaultView
	 */
	protected $defaultView;

	/**
	 * Injects the default view
	 *
	 * @param Tx_ExtBase_MVC_View_DefaultView $defaultView The default view
	 * @return void
	 */
	public function injectDefaultView(Tx_ExtBase_MVC_View_DefaultView $defaultView) {
		$this->defaultView = $defaultView;
	}

	/**
	 * Processes a generic request and returns a response
	 *
	 * @param Tx_ExtBase_MVC_Request $request: The request
	 * @param Tx_ExtBase_MVC_Response $response: The response
	 */
	public function processRequest(Tx_ExtBase_MVC_Request $request, Tx_ExtBase_MVC_Response $response) {
		$request->setDispatched(TRUE);
		switch (get_class($request)) {
			case 'Tx_ExtBase_MVC_Web_Request' :
				$this->processWebRequest($request, $response);
				break;
			default :
				$response->setContent(
					"\nWelcome to TYPO3!\n\n" .
					"This is the default view of the TYPO3 MVC object. You see this message because no \n" .
					"other view is available. Please refer to the Developer's Guide for more information \n" .
					"how to create and configure one.\n\n" .
					"Have fun! The TYPO3 Development Team\n"
				);
		}
	}

	/**
	 * Processes a web request and returns a response
	 *
	 * @param Tx_ExtBase_MVC_Web_Request $request: The request
	 * @param Tx_ExtBase_MVC_Web_Response $response: The response
	 */
	protected function processWebRequest(Tx_ExtBase_MVC_Web_Request $request, Tx_ExtBase_MVC_Web_Response $response) {
		$this->defaultView->setRequest($request);
		$response->setContent($this->defaultView->render());
	}

}

?>