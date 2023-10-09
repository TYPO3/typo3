<?php

declare(strict_types=1);

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface as ExtbaseRequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder as ExtbaseUriBuilder;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * A ViewHelper for creating URIs to TYPO3 pages.
 *
 * Examples
 * ========
 *
 * URI to the current page
 * -----------------------
 *
 * ::
 *
 *    <f:uri.page>page link</f:uri.page>
 *
 * ``/page/path/name.html``
 *
 * Depending on current page, routing and page path configuration.
 *
 * Query parameters
 * ----------------
 *
 * ::
 *
 *    <f:uri.page pageUid="1" additionalParams="{foo: 'bar'}" />
 *
 * ``/page/path/name.html?foo=bar``
 *
 * Depending on current page, routing and page path configuration.
 *
 * Query parameters for extensions
 * -------------------------------
 *
 * ::
 *
 *    <f:uri.page pageUid="1" additionalParams="{extension_key: {foo: 'bar'}}" />
 *
 * ``/page/path/name.html?extension_key[foo]=bar``
 *
 * Depending on current page, routing and page path configuration.
 */
final class PageViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public function initializeArguments(): void
    {
        $this->registerArgument('pageUid', 'int', 'target PID');
        $this->registerArgument('additionalParams', 'array', 'query parameters to be attached to the resulting URI', false, []);
        $this->registerArgument('pageType', 'int', 'type of the target page. See typolink.parameter', false, 0);
        $this->registerArgument('noCache', 'bool', 'set this to disable caching for the target page. You should not need this.', false, false);
        $this->registerArgument('language', 'string', 'link to a specific language - defaults to the current language, use a language ID or "current" to enforce a specific language', false);
        $this->registerArgument('section', 'string', 'the anchor to be added to the URI', false, '');
        $this->registerArgument('linkAccessRestrictedPages', 'bool', 'If set, links pointing to access restricted pages will still link to the page even though the page cannot be accessed.', false, false);
        $this->registerArgument('absolute', 'bool', 'If set, the URI of the rendered link is absolute', false, false);
        $this->registerArgument('addQueryString', 'string', 'If set, the current query parameters will be kept in the URL. If set to "untrusted", then ALL query parameters will be added. Be aware, that this might lead to problems when the generated link is cached.', false, false);
        $this->registerArgument('argumentsToBeExcludedFromQueryString', 'array', 'arguments to be removed from the URI. Only active if $addQueryString = TRUE', false, []);
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        /** @var RenderingContext $renderingContext */
        $request = $renderingContext->getRequest();
        if ($request instanceof ExtbaseRequestInterface) {
            return self::renderWithExtbaseContext($request, $arguments);
        }
        if ($request instanceof ServerRequestInterface) {
            if (ApplicationType::fromRequest($request)->isFrontend()) {
                // Use the regular typolink functionality.
                return self::renderFrontendLinkWithCoreContext($request, $arguments, $renderChildrenClosure);
            }
            return self::renderBackendLinkWithCoreContext($request, $arguments);
        }
        throw new \RuntimeException(
            'The rendering context of ViewHelper f:uri.page is missing a valid request object.',
            1639820200
        );
    }

    protected static function renderBackendLinkWithCoreContext(ServerRequestInterface $request, array $arguments): string
    {
        $pageUid = isset($arguments['pageUid']) ? (int)$arguments['pageUid'] : null;
        $section = isset($arguments['section']) ? (string)$arguments['section'] : '';
        $additionalParams = isset($arguments['additionalParams']) ? (array)$arguments['additionalParams'] : [];
        $absolute = isset($arguments['absolute']) && (bool)$arguments['absolute'];
        $addQueryString = $arguments['addQueryString'] ?? false;
        $argumentsToBeExcludedFromQueryString = isset($arguments['argumentsToBeExcludedFromQueryString']) ? (array)$arguments['argumentsToBeExcludedFromQueryString'] : [];

        $arguments = [];
        if ($addQueryString && $addQueryString !== 'false') {
            $arguments = $request->getQueryParams();
            foreach ($argumentsToBeExcludedFromQueryString as $argumentToBeExcluded) {
                $argumentArrayToBeExcluded = [];
                parse_str($argumentToBeExcluded, $argumentArrayToBeExcluded);
                $arguments = ArrayUtility::arrayDiffKeyRecursive($arguments, $argumentArrayToBeExcluded);
            }
        }

        $id = $pageUid ?? $request->getQueryParams()['id'] ?? null;
        if ($id !== null) {
            $arguments['id'] = $id;
        }
        if (!isset($arguments['route']) && ($route = $request->getAttribute('route')) instanceof Route) {
            $arguments['route'] = $route->getOption('_identifier');
        }
        $arguments = array_replace_recursive($arguments, $additionalParams);
        $routeName = $arguments['route'] ?? null;
        unset($arguments['route'], $arguments['token']);
        $backendUriBuilder = GeneralUtility::makeInstance(BackendUriBuilder::class);
        try {
            if ($absolute) {
                $uri = (string)$backendUriBuilder->buildUriFromRoute($routeName, $arguments, BackendUriBuilder::ABSOLUTE_URL);
            } else {
                $uri = (string)$backendUriBuilder->buildUriFromRoute($routeName, $arguments, BackendUriBuilder::ABSOLUTE_PATH);
            }
        } catch (RouteNotFoundException) {
            $uri = '';
        }
        if ($section !== '') {
            $uri .= '#' . $section;
        }
        return $uri;
    }

    protected static function renderFrontendLinkWithCoreContext(ServerRequestInterface $request, array $arguments, \Closure $renderChildrenClosure): string
    {
        $pageUid = isset($arguments['pageUid']) ? (int)$arguments['pageUid'] : 'current';
        $pageType = isset($arguments['pageType']) ? (int)$arguments['pageType'] : 0;
        $noCache = isset($arguments['noCache']) && (bool)$arguments['noCache'];
        $section = isset($arguments['section']) ? (string)$arguments['section'] : '';
        $language = isset($arguments['language']) ? (string)$arguments['language'] : null;
        $linkAccessRestrictedPages = isset($arguments['linkAccessRestrictedPages']) && (bool)$arguments['linkAccessRestrictedPages'];
        $additionalParams = isset($arguments['additionalParams']) ? (array)$arguments['additionalParams'] : [];
        $absolute = isset($arguments['absolute']) && (bool)$arguments['absolute'];
        $addQueryString = $arguments['addQueryString'] ?? false;
        $argumentsToBeExcludedFromQueryString = isset($arguments['argumentsToBeExcludedFromQueryString']) ? (array)$arguments['argumentsToBeExcludedFromQueryString'] : [];

        $typolinkConfiguration = [
            'parameter' => $pageUid,
        ];
        if ($pageType) {
            $typolinkConfiguration['parameter'] .= ',' . $pageType;
        }
        if ($noCache) {
            $typolinkConfiguration['no_cache'] = 1;
        }
        if ($language !== null) {
            $typolinkConfiguration['language'] = $language;
        }
        if ($section) {
            $typolinkConfiguration['section'] = $section;
        }
        if ($linkAccessRestrictedPages) {
            $typolinkConfiguration['linkAccessRestrictedPages'] = 1;
        }
        if ($additionalParams) {
            $typolinkConfiguration['additionalParams'] = HttpUtility::buildQueryString($additionalParams, '&');
        }
        if ($absolute) {
            $typolinkConfiguration['forceAbsoluteUrl'] = true;
        }
        if ($addQueryString && $addQueryString !== 'false') {
            $typolinkConfiguration['addQueryString'] = $addQueryString;
            if ($argumentsToBeExcludedFromQueryString !== []) {
                $typolinkConfiguration['addQueryString.']['exclude'] = implode(',', $argumentsToBeExcludedFromQueryString);
            }
        }

        try {
            $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $cObj->setRequest($request);
            $linkFactory = GeneralUtility::makeInstance(LinkFactory::class);
            $linkResult = $linkFactory->create((string)$renderChildrenClosure(), $typolinkConfiguration, $cObj);
            return $linkResult->getUrl();
        } catch (UnableToLinkException) {
            return (string)$renderChildrenClosure();
        }
    }

    protected static function renderWithExtbaseContext(ExtbaseRequestInterface $request, array $arguments): string
    {
        $pageUid = $arguments['pageUid'];
        $additionalParams = $arguments['additionalParams'];
        $pageType = (int)($arguments['pageType'] ?? 0);
        $noCache = $arguments['noCache'];
        $section = $arguments['section'];
        $language = isset($arguments['language']) ? (string)$arguments['language'] : null;
        $linkAccessRestrictedPages = $arguments['linkAccessRestrictedPages'];
        $absolute = $arguments['absolute'];
        $addQueryString = $arguments['addQueryString'] ?? false;
        $argumentsToBeExcludedFromQueryString = $arguments['argumentsToBeExcludedFromQueryString'];

        $uriBuilder = GeneralUtility::makeInstance(ExtbaseUriBuilder::class);
        $uri = $uriBuilder
            ->reset()
            ->setRequest($request)
            ->setTargetPageType($pageType)
            ->setNoCache($noCache)
            ->setSection($section)
            ->setLanguage($language)
            ->setLinkAccessRestrictedPages($linkAccessRestrictedPages)
            ->setArguments($additionalParams)
            ->setCreateAbsoluteUri($absolute)
            ->setAddQueryString($addQueryString)
            ->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString);

        if (MathUtility::canBeInterpretedAsInteger($pageUid)) {
            $uriBuilder->setTargetPageUid((int)$pageUid);
        }

        return $uri->build();
    }
}
