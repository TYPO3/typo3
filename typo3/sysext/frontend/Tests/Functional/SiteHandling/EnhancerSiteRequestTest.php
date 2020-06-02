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
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\ApplicableConjunction;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\AspectDeclaration;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Builder;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\EnhancerDeclaration;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\ExceptionExpectation;
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
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/LinkRequest.typoscript',
            ],
            [
                'title' => 'ACME Root',
                'sitetitle' => $this->siteTitle,
            ]
        );

        $this->setUpFrontendRootPage(
            3000,
            [
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/LinkRequest.typoscript',
            ],
            [
                'title' => 'ACME Archive',
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
            'resolveValue' => 100,
            'routePrefix' => '{enhance_name}',
            'aspectName' => 'enhance_name',
            'inArguments' => 'dynamicArguments' // either 'dynamicArguments' or 'staticArguments'
        ]);
        $enhancers = $builder->declareEnhancers();
        $variableContexts = [
            VariablesContext::create(
                Variables::create([
                    'cHash' => '46227b4ce096dc78a4e71463326c9020',
                    'cHash2' => 'f80d112e877175ce8e7d54c35bebe12c'
                ])
            )->withRequiredApplicables($enhancers['Simple']),
            VariablesContext::create(
                Variables::create([
                    'cHash' => 'e24d3d2d5503baba670d827c3b9470c8',
                    'cHash2' => '54f45ea94a5e812fbae944792dac940d'
                ])
            )->withRequiredApplicables($enhancers['Plugin']),
            VariablesContext::create(
                Variables::create([
                    'cHash' => 'eef21771ab3c3dac3514b4479eedd5ff',
                    'cHash2' => 'c822555d4ebd106b0d1687e43a4db9c9'
                ])
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
                    ),
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(0))
                    ->withTargetPageId(3000)
                    ->withUrl(
                        VariableValue::create(
                            'https://archive.acme.com/enhance/[[value]][[pathSuffix]]?cHash=[[cHash2]]',
                            Variables::create(['pathSuffix' => ''])
                        )
                    ),
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(1))
                    ->withTargetPageId(3000)
                    ->withUrl(
                        VariableValue::create(
                            'https://archive.acme.com/fr/augmenter/[[value]][[pathSuffix]]?cHash=[[cHash2]]',
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
            'resolveValue' => 1100,
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
            'resolveValue' => 1100,
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
            'resolveValue' => 100,
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
                    Variables::create([
                        'value' => $value,
                        'resolveValue' => $value,
                    ])
                );
            },
            range(10, 100, 30)
        );

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

            $testSetWithoutEnhancers =
                TestSet::create($testSet)
                    ->withMergedApplicables(LanguageContext::create(0))
                    ->withTargetPageId(3000)
                    ->withUrl(
                        VariableValue::create(
                            'https://archive.acme.com/[[index]][[pathSuffix]]',
                            Variables::create(['pathSuffix' => '', 'index' => ''])
                        )
                    )
            ;
            $testSets = array_merge(
                $testSets,
                [$testSetWithoutEnhancers->describe() => [$testSetWithoutEnhancers]],
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

        $overrides = [
            'routeEnhancers' => [
                'PageType' => $pageTypeConfiguration,
            ]
        ];
        if ($enhancerConfiguration) {
            $overrides['routeEnhancers']['Enhancer'] = $enhancerConfiguration;
        }
        $this->mergeSiteConfiguration('acme-com', $overrides);
        $this->mergeSiteConfiguration('archive-acme-com', $overrides);

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

    /**
     * @return array
     */
    public function pageTypeDecoratorIndexCanBePartOfSlugDataProvider(): array
    {
        $testSets = [];
        foreach (Builder::create()->declarePageTypes() as $pageTypeDeclaration) {
            $testSet = TestSet::create()
                ->withMergedApplicables($pageTypeDeclaration)
                ->withVariables($pageTypeDeclaration->getVariables());

            $testSetForPageContainingIndexInSlug =
                TestSet::create($testSet)
                    ->withMergedApplicables(LanguageContext::create(0))
                    ->withTargetPageId(3200)
                    ->withUrl(
                        VariableValue::create(
                            'https://archive.acme.com/stock-index[[pathSuffix]]',
                            Variables::create(['pathSuffix' => ''])
                        )
                    )
            ;
            $testSets = array_merge(
                $testSets,
                [$testSetForPageContainingIndexInSlug->describe() => [$testSetForPageContainingIndexInSlug]]
            );
        }
        return $testSets;
    }

    /**
     * @param TestSet $testSet
     *
     * @test
     * @dataProvider pageTypeDecoratorIndexCanBePartOfSlugDataProvider
     */
    public function pageTypeDecoratorIndexCanBePartOfSlug(TestSet $testSet): void
    {
        $builder = Builder::create();
        $targetUri = $builder->compileUrl($testSet);
        /** @var LanguageContext $languageContext */
        $languageContext = $testSet->getSingleApplicable(LanguageContext::class);
        $expectedLanguageId = $languageContext->getLanguageId();
        $expectation = $builder->compileResolveArguments($testSet);

        $overrides = [
            'routeEnhancers' => [
                'PageType' => $builder->compilePageTypeConfiguration($testSet),
            ]
        ];
        $this->mergeSiteConfiguration('archive-acme-com', $overrides);

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

    public function routeDefaultsAreConsideredDataProvider($parentSet = null): array
    {
        $builder = Builder::create();
        // variables (applied when invoking expectations)
        $variables = Variables::create()->define([
            'uriValue' => '',
            'resolveValue' => 100,
            'routePrefix' => 'enhance',
            'aspectName' => 'value',
            'inArguments' => 'staticArguments', // either 'dynamicArguments' or 'staticArguments'
            'otherInArguments' => null,
        ]);
        $enhancerDeclarations = $builder->declareEnhancers();
        $englishLanguage = LanguageContext::create(0);
        $frenchLanguage = LanguageContext::create(1);
        $plainRouteParameter = VariablesContext::create(Variables::create(['routeParameter' => '{value}']));
        $enforcedRouteParameter = VariablesContext::create(Variables::create(['routeParameter' => '{!value}']));
        return Permutation::create($variables)
            ->withTargets(
                TestSet::create($parentSet)
                    ->withMergedApplicables($englishLanguage)
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.us/welcome/enhance[[uriValue]][[pathSuffix]]',
                            Variables::create(['pathSuffix' => '', 'uriValue' => '/hundred'])
                        )
                    ),
                TestSet::create($parentSet)
                    ->withMergedApplicables($frenchLanguage)
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.fr/bienvenue/enhance[[uriValue]][[pathSuffix]]',
                            Variables::create(['pathSuffix' => '', 'uriValue' => '/cent'])
                        )
                    )
            )
            ->withApplicableItems($builder->declareEnhancers())
            ->withApplicableSet(
                EnhancerDeclaration::create('defaults.value=100')->withConfiguration([
                    'defaults' => [
                        'value' => 100,
                        // it's expected that `other` is NOT applied in page arguments
                        // since it is not used as `{other}` in `routePath`
                        'other' => 200,
                    ]
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
            ->withApplicableSet($plainRouteParameter, $enforcedRouteParameter)
            ->withApplicableSet(
                // @todo Default route not resolved having enforced route parameter `{!value}`
                VariablesContext::create(Variables::create([
                    'uriValue' => null,
                ]))->withRequiredApplicables($plainRouteParameter),
                VariablesContext::create(Variables::create([
                    'uriValue' => '/hundred',
                ]))->withRequiredApplicables($englishLanguage),
                VariablesContext::create(Variables::create([
                    'uriValue' => '/cent',
                ]))->withRequiredApplicables($frenchLanguage)
            )
            ->permute()
            ->getTargetsForDataProvider();
    }

    /**
     * @param TestSet $testSet
     *
     * @test
     * @dataProvider routeDefaultsAreConsideredDataProvider
     */
    public function routeDefaultsAreConsidered(TestSet $testSet): void
    {
        $this->assertPageArgumentsEquals($testSet);
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
                            'https://acme.us/welcome/enhance/[[value]][[pathSuffix]]',
                            Variables::create(['pathSuffix' => ''])
                        )
                    )
            )
            ->withApplicableSet(
                VariablesContext::create(Variables::create([
                    'value' => 'hundred',
                    'resolveValue' => 100,
                ])),
                VariablesContext::create(Variables::create([
                    'value' => 'hundred/binary',
                    'resolveValue' => 1100100,
                ])),
                ApplicableConjunction::create(
                    VariablesContext::create(Variables::create([
                        'value' => 'hundred',
                        'resolveValue' => 100,
                    ])),
                    EnhancerDeclaration::create('requirements.value=/[a-z_/]+/')->withConfiguration([
                        'requirements' => [
                            'value' => '[a-z_/]+',
                        ]
                    ])
                ),
                ApplicableConjunction::create(
                    VariablesContext::create(Variables::create([
                        'value' => 'hundred/binary',
                        'resolveValue' => 1100100,
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
        $this->assertPageArgumentsEquals($testSet);
    }

    public function routeRequirementsAreConsideredDataProvider($parentSet = null): array
    {
        $builder = Builder::create();
        // variables (applied when invoking expectations)
        $variables = Variables::create()->define([
            'resolveValue' => 100,
            'routePrefix' => 'enhance',
            'aspectName' => 'value',
            'inArguments' => 'dynamicArguments' // either 'dynamicArguments' or 'staticArguments'
        ]);
        $enhancers = $builder->declareEnhancers();
        $variableContexts = [
            VariablesContext::create(
                Variables::create([
                    'cHash' => '46227b4ce096dc78a4e71463326c9020',
                ])
            )->withRequiredApplicables($enhancers['Simple']),
            VariablesContext::create(
                Variables::create([
                    'cHash' => 'e24d3d2d5503baba670d827c3b9470c8',
                ])
            )->withRequiredApplicables($enhancers['Plugin']),
            VariablesContext::create(
                Variables::create([
                    'cHash' => 'eef21771ab3c3dac3514b4479eedd5ff',
                ])
            )->withRequiredApplicables($enhancers['Extbase']),
        ];
        return Permutation::create($variables)
            ->withTargets(
                TestSet::create($parentSet)
                    ->withMergedApplicables(LanguageContext::create(0))
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.us/welcome/enhance/[[uriValue]][[pathSuffix]]?cHash=[[cHash]]',
                            Variables::create(['pathSuffix' => ''])
                        )
                    )
            )
            ->withApplicableItems($enhancers)
            ->withApplicableItems($variableContexts)
            ->withApplicableSet(
                VariablesContext::create(Variables::create([
                    'uriValue' => 100,
                ])),
                ApplicableConjunction::create(
                    VariablesContext::create(Variables::create([
                        'uriValue' => 100,
                        'cHash' => ''
                    ])),
                    ExceptionExpectation::create('Missing cHash')
                        ->withClassName(PageNotFoundException::class)
                        ->withMessage('Request parameters could not be validated (&cHash empty)')
                        ->withCode(1518472189)
                ),
                ApplicableConjunction::create(
                    VariablesContext::create(Variables::create([
                        'uriValue' => 99,
                    ])),
                    ExceptionExpectation::create('too short')
                        ->withClassName(PageNotFoundException::class)
                        ->withMessage('The requested page does not exist')
                        ->withCode(1518472189)
                ),
                ApplicableConjunction::create(
                    VariablesContext::create(Variables::create([
                        'uriValue' => 99999,
                    ])),
                    ExceptionExpectation::create('too long')
                        ->withClassName(PageNotFoundException::class)
                        ->withMessage('The requested page does not exist')
                        ->withCode(1518472189)
                ),
                ApplicableConjunction::create(
                    VariablesContext::create(Variables::create([
                        'uriValue' => 'NaN',
                    ])),
                    ExceptionExpectation::create('NaN')
                        ->withClassName(PageNotFoundException::class)
                        ->withMessage('The requested page does not exist')
                        ->withCode(1518472189)
                )
            )
            ->withApplicableSet(
                EnhancerDeclaration::create('requirements.value=\\d{3}')->withConfiguration([
                    'requirements' => [
                        'value' => '\\d{3}',
                    ]
                ])
            )
            ->permute()
            ->getTargetsForDataProvider();
    }

    /**
     * @param TestSet $testSet
     *
     * @test
     * @dataProvider routeRequirementsAreConsideredDataProvider
     */
    public function routeRequirementsAreConsidered(TestSet $testSet): void
    {
        $this->assertPageArgumentsEquals($testSet);
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
            'namespace[value] ? x^30' => [
                'namespace',
                'value',
                str_repeat('x', 30),
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
            'namespace[value] ? 1^31 (type-cast)' => [
                'namespace',
                'value',
                str_repeat('1', 31),
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
            'namespace[any/value] ? x^30' => [
                'namespace',
                'any/value',
                str_repeat('x', 30),
            ],
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
            'namespace[@any/value] ? x^30' => [
                'namespace',
                '@any/value',
                str_repeat('x', 30),
            ],
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
        $this->mergeSiteConfiguration('archive-acme-com', [
            'routeEnhancers' => ['Enhancer' => $enhancerConfiguration]
        ]);

        $allParameters = array_replace_recursive(
            $expectation['dynamicArguments'],
            $expectation['staticArguments']
        );
        $expectation['pageId'] = $testSet->getTargetPageId();
        $expectation['pageType'] = '0';
        $expectation['languageId'] = $expectedLanguageId;
        $expectation['requestQueryParams'] = $allParameters;
        $expectation['_GET'] = $allParameters;

        $response = $this->executeFrontendRequest(
            new InternalRequest($targetUri),
            $this->internalRequestContext,
            true
        );

        /** @var ExceptionExpectation $exceptionDeclaration */
        $exceptionDeclaration = $testSet->getSingleApplicable(ExceptionExpectation::class);
        if ($exceptionDeclaration !== null) {
            // @todo This part is "ugly"...
            self::assertSame(404, $response->getStatusCode());
            self::assertStringContainsString(
                // searching in HTML content...
                htmlspecialchars($exceptionDeclaration->getMessage()),
                (string)$response->getBody()
            );
        } else {
            $pageArguments = json_decode((string)$response->getBody(), true);
            self::assertSame(200, $response->getStatusCode());
            self::assertEquals($expectation, $pageArguments);
        }
    }
}
