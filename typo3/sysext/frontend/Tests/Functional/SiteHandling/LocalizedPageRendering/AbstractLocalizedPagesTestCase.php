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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\LocalizedPageRendering;

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\AbstractTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

abstract class AbstractLocalizedPagesTestCase extends AbstractTestCase
{
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'German', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
        'DE-CH' => ['id' => 2, 'title' => 'Swiss German', 'locale' => 'de_CH.UTF8', 'iso' => 'de', 'hrefLang' => 'de-CH', 'direction' => ''],
    ];

    /**
     * @var string
     */
    protected $siteTitle = 'A Company that Manufactures Everything Inc';

    /**
     * @var InternalRequestContext
     */
    private $internalRequestContext;

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

        // these settings are forwarded to the frontend sub-request as well
        $this->internalRequestContext = (new InternalRequestContext())
            ->withGlobalSettings(['TYPO3_CONF_VARS' => static::TYPO3_CONF_VARS]);
    }

    protected function setUpDatabaseWithYamlPayload(string $pathToYamlFile): void
    {
        $this->withDatabaseSnapshot(function () use ($pathToYamlFile) {
            $backendUser = $this->setUpBackendUserFromFixture(1);
            Bootstrap::initializeLanguageObject();

            $factory = DataHandlerFactory::fromYamlFile($pathToYamlFile);
            $writer = DataHandlerWriter::withBackendUser($backendUser);
            $writer->invokeFactory($factory);
            static::failIfArrayIsNotEmpty(
                $writer->getErrors()
            );
        });
    }

    protected function tearDown(): void
    {
        unset($this->internalRequestContext);
        parent::tearDown();
    }

    /**
     * @param string $url
     * @param array $scopes
     */
    protected function assertScopes(string $url, array $scopes): void
    {
        $this->setUpFrontendRootPage(
            1000,
            ['typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript'],
            [
                'title' => 'ACME Root',
                'sitetitle' => $this->siteTitle,
            ]
        );

        $response = $this->executeFrontendRequest(new InternalRequest($url), $this->internalRequestContext);
        $responseStructure = ResponseContent::fromString((string)$response->getBody());

        foreach ($scopes as $scopePath => $expectedScopeValue) {
            self::assertSame($expectedScopeValue, $responseStructure->getScopePath($scopePath));
        }
    }

    /**
     * @param string $url
     * @param int $exception
     */
    protected function assertResponseStatusCode(string $url, int $statusCode): void
    {
        $this->setUpFrontendRootPage(
            1000,
            ['typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript']
        );

        $response = $this->executeFrontendRequest(new InternalRequest($url), $this->internalRequestContext);
        self::assertSame(404, $response->getStatusCode());
    }

    /**
     * @param string $url
     * @param array $expectation
     */
    protected function assertMenu(string $url, array $expectation): void
    {
        $this->setUpFrontendRootPage(
            1000,
            ['typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/LinkGenerator.typoscript'],
            [
                'title' => 'ACME Root',
                'sitetitle' => $this->siteTitle,
            ]
        );

        $response = $this->executeFrontendRequest(
            (new InternalRequest($url))
                ->withInstructions([
                    $this->createHierarchicalMenuProcessorInstruction([
                        'levels' => 1,
                        'entryLevel' => 0,
                        'expandAll' => 1,
                        'includeSpacer' => 1,
                        'titleField' => 'title',
                        'as' => 'results',
                    ]),
                ]),
            $this->internalRequestContext
        );

        $json = json_decode((string)$response->getBody(), true);
        $json = $this->filterMenu($json);

        self::assertSame($expectation, $json);
    }
}
