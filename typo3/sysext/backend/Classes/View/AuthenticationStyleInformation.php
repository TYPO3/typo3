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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Provide styling for backend authentication forms, customized via extension configuration.
 *
 * @internal
 */
class AuthenticationStyleInformation implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected array $backendExtensionConfiguration;

    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        $this->backendExtensionConfiguration = (array)$extensionConfiguration->get('backend');
    }

    public function getBackgroundImageStyles(): string
    {
        $backgroundImage = (string)($this->backendExtensionConfiguration['loginBackgroundImage'] ?? '');
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
            .typo3-login { background-image: url("' . $backgroundImageUri . '"); }
            .typo3-login-footnote { background-color: #000000; color: #ffffff; opacity: 0.5; }
        ';
    }

    public function getHighlightColorStyles(): string
    {
        $highlightColor = (string)($this->backendExtensionConfiguration['loginHighlightColor'] ?? '');
        if ($highlightColor === '') {
            return '';
        }

        return '
            .btn-login.disabled, .btn-login[disabled], fieldset[disabled] .btn-login,
            .btn-login.disabled:hover, .btn-login[disabled]:hover, fieldset[disabled] .btn-login:hover,
            .btn-login.disabled:focus, .btn-login[disabled]:focus, fieldset[disabled] .btn-login:focus,
            .btn-login.disabled.focus, .btn-login[disabled].focus, fieldset[disabled] .btn-login.focus,
            .btn-login.disabled:active, .btn-login[disabled]:active, fieldset[disabled] .btn-login:active,
            .btn-login.disabled.active, .btn-login[disabled].active, fieldset[disabled] .btn-login.active,
            .btn-login:hover, .btn-login:focus, .btn-login:active,
            .btn-login:active:hover, .btn-login:active:focus,
            .btn-login { background-color: ' . $highlightColor . '; }
            .card-login .card-footer { border-color: ' . $highlightColor . '; }
        ';
    }

    public function getFooterNote(): string
    {
        $footerNote = (string)($this->backendExtensionConfiguration['loginFootnote'] ?? '');
        if ($footerNote === '') {
            return '';
        }

        return strip_tags(trim($footerNote));
    }

    public function getLogo(): string
    {
        $logo = ($this->backendExtensionConfiguration['loginLogo'] ?? '');
        if ($logo === '') {
            return '';
        }
        $logoUri = $this->getUriForFileName($logo);
        if ($logoUri === '') {
            $this->logger->warning('The configured TYPO3 backend login logo "{logo_url}" can\'t be resolved. Please check if the file exists and the extension is activated.', [
                'logo_url' => $logoUri,
            ]);
            return '';
        }

        return $logoUri;
    }

    public function getLogoAlt(): string
    {
        $logoAlt = trim((string)($this->backendExtensionConfiguration['loginLogoAlt'] ?? ''));
        if ($logoAlt === '') {
            trigger_error(
                'Login logo without alt-text is not accessible and will fall back to "TYPO3 CMS logo" in v12. Configure alt-text in the backend extension.',
                E_USER_DEPRECATED
            );
        }

        return $logoAlt;
    }

    public function getDefaultLogo(): string
    {
        // Use TYPO3 logo depending on highlight color
        $logo = ((string)($this->backendExtensionConfiguration['loginHighlightColor'] ?? '') !== '')
            ? 'EXT:core/Resources/Public/Images/typo3_black.svg'
            : 'EXT:core/Resources/Public/Images/typo3_orange.svg';

        return $this->getUriForFileName($logo);
    }

    public function getDefaultLogoStyles(): string
    {
        return '.typo3-login-logo .typo3-login-image { max-width: 150px; height:100%;}';
    }

    public function getSupportingImages(): array
    {
        return [
            'capslock' => $this->getUriForFileName('EXT:backend/Resources/Public/Images/icon_capslock.svg'),
            'typo3' => $this->getUriForFileName('EXT:core/Resources/Public/Images/typo3_orange.svg'),
        ];
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
    protected function getUriForFileName(string $filename): string
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
}
