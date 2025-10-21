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

namespace TYPO3\CMS\Backend\View;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SystemResource\Exception\SystemResourceException;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\SystemResource\Type\PublicResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provide styling for backend authentication forms, customized via extension configuration.
 *
 * @internal
 */
#[Autoconfigure(public: true)]
readonly class AuthenticationStyleInformation
{
    public function __construct(
        private ExtensionConfiguration $extensionConfiguration,
        private LoggerInterface $logger,
        private SystemResourceFactory $resourceFactory,
        private SystemResourcePublisherInterface $resourcePublisher,
    ) {}

    public function getBackgroundImageStyles(ServerRequestInterface $request): string
    {
        $backgroundImageResource = (string)($this->getBackendExtensionConfiguration()['loginBackgroundImage'] ?? '');
        if ($backgroundImageResource === '') {
            return '';
        }
        try {
            $backgroundImageUri = (string)$this->resourcePublisher->generateUri(
                $this->resourceFactory->createPublicResource($backgroundImageResource),
                $request,
            );
        } catch (SystemResourceException) {
            $this->logger->warning('The configured TYPO3 backend login background image "{image_resource}" can\'t be resolved. Please check if the file exists and the extension is activated.', [
                'image_resource' => $backgroundImageResource,
            ]);
            return '';
        }
        return '
            .typo3-login-carousel-control.right,
            .typo3-login-carousel-control.left,
            .card-login { border: 0; }
            .typo3-login { background-image: url("' . GeneralUtility::sanitizeCssVariableValue($backgroundImageUri) . '"); }
            .typo3-login-footnote { background-color: #000000; color: #ffffff; }
        ';
    }

    public function getHighlightColorStyles(): string
    {
        $highlightColor = (string)($this->getBackendExtensionConfiguration()['loginHighlightColor'] ?? '');
        if ($highlightColor === '') {
            return '';
        }
        $highlightColor = GeneralUtility::sanitizeCssVariableValue($highlightColor);
        return '
            .typo3-login {
                --typo3-login-highlight: ' . $highlightColor . ';
            }
            .btn-login {
                --typo3-btn-color: #fff;
                --typo3-btn-bg: ' . $highlightColor . ';
                --typo3-btn-border-color: hsl(from ' . $highlightColor . ' h s calc(l - 5));
                --typo3-btn-hover-color: #fff;
                --typo3-btn-hover-bg: hsl(from ' . $highlightColor . ' h s calc(l - 3));
                --typo3-btn-hover-border-color: hsl(from ' . $highlightColor . ' h s calc(l - 8));
                --typo3-btn-focus-color: #fff;
                --typo3-btn-focus-bg: hsl(from ' . $highlightColor . ' h s calc(l - 6));
                --typo3-btn-focus-border-color: hsl(from ' . $highlightColor . ' h s calc(l - 11));
                --typo3-btn-disabled-color: #fff;
                --typo3-btn-disabled-bg: ' . $highlightColor . ';
                --typo3-btn-disabled-border-color: hsl(from ' . $highlightColor . ' h s calc(l - 5));
            }
            .card-login .card-footer { border-color: ' . $highlightColor . '; }
        ';
    }

    public function getFooterNote(): string
    {
        $footerNote = (string)($this->getBackendExtensionConfiguration()['loginFootnote'] ?? '');
        if ($footerNote === '') {
            return '';
        }

        return strip_tags(trim($footerNote));
    }

    public function getLogo(): ?PublicResourceInterface
    {
        $logoIdentifier = $this->getBackendExtensionConfiguration()['loginLogo'] ?? '';
        if ($logoIdentifier === '') {
            return null;
        }
        try {
            return $this->resourceFactory->createPublicResource($logoIdentifier);
        } catch (SystemResourceException) {
            return null;
        }
    }

    public function getLogoAlt(): string
    {
        return trim((string)($this->getBackendExtensionConfiguration()['loginLogoAlt'] ?? ''));
    }

    public function getDefaultLogo(): PublicResourceInterface
    {
        return $this->resourceFactory->createPublicResource('PKG:typo3/cms-core:Resources/Public/Images/typo3_variable.svg');
    }

    protected function getBackendExtensionConfiguration(): array
    {
        return (array)$this->extensionConfiguration->get('backend');
    }
}
