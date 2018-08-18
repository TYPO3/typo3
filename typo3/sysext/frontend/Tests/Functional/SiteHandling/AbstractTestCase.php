<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling;

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

use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Fixtures\PhpError;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Abstract test case for frontend requests
 */
abstract class AbstractTestCase extends FunctionalTestCase
{
    protected const ENCRYPTION_KEY = '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6';

    protected const TYPO3_CONF_VARS = [
        'SYS' => [
            'encryptionKey' => self::ENCRYPTION_KEY,
        ],
        'FE' => [
            'cacheHash' => [
                'requireCacheHashPresenceParameters' => ['testing[value]']
            ],
        ]
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-FR', 'direction' => ''],
        'FR-CA' => ['id' => 2, 'title' => 'Franco-Canadian', 'locale' => 'fr_CA.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-CA', 'direction' => ''],
    ];

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = ['frontend', 'workspaces'];

    /**
     * @var string[]
     */
    protected $pathsToLinkInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/AdditionalConfiguration.php' => 'typo3conf/AdditionalConfiguration.php',
    ];

    /**
     * Combines string values of multiple array as cross-product into flat items.
     *
     * Example:
     * + meltStrings(['a','b'], ['c','e'], ['f','g'])
     * + results into ['acf', 'acg', 'aef', 'aeg', 'bcf', 'bcg', 'bef', 'beg']
     *
     * @param array $arrays Distinct array that should be melted
     * @param callable $finalCallback Callback being executed on last multiplier
     * @param string $prefix Prefix containing concatenated previous values
     * @return array
     */
    protected function meltStrings(array $arrays, callable $finalCallback = null, string $prefix = ''): array
    {
        $results = [];
        $array = array_shift($arrays);
        foreach ($array as $item) {
            $resultItem = $prefix . $item;
            if (count($arrays) > 0) {
                $results = array_merge(
                    $results,
                    $this->meltStrings($arrays, $finalCallback, $resultItem)
                );
                continue;
            }
            if ($finalCallback !== null) {
                $resultItem = call_user_func($finalCallback, $resultItem);
            }
            $results[] = $resultItem;
        }
        return $results;
    }

    /**
     * @param array $array
     * @return array
     */
    protected function wrapInArray(array $array): array
    {
        return array_map(
            function ($item) {
                return [$item];
            },
            $array
        );
    }

    /**
     * @param string[] $array
     * @return array
     */
    protected function keysFromValues(array $array): array
    {
        return array_combine($array, $array);
    }

    /**
     * Generates key names based on a template and array items as arguments.
     *
     * + keysFromTemplate([[1, 2, 3], [11, 22, 33]], '%1$d->%2$d (user:%3$d)')
     * + returns the following array with generated keys
     *   [
     *     '1->2 (user:3)'    => [1, 2, 3],
     *     '11->22 (user:33)' => [11, 22, 33],
     *   ]
     *
     * @param array $array
     * @param string $template
     * @param callable|null $callback
     * @return array
     */
    protected function keysFromTemplate(array $array, string $template, callable $callback = null): array
    {
        $keys = array_unique(
            array_map(
                function (array $values) use ($template, $callback) {
                    if ($callback !== null) {
                        $values = call_user_func($callback, $values);
                    }
                    return vsprintf($template, $values);
                },
                $array
            )
        );

        if (count($keys) !== count($array)) {
            throw new \LogicException(
                'Amount of generated keys does not match to item count.',
                1534682840
            );
        }

        return array_combine($keys, $array);
    }

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
        $configuration = [
            'site' => $site,
        ];
        if (!empty($languages)) {
            $configuration['site']['languages'] = $languages;
        }
        if (!empty($errorHandling)) {
            $configuration['site']['errorHandling'] = $errorHandling;
        }

        $siteConfiguration = new SiteConfiguration(
            $this->instancePath . '/typo3conf/sites/'
        );

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
        unset($configuration['fallbackType']);
        return $configuration;
    }

    /**
     * @param string $identifier
     * @param string $base
     * @param array $fallbackIdentifiers
     * @return array
     */
    protected function buildLanguageConfiguration(
        string $identifier,
        string $base,
        array $fallbackIdentifiers = []
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
            'fallbackType' => 'strict',
        ];

        if (!empty($fallbackIdentifiers)) {
            $fallbackIds = array_map(
                function (string $fallbackIdentifier) {
                    $preset = $this->resolveLanguagePreset($fallbackIdentifier);
                    return $preset['id'];
                },
                $fallbackIdentifiers
            );
            $configuration['fallbackType'] = 'fallback';
            $configuration['fallbackType'] = implode(',', $fallbackIds);
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
                'errorFluidTemplate' => 'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/FluidError.html',
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

    /**
     * @param string $uri
     * @return string
     */
    protected static function generateCacheHash(string $uri): string
    {
        if (!isset($GLOBALS['TYPO3_CONF_VARS'])) {
            $GLOBALS['TYPO3_CONF_VARS'] = [];
        }

        $configuration = $GLOBALS['TYPO3_CONF_VARS'];
        ArrayUtility::mergeRecursiveWithOverrule(
            $GLOBALS['TYPO3_CONF_VARS'],
            static::TYPO3_CONF_VARS
        );

        $calculator = new CacheHashCalculator();
        $parameters = $calculator->getRelevantParameters(
            parse_url($uri, PHP_URL_QUERY)
        );
        $cacheHash = $calculator->calculateCacheHash($parameters);

        $GLOBALS['TYPO3_CONF_VARS'] = $configuration;
        return $cacheHash;
    }
}
