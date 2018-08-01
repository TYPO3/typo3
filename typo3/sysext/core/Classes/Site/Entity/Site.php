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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Error\PageErrorHandler\FluidPageErrorHandler;
use TYPO3\CMS\Core\Error\PageErrorHandler\InvalidPageErrorHandlerException;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageContentErrorHandler;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerNotConfiguredException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Entity representing a single site with available languages
 */
class Site implements SiteInterface
{
    protected const ERRORHANDLER_TYPE_PAGE = 'Page';
    protected const ERRORHANDLER_TYPE_FLUID = 'Fluid';
    protected const ERRORHANDLER_TYPE_PHP = 'PHP';

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $base;

    /**
     * @var int
     */
    protected $rootPageId;

    /**
     * Any attributes for this site
     * @var array
     */
    protected $configuration;

    /**
     * @var SiteLanguage[]
     */
    protected $languages;

    /**
     * @var array
     */
    protected $errorHandlers;

    /**
     * Sets up a site object, and its languages and error handlers
     *
     * @param string $identifier
     * @param int $rootPageId
     * @param array $configuration
     */
    public function __construct(string $identifier, int $rootPageId, array $configuration)
    {
        $this->identifier = $identifier;
        $this->rootPageId = $rootPageId;
        $this->configuration = $configuration;
        $configuration['languages'] = $configuration['languages'] ?: [
            0 => [
                'languageId' => 0,
                'title' => 'Default',
                'navigationTitle' => '',
                'typo3Language' => 'default',
                'flag' => 'us',
                'locale' => 'en_US.UTF-8',
                'iso-639-1' => 'en',
                'hreflang' => 'en-US',
                'direction' => '',
            ]
        ];
        $this->base = $this->sanitizeBaseUrl($configuration['base'] ?? '');
        foreach ($configuration['languages'] as $languageConfiguration) {
            $languageUid = (int)$languageConfiguration['languageId'];
            // site language has defined its own base, this is the case most of the time.
            if (!empty($languageConfiguration['base'])) {
                $base = $languageConfiguration['base'];
                $base = $this->sanitizeBaseUrl($base);
                $baseParts = parse_url($base);
                // no host given by the language-specific base, so lets prefix the main site base
                if (empty($baseParts['scheme']) && empty($baseParts['host'])) {
                    $base = rtrim($this->base, '/') . '/' . ltrim($base, '/');
                    $base = $this->sanitizeBaseUrl($base);
                }
            } else {
                // Language configuration does not have a base defined
                // So the main site base is used (usually done for default languages)
                $base = $this->sanitizeBaseUrl(rtrim($this->base, '/') . '/');
            }
            $this->languages[$languageUid] = new SiteLanguage(
                $languageUid,
                $languageConfiguration['locale'],
                $base,
                $languageConfiguration
            );
        }
        foreach ($configuration['errorHandling'] ?? [] as $errorHandlingConfiguration) {
            $code = $errorHandlingConfiguration['errorCode'];
            unset($errorHandlingConfiguration['errorCode']);
            $this->errorHandlers[(int)$code] = $errorHandlingConfiguration;
        }
    }

    /**
     * Gets the identifier of this site,
     * mainly used when maintaining / configuring sites.
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Returns the base URL of this site
     *
     * @return string
     */
    public function getBase(): string
    {
        return $this->base;
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
        $languages = [];
        foreach ($this->languages as $languageId => $language) {
            if ($language->enabled()) {
                $languages[$languageId] = $language;
            }
        }
        return $languages;
    }

    /**
     * Returns all available languages of this site, even the ones disabled for frontend usages
     *
     * @return SiteLanguage[]
     */
    public function getAllLanguages(): array
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
            'Language ' . $languageId . ' does not exist on site ' . $this->identifier . '.',
            1522960188
        );
    }

    /**
     * Fetch the available languages for a specific backend user, used in various places in Backend and Frontend
     * when a Backend User is authenticated.
     *
     * @param BackendUserAuthentication $user
     * @param int $pageId
     * @param bool $includeAllLanguagesFlag
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
     * @throws PageErrorHandlerNotConfiguredException
     * @throws InvalidPageErrorHandlerException
     */
    public function getErrorHandler(int $statusCode): PageErrorHandlerInterface
    {
        $errorHandlerConfiguration = $this->errorHandlers[$statusCode] ?? null;
        switch ($errorHandlerConfiguration['errorHandler']) {
            case self::ERRORHANDLER_TYPE_FLUID:
                return GeneralUtility::makeInstance(FluidPageErrorHandler::class, $statusCode, $errorHandlerConfiguration);
            case self::ERRORHANDLER_TYPE_PAGE:
                return GeneralUtility::makeInstance(PageContentErrorHandler::class, $statusCode, $errorHandlerConfiguration);
            case self::ERRORHANDLER_TYPE_PHP:
                $handler = GeneralUtility::makeInstance($errorHandlerConfiguration['errorPhpClassFQCN'], $statusCode, $errorHandlerConfiguration);
                // Check if the interface is implemented
                if (!($handler instanceof PageErrorHandlerInterface)) {
                    throw new InvalidPageErrorHandlerException('The configured error handler "' . (string)$errorHandlerConfiguration['errorPhpClassFQCN'] . '" for status code ' . $statusCode . ' must implement the PageErrorHandlerInterface.', 1527432330);
                }
                return $handler;
        }
        throw new PageErrorHandlerNotConfiguredException('No error handler given for the status code "' . $statusCode . '".', 1522495914);
    }

    /**
     * Returns the whole configuration for this site
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * Returns a single configuration attribute
     *
     * @param string $attributeName
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function getAttribute(string $attributeName)
    {
        if (isset($this->configuration[$attributeName])) {
            return $this->configuration[$attributeName];
        }
        throw new \InvalidArgumentException(
            'Attribute ' . $attributeName . ' does not exist on site ' . $this->identifier . '.',
            1522495954
        );
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
