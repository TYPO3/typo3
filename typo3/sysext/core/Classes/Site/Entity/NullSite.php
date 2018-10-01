<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Site\Entity;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Entity representing a site for everything on "pid=0". Mostly used in TYPO3 Backend, not really in use elsewhere.
 */
class NullSite implements SiteInterface
{
    /**
     * @var int
     */
    protected $rootPageId = 0;

    /**
     * @var SiteLanguage[]
     */
    protected $languages;

    /**
     * Sets up a null site object
     *
     * @param array $languages (sys_language objects)
     * @param Uri|null $baseEntryPoint
     */
    public function __construct(array $languages = null, Uri $baseEntryPoint = null)
    {
        foreach ($languages ?? [] as $languageConfiguration) {
            $languageUid = (int)$languageConfiguration['languageId'];
            // Language configuration does not have a base defined
            // So the main site base is used (usually done for default languages)
            $this->languages[$languageUid] = new SiteLanguage(
                $languageUid,
                $languageConfiguration['locale'] ?? '',
                $baseEntryPoint ?: new Uri('/'),
                $languageConfiguration
            );
        }
    }

    /**
     * Returns always #NULL
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return '#NULL';
    }

    /**
     * Always "/"
     */
    public function getBase(): UriInterface
    {
        return new Uri('/');
    }

    /**
     * Always zero
     *
     * @return int
     */
    public function getRootPageId(): int
    {
        return 0;
    }

    /**
     * Returns all available languages of this installation
     *
     * @return SiteLanguage[]
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    /**
     * Returns a language of this site, given by the sys_language_uid
     *
     * @param int $languageId
     * @return SiteLanguage
     * @throws \InvalidArgumentException
     */
    public function getLanguageById(int $languageId): SiteLanguage
    {
        if (isset($this->languages[$languageId])) {
            return $this->languages[$languageId];
        }
        throw new \InvalidArgumentException(
            'Language ' . $languageId . ' does not exist on site ' . $this->getIdentifier() . '.',
            1522965188
        );
    }

    /**
     * @inheritdoc
     */
    public function getDefaultLanguage(): SiteLanguage
    {
        return reset($this->languages);
    }

    /**
     * This takes pageTSconfig into account (unlike Site interface) to find
     * mod.SHARED.disableLanguages and mod.SHARED.defaultLanguageLabel
     *
     * @inheritdoc
     */
    public function getAvailableLanguages(BackendUserAuthentication $user, bool $includeAllLanguagesFlag = false, int $pageId = null): array
    {
        $availableLanguages = [];

        // Check if we need to add language "-1"
        if ($includeAllLanguagesFlag && $user->checkLanguageAccess(-1)) {
            $availableLanguages[-1] = new SiteLanguage(-1, '', $this->getBase(), [
                'title' => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:multipleLanguages'),
                'flag' => 'flag-multiple'
            ]);
        }
        $pageTs = BackendUtility::getPagesTSconfig($pageId);
        $pageTs = $pageTs['mod.']['SHARED.'] ?? [];

        $disabledLanguages = GeneralUtility::intExplode(',', $pageTs['disableLanguages'] ?? '', true);
        // Do not add the ones that are not allowed by the user
        foreach ($this->languages as $language) {
            if ($user->checkLanguageAccess($language->getLanguageId()) && !in_array($language->getLanguageId(), $disabledLanguages, true)) {
                if ($language->getLanguageId() === 0) {
                    // 0: "Default" language
                    $defaultLanguageLabel = 'LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:defaultLanguage';
                    $defaultLanguageLabel = $this->getLanguageService()->sL($defaultLanguageLabel);
                    if (isset($pageTs['defaultLanguageLabel'])) {
                        $defaultLanguageLabel = $pageTs['defaultLanguageLabel'] . ' (' . $defaultLanguageLabel . ')';
                    }
                    $defaultLanguageFlag = '';
                    if (isset($pageTs['defaultLanguageFlag'])) {
                        $defaultLanguageFlag = 'flags-' . $pageTs['defaultLanguageFlag'];
                    }
                    $language = new SiteLanguage(0, '', $language->getBase(), [
                        'title' => $defaultLanguageLabel,
                        'flag' => $defaultLanguageFlag,
                    ]);
                }
                $availableLanguages[$language->getLanguageId()] = $language;
            }
        }

        return $availableLanguages;
    }

    /**
     * Returns a ready-to-use error handler, to be used within the ErrorController
     *
     * @param int $statusCode
     * @return PageErrorHandlerInterface
     * @throws \RuntimeException
     */
    public function getErrorHandler(int $statusCode): PageErrorHandlerInterface
    {
        throw new \RuntimeException('No error handler given for the status code "' . $statusCode . '".', 1522495102);
    }

    /**
     * Shorthand functionality for fetching the language service
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
