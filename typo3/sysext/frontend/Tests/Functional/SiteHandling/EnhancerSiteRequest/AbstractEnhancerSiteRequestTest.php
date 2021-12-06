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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\AbstractTestCase;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Builder;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\ExceptionExpectation;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\LanguageContext;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\TestSet;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Abstract test case
 */
abstract class AbstractEnhancerSiteRequestTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://acme.us/'),
                $this->buildLanguageConfiguration('FR', 'https://acme.fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', 'https://acme.ca/', ['FR', 'EN']),
            ]
        );

        $this->writeSiteConfiguration(
            'archive-acme-com',
            $this->buildSiteConfiguration(3000, 'https://archive.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', 'https://archive.acme.com/fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', 'https://archive.acme.com/ca/', ['FR', 'EN']),
            ]
        );

        $this->withDatabaseSnapshot(function () {
            $this->setUpDatabase();
        });
    }

    protected function setUpDatabase(): void
    {
        $backendUser = $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $scenarioFile = __DIR__ . '/../Fixtures/SlugScenario.yaml';
        $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
        $writer = DataHandlerWriter::withBackendUser($backendUser);
        $writer->invokeFactory($factory);
        static::failIfArrayIsNotEmpty(
            $writer->getErrors()
        );

        $this->setUpFrontendRootPage(
            1000,
            [
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/LinkRequest.typoscript',
            ],
            [
                'title' => 'ACME Root',
            ]
        );

        $this->setUpFrontendRootPage(
            3000,
            [
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/LinkRequest.typoscript',
            ],
            [
                'title' => 'ACME Archive',
            ]
        );
    }

    /**
     * This test is re-used in various child classes
     *
     * @param TestSet $testSet
     */
    protected function pageTypeDecoratorIsApplied(TestSet $testSet): void
    {
        $builder = Builder::create();
        $enhancerConfiguration = $builder->compileEnhancerConfiguration($testSet);
        $pageTypeConfiguration = $builder->compilePageTypeConfiguration($testSet);
        $targetUri = $builder->compileUrl($testSet);
        /** @var LanguageContext $languageContext */
        $languageContext = $testSet->getSingleApplicable(LanguageContext::class);
        $expectedLanguageId = $languageContext->getLanguageId();
        $expectation = $builder->compileResolveArguments($testSet);

        $overrides = [
            'routeEnhancers' => [
                'PageType' => $pageTypeConfiguration,
            ],
        ];
        if ($enhancerConfiguration) {
            $overrides['routeEnhancers']['Enhancer'] = $enhancerConfiguration;
        }
        $this->mergeSiteConfiguration('acme-com', $overrides);
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
     * @param TestSet $testSet
     */
    protected function assertPageArgumentsEquals(TestSet $testSet): void
    {
        $builder = Builder::create();
        $enhancerConfiguration = $builder->compileEnhancerConfiguration($testSet);
        $targetUri = $builder->compileUrl($testSet);
        /** @var LanguageContext $languageContext */
        $languageContext = $testSet->getSingleApplicable(LanguageContext::class);
        $expectedLanguageId = $languageContext->getLanguageId();
        $expectation = $builder->compileResolveArguments($testSet);

        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => ['Enhancer' => $enhancerConfiguration],
        ]);
        $this->mergeSiteConfiguration('archive-acme-com', [
            'routeEnhancers' => ['Enhancer' => $enhancerConfiguration],
        ]);

        $allParameters = array_replace_recursive(
            $expectation['dynamicArguments'],
            $expectation['staticArguments']
        );
        $expectation['pageId'] = $testSet->getTargetPageId();
        $expectation['pageType'] = '0';
        $expectation['languageId'] = $expectedLanguageId;
        $expectation['requestQueryParams'] = $allParameters;
        $expectation['_GET'] = $allParameters;

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($targetUri),
            null,
            true
        );

        /** @var ExceptionExpectation $exceptionDeclaration */
        $exceptionDeclaration = $testSet->getSingleApplicable(ExceptionExpectation::class);
        if ($exceptionDeclaration !== null) {
            // @todo This part is "ugly"...
            self::assertSame(404, $response->getStatusCode());
            self::assertStringContainsString(
                // searching in HTML content...
                htmlspecialchars($exceptionDeclaration->getMessage()),
                (string)$response->getBody()
            );
        } else {
            $pageArguments = json_decode((string)$response->getBody(), true);
            self::assertSame(200, $response->getStatusCode());
            self::assertEquals($expectation, $pageArguments);
        }
    }
}
