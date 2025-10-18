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

namespace TYPO3\CMS\Frontend\Typolink;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\FrontendUrlPrefix;

/**
 * Abstract class to provide proper helper for most types necessary
 */
abstract class AbstractTypolinkBuilder
{
    /**
     * @deprecated this will be removed in TYPO3 v15.0. The ContentObjectRenderer will be passed to the buildLink() method of TypolinkBuilderInterface via the PSR-7 Request attribute "currentContentObject" instead.
     */
    protected ContentObjectRenderer $contentObjectRenderer;

    /**
     * The method is not implemented anymore, the class now only serves as a wrapper for helper methods.
     *
     * @param array $linkDetails parsed link details by the LinkService
     * @param string $linkText the link text
     * @param string $target the target to point to
     * @param array $conf the TypoLink configuration array
     * @throws UnableToLinkException
     * @deprecated this method will be removed from this class in TYPO3 v15.
     */
    // abstract public function build(array &$linkDetails, string $linkText, string $target, array $conf): LinkResultInterface;

    /**
     * This method is only here to keep BC for the build() method which will be removed in TYPO3 v15.0.
     * The actual implementation should be done in buildLink() instead.
     * @internal this method will be removed in TYPO3 v15.0 again.
     */
    public function _build(array &$linkDetails, string $linkText, string $target, array $conf, ServerRequestInterface $request, ContentObjectRenderer $contentObjectRenderer): LinkResultInterface
    {
        // For people already migrating in v13, adding the method but not the interface, this works as well :)
        if (method_exists($this, 'buildLink')) {
            return $this->buildLink($linkDetails, $conf, $request, $linkText);
        }
        // This one is in order to keep BC for v14 as we avoid adding the abstract method "build" to implement by subclasses
        $this->contentObjectRenderer = $contentObjectRenderer;
        if (method_exists($this, 'build')) {
            return $this->build($linkDetails, '', '', $conf);
        }
        throw new UnableToLinkException('Invalid link builder, so ' . $linkText . ' was not linked.', 1756746193, null, $linkText);
    }

    /**
     * Forces a given URL to be absolute.
     *
     * @param string $url The URL to be forced to be absolute
     * @param array $configuration TypoScript configuration of typolink
     * @return string The absolute URL
     */
    protected function forceAbsoluteUrl(string $url, array $configuration, ?ServerRequestInterface $request = null): string
    {
        $frontendTypoScriptConfigArray = $request ? $request->getAttribute('frontend.typoscript')?->getConfigArray() : [];
        if ($frontendTypoScriptConfigArray['forceAbsoluteUrls'] ?? false) {
            $forceAbsoluteUrl = true;
        } else {
            $forceAbsoluteUrl = !empty($configuration['forceAbsoluteUrl']);
        }
        if (!empty($url) && $forceAbsoluteUrl && preg_match('#^(?:([a-z]+)(://)([^/]*)/?)?(.*)$#', $url, $matches)) {
            $urlParts = [
                'scheme' => $matches[1],
                'delimiter' => '://',
                'host' => $matches[3],
                'path' => $matches[4],
            ];
            $isUrlModified = false;
            // Set scheme and host if not yet part of the URL
            if (empty($urlParts['host'])) {
                // absRefPrefix has been prepended to $url beforehand
                // so we only modify the path if no absRefPrefix has been set
                // otherwise we would destroy the path
                if (GeneralUtility::makeInstance(FrontendUrlPrefix::class)->getUrlPrefix($request) === '') {
                    $normalizedParams = $request->getAttribute('normalizedParams');
                    // @todo: This fallback should vanish mid-term: typolink has a dependency to ServerRequest
                    //        and should expect the normalizedParams argument is properly set as well. When for
                    //        instance CLI triggers this code, it should have set up a proper request.
                    $normalizedParams ??= NormalizedParams::createFromRequest($request);
                    $urlParts['scheme'] = $normalizedParams->isHttps() ? 'https' : 'http';
                    $urlParts['host'] = $normalizedParams->getHttpHost();
                    $urlParts['path'] = '/' . ltrim($urlParts['path'], '/');
                    $urlParts['path'] = $normalizedParams->getSitePath() . ltrim($urlParts['path'], '/');
                }
                $isUrlModified = true;
            }
            // Override scheme:
            $forcedScheme = $configuration['forceAbsoluteUrl.']['scheme'] ?? null;
            if (!empty($forcedScheme) && $urlParts['scheme'] !== $forcedScheme) {
                $urlParts['scheme'] = $forcedScheme;
                $isUrlModified = true;
            }
            // Also ensure the path has a "/" at the beginning when concatenating everything else together
            if ($urlParts['path'] !== '') {
                $urlParts['path'] = '/' . ltrim($urlParts['path'], '/');
                $isUrlModified = true;
            }
            // Recreate the absolute URL:
            if ($isUrlModified) {
                $url = implode('', $urlParts);
            }
        }
        return $url;
    }

    /**
     * Helper method to a fallback method properly encoding HTML.
     *
     * @param string $originalLinkText the original string, if empty, the fallback link text
     * @param string $fallbackLinkText the string to be used.
     * @return string the final text
     */
    protected function encodeFallbackLinkTextIfLinkTextIsEmpty(string $originalLinkText, string $fallbackLinkText): string
    {
        if ($originalLinkText !== '') {
            return $originalLinkText;
        }
        return htmlspecialchars($fallbackLinkText, ENT_QUOTES);
    }

    /**
     * Creates the value for target="..." in a typolink configuration
     *
     * @param array $conf the typolink configuration
     * @param string $name the key, usually "target", "extTarget" or "fileTarget"
     * @return string the value of the target attribute, if there is one
     */
    protected function resolveTargetAttribute(array $conf, string $name, ?ContentObjectRenderer $contentObjectRenderer = null): string
    {
        $target = '';
        if (isset($conf[$name]) && $conf[$name] !== '') {
            $target = $conf[$name];
        } elseif (!($conf['directImageLink'] ?? false)) {
            $frontendTypoScriptConfigArray = $contentObjectRenderer ? $contentObjectRenderer->getRequest()->getAttribute('frontend.typoscript')?->getConfigArray() : [];
            switch ($name) {
                case 'extTarget':
                case 'fileTarget':
                    $target = (string)($frontendTypoScriptConfigArray[$name] ?? '');
                    break;
                case 'target':
                    $target = (string)($frontendTypoScriptConfigArray['intTarget'] ?? '');
                    break;
            }
        }
        if (isset($conf[$name . '.']) && $conf[$name . '.']) {
            if ($contentObjectRenderer) {
                $target = (string)$contentObjectRenderer->stdWrap($target, $conf[$name . '.'] ?? []);
            }
        }
        return $target;
    }
}
