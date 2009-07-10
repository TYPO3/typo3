<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * The controller context contains information from the controller
 *
 * @package Extbase
 * @subpackage MVC
 * @version $Id: AbstractController.php 2203 2009-05-12 18:44:47Z networkteam_hlubek $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Tx_Extbase_MVC_Controller_ControllerContext {

	/**
	 * @var Tx_Extbase_MVC_Request
	 */
	protected $request;

	/**
	 * @var Tx_Extbase_MVC_Response
	 */
	protected $response;

	/**
	 * @var Tx_Extbase_MVC_Controller_Arguments
	 */
	protected $arguments;

	/**
	 * @var Tx_Extbase_Property_MappingResults
	 */
	protected $argumentsMappingResults;

	/**
	 * @var Tx_Extbase_MVC_Web_Routing_URIBuilder
	 */
	protected $URIBuilder;

	/**
	 * Set the request of the controller
	 *
	 * @param Tx_Extbase_MVC_Request $request
	 * @return void
	 * @internal
	 */
	public function setRequest(Tx_Extbase_MVC_Request $request) {
		$this->request = $request;
	}

	/**
	 * Get the request of the controller
	 *
	 * @return Tx_Extbase_MVC_Request
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Set the response of the controller
	 *
	 * @param Tx_Extbase_MVC_Response $request
	 * @return void
	 * @internal
	 */
	public function setResponse(Tx_Extbase_MVC_Response $response) {
		$this->response = $response;
	}

	/**
	 * Get the response of the controller
	 *
	 * @return Tx_Extbase_MVC_Request
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Set the arguments of the controller
	 *
	 * @param Tx_Extbase_MVC_Controller_Arguments $arguments
	 * @return void
	 * @internal
	 */
	public function setArguments(Tx_Extbase_MVC_Controller_Arguments $arguments) {
		$this->arguments = $arguments;
	}

	/**
	 * Get the arguments of the controller
	 *
	 * @return Tx_Extbase_MVC_Controller_Arguments
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Set the arguments mapping results of the controller
	 *
	 * @param Tx_Extbase_Property_MappingResults $argumentsMappingResults
	 * @return void
	 * @internal
	 */
	public function setArgumentsMappingResults(Tx_Extbase_Property_MappingResults $argumentsMappingResults) {
		$this->argumentsMappingResults = $argumentsMappingResults;
	}

	/**
	 * Get the arguments mapping results of the controller
	 *
	 * @return Tx_Extbase_Property_MappingResults
	 */
	public function getArgumentsMappingResults() {
		return $this->argumentsMappingResults;
	}

	/**
	 * Tx_Extbase_MVC_Web_Routing_URIBuilder $URIBuilder
	 * @return void
	 * @internal
	 */
	public function setURIBuilder(Tx_Extbase_MVC_Web_Routing_URIBuilder $URIBuilder) {
		$this->URIBuilder = $URIBuilder;
	}

	/**
	 * @return Tx_Extbase_MVC_Web_Routing_URIBuilder
	 */
	public function getURIBuilder() {
		return $this->URIBuilder;
	}

}
?>