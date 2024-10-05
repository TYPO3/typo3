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

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

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
    ) {}

    public function getBackgroundImageStyles(): string
    {
        $backgroundImage = (string)($this->getBackendExtensionConfiguration()['loginBackgroundImage'] ?? '');
        if ($backgroundImage === '') {
            return '';
        }

        $backgroundImageUri = $this->getUriForFileName($backgroundImage);
        if ($backgroundImageUri === '') {
            $this->logger->warning('The configured TYPO3 backend login background image "{image_url}" can\'t be resolved. Please check if the file exists and the extension is activated.', [
                'image_url' => $backgroundImageUri,
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

    public function getLogo(): string
    {
        return $this->getBackendExtensionConfiguration()['loginLogo'] ?? '';
    }

    public function getLogoAlt(): string
    {
        return trim((string)($this->getBackendExtensionConfiguration()['loginLogoAlt'] ?? ''));
    }

    public function getDefaultLogo(): string
    {
        return 'EXT:core/Resources/Public/Images/typo3_variable.svg';
    }

    /**
     * Returns the uri of a relative reference, resolves the "EXT:" prefix
     * (way of referring to files inside extensions) and checks that the file is inside
     * the project root of the TYPO3 installation
     *
     * @param string $filename The input filename/filepath to evaluate
     * @return string Returns the filename of $filename if valid, otherwise blank string.
     * @internal
     */
    public function getUriForFileName(string $filename): string
    {
        // Check if it's already a URL
        if (preg_match('/^(https?:)?\/\//', $filename)) {
            return $filename;
        }
        $absoluteFilename = GeneralUtility::getFileAbsFileName(ltrim($filename, '/'));
        $filename = '';
        if ($absoluteFilename !== '' && @is_file($absoluteFilename)) {
            $filename = PathUtility::getAbsoluteWebPath($absoluteFilename);
        }
        return $filename;
    }

    protected function getBackendExtensionConfiguration(): array
    {
        return (array)$this->extensionConfiguration->get('backend');
    }
}
