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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\ApplicableConjunction;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\AspectDeclaration;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Builder;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\EnhancerDeclaration;
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

        $this->writeSiteConfiguration(
            'archive-acme-com',
            $this->buildSiteConfiguration(3000, 'https://archive.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', 'https://archive.acme.com/fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', 'https://archive.acme.com/ca/', ['FR', 'EN'])
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
                    ),
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(0))
                    ->withTargetPageId(3000)
                    ->withUrl(
                        VariableValue::create(
                            'https://archive.acme.com/enhance/[[value]][[pathSuffix]]?cHash=',
                            Variables::create(['pathSuffix' => ''])
                        )
                    ),
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(1))
                    ->withTargetPageId(3000)
                    ->withUrl(
                        VariableValue::create(
                            'https://archive.acme.com/fr/augmenter/[[value]][[pathSuffix]]?cHash=',
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
        $this->mergeSiteConfiguration('archive-acme-com', [
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

        $body = (string)$response->getBody();
        self::assertStringStartsWith($expectation, $body);
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

        self::assertSame($expectation, (string)$response->getBody());
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
                    ),
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(0))
                    ->withTargetPageId(3000)
                    ->withUrl(
                        VariableValue::create(
                            'https://archive.acme.com/enhance/hello-and-welcome-[[value]][[pathSuffix]]',
                            Variables::create(['pathSuffix' => ''])
                        )
                    ),
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(1))
                    ->withTargetPageId(3000)
                    ->withUrl(
                        VariableValue::create(
                            'https://archive.acme.com/fr/enhance/salut-et-bienvenue-[[value]][[pathSuffix]]',
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
        $this->mergeSiteConfiguration('archive-acme-com', [
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

        self::assertSame($expectation, (string)$response->getBody());
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
                    ),
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(0))
                    ->withTargetPageId(3000)
                    ->withUrl(
                        VariableValue::create(
                            'https://archive.acme.com/enhance/hundred[[pathSuffix]]',
                            Variables::create(['pathSuffix' => ''])
                        )
                    ),
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(1))
                    ->withTargetPageId(3000)
                    ->withUrl(
                        VariableValue::create(
                            'https://archive.acme.com/fr/enhance/cent[[pathSuffix]]',
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
        $this->mergeSiteConfiguration('archive-acme-com', [
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

        self::assertStringStartsWith($expectation, (string)$response->getBody());
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
                        'parameter' => $testSet->getTargetPageId(),
                        'language' => $targetLanguageId,
                        'additionalParams' => $additionalParameters,
                        'forceAbsoluteUrl' => 1,
                    ])
                ]),
            $this->internalRequestContext
        );

        self::assertStringStartsWith($expectation, (string)$response->getBody());
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

        $this->mergeSiteConfiguration('archive-acme-com', [
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
                        'parameter' => $testSet->getTargetPageId(),
                        'language' => $targetLanguageId,
                        'additionalParams' => $additionalParameters,
                        'forceAbsoluteUrl' => 1,
                    ])
                ]),
            $this->internalRequestContext
        );

        self::assertStringStartsWith($expectation, (string)$response->getBody());
    }

    public function routeDefaultsForSingleParameterAreConsideredDataProvider($parentSet = null): array
    {
        $builder = Builder::create();
        $enhancerDeclarations = $builder->declareEnhancers();
        // variables (applied when invoking expectations)
        $variables = Variables::create()->define([
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
                            'https://acme.us/welcome/enhance[[uriValue]][[pathSuffix]]',
                            Variables::create(['pathSuffix' => '', 'uriValue' => '/hundred'])
                        )
                    ),
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(1))
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.fr/bienvenue/enhance[[uriValue]][[pathSuffix]]',
                            Variables::create(['pathSuffix' => '', 'uriValue' => '/cent'])
                        )
                    )
            )
            ->withApplicableSet(
                $enhancerDeclarations['Simple'],
                // cannot use Plugin enhancer here - won't be used if no parameters for plugin namespace are given
                // $enhancerDeclarations['Plugin']
                //  ->withConfiguration(['routePath' => $routePath], true),
                $enhancerDeclarations['Extbase']
            )
            ->withApplicableSet(
                EnhancerDeclaration::create('defaults.value=100')->withConfiguration([
                    'defaults' => ['value' => 100],
                ])
            )
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
            ->withApplicableSet(
                VariablesContext::create(Variables::create([
                    'routeParameter' => '{value}',
                    'uriValue' => '',
                ])),
                VariablesContext::create(Variables::create([
                    'routeParameter' => '{!value}',
                ]))
            )
            ->withApplicableSet(
                VariablesContext::create(Variables::create([
                    'value' => null,
                ])),
                VariablesContext::create(Variables::create([
                    'value' => 100,
                ]))
            )
            ->permute()
            ->getTargetsForDataProvider();
    }

    /**
     * @param TestSet $testSet
     *
     * @test
     * @dataProvider routeDefaultsForSingleParameterAreConsideredDataProvider
     */
    public function routeDefaultsForSingleParameterAreConsidered(TestSet $testSet): void
    {
        $this->assertGeneratedUriEquals($testSet);
    }

    public function routeDefaultsForMultipleParametersAreConsideredDataProvider($parentSet = null): array
    {
        $builder = Builder::create();
        $routePath = VariableValue::create('/[[routePrefix]]/[[routeParameter]]/{additional}');
        $enhancerDeclarations = $builder->declareEnhancers();
        // variables (applied when invoking expectations)
        $variables = Variables::create()->define([
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
                            'https://acme.us/welcome/enhance/hundred/20[[pathSuffix]]',
                            Variables::create(['pathSuffix' => ''])
                        )
                    ),
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(1))
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.fr/bienvenue/enhance/cent/20[[pathSuffix]]',
                            Variables::create(['pathSuffix' => ''])
                        )
                    )
            )
            ->withApplicableSet(
                $enhancerDeclarations['Simple']
                    ->withConfiguration(['routePath' => $routePath], true)
                    ->withGenerateParameters(['&additional=20'], true),
                $enhancerDeclarations['Plugin']
                    ->withConfiguration(['routePath' => $routePath], true)
                    ->withGenerateParameters(['&testing[additional]=20'], true),
                $enhancerDeclarations['Extbase']
                    ->withConfiguration(['routes' => [0 => ['routePath' => $routePath]]], true)
                    ->withGenerateParameters(['&tx_testing_link[additional]=20'], true)
            )
            ->withApplicableSet(
                EnhancerDeclaration::create('defaults.value=100')->withConfiguration([
                    'defaults' => ['value' => 100],
                ])
            )
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
            ->withApplicableSet(
                VariablesContext::create(Variables::create([
                    'routeParameter' => '{value}',
                ])),
                VariablesContext::create(Variables::create([
                    'routeParameter' => '{!value}',
                ]))
            )
            ->withApplicableSet(
                VariablesContext::create(Variables::create([
                    'value' => null,
                ])),
                VariablesContext::create(Variables::create([
                    'value' => 100,
                ]))
            )
            ->permute()
            ->getTargetsForDataProvider();
    }

    /**
     * @param TestSet $testSet
     *
     * @test
     * @dataProvider routeDefaultsForMultipleParametersAreConsideredDataProvider
     */
    public function routeDefaultsForMultipleParametersAreConsidered(TestSet $testSet): void
    {
        $this->assertGeneratedUriEquals($testSet);
    }

    public function routeRequirementsHavingAspectsAreConsideredDataProvider($parentSet = null): array
    {
        $builder = Builder::create();
        // variables (applied when invoking expectations)
        $variables = Variables::create()->define([
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
                            'https://acme.us/welcome/enhance/[[resolveValue]][[pathSuffix]]',
                            Variables::create(['pathSuffix' => ''])
                        )
                    )
            )
            ->withApplicableSet(
                VariablesContext::create(Variables::create([
                    'value' => 100,
                    'resolveValue' => 'hundred',
                ])),
                VariablesContext::create(Variables::create([
                    'value' => 1100100,
                    'resolveValue' => 'hundred/binary',
                ])),
                ApplicableConjunction::create(
                    VariablesContext::create(Variables::create([
                        'value' => 100,
                        'resolveValue' => 'hundred',
                    ])),
                    EnhancerDeclaration::create('requirements.value=/[a-z_/]+/')->withConfiguration([
                        'requirements' => [
                            'value' => '[a-z_/]+',
                        ]
                    ])
                ),
                ApplicableConjunction::create(
                    VariablesContext::create(Variables::create([
                        'value' => 1100100,
                        'resolveValue' => 'hundred/binary',
                    ])),
                    EnhancerDeclaration::create('requirements.value=/[a-z_/]+/')->withConfiguration([
                        'requirements' => [
                            'value' => '[a-z_/]+',
                        ]
                    ])
                )
            )
            ->withApplicableItems($builder->declareEnhancers())
            ->withApplicableSet(
                AspectDeclaration::create('StaticValueMapper')->withConfiguration([
                    VariableItem::create('aspectName', [
                        'type' => 'StaticValueMapper',
                        'map' => [
                            'hundred' => 100,
                            'hundred/binary' => 1100100,
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
     * @dataProvider routeRequirementsHavingAspectsAreConsideredDataProvider
     */
    public function routeRequirementsHavingAspectsAreConsidered(TestSet $testSet): void
    {
        $this->assertGeneratedUriEquals($testSet);
    }

    private function assertGeneratedUriEquals(TestSet $testSet): void
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

        self::assertStringStartsWith($expectation, (string)$response->getBody());
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

        self::assertSame($expectation, (string)$response->getBody());
    }
}
