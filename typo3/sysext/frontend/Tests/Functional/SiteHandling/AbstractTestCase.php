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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling;

use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Fixtures\LinkHandlingController;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\ArrayValueInstruction;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Abstract test case for frontend requests
 */
abstract class AbstractTestCase extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const ENCRYPTION_KEY = '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6';

    protected const TYPO3_CONF_VARS = [
        'SYS' => [
            'encryptionKey' => self::ENCRYPTION_KEY,
        ],
        'FE' => [
            'cacheHash' => [
                'requireCacheHashPresenceParameters' => ['value', 'testing[value]', 'tx_testing_link[value]']
            ],
        ]
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-FR', 'direction' => ''],
        'FR-CA' => ['id' => 2, 'title' => 'Franco-Canadian', 'locale' => 'fr_CA.UTF8', 'iso' => 'fr', 'hrefLang' => 'fr-CA', 'direction' => ''],
        'ES' => ['id' => 3, 'title' => 'Spanish', 'locale' => 'es_ES.UTF8', 'iso' => 'es', 'hrefLang' => 'es-ES', 'direction' => ''],
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
     * @param array $typoScript
     * @return ArrayValueInstruction
     */
    protected function createTypoLinkUrlInstruction(array $typoScript): ArrayValueInstruction
    {
        return (new ArrayValueInstruction(LinkHandlingController::class))
            ->withArray([
                '10' => 'TEXT',
                '10.' => [
                    'typolink.' => array_merge(
                        $typoScript,
                        ['returnLast' => 'url']
                    )
                ]
            ]);
    }

    /**
     * @param array $typoScript
     * @return ArrayValueInstruction
     */
    protected function createHierarchicalMenuProcessorInstruction(array $typoScript): ArrayValueInstruction
    {
        return (new ArrayValueInstruction(LinkHandlingController::class))
            ->withArray([
                '10' => 'FLUIDTEMPLATE',
                '10.' => [
                    'file' => 'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/FluidJson.html',
                    'dataProcessing.' => [
                        '1' => 'TYPO3\\CMS\\Frontend\\DataProcessing\\MenuProcessor',
                        '1.' => array_merge(
                            $typoScript,
                            ['as' => 'results']
                        )
                    ],
                ],
            ]);
    }

    /**
     * @param array $typoScript
     * @return ArrayValueInstruction
     */
    protected function createLanguageMenuProcessorInstruction(array $typoScript): ArrayValueInstruction
    {
        return (new ArrayValueInstruction(LinkHandlingController::class))
            ->withArray([
                '10' => 'FLUIDTEMPLATE',
                '10.' => [
                    'file' => 'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/FluidJson.html',
                    'dataProcessing.' => [
                        '1' => 'TYPO3\\CMS\\Frontend\\DataProcessing\\LanguageMenuProcessor',
                        '1.' => array_merge(
                            $typoScript,
                            ['as' => 'results']
                        )
                    ],
                ],
            ]);
    }

    /**
     * Filters and keeps only desired names.
     *
     * @param array $menu
     * @param array $keepNames
     * @return array
     */
    protected function filterMenu(
        array $menu,
        array $keepNames = ['title', 'link']
    ): array {
        if (!in_array('children', $keepNames)) {
            $keepNames[] = 'children';
        }
        return array_map(
            function (array $menuItem) use ($keepNames) {
                $menuItem = array_filter(
                    $menuItem,
                    function (string $name) use ($keepNames) {
                        return in_array($name, $keepNames);
                    },
                    ARRAY_FILTER_USE_KEY
                );
                if (is_array($menuItem['children'] ?? null)) {
                    $menuItem['children'] = $this->filterMenu(
                        $menuItem['children'],
                        $keepNames
                    );
                }
                return $menuItem;
            },
            $menu
        );
    }
}
