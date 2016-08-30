<?php
namespace TYPO3\CMS\Extbase\Mvc\Cli;

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
 * The generic command line interface request handler for the MVC framework.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestHandler implements \TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Dispatcher
     */
    protected $dispatcher;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Cli\RequestBuilder
     */
    protected $requestBuilder;

    /**
     * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
     */
    protected $environmentService;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\Dispatcher $dispatcher
     */
    public function injectDispatcher(\TYPO3\CMS\Extbase\Mvc\Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\Cli\RequestBuilder $requestBuilder
     */
    public function injectRequestBuilder(\TYPO3\CMS\Extbase\Mvc\Cli\RequestBuilder $requestBuilder)
    {
        $this->requestBuilder = $requestBuilder;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService
     */
    public function injectEnvironmentService(\TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService)
    {
        $this->environmentService = $environmentService;
    }

    /**
     * Handles the request
     *
     * @return \TYPO3\CMS\Extbase\Mvc\ResponseInterface
     */
    public function handleRequest()
    {
        $commandLine = isset($_SERVER['argv']) ? $_SERVER['argv'] : [];
        $callingScript = array_shift($commandLine);
        if ($callingScript !== $_SERVER['_']) {
            $callingScript = $_SERVER['_'] . ' ' . $callingScript;
        }
        $request = $this->requestBuilder->build($commandLine, $callingScript . ' extbase');
        /** @var $response \TYPO3\CMS\Extbase\Mvc\Cli\Response */
        $response = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Cli\Response::class);
        $this->dispatcher->dispatch($request, $response);
        $response->send();
        return $response;
    }

    /**
     * This request handler can handle any command line request.
     *
     * @return bool If the request is a command line request, TRUE otherwise FALSE
     */
    public function canHandleRequest()
    {
        return $this->environmentService->isEnvironmentInCliMode();
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority()
    {
        return 100;
    }
}
