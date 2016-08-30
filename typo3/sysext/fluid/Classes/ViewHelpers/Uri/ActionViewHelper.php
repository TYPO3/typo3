<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Uri;

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
 * A view helper for creating URIs to extbase actions.
 *
 * = Examples =
 *
 * <code title="URI to the show-action of the current controller">
 * <f:uri.action action="show" />
 * </code>
 * <output>
 * index.php?id=123&tx_myextension_plugin[action]=show&tx_myextension_plugin[controller]=Standard&cHash=xyz
 * (depending on the current page and your TS configuration)
 * </output>
 */
class ActionViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * Initialize arguments
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('action', 'string', 'Target action');
        $this->registerArgument('arguments', 'array', 'Arguments', false, []);
        $this->registerArgument('controller', 'string', 'Target controller. If NULL current controllerName is used');
        $this->registerArgument('extensionName', 'string', 'Target Extension Name (without "tx_" prefix and no underscores). If NULL the current extension name is used');
        $this->registerArgument('pluginName', 'string', 'Target plugin. If empty, the current plugin name is used');
        $this->registerArgument('pageUid', 'int', 'Target page. See TypoLink destination');
        $this->registerArgument('pageType', 'int', 'Type of the target page. See typolink.parameter', false, 0);
        $this->registerArgument('noCache', 'bool', 'Set this to disable caching for the target page. You should not need this.', false, false);
        $this->registerArgument('noCacheHash', 'bool', 'Set this to suppress the cHash query parameter created by TypoLink. You should not need this.', false, false);
        $this->registerArgument('section', 'string', 'The anchor to be added to the URI', false, '');
        $this->registerArgument('format', 'string', 'The requested format, e.g. ".html', false, '');
        $this->registerArgument('linkAccessRestrictedPages', 'bool', 'If set, links pointing to access restricted pages will still link to the page even though the page cannot be accessed.', false, false);
        $this->registerArgument('additionalParams', 'array', 'additional query parameters that won\'t be prefixed like $arguments (overrule $arguments)', false, []);
        $this->registerArgument('absolute', 'bool', 'If set, an absolute URI is rendered', false, false);
        $this->registerArgument('addQueryString', 'bool', 'If set, the current query parameters will be kept in the URI', false, false);
        $this->registerArgument('argumentsToBeExcludedFromQueryString', 'array', 'arguments to be removed from the URI. Only active if $addQueryString = TRUE', false, []);
        $this->registerArgument('addQueryStringMethod', 'string', 'Set which parameters will be kept. Only active if $addQueryString = TRUE');
    }

    /**
     *
     * @return string Rendered link
     */
    public function render()
    {
        $pageUid = $this->arguments['pageUid'];
        $pageType = $this->arguments['pageType'];
        $noCache = $this->arguments['noCache'];
        $noCacheHash = $this->arguments['noCacheHash'];
        $section = $this->arguments['section'];
        $format = $this->arguments['format'];
        $linkAccessRestrictedPages = $this->arguments['linkAccessRestrictedPages'];
        $additionalParams = $this->arguments['additionalParams'];
        $absolute = $this->arguments['absolute'];
        $addQueryString = $this->arguments['addQueryString'];
        $argumentsToBeExcludedFromQueryString = $this->arguments['argumentsToBeExcludedFromQueryString'];
        $addQueryStringMethod = $this->arguments['addQueryStringMethod'];
        $action = $this->arguments['action'];
        $arguments = $this->arguments['arguments'];
        $controller = $this->arguments['controller'];
        $extensionName = $this->arguments['extensionName'];
        $pluginName = $this->arguments['pluginName'];

        $uriBuilder = $this->controllerContext->getUriBuilder();
        $uri = $uriBuilder
            ->reset()
            ->setTargetPageUid($pageUid)
            ->setTargetPageType($pageType)
            ->setNoCache($noCache)
            ->setUseCacheHash(!$noCacheHash)
            ->setSection($section)
            ->setFormat($format)
            ->setLinkAccessRestrictedPages($linkAccessRestrictedPages)
            ->setArguments($additionalParams)
            ->setCreateAbsoluteUri($absolute)
            ->setAddQueryString($addQueryString)
            ->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
            ->setAddQueryStringMethod($addQueryStringMethod)
            ->uriFor($action, $arguments, $controller, $extensionName, $pluginName);
        return $uri;
    }
}
