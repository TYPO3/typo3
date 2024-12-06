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

namespace TYPO3\CMS\Webhooks\Tests\Functional;

use Doctrine\DBAL\Types\JsonType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests that check if a certain message is triggered and about to be sent
 * out via HTTP.
 *
 * It simulates a full scenario to trigger a webhook message to a remote URL.
 */
final class WebhookExecutionTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['webhooks'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users_admin.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_webhooks.csv');

        $this->writeSiteConfiguration(
            'testing',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    private function registerRequestInspector(callable $inspector): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['logger'] = function () use ($inspector) {
            return function (RequestInterface $request) use ($inspector) {
                $inspector($request);
                return (new ResponseFactory())->createResponse()
                    ->withBody((new StreamFactory())->createStream('success'));
            };
        };
    }

    #[Test]
    public function requestIsSentOutForMessagesWithAGivenType(): void
    {
        $numberOfRequestsFired = 0;
        $inspector = function (RequestInterface $request) use (&$numberOfRequestsFired) {
            $payload = json_decode($request->getBody()->getContents(), true);
            $numberOfRequestsFired++;
            self::assertSame('modified', $payload['action']);
            self::assertSame('Dummy Modified', $payload['changedFields']['title']);
        };
        $this->registerRequestInspector($inspector);

        // Catch any requests, evaluate their payload
        (new ActionService())->modifyRecord('pages', 10, ['title' => 'Dummy Modified']);
        self::assertEquals(1, $numberOfRequestsFired);
    }

    /**
     * @todo This test might not test what should be tested.
     */
    #[Test]
    public function oneMessageWithMultipleRequestsIsTriggeredAndDispatched(): void
    {
        $numberOfRequestsFired = 0;
        $inspector = function (RequestInterface $request) use (&$numberOfRequestsFired) {
            $payload = json_decode($request->getBody()->getContents(), true);
            self::assertSame('backend', $payload['context']);
            self::assertSame('han-solo', $payload['loginData']['uname']);
            self::assertSame('********', $payload['loginData']['uident']);
            $numberOfRequestsFired++;
        };
        $this->registerRequestInspector($inspector);
        $securityAspect = SecurityAspect::provideIn($this->get(Context::class));
        $nonce = $securityAspect->provideNonce();
        $requestToken = RequestToken::create('core/user-auth/be');
        $securityAspect->setReceivedRequestToken($requestToken);

        $request = new ServerRequest('https://example.com/site1/', 'POST');
        $request = $request->withParsedBody([
            'login_status' => 'login',
            'username' => 'han-solo',
            'userident' => 'chewbaka',
            RequestToken::PARAM_NAME => $requestToken->toHashSignedJwt($nonce),
        ]);

        $userRequest = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $userRequest->start($request);
        // second request
        $userRequest->start($request);
        self::assertEquals(2, $numberOfRequestsFired);
    }

    #[Test]
    public function messageWithoutConfiguredTypesDoesNotSendARequest(): void
    {
        // Just empty the table for the request, other ways are possible to do this
        $this->get(ConnectionPool::class)->getConnectionForTable('sys_webhook')->truncate('sys_webhook');
        $numberOfRequestsFired = 0;
        $inspector = function () use (&$numberOfRequestsFired) {
            $numberOfRequestsFired++;
        };
        $this->registerRequestInspector($inspector);

        // Catch any requests, evaluate their payload
        (new ActionService())->modifyRecord('pages', 10, ['title' => 'Dummy Modified']);
        self::assertEquals(0, $numberOfRequestsFired);
    }

    public static function verifyRecordWithJsonDataCanBeAddedDataSets(): \Generator
    {
        // https://forge.typo3.org/issues/105004
        yield 'simple record creation' => [
            'record' => [
                'pid' => 0,
                'name' => 'test-001',
                'identifier' => 'f2c49559-a87f-416a-9d97-31771368326b',
                'secret' => 'some-secret-hash',
                'webhook_type' => 'typo3/file-added',
                'verify_ssl' => 1,
                'additional_headers' => [],
                'url' => 'http://127.0.0.1/',
            ],
            'expectedRow' => [
                'pid' => 0,
                'name' => 'test-001',
                'identifier' => 'f2c49559-a87f-416a-9d97-31771368326b',
                'secret' => 'some-secret-hash',
                'webhook_type' => 'typo3/file-added',
                'verify_ssl' => 1,
                'additional_headers' => '[]',
                'url' => 'http://127.0.0.1/',
            ],
        ];
        // https://forge.typo3.org/issues/105004
        yield 'with additional_headers data' => [
            'record' => [
                'pid' => 0,
                'name' => 'test-001',
                'identifier' => '68242dd3-9ad0-4f69-9b16-265cfcc79d14',
                'secret' => 'some-secret-hash',
                'webhook_type' => 'typo3/file-added',
                'verify_ssl' => 1,
                'additional_headers' => [
                    'x-api-key' => 'some-value',
                ],
                'url' => 'http://127.0.0.1/',
            ],
            'expectedRow' => [
                'pid' => 0,
                'name' => 'test-001',
                'identifier' => '68242dd3-9ad0-4f69-9b16-265cfcc79d14',
                'secret' => 'some-secret-hash',
                'webhook_type' => 'typo3/file-added',
                'verify_ssl' => 1,
                'additional_headers' => '{"x-api-key": "some-value"}',
                'url' => 'http://127.0.0.1/',
            ],
        ];
    }

    #[DataProvider('verifyRecordWithJsonDataCanBeAddedDataSets')]
    #[Test]
    public function verifyRecordWithJsonDataCanBeAdded(array $record, array $expectedRow): void
    {
        $newRecordIdentifier = StringUtility::getUniqueId('NEW');
        $data = [];
        $data['sys_webhook'][$newRecordIdentifier] = $record;
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->enableLogging = true;
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();
        self::assertSame([], $dataHandler->errorLog);
        self::assertArrayHasKey($newRecordIdentifier, $dataHandler->substNEWwithIDs);
        $recordId = $dataHandler->substNEWwithIDs[$newRecordIdentifier];
        self::assertIsInt($recordId);
        self::assertGreaterThan(0, $recordId);

        $connection = (new ConnectionPool())->getConnectionForTable('sys_webhook');
        $row = $connection
            ->select(
                array_keys($expectedRow),
                'sys_webhook',
                [
                    'uid' => $recordId,
                ]
            )->fetchAssociative();
        $row = $this->prepareRowField($connection, $row, ['additional_headers']);
        $expectedRow = $this->prepareRowField($connection, $expectedRow, ['additional_headers']);
        self::assertSame($expectedRow, $row);
    }

    private function prepareRowField(Connection $connection, array $row, array $fields): array
    {
        $type = new JsonType();
        foreach ($fields as $field) {
            if (!array_key_exists($field, $row)) {
                continue;
            }
            $row[$field] = $type->convertToDatabaseValue(
                $type->convertToPHPValue($row[$field], $connection->getDatabasePlatform()),
                $connection->getDatabasePlatform()
            );
        }
        return $row;
    }
}
