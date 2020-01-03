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
        ]);
        return Permutation::create($variables)
            ->withTargets(
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(0))
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.us/welcome/enhance/[[value]][[pathSuffix]]?cHash=',
                            Variables::create(['pathSuffix' => ''])
                        )
                    ),
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(1))
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.fr/bienvenue/augmenter/[[value]][[pathSuffix]]?cHash=',
                            Variables::create(['pathSuffix' => ''])
                        )
                    )
            )
            ->withApplicableItems($builder->declareEnhancers())
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
        $builder = Builder::create();
        $enhancerConfiguration = $builder->compileEnhancerConfiguration($testSet);
        $additionalParameters = $builder->compileGenerateParameters($testSet);
        /** @var LanguageContext $languageContext */
        $languageContext = $testSet->getSingleApplicable(LanguageContext::class);
        $targetLanguageId = $languageContext->getLanguageId();
        $expectation = $builder->compileUrl($testSet);

        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => ['Enhancer' => $enhancerConfiguration]
        ]);

        $response = $this->executeFrontendRequest(
            (new InternalRequest('https://acme.us/'))
                ->withPageId(1100)
                ->withInstructions([
                    $this->createTypoLinkUrlInstruction([
                        'parameter' => $testSet->getTargetPageId(),
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
        $builder = Builder::create();
        $enhancerConfiguration = $builder->compileEnhancerConfiguration($testSet);
        $additionalParameters = $builder->compileGenerateParameters($testSet);
        /** @var LanguageContext $languageContext */
        $languageContext = $testSet->getSingleApplicable(LanguageContext::class);
        $targetLanguageId = $languageContext->getLanguageId();
        $expectation = $builder->compileUrl($testSet);

        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => ['Enhancer' => $enhancerConfiguration]
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
        $builder = Builder::create();
        $enhancerConfiguration = $builder->compileEnhancerConfiguration($testSet);
        $additionalParameters = $builder->compileGenerateParameters($testSet);
        /** @var LanguageContext $languageContext */
        $languageContext = $testSet->getSingleApplicable(LanguageContext::class);
        $targetLanguageId = $languageContext->getLanguageId();
        $expectation = $builder->compileUrl($testSet);

        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => ['Enhancer' => $enhancerConfiguration]
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
        $builder = Builder::create();
        $enhancerConfiguration = $builder->compileEnhancerConfiguration($testSet);
        $additionalParameters = $builder->compileGenerateParameters($testSet);
        /** @var LanguageContext $languageContext */
        $languageContext = $testSet->getSingleApplicable(LanguageContext::class);
        $targetLanguageId = $languageContext->getLanguageId();
        $expectation = $builder->compileUrl($testSet);

        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => ['Enhancer' => $enhancerConfiguration]
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
            'value' => null, // defined via VariableContext
            'routePrefix' => 'enhance',
            'aspectName' => 'value',
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
        $builder = Builder::create();
        $enhancerConfiguration = $builder->compileEnhancerConfiguration($testSet);
        $additionalParameters = $builder->compileGenerateParameters($testSet);
        /** @var LanguageContext $languageContext */
        $languageContext = $testSet->getSingleApplicable(LanguageContext::class);
        $targetLanguageId = $languageContext->getLanguageId();
        $expectation = $builder->compileUrl($testSet);

        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => ['Enhancer' => $enhancerConfiguration]
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
        $additionalParameters = $builder->compileGenerateParameters($testSet);
        /** @var LanguageContext $languageContext */
        $languageContext = $testSet->getSingleApplicable(LanguageContext::class);
        $targetLanguageId = $languageContext->getLanguageId();
        $expectation = $builder->compileUrl($testSet);

        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => [
                'Enhancer' => $enhancerConfiguration,
                'PageType' => $pageTypeConfiguration,
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

    /**
     * @return array
     */
    public function defaultExtbaseControllerActionNamesAreAppliedDataProvider(): array
    {
        return [
            '*::*' => [
                '&tx_testing_link[value]=1',
                'https://acme.us/welcome/link/index/one'
            ],
            '*::list' => [
                '&tx_testing_link[action]=list&tx_testing_link[value]=1',
                'https://acme.us/welcome/link/list/one'
            ],
            'Link::*' => [
                // correctly falling back to defaultController here
                '&tx_testing_link[controller]=Link&tx_testing_link[value]=1',
                'https://acme.us/welcome/link/index/one'
            ],
            'Page::*' => [
                // correctly falling back to defaultController here
                '&tx_testing_link[controller]=Page&tx_testing_link[value]=1',
                'https://acme.us/welcome/link/index/one'
            ],
            'Page::show' => [
                '&tx_testing_link[controller]=Page&tx_testing_link[action]=show&tx_testing_link[value]=1',
                'https://acme.us/welcome/page/show/one'
            ],
        ];
    }

    /**
     * Tests whether ExtbasePluginEnhancer applies `defaultController` values correctly.
     *
     * @param string $additionalParameters
     * @param string $expectation
     *
     * @test
     * @dataProvider defaultExtbaseControllerActionNamesAreAppliedDataProvider
     */
    public function defaultExtbaseControllerActionNamesAreApplied(string $additionalParameters, string $expectation)
    {
        $targetLanguageId = 0;
        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => [
                'Enhancer' => [
                    'type' => 'Extbase',
                    'routes' => [
                        ['routePath' => '/link/index/{value}', '_controller' => 'Link::index'],
                        ['routePath' => '/link/list/{value}',  '_controller' => 'Link::list'],
                        ['routePath' => '/page/show/{value}', '_controller' => 'Page::show'],
                    ],
                    'defaultController' => 'Link::index',
                    'extension' => 'testing',
                    'plugin' => 'link',
                    'aspects' => [
                        'value' => [
                            'type' => 'StaticValueMapper',
                            'map' => [
                                'one' => 1,
                            ],
                        ],
                    ],
                ]
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

        static::assertSame($expectation, (string)$response->getBody());
    }
}
