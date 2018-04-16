<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Fluid\Core\Widget;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder;

/**
 * Builds the WidgetRequest if an AJAX widget is called.
 */
class WidgetRequestBuilder extends RequestBuilder
{
    /**
     * @var AjaxWidgetContextHolder
     */
    private $ajaxWidgetContextHolder;

    /**
     * @param AjaxWidgetContextHolder $ajaxWidgetContextHolder
     */
    public function injectAjaxWidgetContextHolder(AjaxWidgetContextHolder $ajaxWidgetContextHolder)
    {
        $this->ajaxWidgetContextHolder = $ajaxWidgetContextHolder;
    }

    /**
     * Builds a widget request object from the raw HTTP information
     *
     * @return RequestInterface The widget request as an object
     */
    public function build(): RequestInterface
    {
        $request = $this->objectManager->get(WidgetRequest::class);
        $request->setRequestUri(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
        $request->setBaseUri(GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
        $request->setMethod($_SERVER['REQUEST_METHOD'] ?? null);
        if (strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $request->setArguments(GeneralUtility::_POST());
        } else {
            $request->setArguments(GeneralUtility::_GET());
        }
        $rawGetArguments = GeneralUtility::_GET();
        if (isset($rawGetArguments['action'])) {
            $request->setControllerActionName($rawGetArguments['action']);
        }
        if (!isset($rawGetArguments['fluid-widget-id'])) {
            // Low level test, WidgetRequestHandler returns false in canHandleRequest () if this is not set
            throw new \InvalidArgumentException(
                'No Fluid Widget ID was given.',
                1521190675
            );
        }
        $widgetContext = $this->ajaxWidgetContextHolder->get($rawGetArguments['fluid-widget-id']);
        $request->setWidgetContext($widgetContext);
        return $request;
    }
}
