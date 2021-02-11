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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\EnhancerSiteRequest;

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
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Test case
 */
class RouteTest extends AbstractEnhancerSiteRequestTest
{
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

        $response = $this->executeFrontendSubRequest(
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
}
