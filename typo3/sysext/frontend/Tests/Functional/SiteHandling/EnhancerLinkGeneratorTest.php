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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

/**
 * Test case for frontend requests having site handling configured using enhancers.
 */
class EnhancerLinkGeneratorTest extends AbstractTestCase
{
    /**
     * @var string
     */
    private $siteTitle = 'A Company that Manufactures Everything Inc';

    /**
     * @var InternalRequestContext
     */
    private $internalRequestContext;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::initializeDatabaseSnapshot();
    }

    public static function tearDownAfterClass(): void
    {
        static::destroyDatabaseSnapshot();
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // these settings are forwarded to the frontend sub-request as well
        $this->internalRequestContext = (new InternalRequestContext())
            ->withGlobalSettings(['TYPO3_CONF_VARS' => static::TYPO3_CONF_VARS]);

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://acme.us/'),
                $this->buildLanguageConfiguration('FR', 'https://acme.fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', 'https://acme.ca/', ['FR', 'EN']),
            ]
        );

        $this->withDatabaseSnapshot(function () {
            $this->setUpDatabase();
        });
    }

    protected function setUpDatabase()
    {
        $backendUser = $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $scenarioFile = __DIR__ . '/Fixtures/SlugScenario.yaml';
        $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
        $writer = DataHandlerWriter::withBackendUser($backendUser);
        $writer->invokeFactory($factory);
        static::failIfArrayIsNotEmpty(
            $writer->getErrors()
        );

        $this->setUpFrontendRootPage(
            1000,
            [
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/LinkGenerator.typoscript',
            ],
            [
                'title' => 'ACME Root',
                'sitetitle' => $this->siteTitle,
            ]
        );
    }

    protected function tearDown(): void
    {
        unset($this->internalRequestContext);
        parent::tearDown();
    }

    /**
     * @param array $aspect
     * @param array $languages
     * @param array $enhancers
     * @param string $variableName
     * @param array $templateOptions
     * @param array $pageTypeSettings
     * @return array
     */
    protected function createDataSet(
        array $aspect,
        array $languages,
        array $enhancers,
        string $variableName = 'value',
        array $templateOptions = [],
        array $pageTypeSettings
    ): array {
        $dataSet = [];
        foreach ($enhancers as $enhancer) {
            foreach ($languages as $languageId => $expectation) {
                $dataSet[] = [
                    array_merge(
                        $enhancer['enhancer'],
                        ['aspects' => [$variableName => $aspect]]
                    ),
                    $enhancer['parameters'],
                    $languageId,
                    $expectation,
                    $pageTypeSettings,
                ];
            }
        }
        $templatePrefix = isset($templateOptions['prefix']) ? $templateOptions['prefix'] : '';
        $templateSuffix = isset($templateOptions['suffix']) ? $templateOptions['suffix'] : '';
        return $this->keysFromTemplate(
            $dataSet,
            $templatePrefix . 'enhancer:%1$s, lang:%3$d' . $templateSuffix,
            function (array $items) {
                array_splice(
                    $items,
                    0,
                    1,
                    $items[0]['type']
                );
                return $items;
            }
        );
    }

    /**
     * @param array $options
     * @return array
     */
    protected function getEnhancers(array $options = []): array
    {
        $options = array_merge(
            ['name' => 'enhance', 'value' => 100, 'additionalParameters' => ''],
            $options
        );
        return [
            [
                'parameters' => sprintf('&value=%s%s', $options['value'], $options['additionalParameters']),
                'enhancer' => [
                    'type' => 'Simple',
                    'routePath' => sprintf('/%s/{value}', $options['name']),
                    '_arguments' => [],
                ],
            ],
            [
                'parameters' => sprintf('&testing[value]=%s%s', $options['value'], $options['additionalParameters']),
                'enhancer' => [
                    'type' => 'Plugin',
                    'routePath' => sprintf('/%s/{value}', $options['name']),
                    'namespace' => 'testing',
                    '_arguments' => [],
                ],
            ],
            [
                'parameters' => sprintf(
                    '&tx_testing_link[value]=%s&tx_testing_link[controller]=Link&tx_testing_link[action]=index%s',
                    $options['value'],
                    $options['additionalParameters']
                ),
                'enhancer' => [
                    'type' => 'Extbase',
                    'routes' => [
                        [
                            'routePath' => sprintf('/%s/{value}', $options['name']),
                            '_controller' => 'Link::index',
                            '_arguments' => [],
                        ],
                    ],
                    'extension' => 'testing',
                    'plugin' => 'link',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function createPageTypeDecorator(): array
    {
        return [
            'type' => 'PageType',
            'default' => '.html',
            'index' => 'index',
            'map' => [
                '.html' =>  0,
                'menu.json' =>  10,
            ]
        ];
    }

    /**
     * @param string|array|null $options
     * @return array
     */
    public function localeModifierDataProvider($options = null): array
    {
        if (!is_array($options)) {
            $options = [];
        }
        $aspect = [
            'type' => 'LocaleModifier',
            'default' => 'enhance',
            'localeMap' => [
                [
                    'locale' => 'fr_FR',
                    'value' => 'augmenter'
                ]
            ],
        ];

        $languages = [
            '0' => sprintf('https://acme.us/welcome/enhance/100%s?cHash=', $options['pathSuffix'] ?? ''),
            '1' => sprintf('https://acme.fr/bienvenue/augmenter/100%s?cHash=', $options['pathSuffix'] ?? ''),
        ];

        return $this->createDataSet(
            $aspect,
            $languages,
            $this->getEnhancers([
                'name' => '{enhance_name}',
                'additionalParameters' => $options['additionalParameters'] ?? ''
            ]),
            'enhance_name',
            ['prefix' => 'localeModifier/'],
            array_key_exists('pageTypeSettings', $options) ? $options['pageTypeSettings'] : []
        );
    }

    /**
     * @param array $enhancer
     * @param string $additionalParameters
     * @param int $targetLanguageId
     * @param string $expectation
     *
     * @test
     * @dataProvider localeModifierDataProvider
     */
    public function localeModifierIsApplied(array $enhancer, string $additionalParameters, int $targetLanguageId, string $expectation)
    {
        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => ['Enhancer' => $enhancer]
        ]);

        $response = $this->executeFrontendRequest(
            (new InternalRequest('https://acme.us/'))
                ->withPageId(1100)
                ->withInstructions([
                    $this->createTypoLinkUrlInstruction([
                        'parameter' => 1100,
                        'language' => $targetLanguageId,
                        'additionalParams' => $additionalParameters,
                        'forceAbsoluteUrl' => 1,
                    ])
                ]),
            $this->internalRequestContext
        );

        static::assertStringStartsWith($expectation, (string)$response->getBody());
    }

    /**
     * @param string|array|null $options
     * @return array
     */
    public function persistedAliasMapperDataProvider($options = null): array
    {
        if (!is_array($options)) {
            $options = [];
        }
        $aspect = [
            'type' => 'PersistedAliasMapper',
            'tableName' => 'pages',
            'routeFieldName' => 'slug',
            'routeValuePrefix' => '/',
        ];

        $languages = [
            '0' => sprintf('https://acme.us/welcome/enhance/welcome%s', $options['pathSuffix'] ?? ''),
            '1' => sprintf('https://acme.fr/bienvenue/enhance/bienvenue%s', $options['pathSuffix'] ?? ''),
        ];

        return $this->createDataSet(
            $aspect,
            $languages,
            $this->getEnhancers([
                'value' => 1100,
                'additionalParameters' => $options['additionalParameters'] ?? ''
            ]),
            'value',
            ['prefix' => 'persistedAliasMapper/'],
            array_key_exists('pageTypeSettings', $options) ? $options['pageTypeSettings'] : []
        );
    }

    /**
     * @param array $enhancer
     * @param string $additionalParameters
     * @param int $targetLanguageId
     * @param string $expectation
     *
     * @test
     * @dataProvider persistedAliasMapperDataProvider
     */
    public function persistedAliasMapperIsApplied(array $enhancer, string $additionalParameters, int $targetLanguageId, string $expectation)
    {
        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => ['Enhancer' => $enhancer]
        ]);

        $response = $this->executeFrontendRequest(
            (new InternalRequest('https://acme.us/'))
                ->withPageId(1100)
                ->withInstructions([
                    $this->createTypoLinkUrlInstruction([
                        'parameter' => 1100,
                        'language' => $targetLanguageId,
                        'additionalParams' => $additionalParameters,
                        'forceAbsoluteUrl' => 1,
                    ])
                ]),
            $this->internalRequestContext
        );

        static::assertSame($expectation, (string)$response->getBody());
    }

    /**
     * @param string|array|null $options
     * @return array
     */
    public function persistedPatternMapperDataProvider($options = null): array
    {
        if (!is_array($options)) {
            $options = [];
        }
        $aspect = [
            'type' => 'PersistedPatternMapper',
            'tableName' => 'pages',
            'routeFieldPattern' => '^(?P<subtitle>.+)-(?P<uid>\d+)$',
            'routeFieldResult' => '{subtitle}-{uid}',
        ];

        $languages = [
            '0' => sprintf('https://acme.us/welcome/enhance/hello-and-welcome-1100%s', $options['pathSuffix'] ?? ''),
            '1' => sprintf('https://acme.fr/bienvenue/enhance/salut-et-bienvenue-1100%s', $options['pathSuffix'] ?? ''),
        ];

        return $this->createDataSet(
            $aspect,
            $languages,
            $this->getEnhancers([
                'value' => 1100,
                'additionalParameters' => $options['additionalParameters'] ?? ''
            ]),
            'value',
            ['prefix' => 'persistedPatternMapper/'],
            array_key_exists('pageTypeSettings', $options) ? $options['pageTypeSettings'] : []
        );
    }

    /**
     * @param array $enhancer
     * @param string $additionalParameters
     * @param int $targetLanguageId
     * @param string $expectation
     *
     * @test
     * @dataProvider persistedPatternMapperDataProvider
     */
    public function persistedPatternMapperIsApplied(array $enhancer, string $additionalParameters, int $targetLanguageId, string $expectation)
    {
        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => ['Enhancer' => $enhancer]
        ]);

        $response = $this->executeFrontendRequest(
            (new InternalRequest('https://acme.us/'))
                ->withPageId(1100)
                ->withInstructions([
                    $this->createTypoLinkUrlInstruction([
                        'parameter' => 1100,
                        'language' => $targetLanguageId,
                        'additionalParams' => $additionalParameters,
                        'forceAbsoluteUrl' => 1,
                    ])
                ]),
            $this->internalRequestContext
        );

        static::assertSame($expectation, (string)$response->getBody());
    }

    /**
     * @param string|array|null $options
     * @return array
     */
    public function staticValueMapperDataProvider($options = null): array
    {
        if (!is_array($options)) {
            $options = [];
        }
        $aspect = [
            'type' => 'StaticValueMapper',
            'map' => [
                'hundred' => 100,
            ],
            'localeMap' => [
                [
                    'locale' => 'fr_FR',
                    'map' => [
                        'cent' => 100,
                    ],
                ]
            ],
        ];

        $languages = [
            '0' => sprintf('https://acme.us/welcome/enhance/hundred%s', $options['pathSuffix'] ?? ''),
            '1' => sprintf('https://acme.fr/bienvenue/enhance/cent%s', $options['pathSuffix'] ?? ''),
        ];

        return $this->createDataSet(
            $aspect,
            $languages,
            $this->getEnhancers([
                'additionalParameters' => $options['additionalParameters'] ?? ''
            ]),
            'value',
            ['prefix' => 'staticValueMapper/'],
            array_key_exists('pageTypeSettings', $options) ? $options['pageTypeSettings'] : []
        );
    }

    /**
     * @param array $enhancer
     * @param string $additionalParameters
     * @param int $targetLanguageId
     * @param string $expectation
     *
     * @test
     * @dataProvider staticValueMapperDataProvider
     */
    public function staticValueMapperIsApplied(array $enhancer, string $additionalParameters, int $targetLanguageId, string $expectation)
    {
        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => ['Enhancer' => $enhancer]
        ]);

        $response = $this->executeFrontendRequest(
            (new InternalRequest('https://acme.us/'))
                ->withPageId(1100)
                ->withInstructions([
                    $this->createTypoLinkUrlInstruction([
                        'parameter' => 1100,
                        'language' => $targetLanguageId,
                        'additionalParams' => $additionalParameters,
                        'forceAbsoluteUrl' => 1,
                    ])
                ]),
            $this->internalRequestContext
        );

        static::assertStringStartsWith($expectation, (string)$response->getBody());
    }

    /**
     * @param string|array|null $options
     * @return array
     */
    public function staticRangeMapperDataProvider($options = null): array
    {
        if (!is_array($options)) {
            $options = [];
        }
        $aspect = [
            'type' => 'StaticRangeMapper',
            'start' => '1',
            'end' => '100',
        ];

        $dataSet = [[]];
        foreach (range(10, 100, 30) as $value) {
            $languages = [
                '0' => sprintf('https://acme.us/welcome/enhance/%s%s', $value, $options['pathSuffix'] ?? ''),
                '1' => sprintf('https://acme.fr/bienvenue/enhance/%s%s', $value, $options['pathSuffix'] ?? ''),
            ];

            $dataSet[] = $this->createDataSet(
                $aspect,
                $languages,
                $this->getEnhancers([
                    'value' => $value,
                    'additionalParameters' => $options['additionalParameters'] ?? ''
                ]),
                'value',
                [
                    'prefix' => 'staticRangeMapper/',
                    'suffix' => sprintf(', value:%d', $value),
                ],
                array_key_exists('pageTypeSettings', $options) ? $options['pageTypeSettings'] : []
            );
        }
        return array_merge(...$dataSet);
    }

    /**
     * @param array $enhancer
     * @param string $additionalParameters
     * @param int $targetLanguageId
     * @param string $expectation
     *
     * @test
     * @dataProvider staticRangeMapperDataProvider
     */
    public function staticRangeMapperIsApplied(array $enhancer, string $additionalParameters, int $targetLanguageId, string $expectation)
    {
        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => ['Enhancer' => $enhancer]
        ]);

        $response = $this->executeFrontendRequest(
            (new InternalRequest('https://acme.us/'))
                ->withPageId(1100)
                ->withInstructions([
                    $this->createTypoLinkUrlInstruction([
                        'parameter' => 1100,
                        'language' => $targetLanguageId,
                        'additionalParams' => $additionalParameters,
                        'forceAbsoluteUrl' => 1,
                    ])
                ]),
            $this->internalRequestContext
        );

        static::assertStringStartsWith($expectation, (string)$response->getBody());
    }

    /**
     * Combines all previous data providers for mappable aspects into one large
     * data set that is permuted for several page type decorator instructions.
     *
     * @return array
     */
    public function pageTypeDecoratorIsAppliedDataProvider(): array
    {
        $instructions = [
            [
                'pathSuffix' => '.html',
                'type' => null,
                'pageTypeSettings' => $this->createPageTypeDecorator()
            ],
            [
                'pathSuffix' => '.html',
                'type' => 0,
                'pageTypeSettings' => $this->createPageTypeDecorator()
            ],
            [
                'pathSuffix' => '/menu.json',
                'type' => 10,
                'pageTypeSettings' => $this->createPageTypeDecorator()
            ],
            [
                'pathSuffix' => '/',
                'type' => null,
                'pageTypeSettings' => [
                    'type' => 'PageType',
                    'default' => '/',
                    'index' => '/',
                    'map' => [
                        'menu.json' => 10,
                    ]
                ]
            ],
            [
                'pathSuffix' => '/',
                'type' => 0,
                'pageTypeSettings' => [
                    'type' => 'PageType',
                    'default' => '/',
                    'index' => '/',
                    'map' => [
                        'menu.json' => 10,
                    ]
                ]
            ]
        ];

        $dataSet = [[]];
        foreach ($instructions as $instruction) {
            $templateSuffix = sprintf(
                ' [%s=>%s]',
                $instruction['pathSuffix'],
                $instruction['type'] ?? 'null'
            );
            $dataProviderOptions = [
                'pathSuffix' => $instruction['pathSuffix'],
                'additionalParameters' => $instruction['type'] !== null
                    ? '&type=' . $instruction['type']
                    : '',
                'pageTypeSettings' => $instruction['pageTypeSettings']
            ];
            $dataSetCandidates = array_merge(
                $this->localeModifierDataProvider($dataProviderOptions),
                $this->persistedAliasMapperDataProvider($dataProviderOptions),
                $this->persistedPatternMapperDataProvider($dataProviderOptions),
                $this->staticValueMapperDataProvider($dataProviderOptions),
                $this->staticRangeMapperDataProvider($dataProviderOptions)
            );
            $dataSetCandidatesKeys = array_map(
                function (string $dataSetCandidatesKey) use ($templateSuffix) {
                    return $dataSetCandidatesKey . $templateSuffix;
                },
                array_keys($dataSetCandidates)
            );
            $dataSet[] = array_combine($dataSetCandidatesKeys, $dataSetCandidates);
        }
        return array_merge(...$dataSet);
    }

    /**
     * @param array $enhancer
     * @param string $additionalParameters
     * @param int $targetLanguageId
     * @param string $expectation
     * @param array $pageTypeSettings
     *
     * @test
     * @dataProvider pageTypeDecoratorIsAppliedDataProvider
     */
    public function pageTypeDecoratorIsApplied(array $enhancer, string $additionalParameters, int $targetLanguageId, string $expectation, array $pageTypeSettings)
    {
        if (empty($pageTypeSettings)) {
            $pageTypeSettings = $this->createPageTypeDecorator();
        }

        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => [
                'Enhancer' => $enhancer,
                'PageType' => $pageTypeSettings
            ]
        ]);

        $response = $this->executeFrontendRequest(
            (new InternalRequest('https://acme.us/'))
                ->withPageId(1100)
                ->withInstructions([
                    $this->createTypoLinkUrlInstruction([
                        'parameter' => 1100,
                        'language' => $targetLanguageId,
                        'additionalParams' => $additionalParameters,
                        'forceAbsoluteUrl' => 1,
                    ])
                ]),
            $this->internalRequestContext
        );

        static::assertStringStartsWith($expectation, (string)$response->getBody());
    }
}
