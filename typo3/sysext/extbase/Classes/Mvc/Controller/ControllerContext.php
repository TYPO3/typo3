<?php
namespace TYPO3\CMS\Extbase\Mvc\Controller;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
/**
 * The controller context contains information from the controller
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
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
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	protected $argumentsMappingResults;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
	 */
	protected $uriBuilder;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\FlashMessageContainer
	 */
	protected $flashMessageContainer;

	/**
	 * @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue
	 */
	protected $flashMessageQueue;

	/**
	 * @var \TYPO3\CMS\Core\Messaging\FlashMessageService
	 * @inject
	 */
	protected $flashMessageService;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 * @inject
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\ExtensionService
	 * @inject
	 */
	protected $extensionService;

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
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	public function setArgumentsMappingResults(\TYPO3\CMS\Extbase\Property\MappingResults $argumentsMappingResults) {
		$this->argumentsMappingResults = $argumentsMappingResults;
	}

	/**
	 * Get the arguments mapping results of the controller
	 *
	 * @return \TYPO3\CMS\Extbase\Property\MappingResults
	 * @api
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	public function getArgumentsMappingResults() {
		return $this->argumentsMappingResults;
	}

	/**
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
	 * @param \TYPO3\CMS\Extbase\Mvc\Controller\FlashMessageContainer $flashMessageContainer
	 * @deprecated since 6.1, will be removed 2 versions later
	 * @return void
	 */
	public function setFlashMessageContainer(\TYPO3\CMS\Extbase\Mvc\Controller\FlashMessageContainer $flashMessageContainer) {
		$this->flashMessageContainer = $flashMessageContainer;
		$flashMessageContainer->setControllerContext($this);
	}

	/**
	 * Get the flash messages
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\Controller\FlashMessageContainer
	 * @deprecated since 6.1, will be removed 2 versions later
	 */
	public function getFlashMessageContainer() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->flashMessageContainer;
	}

	/**
	 * @return \TYPO3\CMS\Core\Messaging\FlashMessageQueue
	 * @api
	 */
	public function getFlashMessageQueue() {
		if (!$this->flashMessageQueue instanceof \TYPO3\CMS\Core\Messaging\FlashMessageQueue) {
			if ($this->useLegacyFlashMessageHandling()) {
				$this->flashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
			} else {
				$this->flashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier(
					'extbase.flashmessages.' . $this->extensionService->getPluginNamespace($this->request->getControllerExtensionName(), $this->request->getPluginName())
				);
			}
		}

		return $this->flashMessageQueue;
	}

	/**
	 * @deprecated since 6.1, will be removed 2 versions later
	 * @return boolean
	 */
	public function useLegacyFlashMessageHandling() {
		return (boolean) \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath(
			$this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK),
			'legacy.enableLegacyFlashMessageHandling'
		);
	}
}
