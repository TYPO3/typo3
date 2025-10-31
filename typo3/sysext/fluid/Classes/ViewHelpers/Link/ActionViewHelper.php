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
use TYPO3\CMS\Extbase\Mvc\RequestInterface as ExtbaseRequestInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * ViewHelper for creating links to Extbase actions. Tailored for Extbase
 * plugins, uses Extbase Request and Extbase UriBuilder.
 *
 * ```
 *   <f:link.action action="show" arguments="{blog: blog.uid}">action link</f:link.action>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-link-action
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
        $this->registerArgument('action', 'string', 'Target action');
        $this->registerArgument('arguments', 'array', 'Arguments for the controller action, associative array (do not use reserved keywords "action", "controller" or "format" if not referring to these internal variables specifically)', false, []);
        $this->registerArgument('controller', 'string', 'Target controller. If NULL current controllerName is used');
        $this->registerArgument('extensionName', 'string', 'Target Extension Name (without `tx_` prefix and no underscores). If NULL the current extension name is used');
        $this->registerArgument('pluginName', 'string', 'Target plugin. If empty, the current plugin name is used');
        $this->registerArgument('pageUid', 'int', 'Target page. See TypoLink destination');
        $this->registerArgument('pageType', 'int', 'Type of the target page. See typolink.parameter', false, 0);
        $this->registerArgument('noCache', 'bool', 'Set this to disable caching for the target page. You should not need this.');
        $this->registerArgument('language', 'string', 'link to a specific language - defaults to the current language, use a language ID or "current" to enforce a specific language');
        $this->registerArgument('section', 'string', 'The anchor to be added to the URI', false, '');
        $this->registerArgument('format', 'string', 'The requested format, e.g. ".html', false, '');
        $this->registerArgument('linkAccessRestrictedPages', 'bool', 'If set, links pointing to access restricted pages will still link to the page even though the page cannot be accessed.', false, false);
        $this->registerArgument('additionalParams', 'array', 'Additional query parameters that won\'t be prefixed like $arguments (overrule $arguments)', false, []);
        $this->registerArgument('absolute', 'bool', 'If set, the URI of the rendered link is absolute', false, false);
        $this->registerArgument('addQueryString', 'string', 'If set, the current query parameters will be kept in the URL. If set to "untrusted", then ALL query parameters will be added. Be aware, that this might lead to problems when the generated link is cached.', false, false);
        $this->registerArgument('argumentsToBeExcludedFromQueryString', 'array', 'Arguments to be removed from the URI. Only active if $addQueryString = true', false, []);
    }

    public function render(): string
    {
        $request = null;
        if ($this->renderingContext->hasAttribute(ServerRequestInterface::class)) {
            $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        }
        // Since f:uri.action and f:link.action use exactly the same ViewHelper arguments,
        // the glue code between the ViewHelper API and TYPO3's URI generation is shared across both ViewHelpers.
        $childContent = (string)$this->renderChildren();
        if ($request instanceof ExtbaseRequestInterface) {
            $uri = \TYPO3\CMS\Fluid\ViewHelpers\Uri\ActionViewHelper::createUriWithExtbaseContext($request, $this->arguments);
            if ($uri === '') {
                return $childContent;
            }
            $this->tag->addAttribute('href', $uri);
            $this->tag->setContent($childContent);
            $this->tag->forceClosingTag(true);
            return $this->tag->render();
        }

        if ($request instanceof ServerRequestInterface && ApplicationType::fromRequest($request)->isFrontend()) {
            $linkResult = \TYPO3\CMS\Fluid\ViewHelpers\Uri\ActionViewHelper::createFrontendLinkWithCoreContext($request, $this->arguments, $childContent);
            if ($linkResult === null) {
                return $childContent;
            }
            // Removing TypoLink target here to ensure same behaviour with extbase uri builder in this context.
            $linkResultAttributes = $linkResult->getAttributes();
            unset($linkResultAttributes['target']);
            $this->tag->addAttributes($linkResultAttributes);
            $this->tag->setContent($childContent);
            $this->tag->forceClosingTag(true);
            return $this->tag->render();
        }
        throw new \RuntimeException(
            'The rendering context of ViewHelper f:link.action is missing a valid request object.',
            1690365240
        );
    }
}
