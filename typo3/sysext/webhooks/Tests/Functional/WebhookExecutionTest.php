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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Tests that check if a certain message is triggered and about to be sent
 * out via HTTP.
 *
 * It simulates a full scenario to trigger a webhook message to a remote URL.
 */
class WebhookExecutionTest extends AbstractDataHandlerActionTestCase
{
    protected array $coreExtensionsToLoad = ['webhooks'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../../core/Tests/Functional/Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_webhooks.csv');
        $this->setUpFrontendSite(1, $this->siteLanguageConfiguration);
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
        $this->actionService->modifyRecord('pages', 10, ['title' => 'Dummy Modified']);
        // @todo: this is a bug in DataHandler, because it triggers the option twice.
        self::assertEquals(2, $numberOfRequestsFired);
    }

    /**
     * @test
     */
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
        $context = GeneralUtility::makeInstance(Context::class);
        $securityAspect = SecurityAspect::provideIn($context);
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
        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_webhook')->truncate('sys_webhook');
        $numberOfRequestsFired = 0;
        $inspector = function () use (&$numberOfRequestsFired) {
            $numberOfRequestsFired++;
        };
        $this->registerRequestInspector($inspector);

        // Catch any requests, evaluate their payload
        $this->actionService->modifyRecord('pages', 10, ['title' => 'Dummy Modified']);
        self::assertEquals(0, $numberOfRequestsFired);
    }

    protected function assertCleanReferenceIndex(): void
    {
        // do not do anything here yet
    }

    protected function registerRequestInspector(callable $inspector): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['logger'] = function () use ($inspector) {
            return function (RequestInterface $request) use ($inspector) {
                $inspector($request);
                return new Response('success', 200);
            };
        };
    }
}
