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
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\AspectDeclaration;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Builder;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\LanguageContext;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Permutation;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\TestSet;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariableItem;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Variables;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariablesContext;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariableValue;
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
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/LinkRequest.typoscript',
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
     * @param string|TestSet|null $parentSet
     * @return array
     */
    public function localeModifierDataProvider($parentSet = null): array
    {
        $builder = Builder::create();
        // variables (applied when invoking expectations)
        $variables = Variables::create()->define([
            'value' => 100,
            'routePrefix' => '{enhance_name}',
            'aspectName' => 'enhance_name',
            'inArguments' => 'dynamicArguments' // either 'dynamicArguments' or 'staticArguments'
        ]);
        $enhancers = $builder->declareEnhancers();
        $variableContexts = [
            VariablesContext::create(
                Variables::create(['cHash' => '46227b4ce096dc78a4e71463326c9020'])
            )->withRequiredApplicables($enhancers['Simple']),
            VariablesContext::create(
                Variables::create(['cHash' => 'e24d3d2d5503baba670d827c3b9470c8'])
            )->withRequiredApplicables($enhancers['Plugin']),
            VariablesContext::create(
                Variables::create(['cHash' => 'eef21771ab3c3dac3514b4479eedd5ff'])
            )->withRequiredApplicables($enhancers['Extbase']),
        ];
        return Permutation::create($variables)
            ->withTargets(
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(0))
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.us/welcome/enhance/[[value]][[pathSuffix]]?cHash=[[cHash]]',
                            Variables::create(['pathSuffix' => ''])
                        )
                    ),
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(1))
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.fr/bienvenue/augmenter/[[value]][[pathSuffix]]?cHash=[[cHash]]',
                            Variables::create(['pathSuffix' => ''])
                        )
                    )
            )
            ->withApplicableItems($variableContexts)
            ->withApplicableItems($enhancers)
            ->withApplicableSet(
                AspectDeclaration::create('LocaleModifier')->withConfiguration([
                    VariableItem::create('aspectName', [
                        'type' => 'LocaleModifier',
                        'default' => 'enhance',
                        'localeMap' => [
                            [
                                'locale' => 'fr_FR',
                                'value' => 'augmenter'
                            ]
                        ],
                    ])
                ])
            )
            ->permute()
            ->getTargetsForDataProvider();
    }

    /**
     * @param TestSet $testSet
     *
     * @test
     * @dataProvider localeModifierDataProvider
     */
    public function localeModifierIsApplied(TestSet $testSet): void
    {
        $this->assertPageArgumentsEquals($testSet);
    }

    /**
     * @param string|TestSet|null $parentSet
     * @return array
     */
    public function persistedAliasMapperDataProvider($parentSet = null): array
    {
        $builder = Builder::create();
        // variables (applied when invoking expectations)
        $variables = Variables::create()->define([
            'value' => 1100,
            'routePrefix' => 'enhance',
            'aspectName' => 'value',
            'inArguments' => 'staticArguments' // either 'dynamicArguments' or 'staticArguments'
        ]);
        return Permutation::create($variables)
            ->withTargets(
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(0))
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.us/welcome/enhance/welcome[[pathSuffix]]',
                            Variables::create(['pathSuffix' => ''])
                        )
                    ),
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(1))
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.fr/bienvenue/enhance/bienvenue[[pathSuffix]]',
                            Variables::create(['pathSuffix' => ''])
                        )
                    )
            )
            ->withApplicableItems($builder->declareEnhancers())
            ->withApplicableSet(
                AspectDeclaration::create('PersistedAliasMapper')->withConfiguration([
                    VariableItem::create('aspectName', [
                        'type' => 'PersistedAliasMapper',
                        'tableName' => 'pages',
                        'routeFieldName' => 'slug',
                        'routeValuePrefix' => '/',
                    ])
                ])
            )
            ->permute()
            ->getTargetsForDataProvider();
    }

    /**
     * @param TestSet $testSet
     *
     * @test
     * @dataProvider persistedAliasMapperDataProvider
     */
    public function persistedAliasMapperIsApplied(TestSet $testSet): void
    {
        $this->assertPageArgumentsEquals($testSet);
    }

    /**
     * @param string|TestSet|null $parentSet
     * @return array
     */
    public function persistedPatternMapperDataProvider($parentSet = null): array
    {
        $builder = Builder::create();
        // variables (applied when invoking expectations)
        $variables = Variables::create()->define([
            'value' => 1100,
            'routePrefix' => 'enhance',
            'aspectName' => 'value',
            'inArguments' => 'staticArguments' // either 'dynamicArguments' or 'staticArguments'
        ]);
        return Permutation::create($variables)
            ->withTargets(
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(0))
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.us/welcome/enhance/hello-and-welcome-[[value]][[pathSuffix]]',
                            Variables::create(['pathSuffix' => ''])
                        )
                    ),
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(1))
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.fr/bienvenue/enhance/salut-et-bienvenue-[[value]][[pathSuffix]]',
                            Variables::create(['pathSuffix' => ''])
                        )
                    )
            )
            ->withApplicableItems($builder->declareEnhancers())
            ->withApplicableSet(
                AspectDeclaration::create('PersistedPatternMapper')->withConfiguration([
                    VariableItem::create('aspectName', [
                        'type' => 'PersistedPatternMapper',
                        'tableName' => 'pages',
                        'routeFieldPattern' => '^(?P<subtitle>.+)-(?P<uid>\d+)$',
                        'routeFieldResult' => '{subtitle}-{uid}',
                    ])
                ])
            )
            ->permute()
            ->getTargetsForDataProvider();
    }

    /**
     * @param TestSet $testSet
     *
     * @test
     * @dataProvider persistedPatternMapperDataProvider
     */
    public function persistedPatternMapperIsApplied(TestSet $testSet): void
    {
        $this->assertPageArgumentsEquals($testSet);
    }

    /**
     * @param string|TestSet|null $parentSet
     * @return array
     */
    public function staticValueMapperDataProvider($parentSet = null): array
    {
        $builder = Builder::create();
        // variables (applied when invoking expectations)
        $variables = Variables::create()->define([
            'value' => 100,
            'routePrefix' => 'enhance',
            'aspectName' => 'value',
            'inArguments' => 'staticArguments' // either 'dynamicArguments' or 'staticArguments'
        ]);
        return Permutation::create($variables)
            ->withTargets(
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(0))
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.us/welcome/enhance/hundred[[pathSuffix]]',
                            Variables::create(['pathSuffix' => ''])
                        )
                    ),
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(1))
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.fr/bienvenue/enhance/cent[[pathSuffix]]',
                            Variables::create(['pathSuffix' => ''])
                        )
                    )
            )
            ->withApplicableItems($builder->declareEnhancers())
            ->withApplicableSet(
                AspectDeclaration::create('StaticValueMapper')->withConfiguration([
                    VariableItem::create('aspectName', [
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
                    ])
                ])
            )
            ->permute()
            ->getTargetsForDataProvider();
    }

    /**
     * @param TestSet $testSet
     *
     * @test
     * @dataProvider staticValueMapperDataProvider
     */
    public function staticValueMapperIsApplied(TestSet $testSet): void
    {
        $this->assertPageArgumentsEquals($testSet);
    }

    /**
     * @param string|TestSet|null $parentSet
     * @return array
     */
    public function staticRangeMapperDataProvider($parentSet = null): array
    {
        $variableContexts = array_map(
            function ($value) {
                return VariablesContext::create(
                    Variables::create(['value' => $value])
                );
            },
            range(10, 100, 30)
        );

        $builder = Builder::create();
        // variables (applied when invoking expectations)
        $variables = Variables::create()->define([
            'value' => 1100,
            'routePrefix' => 'enhance',
            'aspectName' => 'value',
            'inArguments' => 'staticArguments' // either 'dynamicArguments' or 'staticArguments'
        ]);
        return Permutation::create($variables)
            ->withTargets(
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(0))
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.us/welcome/enhance/[[value]][[pathSuffix]]',
                            Variables::create(['pathSuffix' => ''])
                        )
                    ),
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(1))
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.fr/bienvenue/enhance/[[value]][[pathSuffix]]',
                            Variables::create(['pathSuffix' => ''])
                        )
                    )
            )
            ->withApplicableItems($variableContexts)
            ->withApplicableItems($builder->declareEnhancers())
            ->withApplicableSet(
                AspectDeclaration::create('StaticRangeMapper')->withConfiguration([
                    VariableItem::create('aspectName', [
                        'type' => 'StaticRangeMapper',
                        'start' => '1',
                        'end' => '100',
                    ])
                ])
            )
            ->permute()
            ->getTargetsForDataProvider();
    }

    /**
     * @param TestSet $testSet
     *
     * @test
     * @dataProvider staticRangeMapperDataProvider
     */
    public function staticRangeMapperIsApplied(TestSet $testSet): void
    {
        $this->assertPageArgumentsEquals($testSet);
    }

    /**
     * @return array
     */
    public function pageTypeDecoratorIsAppliedDataProvider(): array
    {
        $testSets = [];
        foreach (Builder::create()->declarePageTypes() as $pageTypeDeclaration) {
            $testSet = TestSet::create()
                ->withMergedApplicables($pageTypeDeclaration)
                ->withVariables($pageTypeDeclaration->getVariables());
            $testSets = array_merge(
                $testSets,
                $this->localeModifierDataProvider($testSet),
                $this->persistedAliasMapperDataProvider($testSet),
                $this->persistedPatternMapperDataProvider($testSet),
                $this->staticValueMapperDataProvider($testSet),
                $this->staticRangeMapperDataProvider($testSet)
            );
        }
        return $testSets;
    }

    /**
     * @param TestSet $testSet
     *
     * @test
     * @dataProvider pageTypeDecoratorIsAppliedDataProvider
     */
    public function pageTypeDecoratorIsApplied(TestSet $testSet): void
    {
        $builder = Builder::create();
        $enhancerConfiguration = $builder->compileEnhancerConfiguration($testSet);
        $pageTypeConfiguration = $builder->compilePageTypeConfiguration($testSet);
        $targetUri = $builder->compileUrl($testSet);
        /** @var LanguageContext $languageContext */
        $languageContext = $testSet->getSingleApplicable(LanguageContext::class);
        $expectedLanguageId = $languageContext->getLanguageId();
        $expectation = $builder->compileResolveArguments($testSet);

        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => [
                'Enhancer' => $enhancerConfiguration,
                'PageType' => $pageTypeConfiguration,
            ]
        ]);

        $allParameters = array_replace_recursive(
            $expectation['dynamicArguments'],
            $expectation['staticArguments']
        );
        $expectation['pageId'] = $testSet->getTargetPageId();
        $expectation['languageId'] = $expectedLanguageId;
        $expectation['requestQueryParams'] = $allParameters;
        $expectation['_GET'] = $allParameters;

        $response = $this->executeFrontendRequest(
            new InternalRequest($targetUri),
            $this->internalRequestContext,
            true
        );

        $pageArguments = json_decode((string)$response->getBody(), true);
        self::assertEquals($expectation, $pageArguments);
    }

    public function routeIdentifiersAreResolvedDataProvider(): array
    {
        return [
            // namespace[value]
            'namespace[value] ? test' => [
                'namespace',
                'value',
                'test',
            ],
            'namespace[value] ? x^31' => [
                'namespace',
                'value',
                str_repeat('x', 31),
            ],
            'namespace[value] ? x^32' => [
                'namespace',
                'value',
                str_repeat('x', 32),
            ],
            'namespace[value] ? x^33' => [
                'namespace',
                'value',
                str_repeat('x', 33),
            ],
            'namespace[value] ? 1^32 (type-cast)' => [
                'namespace',
                'value',
                str_repeat('1', 32),
            ],
            // md5('namespace__@otne3') is 60360798585102000952995164024754 (numeric)
            // md5('ximaz') is 61529519452809720693702583126814 (numeric)
            'namespace[@otne3] ? numeric-md5 (type-cast)' => [
                'namespace',
                '@otne3',
                md5('ximaz'),
            ],
            'namespace[value] ? namespace__value' => [
                'namespace',
                'value',
                'namespace__value',
            ],
            'namespace[value] ? namespace/value' => [
                'namespace',
                'value',
                'namespace/value',
                'The requested URL is not distinct',
            ],
            'namespace[value] ? namespace__other' => [
                'namespace',
                'value',
                'namespace__other',
            ],
            'namespace[value] ? namespace/other' => [
                'namespace',
                'value',
                'namespace/other',
            ],
            // namespace[any/value]
            'namespace[any/value] ? x^31' => [
                'namespace',
                'any/value',
                str_repeat('x', 31),
            ],
            'namespace[any/value] ? x^32' => [
                'namespace',
                'any/value',
                str_repeat('x', 32),
            ],
            'namespace[any/value] ? namespace__any__value' => [
                'namespace',
                'any/value',
                'namespace__any__value',
            ],
            'namespace[any/value] ? namespace/any/value' => [
                'namespace',
                'any/value',
                'namespace/any/value',
                'The requested URL is not distinct',
            ],
            'namespace[any/value] ? namespace__any__other' => [
                'namespace',
                'any/value',
                'namespace__any__other',
            ],
            'namespace[any/value] ? namespace/any/other' => [
                'namespace',
                'any/value',
                'namespace/any/other',
            ],
            // namespace[@any/value]
            'namespace[@any/value] ? x^31' => [
                'namespace',
                '@any/value',
                str_repeat('x', 31),
            ],
            'namespace[@any/value] ? x^32' => [
                'namespace',
                '@any/value',
                str_repeat('x', 32),
            ],
            'namespace[@any/value] ? md5(namespace__@any__value)' => [
                'namespace',
                '@any/value',
                md5('namespace__@any__value'),
            ],
            'namespace[@any/value] ? namespace/@any/value' => [
                'namespace',
                '@any/value',
                'namespace/@any/value',
                'The requested URL is not distinct',
            ],
            'namespace[@any/value] ? md5(namespace__@any__other)' => [
                'namespace',
                '@any/value',
                md5('namespace__@any__other'),
            ],
            'namespace[@any/value] ? namespace/@any/other' => [
                'namespace',
                '@any/value',
                'namespace/@any/other',
            ],
        ];
    }

    /**
     * @param string $namespace
     * @param string $argumentName
     * @param string $queryPath
     * @param string|null $failureReason
     *
     * @test
     * @dataProvider routeIdentifiersAreResolvedDataProvider
     */
    public function routeIdentifiersAreResolved(string $namespace, string $argumentName, string $queryPath, string $failureReason = null)
    {
        $query = [];
        $routeValue = 'route-value';
        $queryValue = 'parameter-value';
        $query = ArrayUtility::setValueByPath($query, $queryPath, $queryValue);
        $queryParameters = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $targetUri = sprintf('https://acme.us/welcome/%s?%s', $routeValue, $queryParameters);

        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => ['Enhancer' => [
                'type' => 'Plugin',
                'routePath' => '/{name}',
                '_arguments' => [
                    'name' => $argumentName,
                ],
                'namespace' => $namespace,
            ]]
        ]);

        $response = $this->executeFrontendRequest(
            new InternalRequest($targetUri),
            $this->internalRequestContext,
            true
        );

        $body = (string)$response->getBody();
        if ($failureReason === null) {
            $pageArguments = json_decode($body, true);
            self::assertNotNull($pageArguments, 'PageArguments could not be resolved');

            $expected = [];
            $expected = ArrayUtility::setValueByPath($expected, $namespace . '/' . $argumentName, $routeValue);
            $expected = ArrayUtility::setValueByPath($expected, $queryPath, $queryValue);
            self::assertEquals($expected, $pageArguments['requestQueryParams']);
        } else {
            self::assertStringContainsString($failureReason, $body);
        }
    }

    /**
     * @param TestSet $testSet
     */
    protected function assertPageArgumentsEquals(TestSet $testSet)
    {
        $builder = Builder::create();
        $enhancerConfiguration = $builder->compileEnhancerConfiguration($testSet);
        $targetUri = $builder->compileUrl($testSet);
        /** @var LanguageContext $languageContext */
        $languageContext = $testSet->getSingleApplicable(LanguageContext::class);
        $expectedLanguageId = $languageContext->getLanguageId();
        $expectation = $builder->compileResolveArguments($testSet);

        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => ['Enhancer' => $enhancerConfiguration]
        ]);

        $allParameters = array_replace_recursive(
            $expectation['dynamicArguments'],
            $expectation['staticArguments']
        );
        $expectation['pageId'] = 1100;
        $expectation['pageType'] = '0';
        $expectation['languageId'] = $expectedLanguageId;
        $expectation['requestQueryParams'] = $allParameters;
        $expectation['_GET'] = $allParameters;

        $response = $this->executeFrontendRequest(
            new InternalRequest($targetUri),
            $this->internalRequestContext,
            true
        );

        $pageArguments = json_decode((string)$response->getBody(), true);
        self::assertEquals($expectation, $pageArguments);
    }
}
