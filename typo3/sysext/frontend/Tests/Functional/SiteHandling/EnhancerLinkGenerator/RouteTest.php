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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\EnhancerLinkGenerator;

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
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\TestSetDataProviderTrait;

class RouteTest extends AbstractEnhancerLinkGeneratorTestCase
{
    use TestSetDataProviderTrait;

    public function routeDefaultsForSingleParameterAreConsideredDataProvider(string|TestSet|null $parentSet = null): array
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
                                'locale' => 'fr-FR',
                                'map' => [
                                    'cent' => 100,
                                ],
                            ],
                        ],
                    ]),
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
                                'locale' => 'fr-FR',
                                'map' => [
                                    'cent' => 100,
                                ],
                            ],
                        ],
                    ]),
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
            'inArguments' => 'staticArguments', // either 'dynamicArguments' or 'staticArguments'
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
                        ],
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
                        ],
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
                    ]),
                ])
            )
            ->permute()
            ->getTargetsForDataProvider();
    }

    /**
     * @test
     * @dataProvider routeRequirementsHavingAspectsAreConsideredDataProvider
     */
    public function routeRequirementsHavingAspectsAreConsidered(TestSet $testSet): void
    {
        $this->assertGeneratedUriEquals($testSet);
    }

    /**
     * @test
     * @dataProvider nestedRouteArgumentsAreConsideredDataProvider
     */
    public function nestedRouteArgumentsAreConsidered(TestSet $testSet): void
    {
        $this->assertGeneratedUriEquals($testSet);
    }
}
