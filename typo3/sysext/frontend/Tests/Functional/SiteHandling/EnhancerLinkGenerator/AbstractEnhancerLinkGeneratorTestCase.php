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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\AbstractTestCase;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\Builder;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\LanguageContext;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder\TestSet;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Abstract test case
 */
abstract class AbstractEnhancerLinkGeneratorTestCase extends AbstractTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::initializeDatabaseSnapshot();
    }

    public static function tearDownAfterClass(): void
    {
        static::destroyDatabaseSnapshot();
        parent::tearDownAfterClass();
    }

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
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/LinkGenerator.typoscript',
            ],
            [
                'title' => 'ACME Root',
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
        $additionalParameters = $builder->compileGenerateParameters($testSet);
        /** @var LanguageContext $languageContext */
        $languageContext = $testSet->getSingleApplicable(LanguageContext::class);
        $targetLanguageId = $languageContext->getLanguageId();
        $expectation = $builder->compileUrl($testSet);

        $this->mergeSiteConfiguration('acme-com', [
            'routeEnhancers' => [
                'Enhancer' => $enhancerConfiguration,
                'PageType' => $pageTypeConfiguration,
            ],
        ]);

        $this->mergeSiteConfiguration('archive-acme-com', [
            'routeEnhancers' => [
                'Enhancer' => $enhancerConfiguration,
                'PageType' => $pageTypeConfiguration,
            ],
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

    protected function assertGeneratedUriEquals(TestSet $testSet): void
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
}
