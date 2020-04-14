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

namespace TYPO3\CMS\Core\Tests\Functional\SiteHandling;

use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Tests\Functional\Fixtures\Frontend\PhpError;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Trait used for test classes that want to set up (= write) site configuration files.
 *
 * Mainly used when testing Site-related tests in Frontend requests.
 *
 * Be sure to set the LANGUAGE_PRESETS const in your class.
 */
trait SiteBasedTestTrait
{
    /**
     * @param array $items
     */
    protected static function failIfArrayIsNotEmpty(array $items): void
    {
        if (empty($items)) {
            return;
        }

        static::fail(
            'Array was not empty as expected, but contained these items:' . LF
            . '* ' . implode(LF . '* ', $items)
        );
    }

    /**
     * @param string $identifier
     * @param array $site
     * @param array $languages
     * @param array $errorHandling
     */
    protected function writeSiteConfiguration(
        string $identifier,
        array $site = [],
        array $languages = [],
        array $errorHandling = []
    ) {
        $configuration = $site;
        if (!empty($languages)) {
            $configuration['languages'] = $languages;
        }
        if (!empty($errorHandling)) {
            $configuration['errorHandling'] = $errorHandling;
        }
        $siteConfiguration = new SiteConfiguration(
            $this->instancePath . '/typo3conf/sites/'
        );

        try {
            // ensure no previous site configuration influences the test
            GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites/' . $identifier, true);
            $siteConfiguration->write($identifier, $configuration);
        } catch (\Exception $exception) {
            $this->markTestSkipped($exception->getMessage());
        }
    }

    /**
     * @param string $identifier
     * @param array $overrides
     */
    protected function mergeSiteConfiguration(
        string $identifier,
        array $overrides
    ) {
        $siteConfiguration = new SiteConfiguration(
            $this->instancePath . '/typo3conf/sites/'
        );
        $configuration = $siteConfiguration->load($identifier);
        $configuration = array_merge($configuration, $overrides);
        try {
            $siteConfiguration->write($identifier, $configuration);
        } catch (\Exception $exception) {
            $this->markTestSkipped($exception->getMessage());
        }
    }

    /**
     * @param int $rootPageId
     * @param string $base
     * @return array
     */
    protected function buildSiteConfiguration(
        int $rootPageId,
        string $base = ''
    ): array {
        return [
            'rootPageId' => $rootPageId,
            'base' => $base,
        ];
    }

    /**
     * @param string $identifier
     * @param string $base
     * @return array
     */
    protected function buildDefaultLanguageConfiguration(
        string $identifier,
        string $base
    ): array {
        $configuration = $this->buildLanguageConfiguration($identifier, $base);
        $configuration['typo3Language'] = 'default';
        $configuration['flag'] = 'global';
        unset($configuration['fallbackType'], $configuration['fallbacks']);
        return $configuration;
    }

    /**
     * @param string $identifier
     * @param string $base
     * @param array $fallbackIdentifiers
     * @param string $fallbackType
     * @return array
     */
    protected function buildLanguageConfiguration(
        string $identifier,
        string $base,
        array $fallbackIdentifiers = [],
        string $fallbackType = null
    ): array {
        $preset = $this->resolveLanguagePreset($identifier);

        $configuration = [
            'languageId' => $preset['id'],
            'title' => $preset['title'],
            'navigationTitle' => $preset['title'],
            'base' => $base,
            'locale' => $preset['locale'],
            'iso-639-1' => $preset['iso'],
            'hreflang' => $preset['hrefLang'],
            'direction' => $preset['direction'],
            'typo3Language' => $preset['iso'],
            'flag' => $preset['iso'],
            'fallbackType' => $fallbackType ?? (empty($fallbackIdentifiers) ? 'strict' : 'fallback'),
        ];

        if (!empty($fallbackIdentifiers)) {
            $fallbackIds = array_map(
                function (string $fallbackIdentifier) {
                    $preset = $this->resolveLanguagePreset($fallbackIdentifier);
                    return $preset['id'];
                },
                $fallbackIdentifiers
            );
            $configuration['fallbackType'] = $fallbackType ?? 'fallback';
            $configuration['fallbacks'] = implode(',', $fallbackIds);
        }

        return $configuration;
    }

    /**
     * @param string $handler
     * @param array $codes
     * @return array
     */
    protected function buildErrorHandlingConfiguration(
        string $handler,
        array $codes
    ): array {
        if ($handler === 'Page') {
            $baseConfiguration = [
                'errorContentSource' => '404',
            ];
        } elseif ($handler === 'Fluid') {
            $baseConfiguration = [
                'errorFluidTemplate' => 'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/FluidError.html',
                'errorFluidTemplatesRootPath' => '',
                'errorFluidLayoutsRootPath' => '',
                'errorFluidPartialsRootPath' => '',
            ];
        } elseif ($handler === 'PHP') {
            $baseConfiguration = [
                'errorPhpClassFQCN' => PhpError::class,
            ];
        } else {
            throw new \LogicException(
                sprintf('Invalid handler "%s"', $handler),
                1533894782
            );
        }

        $baseConfiguration['errorHandler'] = $handler;

        return array_map(
            function (int $code) use ($baseConfiguration) {
                $baseConfiguration['errorCode'] = $code;
                return $baseConfiguration;
            },
            $codes
        );
    }

    /**
     * @param string $identifier
     * @return mixed
     */
    protected function resolveLanguagePreset(string $identifier)
    {
        if (!isset(static::LANGUAGE_PRESETS[$identifier])) {
            throw new \LogicException(
                sprintf('Undefined preset identifier "%s"', $identifier),
                1533893665
            );
        }
        return static::LANGUAGE_PRESETS[$identifier];
    }
}
