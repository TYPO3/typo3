<?php
namespace TYPO3\CMS\Extbase\Mvc\Controller;

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

/**
 * The controller context contains information from the controller
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
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
    public function injectFlashMessageService(\TYPO3\CMS\Core\Messaging\FlashMessageService $flashMessageService)
    {
        $this->flashMessageService = $flashMessageService;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Service\ExtensionService $extensionService
     */
    public function injectExtensionService(\TYPO3\CMS\Extbase\Service\ExtensionService $extensionService)
    {
        $this->extensionService = $extensionService;
    }

    /**
     * Set the request of the controller
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Request $request
     * @return void
     */
    public function setRequest(\TYPO3\CMS\Extbase\Mvc\Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get the request of the controller
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Request
     * @api
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the response of the controller
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Response $response
     * @return void
     */
    public function setResponse(\TYPO3\CMS\Extbase\Mvc\Response $response)
    {
        $this->response = $response;
    }

    /**
     * Get the response of the controller
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Request
     * @api
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Set the arguments of the controller
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\Arguments $arguments
     * @return void
     */
    public function setArguments(\TYPO3\CMS\Extbase\Mvc\Controller\Arguments $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * Get the arguments of the controller
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Controller\Arguments
     * @api
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder $uriBuilder
     * @return void
     */
    public function setUriBuilder(\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder $uriBuilder)
    {
        $this->uriBuilder = $uriBuilder;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
     * @api
     */
    public function getUriBuilder()
    {
        return $this->uriBuilder;
    }

    /**
     * @param string $identifier Queue-identifier
     * @return \TYPO3\CMS\Core\Messaging\FlashMessageQueue
     * @api
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
