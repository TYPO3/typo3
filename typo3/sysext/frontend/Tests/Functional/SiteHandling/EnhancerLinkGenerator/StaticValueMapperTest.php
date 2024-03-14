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
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\AspectDeclaration;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Builder;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\LanguageContext;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Permutation;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\TestSet;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariableItem;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Variables;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariableValue;

final class StaticValueMapperTest extends AbstractEnhancerLinkGeneratorTestCase
{
    private static function staticValueMapperDataProviderBuilder(string|TestSet|null $parentSet = null): array
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
                            ],
                        ],
                    ]),
                ])
            )
            ->permute()
            ->getTargetsForDataProvider();
    }

    public static function staticValueMapperIsAppliedDataProvider(): array
    {
        return self::staticValueMapperDataProviderBuilder();
    }

    #[DataProvider('staticValueMapperIsAppliedDataProvider')]
    #[Test]
    public function staticValueMapperIsApplied(TestSet $testSet): void
    {
        $this->assertGeneratedUriEquals($testSet);
    }

    /**
     * Combines the previous data provider for mappable aspects into one large
     * data set that is permuted for several page type decorator instructions.
     */
    public static function pageTypeDecoratorIsAppliedDataProvider(): array
    {
        $testSets = [];
        foreach (Builder::create()->declarePageTypes() as $pageTypeDeclaration) {
            $testSet = TestSet::create()
                ->withMergedApplicables($pageTypeDeclaration)
                ->withVariables($pageTypeDeclaration->getVariables());
            $testSets = array_merge(
                $testSets,
                self::staticValueMapperDataProviderBuilder($testSet),
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
