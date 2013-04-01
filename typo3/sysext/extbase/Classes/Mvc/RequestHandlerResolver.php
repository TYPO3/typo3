<?php
namespace TYPO3\CMS\Extbase\Mvc;

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
 * Analyzes the raw request and delivers a request handler which can handle it.
 */
class RequestHandlerResolver {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * Injects the object manager
	 *
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager A reference to the object manager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the reflection service
	 *
	 * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the configuration manager
	 *
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Analyzes the raw request and tries to find a request handler which can handle
	 * it. If none is found, an exception is thrown.
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface A request handler
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception
	 */
	public function resolveRequestHandler() {
		$availableRequestHandlerClassNames = $this->getRegisteredRequestHandlerClassNames();
		$suitableRequestHandlers = array();
		foreach ($availableRequestHandlerClassNames as $requestHandlerClassName) {
			$requestHandler = $this->objectManager->get($requestHandlerClassName);
			if ($requestHandler->canHandleRequest()) {
				$priority = $requestHandler->getPriority();
				if (isset($suitableRequestHandlers[$priority])) {
					throw new \TYPO3\CMS\Extbase\Mvc\Exception('More than one request handler with the same priority can handle the request, but only one handler may be active at a time!', 1176475350);
				}
				$suitableRequestHandlers[$priority] = $requestHandler;
			}
		}
		if (count($suitableRequestHandlers) === 0) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception('No suitable request handler found.', 1205414233);
		}
		ksort($suitableRequestHandlers);
		return array_pop($suitableRequestHandlers);
	}

	/**
	 * Returns a list of all registered request handlers.
	 *
	 * @return array
	 */
	public function getRegisteredRequestHandlerClassNames() {
		$settings = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		return is_array($settings['mvc']['requestHandlers']) ? $settings['mvc']['requestHandlers'] : array();
	}
}

?>