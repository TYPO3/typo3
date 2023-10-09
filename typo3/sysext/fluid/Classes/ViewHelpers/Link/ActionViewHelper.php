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
use TYPO3\CMS\Core\Http\ApplicationType;
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
 * A ViewHelper for creating links to extbase actions. Tailored for extbase plugins, uses extbase Request and extbase UriBuilder.
 *
 * Examples
 * ========
 *
 * link to the show-action of the current controller::
 *
 *    <f:link.action action="show">action link</f:link.action>
 *
 * Output::
 *
 *    <a href="index.php?id=123&tx_myextension_plugin[action]=show&tx_myextension_plugin[controller]=Standard&cHash=xyz">action link</a>
 *
 * Depending on the current page and your TypoScript configuration.
 */
final class ActionViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('name', 'string', 'Specifies the name of an anchor');
        $this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document');
        $this->registerTagAttribute('rev', 'string', 'Specifies the relationship between the linked document and the current document');
        $this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
        $this->registerArgument('action', 'string', 'Target action');
        $this->registerArgument('controller', 'string', 'Target controller. If NULL current controllerName is used');
        $this->registerArgument('extensionName', 'string', 'Target Extension Name (without `tx_` prefix and no underscores). If NULL the current extension name is used');
        $this->registerArgument('pluginName', 'string', 'Target plugin. If empty, the current plugin name is used');
        $this->registerArgument('pageUid', 'int', 'Target page. See TypoLink destination');
        $this->registerArgument('pageType', 'int', 'Type of the target page. See typolink.parameter');
        $this->registerArgument('noCache', 'bool', 'Set this to disable caching for the target page. You should not need this.');
        $this->registerArgument('language', 'string', 'link to a specific language - defaults to the current language, use a language ID or "current" to enforce a specific language', false);
        $this->registerArgument('section', 'string', 'The anchor to be added to the URI');
        $this->registerArgument('format', 'string', 'The requested format, e.g. ".html');
        $this->registerArgument('linkAccessRestrictedPages', 'bool', 'If set, links pointing to access restricted pages will still link to the page even though the page cannot be accessed.');
        $this->registerArgument('additionalParams', 'array', 'Additional query parameters that won\'t be prefixed like $arguments (overrule $arguments)');
        $this->registerArgument('absolute', 'bool', 'If set, the URI of the rendered link is absolute');
        $this->registerArgument('addQueryString', 'string', 'If set, the current query parameters will be kept in the URL. If set to "untrusted", then ALL query parameters will be added. Be aware, that this might lead to problems when the generated link is cached.', false, false);
        $this->registerArgument('argumentsToBeExcludedFromQueryString', 'array', 'Arguments to be removed from the URI. Only active if $addQueryString = TRUE');
        $this->registerArgument('arguments', 'array', 'Arguments for the controller action, associative array');
    }

    public function render(): string
    {
        /** @var RenderingContext $renderingContext */
        $renderingContext = $this->renderingContext;
        $request = $renderingContext->getRequest();
        if ($request instanceof ExtbaseRequestInterface) {
            return $this->renderWithExtbaseContext($request);
        }
        if ($request instanceof ServerRequestInterface && ApplicationType::fromRequest($request)->isFrontend()) {
            return $this->renderFrontendLinkWithCoreContext($request);
        }
        throw new \RuntimeException(
            'The rendering context of ViewHelper f:link.action is missing a valid request object.',
            1690365240
        );
    }

    protected function renderFrontendLinkWithCoreContext(ServerRequestInterface $request): string
    {
        // No support for following arguments:
        //  * format
        $pageUid = (int)($this->arguments['pageUid'] ?? 0);
        $pageType = (int)($this->arguments['pageType'] ?? 0);
        $noCache = (bool)($this->arguments['noCache'] ?? false);
        /** @var string|null $language */
        $language = isset($this->arguments['language']) ? (string)$this->arguments['language'] : null;
        /** @var string|null $section */
        $section = $this->arguments['section'] ?? null;
        $linkAccessRestrictedPages = (bool)($this->arguments['linkAccessRestrictedPages'] ?? false);
        /** @var array|null $additionalParams */
        $additionalParams = $this->arguments['additionalParams'] ?? null;
        $absolute = (bool)($this->arguments['absolute'] ?? false);
        /** @var bool|string $addQueryString */
        $addQueryString = $this->arguments['addQueryString'] ?? false;
        /** @var array|null $argumentsToBeExcludedFromQueryString */
        $argumentsToBeExcludedFromQueryString = $this->arguments['argumentsToBeExcludedFromQueryString'] ?? null;
        /** @var string|null $action */
        $action = $this->arguments['action'] ?? null;
        /** @var string|null $controller */
        $controller = $this->arguments['controller'] ?? null;
        /** @var string|null $extensionName */
        $extensionName = $this->arguments['extensionName'] ?? null;
        /** @var string|null $pluginName */
        $pluginName = $this->arguments['pluginName'] ?? null;
        /** @var array|null $arguments */
        $arguments = $this->arguments['arguments'] ?? [];

        $allExtbaseArgumentsAreSet = (
            is_string($extensionName) && $extensionName !== ''
            && is_string($pluginName) && $pluginName !== ''
            && is_string($controller) && $controller !== ''
            && is_string($action) && $action !== ''
        );
        if (!$allExtbaseArgumentsAreSet) {
            throw new \RuntimeException(
                'ViewHelper f:link.action needs either all extbase arguments set'
                . ' ("extensionName", "pluginName", "controller", "action")'
                . ' or needs a request implementing extbase RequestInterface.',
                1690370264
            );
        }

        // Provide extbase default and custom arguments as prefixed additional params
        $extbaseArgumentNamespace = 'tx_'
            . str_replace('_', '', strtolower($extensionName))
            . '_'
            . str_replace('_', '', strtolower($pluginName));
        $additionalParams ??= [];
        $additionalParams[$extbaseArgumentNamespace] = array_replace(
            [
                'controller' => $controller,
                'action' => $action,
            ],
            $arguments
        );

        $typolinkConfiguration = [
            'parameter' => $pageUid,
        ];
        if ($pageType) {
            $typolinkConfiguration['parameter'] .= ',' . $pageType;
        }
        if ($language !== null) {
            $typolinkConfiguration['language'] = $language;
        }
        if ($noCache) {
            $typolinkConfiguration['no_cache'] = 1;
        }
        if ($section) {
            $typolinkConfiguration['section'] = $section;
        }
        if ($linkAccessRestrictedPages) {
            $typolinkConfiguration['linkAccessRestrictedPages'] = 1;
        }
        $typolinkConfiguration['additionalParams'] = HttpUtility::buildQueryString($additionalParams, '&');
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
            return $this->tag->render();
        } catch (UnableToLinkException) {
            return (string)$this->renderChildren();
        }
    }

    protected function renderWithExtbaseContext(ExtbaseRequestInterface $request): string
    {
        $action = $this->arguments['action'];
        $controller = $this->arguments['controller'];
        $extensionName = $this->arguments['extensionName'];
        $pluginName = $this->arguments['pluginName'];
        $pageUid = (int)$this->arguments['pageUid'] ?: null;
        $pageType = (int)($this->arguments['pageType'] ?? 0);
        $noCache = (bool)($this->arguments['noCache'] ?? false);
        $language = isset($this->arguments['language']) ? (string)$this->arguments['language'] : null;
        $section = (string)$this->arguments['section'];
        $format = (string)$this->arguments['format'];
        $linkAccessRestrictedPages = (bool)($this->arguments['linkAccessRestrictedPages'] ?? false);
        $additionalParams = (array)$this->arguments['additionalParams'];
        $absolute = (bool)($this->arguments['absolute'] ?? false);
        $addQueryString = $this->arguments['addQueryString'] ?? false;
        $argumentsToBeExcludedFromQueryString = (array)$this->arguments['argumentsToBeExcludedFromQueryString'];
        $parameters = $this->arguments['arguments'];

        $uriBuilder = GeneralUtility::makeInstance(ExtbaseUriBuilder::class);
        $uriBuilder
            ->reset()
            ->setRequest($request)
            ->setTargetPageType($pageType)
            ->setNoCache($noCache)
            ->setLanguage($language)
            ->setSection($section)
            ->setFormat($format)
            ->setLinkAccessRestrictedPages($linkAccessRestrictedPages)
            ->setArguments($additionalParams)
            ->setCreateAbsoluteUri($absolute)
            ->setAddQueryString($addQueryString)
            ->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString)
        ;

        if (MathUtility::canBeInterpretedAsInteger($pageUid)) {
            $uriBuilder->setTargetPageUid((int)$pageUid);
        }
        $uri = $uriBuilder->uriFor($action, $parameters, $controller, $extensionName, $pluginName);
        if ($uri === '') {
            return $this->renderChildren();
        }
        $this->tag->addAttribute('href', $uri);
        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }
}
