<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Enter descriptions here
 *
 * @package $PACKAGE$
 * @subpackage $SUBPACKAGE$
 * @scope prototype
 * @entity
 * @api
 */
class t3lib_webservice_simpleWebserviceFixture implements t3lib_webservice_WebserviceInterface {

	/**
	 * Contains request
	 *
	 * @var t3lib_webservice_Request
	 */
	protected $request;

	/**
	 * Contains response
	 *
	 * @var t3lib_webservice_Response
	 */
	protected $response;

	/**
	 * Setter for the webservice request
	 * @param t3lib_webservice_Request $request
	 * @return void
	 */
	public function setRequest(t3lib_webservice_Request $request) {
		$this->request = $request;
	}

	/**
	 * Setter for the webservice response
	 * @param t3lib_webservice_Response $response
	 * @return void
	 */
	public function setResponse(t3lib_webservice_Response $response) {
		$this->response = $response;
	}

	/**
	 * This method runs the actual webservice after request/response have been set
	 * @return void
	 */
	public function run() {
		$this->response->appendToBody(implode(',', $this->request->getResolvedArguments()));
	}

}
