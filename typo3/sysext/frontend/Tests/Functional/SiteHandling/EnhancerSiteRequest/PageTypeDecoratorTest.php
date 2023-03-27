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

use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Builder;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\LanguageContext;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\TestSet;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Variables;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\VariableValue;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class PageTypeDecoratorTest extends AbstractEnhancerSiteRequestTest
{
    public static function pageTypeDecoratorIsAppliedDataProvider(): array
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

    public static function pageTypeDecoratorIndexCanBePartOfSlugDataProvider(): array
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
            ],
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

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($targetUri),
            null,
            true
        );

        $pageArguments = json_decode((string)$response->getBody(), true);
        self::assertEquals($expectation, $pageArguments);
    }

    /**
     * @test
     */
    public function unmappedPageTypeDecoratorIsAddedAsRegularQueryParam(): void
    {
        $this->mergeSiteConfiguration('archive-acme-com', [
            'routeEnhancers' => [
                'PageType' => [
                    'type' => 'PageType',
                    'default' => '/',
                    'index' => '',
                    'map' => [
                        '/' => 0,
                        'sitemap.xml' => '1533906435',
                    ],
                ],
            ],
        ]);

        GeneralUtility::makeInstance(SiteConfiguration::class)->resolveAllExistingSites(false);
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByIdentifier('archive-acme-com');
        $uri = $site->getRouter()->generateUri(3000);
        self::assertEquals('https://archive.acme.com/', (string)$uri);
        $uri = $site->getRouter()->generateUri(3000, ['type' => '1533906435']);
        self::assertEquals('https://archive.acme.com/sitemap.xml', (string)$uri);
        $uri = $site->getRouter()->generateUri(3000, ['type' => '13']);
        self::assertEquals('https://archive.acme.com/?type=13', (string)$uri);
    }
}
