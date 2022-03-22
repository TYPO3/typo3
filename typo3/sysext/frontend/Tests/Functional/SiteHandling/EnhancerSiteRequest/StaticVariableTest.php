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
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Builder;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\EnhancerDeclaration;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\LanguageContext;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Permutation;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\TestSet;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Variables;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariablesContext;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariableValue;

final class StaticVariableTest extends AbstractEnhancerSiteRequestTestCase
{
    private static function staticVariableDataProviderBuilder(string|TestSet|null $parentSet = null): array
    {
        $variableContexts = array_map(
            static fn(string $value) => VariablesContext::create(
                Variables::create([
                    'value' => $value,
                    'resolveValue' => $value,
                ])
            ),
            ['test-abcd', 'test-9876', 'test-fe01']
        );

        $builder = Builder::create();
        // variables (applied when invoking expectations)
        $variables = Variables::create()->define([
            'routePrefix' => 'enhance',
            'inArguments' => 'staticArguments', // either 'dynamicArguments' or 'staticArguments'
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
                EnhancerDeclaration::create('static.value=true')
                    ->withConfiguration(['static' => ['value' => true]]),
            )
            ->withApplicableSet(
                EnhancerDeclaration::create('requirements.value=/test-[a-f0-9]{4}/')
                    ->withConfiguration(['requirements' => ['value' => 'test-[a-f0-9]{4}']])
            )
            ->permute()
            ->getTargetsForDataProvider();
    }

    public static function staticVariableIsAppliedDataProvider(): array
    {
        return self::staticVariableDataProviderBuilder();
    }

    #[DataProvider('staticVariableIsAppliedDataProvider')]
    #[Test]
    public function staticVariableIsApplied(TestSet $testSet): void
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
                self::staticVariableDataProviderBuilder($testSet)
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
