<?php
namespace TYPO3\CMS\Extbase\Mvc\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 * An action controller for RESTful web services
 */
class RestController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {
	/**
	 * The current request.
	 *
	 * @var \TYPO3\CMS\Extbase\Mvc\Web\Request
	 * @api
	 */
	protected $request;

	/**
	 * Name of the action method argument which acts as the resource for the
	 * RESTful controller. If an argument with the specified name is passed
	 * to the controller, the show, update and delete actions can be triggered
	 * automatically.
	 *
	 * @var string
	 */
	protected $resourceArgumentName = 'resource';

	/**
	 * Determines the action method and assures that the method exists.
	 *
	 * @return string The action method name
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchActionException if the action specified in the request object does not exist (and if there's no default action either).
	 */
	protected function resolveActionMethodName() {
		if ($this->request->getControllerActionName() === 'index') {
			$actionName = 'index';
			switch ($this->request->getMethod()) {
				case 'HEAD':
				case 'GET' :
					$actionName = ($this->request->hasArgument($this->resourceArgumentName)) ? 'show' : 'list';
				break;
				case 'POST' :
					$actionName = 'create';
				break;
				case 'PUT' :
					if (!$this->request->hasArgument($this->resourceArgumentName)) {
						$this->throwStatus(400, NULL, 'No resource specified');
					}
					$actionName = 'update';
				break;
				case 'DELETE' :
					if (!$this->request->hasArgument($this->resourceArgumentName)) {
						$this->throwStatus(400, NULL, 'No resource specified');
					}
					$actionName = 'delete';
				break;
			}
			$this->request->setControllerActionName($actionName);
		}
		return parent::resolveActionMethodName();
	}

	/**
	 * Allow creation of resources in createAction()
	 *
	 * @return void
	 */
	public function initializeCreateAction() {
		$propertyMappingConfiguration = $this->arguments[$this->resourceArgumentName]->getPropertyMappingConfiguration();
		$propertyMappingConfiguration->setTypeConverterOption('TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\PersistentObjectConverter', \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE);
		$propertyMappingConfiguration->allowAllProperties();
	}

	/**
	 * Allow modification of resources in updateAction()
	 *
	 * @return void
	 */
	public function initializeUpdateAction() {
		$propertyMappingConfiguration = $this->arguments[$this->resourceArgumentName]->getPropertyMappingConfiguration();
		$propertyMappingConfiguration->setTypeConverterOption('TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\PersistentObjectConverter', \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, TRUE);
		$propertyMappingConfiguration->allowAllProperties();
	}

	/**
	 * Redirects the web request to another uri.
	 *
	 * NOTE: This method only supports web requests and will throw an exception
	 * if used with other request types.
	 *
	 * @param mixed $uri Either a string representation of a URI or a \TYPO3\CMS\Extbase\Http\Uri object
	 * @param integer $delay (optional) The delay in seconds. Default is no delay.
	 * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
	 * @return void
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
	 * @api
	 */
	protected function redirectToUri($uri, $delay = 0, $statusCode = 303) {
			// the parent method throws the exception, but we need to act afterwards
			// thus the code in catch - it's the expected state
		try {
			parent::redirectToUri($uri, $delay, $statusCode);
		} catch (\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException $exception) {
			if ($this->request->getFormat() === 'json') {
				$this->response->setContent('');
			}
			throw $exception;
		}
	}
}
?>