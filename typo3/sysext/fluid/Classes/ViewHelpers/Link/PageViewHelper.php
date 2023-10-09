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

namespace TYPO3\CMS\Fluid\ViewHelpers\Link;

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
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * A ViewHelper for creating links to TYPO3 pages.
 *
 * Examples
 * ========
 *
 * Link to the current page
 * ------------------------
 *
 * ::
 *
 *    <f:link.page>page link</f:link.page>
 *
 * Output::
 *
 *    <a href="/page/path/name.html">page link</a>
 *
 * Depending on current page, routing and page path configuration.
 *
 * Query parameters
 * ----------------
 *
 * ::
 *
 *    <f:link.page pageUid="1" additionalParams="{foo: 'bar'}">page link</f:link.page>
 *
 * Output::
 *
 *    <a href="/page/path/name.html?foo=bar">page link</a>
 *
 * Depending on current page, routing and page path configuration.
 *
 * Query parameters for extensions
 * -------------------------------
 *
 * ::
 *
 *    <f:link.page pageUid="1" additionalParams="{extension_key: {foo: 'bar'}}">page link</f:link.page>
 *
 * Output::
 *
 *    <a href="/page/path/name.html?extension_key[foo]=bar">page link</a>
 *
 * Depending on current page, routing and page path configuration.
 */
final class PageViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('target', 'string', 'Target of link', false);
        $this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document', false);
        $this->registerArgument('pageUid', 'int', 'Target page. See TypoLink destination');
        $this->registerArgument('pageType', 'int', 'Type of the target page. See typolink.parameter');
        $this->registerArgument('noCache', 'bool', 'Set this to disable caching for the target page. You should not need this.');
        $this->registerArgument('language', 'string', 'link to a specific language - defaults to the current language, use a language ID or "current" to enforce a specific language', false);
        $this->registerArgument('section', 'string', 'The anchor to be added to the URI');
        $this->registerArgument('linkAccessRestrictedPages', 'bool', 'If set, links pointing to access restricted pages will still link to the page even though the page cannot be accessed.');
        $this->registerArgument('additionalParams', 'array', 'Additional query parameters that won\'t be prefixed like $arguments (overrule $arguments)');
        $this->registerArgument('absolute', 'bool', 'If set, the URI of the rendered link is absolute');
        $this->registerArgument('addQueryString', 'string', 'If set, the current query parameters will be kept in the URL. If set to "untrusted", then ALL query parameters will be added. Be aware, that this might lead to problems when the generated link is cached.', false, false);
        $this->registerArgument('argumentsToBeExcludedFromQueryString', 'array', 'Arguments to be removed from the URI. Only active if $addQueryString = TRUE');
    }

    public function render(): string
    {
        /** @var RenderingContext $renderingContext */
        $renderingContext = $this->renderingContext;
        $request = $renderingContext->getRequest();

        if ($request instanceof ExtbaseRequestInterface) {
            return $this->renderWithExtbaseContext($request);
        }
        if ($request instanceof ServerRequestInterface) {
            if (ApplicationType::fromRequest($request)->isFrontend()) {
                // Use the regular typolink functionality.
                return $this->renderFrontendLinkWithCoreContext($request);
            }
            $uri = $this->renderBackendLinkWithCoreContext($request);
            if ($uri !== '') {
                $this->tag->addAttribute('href', $uri);
                $this->tag->setContent($this->renderChildren());
                $this->tag->forceClosingTag(true);
                $result = $this->tag->render();
            } else {
                $result = (string)$this->renderChildren();
            }
            return $result;
        }
        throw new \RuntimeException(
            'The rendering context of ViewHelper f:link.page is missing a valid request object.',
            1639819269
        );
    }

    protected function renderFrontendLinkWithCoreContext(ServerRequestInterface $request): string
    {
        $pageUid = isset($this->arguments['pageUid']) ? (int)$this->arguments['pageUid'] : 'current';
        $pageType = isset($this->arguments['pageType']) ? (int)$this->arguments['pageType'] : 0;
        $noCache = isset($this->arguments['noCache']) && (bool)$this->arguments['noCache'];
        $section = isset($this->arguments['section']) ? (string)$this->arguments['section'] : '';
        $language = isset($this->arguments['language']) ? (string)$this->arguments['language'] : null;
        $linkAccessRestrictedPages = isset($this->arguments['linkAccessRestrictedPages']) && (bool)$this->arguments['linkAccessRestrictedPages'];
        $additionalParams = isset($this->arguments['additionalParams']) ? (array)$this->arguments['additionalParams'] : [];
        $absolute = isset($this->arguments['absolute']) && (bool)$this->arguments['absolute'];
        $addQueryString = $this->arguments['addQueryString'] ?? false;
        $argumentsToBeExcludedFromQueryString = isset($this->arguments['argumentsToBeExcludedFromQueryString']) ? (array)$this->arguments['argumentsToBeExcludedFromQueryString'] : [];

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
            $linkResult = $linkFactory->create((string)$this->renderChildren(), $typolinkConfiguration, $cObj);

            // Removing TypoLink target here to ensure same behaviour with extbase uri builder in this context.
            $linkResultAttributes = $linkResult->getAttributes();
            unset($linkResultAttributes['target']);

            $this->tag->addAttributes($linkResultAttributes);
            $this->tag->setContent($this->renderChildren());
            $this->tag->forceClosingTag(true);
            $result = $this->tag->render();
        } catch (UnableToLinkException) {
            $result = (string)$this->renderChildren();
        }
        return $result;
    }

    protected function renderBackendLinkWithCoreContext(ServerRequestInterface $request): string
    {
        $pageUid = isset($this->arguments['pageUid']) ? (int)$this->arguments['pageUid'] : null;
        $section = isset($this->arguments['section']) ? (string)$this->arguments['section'] : '';
        $additionalParams = isset($this->arguments['additionalParams']) ? (array)$this->arguments['additionalParams'] : [];
        $absolute = isset($this->arguments['absolute']) && (bool)$this->arguments['absolute'];
        $addQueryString = $this->arguments['addQueryString'] ?? false;
        $argumentsToBeExcludedFromQueryString = isset($this->arguments['argumentsToBeExcludedFromQueryString']) ? (array)$this->arguments['argumentsToBeExcludedFromQueryString'] : [];

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

    protected function renderWithExtbaseContext(ExtbaseRequestInterface $request): string
    {
        $pageUid = isset($this->arguments['pageUid']) ? (int)$this->arguments['pageUid'] : null;
        $pageType = isset($this->arguments['pageType']) ? (int)$this->arguments['pageType'] : 0;
        $noCache = isset($this->arguments['noCache']) && (bool)$this->arguments['noCache'];
        $section = isset($this->arguments['section']) ? (string)$this->arguments['section'] : '';
        $language = isset($this->arguments['language']) ? (string)$this->arguments['language'] : null;
        $linkAccessRestrictedPages = isset($this->arguments['linkAccessRestrictedPages']) && (bool)$this->arguments['linkAccessRestrictedPages'];
        $additionalParams = isset($this->arguments['additionalParams']) ? (array)$this->arguments['additionalParams'] : [];
        $absolute = isset($this->arguments['absolute']) && (bool)$this->arguments['absolute'];
        $addQueryString = $this->arguments['addQueryString'] ?? false;
        $argumentsToBeExcludedFromQueryString = isset($this->arguments['argumentsToBeExcludedFromQueryString']) ? (array)$this->arguments['argumentsToBeExcludedFromQueryString'] : [];

        $uriBuilder = GeneralUtility::makeInstance(ExtbaseUriBuilder::class);
        $uriBuilder->reset()
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

        $uri = $uriBuilder->build();
        if ($uri !== '') {
            $this->tag->addAttribute('href', $uri);
            $this->tag->setContent($this->renderChildren());
            $this->tag->forceClosingTag(true);
            $result = $this->tag->render();
        } else {
            $result = (string)$this->renderChildren();
        }
        return $result;
    }
}
