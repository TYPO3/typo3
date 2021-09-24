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

use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\AspectDeclaration;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Builder;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\LanguageContext;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Permutation;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\TestSet;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariableItem;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Variables;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariablesContext;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariableValue;

/**
 * Test case
 */
class LocaleModifierTest extends AbstractEnhancerSiteRequestTest
{
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
            'inArguments' => 'dynamicArguments', // either 'dynamicArguments' or 'staticArguments'
        ]);
        $enhancers = $builder->declareEnhancers();
        $variableContexts = [
            VariablesContext::create(
                Variables::create([
                    'cHash' => '46227b4ce096dc78a4e71463326c9020',
                    'cHash2' => 'f80d112e877175ce8e7d54c35bebe12c',
                ])
            )->withRequiredApplicables($enhancers['Simple']),
            VariablesContext::create(
                Variables::create([
                    'cHash' => 'e24d3d2d5503baba670d827c3b9470c8',
                    'cHash2' => '54f45ea94a5e812fbae944792dac940d',
                ])
            )->withRequiredApplicables($enhancers['Plugin']),
            VariablesContext::create(
                Variables::create([
                    'cHash' => 'eef21771ab3c3dac3514b4479eedd5ff',
                    'cHash2' => 'c822555d4ebd106b0d1687e43a4db9c9',
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
                                'value' => 'augmenter',
                            ],
                        ],
                    ]),
                ])
            )
            ->permute()
            ->getTargetsForDataProvider();
    }

    /**
     * @param TestSet $testSet
     * @test
     * @dataProvider localeModifierDataProvider
     */
    public function localeModifierIsApplied(TestSet $testSet): void
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
            );
        }
        return $testSets;
    }

    /**
     * @param TestSet $testSet
     * @test
     * @dataProvider pageTypeDecoratorIsAppliedDataProvider
     */
    public function pageTypeDecoratorIsApplied(TestSet $testSet): void
    {
        parent::pageTypeDecoratorIsApplied($testSet);
    }
}
