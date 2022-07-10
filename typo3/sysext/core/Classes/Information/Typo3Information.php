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

namespace TYPO3\CMS\Core\Information;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains information and links, or copyright information for the project.
 */
class Typo3Information
{
    public const URL_COMMUNITY = 'https://typo3.org/';
    public const URL_LICENSE = 'https://typo3.org/project/licenses/';
    public const URL_EXCEPTION = 'https://typo3.org/go/exception/CMS/';
    public const URL_DONATE = 'https://typo3.org/community/contribute/donate/';
    public const URL_OPCACHE = 'https://docs.typo3.org/m/typo3/tutorial-getting-started/main/en-us/Troubleshooting/PHP.html#opcode-cache-messages';

    protected LanguageService $languageService;

    public function __construct()
    {
        if (($GLOBALS['LANG'] ?? null) instanceof LanguageService) {
            $this->languageService = $GLOBALS['LANG'];
        } else {
            $this->languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
        }
    }

    public function getCopyrightYear(): string
    {
        return '1998-' . date('Y');
    }

    /**
     * Used for any backend rendering in the <meta generator> tag when rendering HTML.
     *
     * @return string
     */
    public function getHtmlGeneratorTagContent(): string
    {
        return 'TYPO3 CMS, ' . static::URL_COMMUNITY . ', &#169; Kasper Sk&#229;rh&#248;j ' . $this->getCopyrightYear() . ', extensions are copyright of their respective owners.';
    }

    /**
     * Used for any frontend rendering in the <head> tag when rendering HTML.
     *
     * @return string
     */
    public function getInlineHeaderComment(): string
    {
        return '	This website is powered by TYPO3 - inspiring people to share!
	TYPO3 is a free open source Content Management Framework initially created by Kasper Skaarhoj and licensed under GNU/GPL.
	TYPO3 is copyright ' . $this->getCopyrightYear() . ' of Kasper Skaarhoj. Extensions are copyright of their respective owners.
	Information and contribution at ' . static::URL_COMMUNITY . '
';
    }

    /**
     * Prints TYPO3 Copyright notice for About Modules etc. modules.
     *
     * Warning:
     * DO NOT prevent this notice from being shown in ANY WAY.
     * According to the GPL license an interactive application must show such a notice on start-up
     * ('If the program is interactive, make it output a short notice... ' - see GPL.txt)
     * Therefore preventing this notice from being properly shown is a violation of the license, regardless of whether
     * you remove it or use a stylesheet to obstruct the display.
     *
     * @return string Text/Image (HTML) for copyright notice.
     */
    public function getCopyrightNotice(): string
    {
        // Copyright Notice
        $loginCopyrightWarrantyProvider = strip_tags(trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['loginCopyrightWarrantyProvider']));
        $loginCopyrightWarrantyURL = strip_tags(trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['loginCopyrightWarrantyURL']));

        if (strlen($loginCopyrightWarrantyProvider) >= 2 && strlen($loginCopyrightWarrantyURL) >= 10) {
            $warrantyNote = sprintf(
                $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:warranty.by'),
                htmlspecialchars($loginCopyrightWarrantyProvider),
                '<a href="' . htmlspecialchars($loginCopyrightWarrantyURL) . '" target="_blank" rel="noreferrer">',
                '</a>'
            );
        } else {
            $warrantyNote = sprintf(
                $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:no.warranty'),
                '<a href="' . htmlspecialchars(static::URL_LICENSE) . '" target="_blank" rel="noreferrer">',
                '</a>'
            );
        }
        return '<a href="' . htmlspecialchars(static::URL_COMMUNITY) . '" target="_blank" rel="noreferrer">' .
            $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:typo3.cms') . '</a>. ' .
            $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:copyright') . ' &copy; '
            . htmlspecialchars($this->getCopyrightYear()) . ' Kasper Sk&aring;rh&oslash;j. ' .
            $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:extension.copyright') . ' ' .
            sprintf(
                $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:details.link'),
                '<a href="' . htmlspecialchars(static::URL_COMMUNITY) . '" target="_blank" rel="noreferrer">' . htmlspecialchars(static::URL_COMMUNITY) . '</a>'
            ) . ' ' .
            strip_tags($warrantyNote, '<a>') . ' ' .
            sprintf(
                $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:free.software'),
                '<a href="' . htmlspecialchars(static::URL_LICENSE) . '" target="_blank" rel="noreferrer">',
                '</a> '
            )
            . $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:keep.notice');
    }
}
