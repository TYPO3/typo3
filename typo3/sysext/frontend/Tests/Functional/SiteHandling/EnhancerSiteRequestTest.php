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
class EnhancerSiteRequestTest extends AbstractTestCase
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
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/LinkRequest.typoscript',
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
     * @param array $enhancerLanguageUris
     * @param array $enhancers
     * @param string $variableName
     * @param string $templateSuffix
     * @return array
     */
    protected function createDataSet(
        array $aspect,
        array $enhancerLanguageUris,
        array $enhancers,
        string $variableName = 'value',
        string $templateSuffix = ''
    ): array {
        $dataSet = [];
        foreach ($enhancers as $enhancer) {
            $enhancerType = $enhancer['enhancer']['type'];
            foreach ($enhancerLanguageUris[$enhancerType] as $languageId => $uri) {
                $expectation = $enhancer['arguments'];
                $expectation['staticArguments'] = $expectation['staticArguments'] ?? [];
                $expectation['dynamicArguments'] = $expectation['dynamicArguments'] ?? [];
                $expectation['queryArguments'] = $expectation['queryArguments'] ?? [];
                if (preg_match('#\?cHash=([a-z0-9]+)#i', $uri, $matches)) {
                    $expectation['dynamicArguments']['cHash'] = $matches[1];
                    $expectation['queryArguments']['cHash'] = $matches[1];
                }
                $dataSet[] = [
                    array_merge(
                        $enhancer['enhancer'],
                        ['aspects' => [$variableName => $aspect]]
                    ),
                    $uri,
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

    /**
     * @param array $options
     * @param bool $isStatic
     * @return array
     */
    protected function getEnhancers(array $options = [], bool $isStatic = false): array
    {
        $inArguments = $isStatic ? 'staticArguments' : 'dynamicArguments';
        $options = array_merge(['name' => 'enhance', 'value' => 100], $options);
        return [
            [
                'arguments' => [
                    $inArguments => [
                        'value' => (string)$options['value'],
                    ],
                ],
                'enhancer' => [
                    'type' => 'Simple',
                    'routePath' => sprintf('/%s/{value}', $options['name']),
                    '_arguments' => [],
                ],
            ],
            [
                'arguments' => [
                    $inArguments => [
                        'testing' => [
                            'value' => (string)$options['value'],
                        ],
                    ],
                ],
                'enhancer' => [
                    'type' => 'Plugin',
                    'routePath' => sprintf('/%s/{value}', $options['name']),
                    'namespace' => 'testing',
                    '_arguments' => [],
                ],
            ],
            [
                'arguments' => array_merge_recursive([
                    $inArguments => [
                        'tx_testing_link' => [
                            'value' => (string)$options['value'],
                        ],
                    ],
                ], [
                    'staticArguments' => [
                        'tx_testing_link' => [
                            'controller' => 'Link',
                            'action' => 'index',
                        ],
                    ],
                ]),
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

        $enhancerLanguageUris = [
            'Simple' => [
                '0' => 'https://acme.us/welcome/enhance/100?cHash=46227b4ce096dc78a4e71463326c9020',
                '1' => 'https://acme.fr/bienvenue/augmenter/100?cHash=46227b4ce096dc78a4e71463326c9020',
            ],
            'Plugin' => [
                '0' => 'https://acme.us/welcome/enhance/100?cHash=e24d3d2d5503baba670d827c3b9470c8',
                '1' => 'https://acme.fr/bienvenue/augmenter/100?cHash=e24d3d2d5503baba670d827c3b9470c8',
            ],
            'Extbase' => [
                '0' => 'https://acme.us/welcome/enhance/100?cHash=eef21771ab3c3dac3514b4479eedd5ff',
                '1' => 'https://acme.fr/bienvenue/augmenter/100?cHash=eef21771ab3c3dac3514b4479eedd5ff',
            ]
        ];

        return $this->createDataSet(
            $aspect,
            $enhancerLanguageUris,
            $this->getEnhancers(['name' => '{enhance_name}']),
            'enhance_name'
        );
    }

    /**
     * @param array $enhancer
     * @param string $targetUri
     * @param int $expectedLanguageId
     * @param string $expectation
     *
     * @test
     * @dataProvider localeModifierDataProvider
     */
    public function localeModifierIsApplied(array $enhancer, string $targetUri, int $expectedLanguageId, array $expectation)
    {
        $this->assertPageArgumentsEquals(
            $enhancer,
            $targetUri,
            $expectedLanguageId,
            $expectation
        );
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

        $enhancerLanguageUris = $this->populateToKeys(
            ['Simple', 'Plugin', 'Extbase'],
            [
                '0' => 'https://acme.us/welcome/enhance/welcome',
                '1' => 'https://acme.fr/bienvenue/enhance/bienvenue',
            ]
        );

        return $this->createDataSet(
            $aspect,
            $enhancerLanguageUris,
            $this->getEnhancers(['value' => 1100], true)
        );
    }

    /**
     * @param array $enhancer
     * @param string $targetUri
     * @param int $expectedLanguageId
     * @param string $expectation
     *
     * @test
     * @dataProvider persistedAliasMapperDataProvider
     */
    public function persistedAliasMapperIsApplied(array $enhancer, string $targetUri, int $expectedLanguageId, array $expectation)
    {
        $this->assertPageArgumentsEquals(
            $enhancer,
            $targetUri,
            $expectedLanguageId,
            $expectation
        );
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

        $enhancerLanguageUris = $this->populateToKeys(
            ['Simple', 'Plugin', 'Extbase'],
            [
                '0' => 'https://acme.us/welcome/enhance/hello-and-welcome-1100',
                '1' => 'https://acme.fr/bienvenue/enhance/salut-et-bienvenue-1100',
            ]
        );

        return $this->createDataSet(
            $aspect,
            $enhancerLanguageUris,
            $this->getEnhancers(['value' => 1100], true)
        );
    }

    /**
     * @param array $enhancer
     * @param string $targetUri
     * @param int $expectedLanguageId
     * @param string $expectation
     *
     * @test
     * @dataProvider persistedPatternMapperDataProvider
     */
    public function persistedPatternMapperIsApplied(array $enhancer, string $targetUri, int $expectedLanguageId, array $expectation)
    {
        $this->assertPageArgumentsEquals(
            $enhancer,
            $targetUri,
            $expectedLanguageId,
            $expectation
        );
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

        $enhancerLanguageUris = $this->populateToKeys(
            ['Simple', 'Plugin', 'Extbase'],
            [
                '0' => 'https://acme.us/welcome/enhance/hundred',
                '1' => 'https://acme.fr/bienvenue/enhance/cent',
            ]
        );

        return $this->createDataSet(
            $aspect,
            $enhancerLanguageUris,
            $this->getEnhancers([], true)
        );
    }

    /**
     * @param array $enhancer
     * @param string $targetUri
     * @param int $expectedLanguageId
     * @param string $expectation
     *
     * @test
     * @dataProvider staticValueMapperDataProvider
     */
    public function staticValueMapperIsApplied(array $enhancer, string $targetUri, int $expectedLanguageId, array $expectation)
    {
        $this->assertPageArgumentsEquals(
            $enhancer,
            $targetUri,
            $expectedLanguageId,
            $expectation
        );
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
            $enhancerLanguageUris = $this->populateToKeys(
                ['Simple', 'Plugin', 'Extbase'],
                [
                    '0' => sprintf('https://acme.us/welcome/enhance/%s', $value),
                    '1' => sprintf('https://acme.fr/bienvenue/enhance/%s', $value),
                ]
            );

            $dataSet = array_merge(
                $dataSet,
                $this->createDataSet(
                    $aspect,
                    $enhancerLanguageUris,
                    $this->getEnhancers(['value' => $value], true),
                    'value',
                    sprintf(', value:%d', $value)
                )
            );
        }
        return $dataSet;
    }

    /**
     * @param array $enhancer
     * @param string $targetUri
     * @param int $expectedLanguageId
     * @param string $expectation
     *
     * @test
     * @dataProvider staticRangeMapperDataProvider
     */
    public function staticRangeMapperIsApplied(array $enhancer, string $targetUri, int $expectedLanguageId, array $expectation)
    {
        $this->assertPageArgumentsEquals(
            $enhancer,
            $targetUri,
            $expectedLanguageId,
            $expectation
        );
    }

    /**
     * @param array $enhancer
     * @param string $targetUri
     * @param int $expectedLanguageId
     * @param array $expectation
     */
    protected function assertPageArgumentsEquals(array $enhancer, string $targetUri, int $expectedLanguageId, array $expectation)
    {
        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => ['Enhancer' => $enhancer]
        ]);

        $allParameters = array_replace_recursive(
            $expectation['dynamicArguments'],
            $expectation['staticArguments']
        );
        $expectation['pageId'] = 1100;
        $expectation['languageId'] = $expectedLanguageId;
        $expectation['requestQueryParams'] = $allParameters;
        $expectation['_GET'] = $allParameters;

        $response = $this->executeFrontendRequest(
            new InternalRequest($targetUri),
            $this->internalRequestContext,
            true
        );

        $pageArguments = json_decode((string)$response->getBody(), true);
        static::assertEquals($expectation, $pageArguments);
    }
}
