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

/**
 * A view helper for creating URIs to extbase actions within widgets.
 *
 * = Examples =
 *
 * <code title="URI to the show-action of the current controller">
 * <f:widget.uri action="show" />
 * </code>
 * <output>
 * index.php?id=123&tx_myextension_plugin[widgetIdentifier][action]=show&tx_myextension_plugin[widgetIdentifier][controller]=Standard&cHash=xyz
 * (depending on the current page, widget and your TS configuration)
 * </output>
 *
 * @api
 */
class UriViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * Initialize arguments
     *
     * @api
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('useCacheHash', 'bool', 'True whether the cache hash should be appended to the URL', false, true);
        $this->registerArgument('addQueryStringMethod', 'string', 'Method to be used for query string');
        $this->registerArgument('action', 'string', 'Target action');
        $this->registerArgument('arguments', 'array', 'Arguments', false, []);
        $this->registerArgument('section', 'string', 'The anchor to be added to the URI', false, '');
        $this->registerArgument('format', 'string', 'The requested format, e.g. ".html', false, '');
        $this->registerArgument('ajax', 'bool', 'TRUE if the URI should be to an AJAX widget, FALSE otherwise.', false, false);
    }

    /**
     * Render the Uri.
     *
     * @return string The rendered link
     * @api
     */
    public function render()
    {
        $ajax = $this->arguments['ajax'];

        if ($ajax === true) {
            return $this->getAjaxUri();
        }
        return $this->getWidgetUri();

        return static::getWidgetUri($renderingContext, $arguments);
    }

    /**
     * Get the URI for an AJAX Request.
     *
     * @return string the AJAX URI
     */
    protected function getAjaxUri()
    {
        $action = $this->arguments['action'];
        $arguments = $this->arguments['arguments'];
        if ($action === null) {
            $action = $this->controllerContext->getRequest()->getControllerActionName();
        }
        $arguments['id'] = $GLOBALS['TSFE']->id;
        // @todo page type should be configurable
        $arguments['type'] = 7076;
        $arguments['fluid-widget-id'] = $this->controllerContext->getRequest()->getWidgetContext()->getAjaxWidgetIdentifier();
        $arguments['action'] = $action;
        return '?' . http_build_query($arguments, null, '&');
    }

    /**
     * Get the URI for a non-AJAX Request.
     *
     * @return string the Widget URI
     */
    protected function getWidgetUri()
    {
        $uriBuilder = $this->controllerContext->getUriBuilder();
        $argumentPrefix = $this->controllerContext->getRequest()->getArgumentPrefix();
        $arguments = $this->hasArgument('arguments') ? $this->arguments['arguments'] : [];
        if ($this->hasArgument('action')) {
            $arguments['action'] = $this->arguments['action'];
        }
        if ($this->hasArgument('format') && $this->arguments['format'] !== '') {
            $arguments['format'] = $this->arguments['format'];
        }
        return $uriBuilder->reset()
            ->setArguments([$argumentPrefix => $arguments])
            ->setSection($this->arguments['section'])
            ->setUseCacheHash($this->arguments['useCacheHash'])
            ->setAddQueryString(true)
            ->setAddQueryStringMethod($this->arguments['addQueryStringMethod'])
            ->setArgumentsToBeExcludedFromQueryString([$argumentPrefix, 'cHash'])
            ->setFormat($this->arguments['format'])
            ->build();
    }
}
