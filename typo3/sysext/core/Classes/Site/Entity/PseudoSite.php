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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Entity representing a site with legacy configuration (sys_domain) and all available languages in the system (sys_language)
 */
class PseudoSite implements SiteInterface
{
    /**
     * @var string[]
     */
    protected $entryPoints;

    /**
     * @var int
     */
    protected $rootPageId;

    /**
     * @var SiteLanguage[]
     */
    protected $languages;

    /**
     * attached sys_domain records
     * @var array
     */
    protected $domainRecords = [];

    /**
     * Sets up a pseudo site object, and its languages and error handlers
     *
     * @param int $rootPageId
     * @param array $configuration
     */
    public function __construct(int $rootPageId, array $configuration)
    {
        $this->rootPageId = $rootPageId;
        foreach ($configuration['domains'] ?? [] as $domain) {
            if (empty($domain['domainName'] ?? false)) {
                continue;
            }
            $this->domainRecords[] = $domain;
            $this->entryPoints[] = $this->sanitizeBaseUrl($domain['domainName'] ?: '');
        }
        if (empty($this->entryPoints)) {
            $this->entryPoints = ['/'];
        }
        $baseEntryPoint = reset($this->entryPoints);
        foreach ($configuration['languages'] as $languageConfiguration) {
            $languageUid = (int)$languageConfiguration['languageId'];
            // Language configuration does not have a base defined
            // So the main site base is used (usually done for default languages)
            $base = $this->sanitizeBaseUrl(rtrim($baseEntryPoint, '/') . '/');
            $this->languages[$languageUid] = new SiteLanguage(
                $languageUid,
                $languageConfiguration['locale'] ?? '',
                $base,
                $languageConfiguration
            );
        }
    }

    /**
     * Returns a generic identifier
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'PSEUDO_' . $this->rootPageId;
    }

    /**
     * Returns the first base URL of this site, falls back to "/"
     */
    public function getBase(): string
    {
        return $this->entryPoints[0] ?? '/';
    }

    /**
     * Returns the base URLs of this site, if none given, it's always "/"
     *
     * @return array
     */
    public function getEntryPoints(): array
    {
        return $this->entryPoints;
    }

    /**
     * Returns the root page ID of this site
     *
     * @return int
     */
    public function getRootPageId(): int
    {
        return $this->rootPageId;
    }

    /**
     * Returns all available languages of this site
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
     * Fetch the available languages for a specific backend user, used in various places in Backend and Frontend
     * when a Backend User is authenticated.
     *
     * @param BackendUserAuthentication $user
     * @param int $pageId
     * @param bool $includeAllLanguagesFlag whether to include "-1" into the list, useful for some backend outputs
     * @return array
     */
    public function getAvailableLanguages(BackendUserAuthentication $user, int $pageId, bool $includeAllLanguagesFlag = false)
    {
        $availableLanguages = [];

        // Check if we need to add language "-1"
        if ($includeAllLanguagesFlag && $user->checkLanguageAccess(-1)) {
            $availableLanguages[-1] = new SiteLanguage(-1, '', $this->getBase(), [
                'title' => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:multipleLanguages'),
                'flag' => 'flag-multiple'
            ]);
        }

        // Do not add the ones that are not allowed by the user
        foreach ($this->languages as $language) {
            if ($user->checkLanguageAccess($language->getLanguageId())) {
                if ($language->getLanguageId() === 0) {
                    $pageTs = BackendUtility::getPagesTSconfig($pageId);
                    // 0: "Default" language
                    $defaultLanguageLabel = 'LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:defaultLanguage';
                    if (isset($pageTs['mod.']['SHARED.']['defaultLanguageLabel'])) {
                        $defaultLanguageLabel = $pageTs['mod.']['SHARED.']['defaultLanguageLabel'] . ' (' . $this->getLanguageService()->sL($defaultLanguageLabel) . ')';
                    }
                    $defaultLanguageFlag = 'empty-empty';
                    if (isset($pageTs['mod.']['SHARED.']['defaultLanguageFlag'])) {
                        $defaultLanguageFlag = 'flags-' . $pageTs['mod.']['SHARED.']['defaultLanguageFlag'];
                    }
                    $language = new SiteLanguage(0, '', $language->getBase(), [
                        'title' => $this->getLanguageService()->sL($defaultLanguageLabel),
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
     * If a site base contains "/" or "www.domain.com", it is ensured that
     * parse_url() can handle this kind of configuration properly.
     *
     * @param string $base
     * @return string
     */
    protected function sanitizeBaseUrl(string $base): string
    {
        // no protocol ("//") and the first part is no "/" (path), means that this is a domain like
        // "www.domain.com/blabla", and we want to ensure that this one then gets a "no-scheme agnostic" part
        if (!empty($base) && strpos($base, '//') === false && $base{0} !== '/') {
            // either a scheme is added, or no scheme but with domain, or a path which is not absolute
            // make the base prefixed with a slash, so it is recognized as path, not as domain
            // treat as path
            if (strpos($base, '.') === false) {
                $base = '/' . $base;
            } else {
                // treat as domain name
                $base = '//' . $base;
            }
        }
        return $base;
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
