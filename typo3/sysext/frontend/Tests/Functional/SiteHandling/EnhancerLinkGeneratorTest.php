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

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::initializeDatabaseSnapshot();
    }

    public static function tearDownAfterClass()
    {
        static::destroyDatabaseSnapshot();
        parent::tearDownAfterClass();
    }

    protected function setUp()
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

    protected function tearDown()
    {
        unset($this->internalRequestContext);
        parent::tearDown();
    }

    /**
     * @param array $aspect
     * @param array $languages
     * @param array $enhancers
     * @param string $variableName
     * @param string $templateSuffix
     * @return array
     */
    protected function createDataSet(
        array $aspect,
        array $languages,
        array $enhancers,
        string $variableName = 'value',
        string $templateSuffix = ''
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
                ];
            }
        }
        return $this->keysFromTemplate(
            $dataSet,
            'enhancer:%1$s, lang:%3$d' . $templateSuffix,
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

    protected function getEnhancers(array $options = []): array
    {
        $options = array_merge(['name' => 'enhance', 'value' => 100], $options);
        return [
            [
                'parameters' => sprintf('&value=%s', $options['value']),
                'enhancer' => [
                    'type' => 'Simple',
                    'routePath' => sprintf('/%s/{value}', $options['name']),
                    '_arguments' => [],
                ],
            ],
            [
                'parameters' => sprintf('&testing[value]=%s', $options['value']),
                'enhancer' => [
                    'type' => 'Plugin',
                    'routePath' => sprintf('/%s/{value}', $options['name']),
                    'namespace' => 'testing',
                    '_arguments' => [],
                ],
            ],
            [
                'parameters' => sprintf('&tx_testing_link[value]=%s&tx_testing_link[controller]=Link&tx_testing_link[action]=index', $options['value']),
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
    public function localeModifierDataProvider(): array
    {
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
            '0' => 'https://acme.us/welcome/enhance/100?cHash=',
            '1' => 'https://acme.fr/bienvenue/augmenter/100?cHash=',
        ];

        return $this->createDataSet(
            $aspect,
            $languages,
            $this->getEnhancers(['name' => '{enhance_name}']),
            'enhance_name'
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
     * @return array
     */
    public function persistedAliasMapperDataProvider(): array
    {
        $aspect = [
            'type' => 'PersistedAliasMapper',
            'tableName' => 'pages',
            'routeFieldName' => 'slug',
            'routeValuePrefix' => '/',
        ];

        $languages = [
            '0' => 'https://acme.us/welcome/enhance/welcome',
            '1' => 'https://acme.fr/bienvenue/enhance/bienvenue',
        ];

        return $this->createDataSet(
            $aspect,
            $languages,
            $this->getEnhancers(['value' => 1100])
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
     * @return array
     */
    public function persistedPatternMapperDataProvider(): array
    {
        $aspect = [
            'type' => 'PersistedPatternMapper',
            'tableName' => 'pages',
            'routeFieldPattern' => '^(?P<subtitle>.+)-(?P<uid>\d+)$',
            'routeFieldResult' => '{subtitle}-{uid}',
        ];

        $languages = [
            '0' => 'https://acme.us/welcome/enhance/hello-and-welcome-1100',
            '1' => 'https://acme.fr/bienvenue/enhance/salut-et-bienvenue-1100',
        ];

        return $this->createDataSet(
            $aspect,
            $languages,
            $this->getEnhancers(['value' => 1100])
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
     * @return array
     */
    public function staticValueMapperDataProvider(): array
    {
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
            '0' => 'https://acme.us/welcome/enhance/hundred',
            '1' => 'https://acme.fr/bienvenue/enhance/cent',
        ];

        return $this->createDataSet($aspect, $languages, $this->getEnhancers());
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
     * @return array
     */
    public function staticRangeMapperDataProvider(): array
    {
        $aspect = [
            'type' => 'StaticRangeMapper',
            'start' => '1',
            'end' => '100',
        ];

        $dataSet = [];
        foreach (range(10, 100, 30) as $value) {
            $languages = [
                '0' => sprintf('https://acme.us/welcome/enhance/%s', $value),
                '1' => sprintf('https://acme.fr/bienvenue/enhance/%s', $value),
            ];

            $dataSet = array_merge(
                $dataSet,
                $this->createDataSet(
                    $aspect,
                    $languages,
                    $this->getEnhancers(['value' => $value]),
                    'value',
                    sprintf(', value:%d', $value)
                )
            );
        }
        return $dataSet;
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
}
