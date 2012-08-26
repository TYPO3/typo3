<?php
namespace TYPO3\CMS\Extbase\Mvc\Web;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Jochen Rau <jochen.rau@typoplanet.de>
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
 * A request handler which can handle web requests invoked by the frontend.
 */
class FrontendRequestHandler extends \TYPO3\CMS\Extbase\Mvc\Web\AbstractRequestHandler {

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\ExtensionService
	 */
	protected $extensionService;

	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Service\ExtensionService $extensionService
	 * @return void
	 */
	public function injectExtensionService(\TYPO3\CMS\Extbase\Service\ExtensionService $extensionService) {
		$this->extensionService = $extensionService;
	}

	/**
	 * Handles the web request. The response will automatically be sent to the client.
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\ResponseInterface|NULL
	 */
	public function handleRequest() {
		$request = $this->requestBuilder->build();
		/** @var $requestHashService \TYPO3\CMS\Extbase\Security\Channel\RequestHashService */
		$requestHashService = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Security\\Channel\\RequestHashService');
		$requestHashService->verifyRequest($request);
		if ($this->extensionService->isActionCacheable(NULL, NULL, $request->getControllerName(), $request->getControllerActionName())) {
			$request->setIsCached(TRUE);
		} else {
			$contentObject = $this->configurationManager->getContentObject();
			if ($contentObject->getUserObjectType() === \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::OBJECTTYPE_USER) {
				$contentObject->convertToUserIntObject();
				// tslib_cObj::convertToUserIntObject() will recreate the object, so we have to stop the request here
				return NULL;
			}
			$request->setIsCached(FALSE);
		}
		/** @var $response \TYPO3\CMS\Extbase\Mvc\ResponseInterface */
		$response = $this->objectManager->create('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Response');
		$this->dispatcher->dispatch($request, $response);
		return $response;
	}

	/**
	 * This request handler can handle any web request.
	 *
	 * @return boolean If the request is a web request, TRUE otherwise FALSE
	 */
	public function canHandleRequest() {
		return TYPO3_MODE === 'FE';
	}

}


?>