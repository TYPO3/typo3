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

namespace TYPO3\CMS\Core\Site\Entity;

use Psr\Http\Message\UriInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Error\PageErrorHandler\FluidPageErrorHandler;
use TYPO3\CMS\Core\Error\PageErrorHandler\InvalidPageErrorHandlerException;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageContentErrorHandler;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerNotConfiguredException;
use TYPO3\CMS\Core\ExpressionLanguage\Resolver;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Routing\PageRouter;
use TYPO3\CMS\Core\Routing\RouterInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Resources\ResourceInterface;

/**
 * Entity representing a single site with available languages
 *
 * @phpstan-type LanguageRef -1|0|positive-int
 */
class Site implements SiteInterface, ResourceInterface
{
    protected const ERRORHANDLER_TYPE_PAGE = 'Page';
    protected const ERRORHANDLER_TYPE_FLUID = 'Fluid';
    protected const ERRORHANDLER_TYPE_PHP = 'PHP';

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var UriInterface
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
     * @var array<LanguageRef, SiteLanguage>
     */
    protected $languages;

    /**
     * @var array
     */
    protected $errorHandlers;

    protected SiteSettings $settings;

    /**
     * Sets up a site object, and its languages, error handlers and the settings
     */
    public function __construct(string $identifier, int $rootPageId, array $configuration, SiteSettings $settings = null)
    {
        $this->identifier = $identifier;
        $this->rootPageId = $rootPageId;
        if ($settings === null) {
            $settings = new SiteSettings($configuration['settings'] ?? []);
        }
        $this->settings = $settings;
        // Merge settings back in configuration for backwards-compatibility
        $configuration['settings'] = $this->settings->getAll();
        $this->configuration = $configuration;
        $configuration['languages'] = !empty($configuration['languages']) ? $configuration['languages'] : [
            0 => [
                'languageId' => 0,
                'title' => 'Default',
                'navigationTitle' => '',
                'flag' => 'us',
                'locale' => 'en_US.UTF-8',
            ],
        ];
        $baseUrl = $this->resolveBaseWithVariants(
            $configuration['base'] ?? '',
            $configuration['baseVariants'] ?? null
        );
        $this->base = new Uri($this->sanitizeBaseUrl($baseUrl));

        foreach ($configuration['languages'] as $languageConfiguration) {
            $languageUid = (int)$languageConfiguration['languageId'];
            // site language has defined its own base, this is the case most of the time.
            if (!empty($languageConfiguration['base'])) {
                $base = $this->resolveBaseWithVariants(
                    $languageConfiguration['base'],
                    $languageConfiguration['baseVariants'] ?? null
                );
                $base = new Uri($this->sanitizeBaseUrl($base));
                // no host given by the language-specific base, so lets prefix the main site base
                if ($base->getScheme() === '' && $base->getHost() === '') {
                    $base = rtrim((string)$this->base, '/') . '/' . ltrim((string)$base, '/');
                    $base = new Uri($this->sanitizeBaseUrl($base));
                }
            } else {
                // Language configuration does not have a base defined
                // So the main site base is used (usually done for default languages)
                $base = new Uri($this->sanitizeBaseUrl(rtrim((string)$this->base, '/') . '/'));
            }
            if (!empty($languageConfiguration['flag'])) {
                if ($languageConfiguration['flag'] === 'global') {
                    $languageConfiguration['flag'] = 'flags-multiple';
                } elseif ($languageConfiguration['flag'] !== 'empty-empty') {
                    $languageConfiguration['flag'] = 'flags-' . $languageConfiguration['flag'];
                }
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
     * Checks if the base has variants, and takes the first variant which matches an expression.
     */
    protected function resolveBaseWithVariants(string $baseUrl, ?array $baseVariants): string
    {
        if (!empty($baseVariants)) {
            $expressionLanguageResolver = GeneralUtility::makeInstance(
                Resolver::class,
                'site',
                []
            );
            foreach ($baseVariants as $baseVariant) {
                try {
                    if ((bool)$expressionLanguageResolver->evaluate($baseVariant['condition'])) {
                        $baseUrl = $baseVariant['base'];
                        break;
                    }
                } catch (SyntaxError $e) {
                    // silently fail and do not evaluate
                    // no logger here, as Site is currently cached and serialized
                }
            }
        }
        return $baseUrl;
    }

    /**
     * Gets the identifier of this site,
     * mainly used when maintaining / configuring sites.
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getId(): string
    {
        return $this->getIdentifier();
    }

    /**
     * Returns the base URL of this site
     */
    public function getBase(): UriInterface
    {
        return $this->base;
    }

    /**
     * Returns the root page ID of this site
     */
    public function getRootPageId(): int
    {
        return $this->rootPageId;
    }

    /**
     * Returns all available languages of this site
     *
     * @return array<LanguageRef, SiteLanguage>
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
     * @return array<LanguageRef, SiteLanguage>
     */
    public function getAllLanguages(): array
    {
        return $this->languages;
    }

    /**
     * Returns a language of this site, given by the sys_language_uid
     *
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

    public function getDefaultLanguage(): SiteLanguage
    {
        return reset($this->languages);
    }

    /**
     * @return array<LanguageRef, SiteLanguage>
     */
    public function getAvailableLanguages(BackendUserAuthentication $user, bool $includeAllLanguagesFlag = false, int $pageId = null): array
    {
        $availableLanguages = [];

        // Check if we need to add language "-1"
        if ($includeAllLanguagesFlag && $user->checkLanguageAccess(-1)) {
            $availableLanguages[-1] = new SiteLanguage(-1, '', $this->getBase(), [
                'title' => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:multipleLanguages'),
                'flag' => 'flags-multiple',
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
     * @throws PageErrorHandlerNotConfiguredException
     * @throws InvalidPageErrorHandlerException
     */
    public function getErrorHandler(int $statusCode): PageErrorHandlerInterface
    {
        $errorHandlerConfiguration = $this->errorHandlers[$statusCode] ?? $this->errorHandlers[0] ?? null;
        switch ($errorHandlerConfiguration['errorHandler'] ?? null) {
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
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function getSettings(): SiteSettings
    {
        return $this->settings;
    }

    /**
     * Returns a single configuration attribute
     *
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
     */
    protected function sanitizeBaseUrl(string $base): string
    {
        // no protocol ("//") and the first part is no "/" (path), means that this is a domain like
        // "www.domain.com/subpage", and we want to ensure that this one then gets a "no-scheme agnostic" part
        if (!empty($base) && !str_contains($base, '//') && $base[0] !== '/') {
            // either a scheme is added, or no scheme but with domain, or a path which is not absolute
            // make the base prefixed with a slash, so it is recognized as path, not as domain
            // treat as path
            if (!str_contains($base, '.')) {
                $base = '/' . $base;
            } else {
                // treat as domain name
                $base = '//' . $base;
            }
        }
        return $base;
    }

    /**
     * Returns the applicable router for this site. This might be configurable in the future.
     */
    public function getRouter(Context $context = null): RouterInterface
    {
        return GeneralUtility::makeInstance(PageRouter::class, $this, $context);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
