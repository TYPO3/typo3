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

namespace TYPO3\CMS\Backend\ViewHelpers;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\View\AuthenticationStyleInformation;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Resource\Security\SvgSanitizer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to display the login logo.
 *
 * ```
 *   <backend:loginLogo />
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-backend-loginlogo
 * @internal
 */
final class LoginLogoViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function __construct(
        private readonly AuthenticationStyleInformation $authenticationStyleInformation,
        private readonly SvgSanitizer $svgSanitizer,
        private readonly PackageDependentCacheIdentifier $packageDependentCacheIdentifier,
        #[Autowire(service: 'cache.assets')]
        private readonly FrontendInterface $cache,
    ) {}

    public function render(): string
    {
        $languageService = $this->getLanguageService();

        if (($filepath = $this->authenticationStyleInformation->getLogo()) !== '') {
            $alternativeText = $this->authenticationStyleInformation->getLogoAlt() ?: $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:typo3.altText');
        } else {
            $filepath = $this->authenticationStyleInformation->getDefaultLogo();
            $alternativeText = $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:typo3.altText');
        }

        if (($renderedSvg = $this->getInlineSvg($filepath)) !== null) {
            return $renderedSvg;
        }

        $uri = $this->authenticationStyleInformation->getUriForFileName($filepath);
        return $this->renderImage($uri, $alternativeText);
    }

    private function renderImage(string $uri, string $alt): string
    {
        return sprintf('<img %s>', GeneralUtility::implodeAttributes([
            'src' => $uri,
            'alt' => $alt,
        ], true));
    }

    private function getInlineSvg(string $filepath): ?string
    {
        $cacheIdentifier = $this->packageDependentCacheIdentifier
            ->withPrefix('LoginLogo')
            ->withAdditionalHashedIdentifier($filepath)
            ->toString();
        if ($this->cache->has($cacheIdentifier)) {
            return $this->cache->get($cacheIdentifier);
        }

        $svgContent = $this->parseSvg($filepath);
        $this->cache->set($cacheIdentifier, $svgContent);
        return $svgContent;
    }

    private function parseSvg(string $filepath): ?string
    {
        if (!str_ends_with($filepath, '.svg')) {
            return null;
        }

        // Check if it's a URL
        if (preg_match('/^(https?:)?\/\//', $filepath)) {
            return null;
        }

        $absoluteFilePath = GeneralUtility::getFileAbsFileName(ltrim($filepath, '/'));
        if (!file_exists($absoluteFilePath)) {
            return null;
        }

        $svgContent = file_get_contents($absoluteFilePath);
        if ($svgContent === false) {
            return null;
        }

        // SVG is sanitized, because login screen needs increased security precautions
        $svgContent = $this->svgSanitizer->sanitizeContent($svgContent);
        if (!$svgContent) {
            return null;
        }

        $domXml = new \DOMDocument();
        if (!$domXml->loadXML($svgContent)) {
            return null;
        }

        // @todo: move link-removal into a configurable svg-sanitizer option
        $xpath = new \DOMXPath($domXml);
        $links = $xpath->query('//a');
        foreach ($links as $link) {
            if ($link instanceof \DOMElement) {
                $link->remove();
            }
        }

        // Remove SVG xmlns which is not needed in HTML5, as HTML5 imports SVG into it's namespace
        $svgElement = $domXml->documentElement;
        $svgElement->removeAttributeNS('http://www.w3.org/2000/svg', '');
        if ($svgElement->hasAttribute('version')) {
            $svgElement->removeAttribute('version');
        }
        if (!$svgElement->hasAttribute('viewBox') && $svgElement->hasAttribute('width') && $svgElement->hasAttribute('height')) {
            $svgElement->setAttribute('viewBox', sprintf('0 0 %d %d', (int)$svgElement->getAttribute('width'), (int)$svgElement->getAttribute('height')));
        }
        $svgElement->setAttribute('aria-hidden', 'true');
        return $domXml->saveXML($svgElement);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
