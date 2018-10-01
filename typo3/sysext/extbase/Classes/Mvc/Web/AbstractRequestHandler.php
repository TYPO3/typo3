<?php
namespace TYPO3\CMS\Extbase\Mvc\Web;

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
 * A request handler which can handle web requests.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
abstract class AbstractRequestHandler implements \TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface
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
     * @var \TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder
     */
    protected $requestBuilder;

    /**
     * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
     */
    protected $environmentService;

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\Dispatcher $dispatcher
     */
    public function injectDispatcher(\TYPO3\CMS\Extbase\Mvc\Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder $requestBuilder
     */
    public function injectRequestBuilder(\TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder $requestBuilder)
    {
        $this->requestBuilder = $requestBuilder;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService
     */
    public function injectEnvironmentService(\TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService)
    {
        $this->environmentService = $environmentService;
    }

    /**
     * This request handler can handle any web request.
     *
     * @return bool If the request is a web request, TRUE otherwise FALSE
     */
    public function canHandleRequest()
    {
        return true;
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
