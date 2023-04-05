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

use Psr\Http\Message\RequestInterface;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
    protected array $coreExtensionsToLoad = ['webhooks'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users_admin.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_webhooks.csv');

        $this->setUpBackendUser(1);
        Bootstrap::initializeLanguageObject();

        $configuration = [
            'rootPageId' => 1,
            'base' => '/',
            'languages' => [
                0 => [
                    'title' => 'English',
                    'enabled' => true,
                    'languageId' => 0,
                    'base' => '/',
                    'locale' => 'en_US.UTF-8',
                    'navigationTitle' => '',
                    'flag' => 'us',
                ],
            ],
            'errorHandling' => [],
            'routes' => [],
        ];
        GeneralUtility::mkdir_deep($this->instancePath . '/typo3conf/sites/testing/');
        $yamlFileContents = Yaml::dump($configuration, 99, 2);
        $fileName = $this->instancePath . '/typo3conf/sites/testing/config.yaml';
        GeneralUtility::writeFile($fileName, $yamlFileContents);
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

    /**
     * @test
     */
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
        // @todo: this is a bug in DataHandler, because it triggers the option twice.
        self::assertEquals(2, $numberOfRequestsFired);
    }

    /**
     * @test
     */
    public function oneMessageWithMultipleRequestsIsTriggeredAndDispatched(): void
    {
        self::markTestSkipped('Fails with phpunit 10 for unknown reasons. Probably broken since ever?');
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
        self::assertEquals(2, $numberOfRequestsFired);
    }

    /**
     * @test
     */
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
}
