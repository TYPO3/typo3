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

use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\AspectDeclaration;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Builder;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\LanguageContext;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Permutation;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\TestSet;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariableItem;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Variables;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariablesContext;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariableValue;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class StaticRangeMapperTest extends AbstractEnhancerLinkGeneratorTestCase
{
    public static function staticRangeMapperDataProvider(string|TestSet|null $parentSet = null): array
    {
        $variableContexts = array_map(
            static function ($value) {
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
                    ]),
                ])
            )
            ->permute()
            ->getTargetsForDataProvider();
    }

    /**
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
            'routeEnhancers' => ['Enhancer' => $enhancerConfiguration],
        ]);

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('https://acme.us/'))
                ->withPageId(1100)
                ->withInstructions([
                    $this->createTypoLinkUrlInstruction([
                        'parameter' => $testSet->getTargetPageId(),
                        'language' => $targetLanguageId,
                        'additionalParams' => $additionalParameters,
                        'forceAbsoluteUrl' => 1,
                    ]),
                ])
        );

        self::assertStringStartsWith($expectation, (string)$response->getBody());
    }

    /**
     * Combines the previous data provider for mappable aspects into one large
     * data set that is permuted for several page type decorator instructions.
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
                $this->staticRangeMapperDataProvider($testSet)
            );
        }
        return $testSets;
    }

    /**
     * @test
     * @dataProvider pageTypeDecoratorIsAppliedDataProvider
     */
    public function pageTypeDecoratorIsApplied(TestSet $testSet): void
    {
        parent::pageTypeDecoratorIsApplied($testSet);
    }
}
