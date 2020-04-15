<?php

/*
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

namespace TYPO3\CMS\Extbase\Mvc\Controller;

use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Service\ExtensionService;

/**
 * The controller context contains information from the controller
 */
class ControllerContext
{
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
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
     */
    protected $uriBuilder;

    /**
     * @var string
     */
    protected $flashMessageQueueDefaultIdentifier;

    /**
     * @var \TYPO3\CMS\Core\Messaging\FlashMessageService
     */
    protected $flashMessageService;

    /**
     * @var \TYPO3\CMS\Extbase\Service\ExtensionService
     */
    protected $extensionService;

    /**
     * @param \TYPO3\CMS\Core\Messaging\FlashMessageService $flashMessageService
     */
    public function injectFlashMessageService(FlashMessageService $flashMessageService)
    {
        $this->flashMessageService = $flashMessageService;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Service\ExtensionService $extensionService
     */
    public function injectExtensionService(ExtensionService $extensionService)
    {
        $this->extensionService = $extensionService;
    }

    /**
     * Set the request of the controller
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get the request of the controller
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the response of the controller
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Get the response of the controller
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Set the arguments of the controller
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\Arguments $arguments
     */
    public function setArguments(Arguments $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * Get the arguments of the controller
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Controller\Arguments
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder $uriBuilder
     */
    public function setUriBuilder(UriBuilder $uriBuilder)
    {
        $this->uriBuilder = $uriBuilder;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
     */
    public function getUriBuilder()
    {
        return $this->uriBuilder;
    }

    /**
     * @param string $identifier Queue-identifier
     * @return \TYPO3\CMS\Core\Messaging\FlashMessageQueue
     */
    public function getFlashMessageQueue($identifier = null)
    {
        if ($identifier === null) {
            if ($this->flashMessageQueueDefaultIdentifier === null) {
                // cache the default-identifier for performance-reasons
                $this->flashMessageQueueDefaultIdentifier = 'extbase.flashmessages.' . $this->extensionService->getPluginNamespace($this->request->getControllerExtensionName(), $this->request->getPluginName());
            }
            $identifier = $this->flashMessageQueueDefaultIdentifier;
        }
        return $this->flashMessageService->getMessageQueueByIdentifier($identifier);
    }
}
