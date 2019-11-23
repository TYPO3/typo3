<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Information;

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

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Typo3Copyright
{
    /**
     * @var LanguageService
     */
    protected $languageService;

    public function __construct(LanguageService $languageService = null)
    {
        if ($languageService) {
            $this->languageService = $languageService;
        } elseif ($GLOBALS['LANG'] instanceof LanguageService) {
            $this->languageService = $GLOBALS['LANG'];
        } else {
            $this->languageService = GeneralUtility::makeInstance(LanguageService::class);
            $this->languageService->init('default');
        }
    }

    public function getCopyrightYear(): string
    {
        return TYPO3_copyright_year;
    }

    public function getCommunityWebsiteUrl(): UriInterface
    {
        return new Uri(TYPO3_URL_GENERAL);
    }

    public function getLicenseUrl(): UriInterface
    {
        return new Uri(TYPO3_URL_LICENSE);
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
                '<a href="' . htmlspecialchars((string)$this->getLicenseUrl()) . '" target="_blank" rel="noreferrer">',
                '</a>'
            );
        }
        return '<a href="' . htmlspecialchars((string)$this->getCommunityWebsiteUrl()) . '" target="_blank" rel="noreferrer">' .
            $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:typo3.cms') . '</a>. ' .
            $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:copyright') . ' &copy; '
            . htmlspecialchars($this->getCopyrightYear()) . ' Kasper Sk&aring;rh&oslash;j. ' .
            $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:extension.copyright') . ' ' .
            sprintf(
                $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:details.link'),
                '<a href="' . htmlspecialchars((string)$this->getCommunityWebsiteUrl()) . '" target="_blank" rel="noreferrer">' . htmlspecialchars((string)$this->getCommunityWebsiteUrl()) . '</a>'
            ) . ' ' .
            strip_tags($warrantyNote, '<a>') . ' ' .
            sprintf(
                $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:free.software'),
                '<a href="' . htmlspecialchars((string)$this->getLicenseUrl()) . '" target="_blank" rel="noreferrer">',
                '</a> '
            )
            . $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:keep.notice');
    }
}
