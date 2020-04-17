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

namespace TYPO3\CMS\Fluid\Core\Widget;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Web\AbstractRequestHandler;
use TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder;
use TYPO3\CMS\Extbase\Mvc\Web\Response;

/**
 * Widget request handler, which handles the request if
 * f3-fluid-widget-id is found.
 *
 * This Request Handler gets the WidgetRequestBuilder injected.
 * @internal It is a purely internal class which should not be used outside of Fluid.
 */
class WidgetRequestHandler extends AbstractRequestHandler
{
    /**
     * @var \TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder
     */
    protected $ajaxWidgetContextHolder;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var \TYPO3\CMS\Fluid\Core\Widget\WidgetRequestBuilder
     */
    protected $requestBuilder;

    /**
     * @param \TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder $ajaxWidgetContextHolder
     */
    public function injectAjaxWidgetContextHolder(AjaxWidgetContextHolder $ajaxWidgetContextHolder)
    {
        $this->ajaxWidgetContextHolder = $ajaxWidgetContextHolder;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder $requestBuilder
     */
    public function injectRequestBuilder(RequestBuilder $requestBuilder)
    {
        // This method intentionally left blank
    }

    /**
     * @param \TYPO3\CMS\Fluid\Core\Widget\WidgetRequestBuilder $requestBuilder
     */
    public function injectWidgetRequestBuilder(WidgetRequestBuilder $requestBuilder)
    {
        $this->requestBuilder = $requestBuilder;
    }

    /**
     * Handles the web request. The response will automatically be sent to the client.
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Response
     */
    public function handleRequest()
    {
        $request = $this->requestBuilder->build();
        $response = $this->objectManager->get(Response::class);
        $this->dispatcher->dispatch($request, $response);
        return $response;
    }

    /**
     * @return bool TRUE if it is an AJAX widget request
     */
    public function canHandleRequest()
    {
        $rawGetArguments = GeneralUtility::_GET();
        return isset($rawGetArguments['fluid-widget-id']);
    }

    /**
     * This request handler has a higher priority than the default request handler.
     *
     * @return int
     */
    public function getPriority()
    {
        return 200;
    }
}
