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

namespace TYPO3\CMS\Fluid\ViewHelpers\Uri;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * A ViewHelper for creating URIs to extbase actions.
 *
 * Examples
 * ========
 *
 * URI to the show-action of the current controller::
 *
 *    <f:uri.action action="show" />
 *
 * ``/page/path/name.html?tx_myextension_plugin[action]=show&tx_myextension_plugin[controller]=Standard&cHash=xyz``
 *
 * Depending on current page, routing and page path configuration.
 */
class ActionViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('action', 'string', 'Target action');
        $this->registerArgument('arguments', 'array', 'Arguments', false, []);
        $this->registerArgument('controller', 'string', 'Target controller. If NULL current controllerName is used');
        $this->registerArgument('extensionName', 'string', 'Target Extension Name (without "tx_" prefix and no underscores). If NULL the current extension name is used');
        $this->registerArgument('pluginName', 'string', 'Target plugin. If empty, the current plugin name is used');
        $this->registerArgument('pageUid', 'int', 'Target page. See TypoLink destination');
        $this->registerArgument('pageType', 'int', 'Type of the target page. See typolink.parameter', false, 0);
        $this->registerArgument('noCache', 'bool', 'Set this to disable caching for the target page. You should not need this.', false, null);
        $this->registerArgument('section', 'string', 'The anchor to be added to the URI', false, '');
        $this->registerArgument('format', 'string', 'The requested format, e.g. ".html', false, '');
        $this->registerArgument('linkAccessRestrictedPages', 'bool', 'If set, links pointing to access restricted pages will still link to the page even though the page cannot be accessed.', false, false);
        $this->registerArgument('additionalParams', 'array', 'additional query parameters that won\'t be prefixed like $arguments (overrule $arguments)', false, []);
        $this->registerArgument('absolute', 'bool', 'If set, an absolute URI is rendered', false, false);
        $this->registerArgument('addQueryString', 'bool', 'If set, the current query parameters will be kept in the URI', false, false);
        $this->registerArgument('argumentsToBeExcludedFromQueryString', 'array', 'arguments to be removed from the URI. Only active if $addQueryString = TRUE', false, []);
        $this->registerArgument('addQueryStringMethod', 'string', 'This argument is not evaluated anymore and will be removed in TYPO3 v12.');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        if (isset($arguments['addQueryStringMethod'])) {
            trigger_error('Using the argument "addQueryStringMethod" in <f:uri.action> ViewHelper has no effect anymore and will be removed in TYPO3 v12. Remove the argument in your fluid template, as it will result in a fatal error.', E_USER_DEPRECATED);
        }
        /** @var int $pageUid */
        $pageUid = $arguments['pageUid'] ?? 0;
        /** @var int $pageType */
        $pageType = $arguments['pageType'] ?? 0;
        /** @var bool $noCache */
        $noCache = $arguments['noCache'] ?? false;
        /** @var string|null $section */
        $section = $arguments['section'] ?? null;
        /** @var string|null $format */
        $format = $arguments['format'] ?? null;
        /** @var bool $linkAccessRestrictedPages */
        $linkAccessRestrictedPages = $arguments['linkAccessRestrictedPages'] ?? false;
        /** @var array|null $additionalParams */
        $additionalParams = $arguments['additionalParams'] ?? null;
        /** @var bool $absolute */
        $absolute = $arguments['absolute'] ?? false;
        /** @var bool $addQueryString */
        $addQueryString = $arguments['addQueryString'] ?? false;
        /** @var array|null $argumentsToBeExcludedFromQueryString */
        $argumentsToBeExcludedFromQueryString = $arguments['argumentsToBeExcludedFromQueryString'] ?? null;
        /** @var string|null $action */
        $action = $arguments['action'] ?? null;
        /** @var string|null $controller */
        $controller = $arguments['controller'] ?? null;
        /** @var string|null $extensionName */
        $extensionName = $arguments['extensionName'] ?? null;
        /** @var string|null $pluginName */
        $pluginName = $arguments['pluginName'] ?? null;
        /** @var array|null $arguments */
        $arguments = $arguments['arguments'] ?? [];

        $uriBuilder = $renderingContext->getUriBuilder();
        $uriBuilder->reset();

        if ($pageUid > 0) {
            $uriBuilder->setTargetPageUid($pageUid);
        }

        if ($pageType > 0) {
            $uriBuilder->setTargetPageType($pageType);
        }

        if ($noCache === true) {
            $uriBuilder->setNoCache($noCache);
        }

        if (is_string($section)) {
            $uriBuilder->setSection($section);
        }

        if (is_string($format)) {
            $uriBuilder->setFormat($format);
        }

        if (is_array($additionalParams)) {
            $uriBuilder->setArguments($additionalParams);
        }

        if ($absolute === true) {
            $uriBuilder->setCreateAbsoluteUri($absolute);
        }

        if ($addQueryString === true) {
            $uriBuilder->setAddQueryString($addQueryString);
        }

        if (is_array($argumentsToBeExcludedFromQueryString)) {
            $uriBuilder->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString);
        }

        if ($linkAccessRestrictedPages === true) {
            $uriBuilder->setLinkAccessRestrictedPages($linkAccessRestrictedPages);
        }

        return $uriBuilder->uriFor($action, $arguments, $controller, $extensionName, $pluginName);
    }
}
