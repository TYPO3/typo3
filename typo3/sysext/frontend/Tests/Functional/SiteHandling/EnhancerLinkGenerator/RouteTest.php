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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\ApplicableConjunction;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\AspectDeclaration;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Builder;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\EnhancerDeclaration;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\LanguageContext;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Permutation;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\TestSet;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Variable;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariableItem;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Variables;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariablesContext;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariableValue;

final class RouteTest extends AbstractEnhancerLinkGeneratorTestCase
{
    public static function routeDefaultsForSingleParameterAreConsideredDataProvider(): array
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
                TestSet::create()
                    ->withMergedApplicables(LanguageContext::create(0))
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.us/welcome/enhance[[uriValue]][[pathSuffix]]',
                            Variables::create(['pathSuffix' => '', 'uriValue' => '/hundred'])
                        )
                    ),
                TestSet::create()
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

    #[DataProvider('routeDefaultsForSingleParameterAreConsideredDataProvider')]
    #[Test]
    public function routeDefaultsForSingleParameterAreConsidered(TestSet $testSet): void
    {
        $this->assertGeneratedUriEquals($testSet);
    }

    public static function routeDefaultsForMultipleParametersAreConsideredDataProvider(): array
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
                TestSet::create()
                    ->withMergedApplicables(LanguageContext::create(0))
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.us/welcome/enhance/hundred/20[[pathSuffix]]?cHash=',
                            Variables::create(['pathSuffix' => ''])
                        )
                    ),
                TestSet::create()
                    ->withMergedApplicables(LanguageContext::create(1))
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create(
                            'https://acme.fr/bienvenue/enhance/cent/20[[pathSuffix]]?cHash=',
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

    #[DataProvider('routeDefaultsForMultipleParametersAreConsideredDataProvider')]
    #[Test]
    public function routeDefaultsForMultipleParametersAreConsidered(TestSet $testSet): void
    {
        $this->assertGeneratedUriEquals($testSet, false);
    }

    public static function routeRequirementsHavingAspectsAreConsideredDataProvider(): array
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
                TestSet::create()
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

    #[DataProvider('routeRequirementsHavingAspectsAreConsideredDataProvider')]
    #[Test]
    public function routeRequirementsHavingAspectsAreConsidered(TestSet $testSet): void
    {
        $this->assertGeneratedUriEquals($testSet);
    }

    public static function nestedRouteArgumentsAreConsideredDataProvider(): array
    {
        $routePath = VariableValue::create(
            '/enhance/[[routeParameter]]',
            Variables::create(['routeParameter' => '{known_value}'])
        );
        $cHashVar = Variable::create('cHash', Variable::CAST_STRING);
        $resolveValueVar = Variable::create('resolveValue', Variable::CAST_STRING);
        // variables (applied when invoking expectations)
        $variables = Variables::create()->define([
            'aspectName' => 'value',
        ]);
        $enhancers = [
            'Simple' => EnhancerDeclaration::create('Simple')
                ->withConfiguration([
                    'type' => 'Simple',
                    'routePath' => $routePath,
                    '_arguments' => [
                        'known_value' => 'known/value',
                    ],
                ])
                ->withGenerateParameters([
                    VariableValue::create('&known[value]=[[value]]&any[other]=other')
                        ->withRequiredDefinedVariableNames('value'),
                ])
                ->withResolveArguments([
                    'routeArguments' => [
                        'known' => ['value' => $resolveValueVar],
                    ],
                    'dynamicArguments' => [
                        'known' => ['value' => $resolveValueVar],
                        'any' => ['other' => 'other'],
                        'cHash' => $cHashVar,
                    ],
                    'queryArguments' => [
                        'any' => ['other' => 'other'],
                        'cHash' => $cHashVar,
                    ],
                ]),
            'Plugin' => EnhancerDeclaration::create('Plugin')
                ->withConfiguration([
                    'type' => 'Plugin',
                    'routePath' => $routePath,
                    'namespace' => 'testing',
                    '_arguments' => [
                        'known_value' => 'known/value',
                    ],
                ])
                ->withGenerateParameters([
                    VariableValue::create('&testing[known][value]=[[value]]&testing[any][other]=other')
                        ->withRequiredDefinedVariableNames('value'),
                ])
                ->withResolveArguments([
                    'routeArguments' => [
                        'testing' => [
                            'known' => ['value' => $resolveValueVar],
                        ],
                    ],
                    'dynamicArguments' => [
                        'testing' => [
                            'known' => ['value' => $resolveValueVar],
                            'any' => ['other' => 'other'],
                        ],
                        'cHash' => $cHashVar,
                    ],
                    'queryArguments' => [
                        'testing' => [
                            'any' => ['other' => 'other'],
                        ],
                        'cHash' => $cHashVar,
                    ],
                ]),
            'Extbase' => EnhancerDeclaration::create('Extbase')
                ->withConfiguration([
                    'type' => 'Extbase',
                    'defaultController' => 'Link::index',
                    'extension' => 'testing',
                    'plugin' => 'link',
                    'routes' => [
                        [
                            'routePath' => $routePath,
                            '_controller' => 'Link::index',
                            '_arguments' => ['known_value' => 'known/value'],
                        ],
                    ],
                ])
                ->withGenerateParameters([
                    VariableValue::create('&tx_testing_link[known][value]=[[value]]&tx_testing_link[any][other]=other')
                        ->withRequiredDefinedVariableNames('value'),
                ])
                ->withResolveArguments([
                    'routeArguments' => [
                        'tx_testing_link' => [
                            'known' => ['value' => $resolveValueVar],
                            'controller' => 'Link',
                            'action' => 'index',
                        ],
                    ],
                    'dynamicArguments' => [
                        'tx_testing_link' => [
                            'known' => ['value' => $resolveValueVar],
                            'any' => ['other' => 'other'],
                        ],
                        'cHash' => $cHashVar,
                    ],
                    'staticArguments' => [
                        'tx_testing_link' => [
                            'controller' => 'Link',
                            'action' => 'index',
                        ],
                    ],
                    'queryArguments' => [
                        'tx_testing_link' => [
                            'any' => ['other' => 'other'],
                        ],
                        'cHash' => $cHashVar,
                    ],
                ]),
        ];

        return Permutation::create($variables)
            ->withTargets(
                TestSet::create()
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
                    'value' => 'known',
                    'resolveValue' => 'known',
                ]))
            )
            ->withApplicableItems($enhancers)
            ->withApplicableSet(
                VariablesContext::create(Variables::create([
                    'pathSuffix' => '?any%5Bother%5D=other&cHash=[[cHash]]',
                    'cHash' => self::calculateCacheHash([
                        'id' => '1100',
                        'any[other]' => 'other',
                        'known[value]' => 'known',
                    ]),
                ]))->withRequiredApplicables($enhancers['Simple']),
                VariablesContext::create(Variables::create([
                    'pathSuffix' => '?testing%5Bany%5D%5Bother%5D=other&cHash=[[cHash]]',
                    'cHash' => self::calculateCacheHash([
                        'id' => '1100',
                        'testing[any][other]' => 'other',
                        'testing[known][value]' => 'known',
                    ]),
                ]))->withRequiredApplicables($enhancers['Plugin']),
                VariablesContext::create(Variables::create([
                    'pathSuffix' => '?tx_testing_link%5Bany%5D%5Bother%5D=other&cHash=[[cHash]]',
                    'cHash' => self::calculateCacheHash([
                        'id' => '1100',
                        'tx_testing_link[any][other]' => 'other',
                        'tx_testing_link[known][value]' => 'known',
                    ]),
                ]))->withRequiredApplicables($enhancers['Extbase'])
            )
            ->permute()
            ->getTargetsForDataProvider();
    }

    #[DataProvider('nestedRouteArgumentsAreConsideredDataProvider')]
    #[Test]
    public function nestedRouteArgumentsAreConsidered(TestSet $testSet): void
    {
        $this->assertGeneratedUriEquals($testSet);
    }

    public static function outOfScopeValueIsNotMappedDataProvider(): array
    {
        // variables (applied when invoking expectations)
        $variables = Variables::create()->define([
            'value' => null, // defined via VariableContext
            'routePrefix' => 'enhance',
            'aspectName' => 'value',
        ]);
        $variableContexts = [];
        $enhancers = Builder::create()->declareEnhancers();
        foreach ($enhancers as $enhancer) {
            $generatedParams = match ($enhancer->describe()) {
                'Simple' => '&value=[[value]]',
                'Plugin' => '&testing[value]=[[value]]',
                'Extbase' => '&tx_testing_link[action]=index&tx_testing_link[controller]=Link&tx_testing_link[value]=[[value]]',
                default => null,
            };
            if ($generatedParams === null) {
                continue;
            }
            $variableContexts[] = VariablesContext::create(
                Variables::create([
                    'generatedParams' => VariableValue::createUrlEncodedParams($generatedParams, null, '?'),
                ])
            )->withRequiredApplicables($enhancer);
        }
        return Permutation::create($variables)
            ->withTargets(
                TestSet::create()
                    ->withMergedApplicables(LanguageContext::create(0))
                    ->withTargetPageId(1100)
                    ->withUrl(
                        VariableValue::create('https://acme.us/welcome[[generatedParams]]&cHash=')
                    ),
            )
            ->withApplicableSet(
                // value `5000` is out of scope for the given range `[1; 100]`
                VariablesContext::create(Variables::create(['value' => 5000])),
            )
            ->withApplicableItems($enhancers)
            ->withApplicableItems($variableContexts)
            ->withApplicableSet(
                AspectDeclaration::create('StaticRangeMapper')->withConfiguration([
                    VariableItem::create('aspectName', [
                        'type' => 'StaticRangeMapper',
                        'start' => '1',
                        'end' => '100',
                    ]),
                ])
            )
            ->permute()
            ->getTargetsForDataProvider();
    }

    /**
     * Asserts that value `5000` cannot generate a valid URL, having
     * a `StaticRangeMapper` which only allows values in range `[1; 100]`.
     */
    #[DataProvider('outOfScopeValueIsNotMappedDataProvider')]
    #[Test]
    public function outOfScopeValueIsNotMapped(TestSet $testSet): void
    {
        $this->assertGeneratedUriEquals($testSet, false);
    }
}
