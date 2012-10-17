<?php
namespace TYPO3\CMS\Extbase\Mvc\Controller;

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
 * @subpackage MVC\Controller
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 * @api
 */
class ControllerContext {

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Request
	 */
	protected $request;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Response
	 */
	protected $response;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\Arguments
	 */
	protected $arguments;

	/**
	 * @var \TYPO3\CMS\Extbase\Property\MappingResults
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 6.0
	 */
	protected $argumentsMappingResults;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
	 */
	protected $uriBuilder;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\FlashMessages
	 */
	protected $flashMessageContainer;

	/**
	 * Set the request of the controller
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Request $request
	 * @return void
	 */
	public function setRequest(\TYPO3\CMS\Extbase\Mvc\Request $request) {
		$this->request = $request;
	}

	/**
	 * Get the request of the controller
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\Request
	 * @api
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Set the response of the controller
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Response $response
	 * @return void
	 */
	public function setResponse(\TYPO3\CMS\Extbase\Mvc\Response $response) {
		$this->response = $response;
	}

	/**
	 * Get the response of the controller
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\Request
	 * @api
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Set the arguments of the controller
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Controller\Arguments $arguments
	 * @return void
	 */
	public function setArguments(\TYPO3\CMS\Extbase\Mvc\Controller\Arguments $arguments) {
		$this->arguments = $arguments;
	}

	/**
	 * Get the arguments of the controller
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\Controller\Arguments
	 * @api
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Set the arguments mapping results of the controller
	 *
	 * @param \TYPO3\CMS\Extbase\Property\MappingResults $argumentsMappingResults
	 * @return void
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 6.0
	 */
	public function setArgumentsMappingResults(\TYPO3\CMS\Extbase\Property\MappingResults $argumentsMappingResults) {
		$this->argumentsMappingResults = $argumentsMappingResults;
	}

	/**
	 * Get the arguments mapping results of the controller
	 *
	 * @return \TYPO3\CMS\Extbase\Property\MappingResults
	 * @api
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 6.0
	 */
	public function getArgumentsMappingResults() {
		return $this->argumentsMappingResults;
	}

	/**
	 * Tx_Extbase_MVC_Web_Routing_UriBuilder $uriBuilder
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder $uriBuilder
	 * @return void
	 */
	public function setUriBuilder(\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder $uriBuilder) {
		$this->uriBuilder = $uriBuilder;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
	 * @api
	 */
	public function getUriBuilder() {
		return $this->uriBuilder;
	}

	/**
	 * Set the flash messages
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Controller\FlashMessages $flashMessageContainer
	 * @return void
	 */
	public function setFlashMessageContainer(\TYPO3\CMS\Extbase\Mvc\Controller\FlashMessages $flashMessageContainer) {
		$this->flashMessageContainer = $flashMessageContainer;
	}

	/**
	 * Get the flash messages
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\Controller\FlashMessages
	 * @deprecated since Extbase 1.1; will be removed in Extbase 6.0
	 */
	public function getFlashMessages() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->flashMessageContainer;
	}

	/**
	 * Get the flash messages
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\Controller\FlashMessages
	 * @api
	 */
	public function getFlashMessageContainer() {
		return $this->flashMessageContainer;
	}

}


?>