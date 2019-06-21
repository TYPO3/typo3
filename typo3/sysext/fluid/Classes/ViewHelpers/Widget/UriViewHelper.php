<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Widget;

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

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * A ViewHelper for creating URIs to Extbase actions within widgets.
 *
 * Examples
 * ========
 *
 * URI to the show-action of the current controller::
 *
 *    <f:widget.uri action="show" />
 *
 * ``/page/path/name.html?tx_myextension_plugin[widgetIdentifier][action]=show&tx_myextension_plugin[widgetIdentifier][controller]=Standard&cHash=xyz``
 *
 * Depending on current page, routing and page path configuration.
 */
class UriViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('useCacheHash', 'bool', 'True whether the cache hash should be appended to the URL', false, false);
        $this->registerArgument('addQueryStringMethod', 'string', 'Method to be used for query string');
        $this->registerArgument('action', 'string', 'Target action');
        $this->registerArgument('arguments', 'array', 'Arguments', false, []);
        $this->registerArgument('section', 'string', 'The anchor to be added to the URI', false, '');
        $this->registerArgument('format', 'string', 'The requested format, e.g. ".html', false, '');
        $this->registerArgument('ajax', 'bool', 'TRUE if the URI should be to an AJAX widget, FALSE otherwise.', false, false);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $ajax = $arguments['ajax'];

        if ($ajax === true) {
            return static::getAjaxUri($renderingContext, $arguments);
        }
        return static::getWidgetUri($renderingContext, $arguments);
    }

    /**
     * Get the URI for an AJAX Request.
     *
     * @param RenderingContextInterface $renderingContext
     * @param array $arguments
     * @return string the AJAX URI
     */
    protected static function getAjaxUri(RenderingContextInterface $renderingContext, array $arguments)
    {
        $controllerContext = $renderingContext->getControllerContext();
        $action = $arguments['action'];
        $arguments = $arguments['arguments'];
        if ($action === null) {
            $action = $controllerContext->getRequest()->getControllerActionName();
        }
        $arguments['id'] = $GLOBALS['TSFE']->id;
        // @todo page type should be configurable
        $arguments['type'] = 7076;
        $arguments['fluid-widget-id'] = $controllerContext->getRequest()->getWidgetContext()->getAjaxWidgetIdentifier();
        $arguments['action'] = $action;
        return '?' . http_build_query($arguments, null, '&');
    }

    /**
     * Get the URI for a non-AJAX Request.
     *
     * @param RenderingContextInterface $renderingContext
     * @param array $arguments
     * @return string the Widget URI
     */
    protected static function getWidgetUri(RenderingContextInterface $renderingContext, array $arguments)
    {
        $controllerContext = $renderingContext->getControllerContext();
        $uriBuilder = $controllerContext->getUriBuilder();
        $argumentPrefix = $controllerContext->getRequest()->getArgumentPrefix();
        $parameters = $arguments['arguments'] ?? [];
        if ($arguments['action'] ?? false) {
            $parameters['action'] = $arguments['action'];
        }
        if (($arguments['format'] ?? '') !== '') {
            $parameters['format'] = $arguments['format'];
        }
        return $uriBuilder->reset()
            ->setArguments([$argumentPrefix => $parameters])
            ->setSection($arguments['section'])
            ->setUseCacheHash($arguments['useCacheHash'])
            ->setAddQueryString(true)
            ->setAddQueryStringMethod($arguments['addQueryStringMethod'])
            ->setArgumentsToBeExcludedFromQueryString([$argumentPrefix, 'cHash'])
            ->setFormat($arguments['format'])
            ->build();
    }
}
