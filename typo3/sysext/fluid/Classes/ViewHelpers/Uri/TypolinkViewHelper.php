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
use TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService;
use TYPO3\CMS\Core\LinkHandling\TypolinkParameter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * A ViewHelper to create uris from fields supported by the link wizard.
 *
 * Example
 * =======
 *
 * ``{link}`` contains ``19 - - - &X=y``
 *
 * Please note that due to the nature of typolink you have to provide a full
 * set of parameters.
 * If you use the parameter only, then target, class and title will be discarded.
 *
 * Minimal usage
 * -------------
 *
 * ::
 *
 *    <f:uri.typolink parameter="{link}" />
 *
 * ``/page/path/name.html?X=y``
 *
 * Depending on routing and page path configuration.
 *
 * Full parameter usage
 * --------------------
 *
 * ::
 *
 *    <f:uri.typolink parameter="{link}" additionalParams="&u=b" />
 *
 * ``/page/path/name.html?X=y&u=b``
 *
 * Depending on routing and page path configuration.
 */
final class TypolinkViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('parameter', 'mixed', 'stdWrap.typolink style parameter string', true);
        $this->registerArgument('additionalParams', 'string', 'stdWrap.typolink additionalParams', false, '');
        $this->registerArgument('language', 'string', 'link to a specific language - defaults to the current language, use a language ID or "current" to enforce a specific language');
        $this->registerArgument('addQueryString', 'string', 'If set, the current query parameters will be kept in the URL. If set to "untrusted", then ALL query parameters will be added. Be aware, that this might lead to problems when the generated link is cached.', false, false);
        $this->registerArgument('addQueryStringExclude', 'string', 'Define parameters to be excluded from the query string (only active if addQueryString is set)', false, '');
        $this->registerArgument('absolute', 'bool', 'Ensure the resulting URL is an absolute URL', false, false);
    }

    public function render(): string
    {
        $parameter = $this->arguments['parameter'] ?? '';
        $typoLinkCodecService = GeneralUtility::makeInstance(TypoLinkCodecService::class);
        if (!$parameter instanceof TypolinkParameter) {
            $parameter = TypolinkParameter::createFromTypolinkParts(
                is_scalar($parameter) ? $typoLinkCodecService->decode((string)$parameter) : []
            );
        }
        // Merge the $parameter with other arguments and encode the typolink again
        $typolink = $typoLinkCodecService->encode(
            TypolinkParameter::createFromTypolinkParts(self::mergeTypoLinkConfiguration($parameter->toArray(), $this->arguments))->toArray()
        );
        $request = null;
        if ($this->renderingContext->hasAttribute(ServerRequestInterface::class)) {
            $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        }
        return $typolink !== '' ? self::invokeContentObjectRenderer($this->arguments, $typolink, $request) : '';
    }

    protected static function invokeContentObjectRenderer(array $arguments, string $typoLinkParameter, ?ServerRequestInterface $request): string
    {
        $addQueryString = $arguments['addQueryString'] ?? false;
        $addQueryStringExclude = $arguments['addQueryStringExclude'] ?? '';
        $absolute = $arguments['absolute'] ?? false;

        $instructions = [
            'parameter' => $typoLinkParameter,
            'forceAbsoluteUrl' => $absolute,
        ];
        if (isset($arguments['language']) && $arguments['language'] !== null) {
            $instructions['language'] = (string)$arguments['language'];
        }
        if ($addQueryString && $addQueryString !== 'false') {
            $instructions['addQueryString'] = $addQueryString;
            $instructions['addQueryString.'] = [
                'exclude' => $addQueryStringExclude,
            ];
        }

        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        if ($request) {
            $contentObject->setRequest($request);
        }
        return $contentObject->createUrl($instructions);
    }

    /**
     * Merges view helper arguments with typolink parts.
     */
    protected static function mergeTypoLinkConfiguration(array $typoLinkConfiguration, array $arguments): array
    {
        if ($typoLinkConfiguration === []) {
            return $typoLinkConfiguration;
        }

        $additionalParameters = $arguments['additionalParams'] ?? '';

        // Combine additionalParams
        if ($additionalParameters) {
            $typoLinkConfiguration['additionalParams'] .= $additionalParameters;
        }

        return $typoLinkConfiguration;
    }
}
