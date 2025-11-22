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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\AspectDeclaration;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Builder;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\LanguageContext;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Permutation;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\TestSet;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariableItem;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Variables;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariablesContext;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariableValue;

final class LocaleModifierTest extends AbstractEnhancerSiteRequestTestCase
{
    private static function localeModifierDataProviderBuilder(string|TestSet|null $parentSet = null): array
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
                    'cHash' => self::calculateCacheHash(['id' => '1100', 'value' => '100']),
                    'cHash2' => self::calculateCacheHash(['id' => '3000', 'value' => '100']),
                ])
            )->withRequiredApplicables($enhancers['Simple']),
            VariablesContext::create(
                Variables::create([
                    'cHash' => self::calculateCacheHash(['id' => '1100', 'testing[value]' => '100']),
                    'cHash2' => self::calculateCacheHash(['id' => '3000', 'testing[value]' => '100']),
                ])
            )->withRequiredApplicables($enhancers['Plugin']),
            VariablesContext::create(
                Variables::create([
                    'cHash' => self::calculateCacheHash(['id' => '1100', 'tx_testing_link[value]' => '100']),
                    'cHash2' => self::calculateCacheHash(['id' => '3000', 'tx_testing_link[value]' => '100']),
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

    public static function localeModifierIsAppliedDataProvider(): array
    {
        return self::localeModifierDataProviderBuilder();
    }

    #[DataProvider('localeModifierIsAppliedDataProvider')]
    #[Test]
    public function localeModifierIsApplied(TestSet $testSet): void
    {
        $this->assertPageArgumentsEquals($testSet);
    }

    public static function pageTypeDecoratorIsAppliedDataProvider(): array
    {
        $testSets = [];
        foreach (Builder::create()->declarePageTypes() as $pageTypeDeclaration) {
            $testSet = TestSet::create()
                ->withMergedApplicables($pageTypeDeclaration)
                ->withVariables($pageTypeDeclaration->getVariables());
            $testSets = array_merge(
                $testSets,
                self::localeModifierDataProviderBuilder($testSet),
            );
        }
        return $testSets;
    }

    #[DataProvider('pageTypeDecoratorIsAppliedDataProvider')]
    #[Test]
    public function pageTypeDecoratorIsApplied(TestSet $testSet): void
    {
        parent::pageTypeDecoratorIsApplied($testSet);
    }
}
