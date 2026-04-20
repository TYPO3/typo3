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

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\View\AuthenticationStyleInformation;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Imaging\Exception\InvalidSvgException;
use TYPO3\CMS\Core\Imaging\Svg\SvgDocumentFactory;
use TYPO3\CMS\Core\Imaging\Svg\SvgDocumentService;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\SystemResource\Exception\SystemResourceDoesNotExistException;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
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
        private readonly PackageDependentCacheIdentifier $packageDependentCacheIdentifier,
        #[Autowire(service: 'cache.assets')]
        private readonly FrontendInterface $cache,
        private readonly SystemResourcePublisherInterface $resourcePublisher,
        private readonly SvgDocumentFactory $svgDocumentFactory,
        private readonly SvgDocumentService $svgDocumentService,
    ) {}

    public function render(): string
    {
        $languageService = $this->getLanguageService();
        $logo = $this->authenticationStyleInformation->getLogo();
        $alternativeText = $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:typo3.altText');
        if ($logo !== null) {
            $alternativeText = $this->authenticationStyleInformation->getLogoAlt() ?: $alternativeText;
        } else {
            $logo = $this->authenticationStyleInformation->getDefaultLogo();
        }

        if ($logo instanceof SystemResourceInterface && ($renderedSvg = $this->getInlineSvg($logo)) !== null) {
            return $renderedSvg;
        }

        $request = null;
        if ($this->renderingContext->hasAttribute(ServerRequestInterface::class)) {
            $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        }
        $uri = (string)$this->resourcePublisher->generateUri($logo, $request);
        return $this->renderImage($uri, $alternativeText);
    }

    private function renderImage(string $uri, string $alt): string
    {
        return sprintf('<img %s>', GeneralUtility::implodeAttributes([
            'src' => $uri,
            'alt' => $alt,
        ], true));
    }

    private function getInlineSvg(SystemResourceInterface $svg): ?string
    {
        $cacheIdentifier = $this->packageDependentCacheIdentifier
            ->withPrefix('LoginLogo')
            ->withAdditionalHashedIdentifier((string)$svg)
            ->toString();
        if ($this->cache->has($cacheIdentifier)) {
            return $this->cache->get($cacheIdentifier);
        }

        $svgContent = $this->parseSvg($svg);
        $this->cache->set($cacheIdentifier, $svgContent);
        return $svgContent;
    }

    private function parseSvg(SystemResourceInterface $svg): ?string
    {
        if (!str_ends_with($svg->getName(), '.svg')) {
            return null;
        }
        try {
            $svgContent = $svg->getContents();
        } catch (SystemResourceDoesNotExistException) {
            return null;
        }

        // SVG is sanitized, because login screen needs increased security precautions.
        // Links are stripped so the rendered logo cannot contain clickable areas.
        try {
            $document = $this->svgDocumentFactory->fromStringAndSanitize($svgContent, true);
        } catch (InvalidSvgException) {
            return null;
        }

        // Logo is decorative; the login page has its own heading.
        $document->documentElement->setAttribute('aria-hidden', 'true');
        return $this->svgDocumentService->toInlineMarkup($document);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
