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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling;

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

/**
 * Eid request test
 */
class EidRequestTest extends AbstractTestCase
{
    private InternalRequestContext $internalRequestContext;

    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_eid',
    ];

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

        $this->withDatabaseSnapshot(function () {
            $this->setUpDatabase();
        });
    }

    protected function setUpDatabase(): void
    {
        $backendUser = $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $scenarioFile = __DIR__ . '/Fixtures/PlainScenario.yaml';
        $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
        $writer = DataHandlerWriter::withBackendUser($backendUser);
        $writer->invokeFactory($factory);
        static::failIfArrayIsNotEmpty(
            $writer->getErrors()
        );

        $this->setUpFrontendRootPage(
            1000,
            [
                'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript',
                'typo3/sysext/frontend/Tests/Functional/SiteHandling/Fixtures/JsonRenderer.typoscript',
            ],
            [
                'title' => 'ACME Root',
            ]
        );
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/')
        );
    }

    /**
     * @return array[]
     */
    public function ensureEidRequestsWorkDataProvider(): array
    {
        return [
            'eid without index.php' => [
                'https://website.local/?eID=test_eid&id=123&some_parameter=1',
                200,
                [
                    'content-type' => [
                        'application/json',
                    ],
                    'eid_responder' => [
                        'responded',
                    ],
                ],
                [
                    'eid_responder' => true,
                    'uri' => 'https://website.local/?eID=test_eid&id=123&some_parameter=1',
                    'method' => 'GET',
                    'queryParams' => [
                        'eID' => 'test_eid',
                        'id' => '123',
                        'some_parameter' => '1',
                    ],
                ],
            ],
            'eid with index.php' => [
                'https://website.local/index.php?eID=test_eid&id=123&some_parameter=1',
                200,
                [
                    'content-type' => [
                        'application/json',
                    ],
                    'eid_responder' => [
                        'responded',
                    ],
                ],
                [
                    'eid_responder' => true,
                    'uri' => 'https://website.local/index.php?eID=test_eid&id=123&some_parameter=1',
                    'method' => 'GET',
                    'queryParams' => [
                        'eID' => 'test_eid',
                        'id' => '123',
                        'some_parameter' => '1',
                    ],
                ],
            ],
            'eid on slug page' => [
                'https://website.local/en-welcome/?eID=test_eid&id=123&some_parameter=1',
                200,
                [
                    'content-type' => [
                        'application/json',
                    ],
                    'eid_responder' => [
                        'responded',
                    ],
                ],
                [
                    'eid_responder' => true,
                    'uri' => 'https://website.local/en-welcome/?eID=test_eid&id=123&some_parameter=1',
                    'method' => 'GET',
                    'queryParams' => [
                        'eID' => 'test_eid',
                        'id' => '123',
                        'some_parameter' => '1',
                    ],
                ],
            ],
            'eid without index.php with type' => [
                'https://website.local/?eID=test_eid&id=123&some_parameter=1&type=0',
                200,
                [
                    'content-type' => [
                        'application/json',
                    ],
                    'eid_responder' => [
                        'responded',
                    ],
                ],
                [
                    'eid_responder' => true,
                    'uri' => 'https://website.local/?eID=test_eid&id=123&some_parameter=1&type=0',
                    'method' => 'GET',
                    'queryParams' => [
                        'eID' => 'test_eid',
                        'id' => '123',
                        'some_parameter' => '1',
                        'type' => '0',
                    ],
                ],
            ],
            'eid with index.php with type' => [
                'https://website.local/index.php?eID=test_eid&id=123&some_parameter=1&type=0',
                200,
                [
                    'content-type' => [
                        'application/json',
                    ],
                    'eid_responder' => [
                        'responded',
                    ],
                ],
                [
                    'eid_responder' => true,
                    'uri' => 'https://website.local/index.php?eID=test_eid&id=123&some_parameter=1&type=0',
                    'method' => 'GET',
                    'queryParams' => [
                        'eID' => 'test_eid',
                        'id' => '123',
                        'some_parameter' => '1',
                        'type' => '0',
                    ],
                ],
            ],
            'eid on slug page with type' => [
                'https://website.local/en-welcome/?eID=test_eid&id=123&some_parameter=1&type=0',
                200,
                [
                    'content-type' => [
                        'application/json',
                    ],
                    'eid_responder' => [
                        'responded',
                    ],
                ],
                [
                    'eid_responder' => true,
                    'uri' => 'https://website.local/en-welcome/?eID=test_eid&id=123&some_parameter=1&type=0',
                    'method' => 'GET',
                    'queryParams' => [
                        'eID' => 'test_eid',
                        'id' => '123',
                        'some_parameter' => '1',
                        'type' => '0',
                    ],
                ],
            ],
            'eid with empty array as eID identifier' => [
                'https://website.local/en-welcome/?eID[]=',
                400,
                [],
                null,
            ],
        ];
    }

    /**
     * @param string $uri
     * @param int $expectedStatusCode
     * @param array $expectedHeaders
     * @param array $expectedResponseData
     *
     * @test
     * @dataProvider ensureEidRequestsWorkDataProvider
     */
    public function ensureEidRequestsWork(
        string $uri,
        int $expectedStatusCode,
        array $expectedHeaders,
        ?array $expectedResponseData
    ): void {
        $response = $this->executeFrontendSubRequest(
            new InternalRequest($uri),
            $this->internalRequestContext
        );

        self::assertSame($expectedStatusCode, $response->getStatusCode());
        self::assertSame($expectedHeaders, $response->getHeaders());
        if ($expectedResponseData !== null) {
            self::assertSame($expectedResponseData, json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR));
        }
    }
}
