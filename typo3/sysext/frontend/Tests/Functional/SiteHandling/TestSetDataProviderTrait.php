<?php

declare(strict_types = 1);

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

use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\EnhancerDeclaration;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\LanguageContext;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Permutation;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\TestSet;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Variable;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Variables;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariablesContext;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariableValue;

/**
 * TestSet declarations shared by several tests
 */
trait TestSetDataProviderTrait
{
    /**
     * @param TestSet|string|null $parentSet
     * @return TestSet[]
     */
    public function nestedRouteArgumentsAreConsideredDataProvider($parentSet = null): array
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
                        'known_value' => 'known/value'
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
                        'known_value' => 'known/value'
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
                    'value' => 'known',
                    'resolveValue' => 'known',
                ]))
            )
            ->withApplicableItems($enhancers)
            ->withApplicableSet(
                VariablesContext::create(Variables::create([
                    'pathSuffix' => '?any%5Bother%5D=other&cHash=[[cHash]]',
                    'cHash' => 'a655d1f1d346f7d3fa7aef5459a6547f',
                ]))->withRequiredApplicables($enhancers['Simple']),
                VariablesContext::create(Variables::create([
                    'pathSuffix' => '?testing%5Bany%5D%5Bother%5D=other&cHash=[[cHash]]',
                    'cHash' => 'bfd5274d1f8a5051f44ca703a0dbd359',
                ]))->withRequiredApplicables($enhancers['Plugin']),
                VariablesContext::create(Variables::create([
                    'pathSuffix' => '?tx_testing_link%5Bany%5D%5Bother%5D=other&cHash=[[cHash]]',
                    'cHash' => '0d1b27ac1cc957c16c9c02cf24f90af4',
                ]))->withRequiredApplicables($enhancers['Extbase'])
            )
            ->permute()
            ->getTargetsForDataProvider();
    }
}
