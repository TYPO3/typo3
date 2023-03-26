<?php

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

namespace TYPO3\CMS\Redirects\Tests\Functional;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Redirects\Event\RedirectWasHitEvent;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class WebhookExecutionTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'redirects',
        'webhooks',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_webhook.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_redirect.csv');
    }

    /**
     * @test
     */
    public function matchedRedirectEmitsWebhookMessage(): void
    {
        $payloadSourceUrl = null;
        $payloadTargetUrl = null;
        $payloadStatusCode = null;
        $payloadRedirectUid = null;
        $numberOfRequestFired = 0;
        $inspector = function (RequestInterface $request) use (
            &$numberOfRequestFired,
            &$payloadSourceUrl,
            &$payloadTargetUrl,
            &$payloadStatusCode,
            &$payloadRedirectUid
        ) {
            $payload = json_decode($request->getBody()->getContents(), true);
            $payloadSourceUrl = $payload['sourceUrl'] ?? null;
            $payloadTargetUrl = $payload['targetUrl'] ?? null;
            $payloadStatusCode = $payload['statusCode'] ?? null;
            $payloadRedirectUid = $payload['redirect']['uid'] ?? null;
            $numberOfRequestFired++;
        };
        $this->registerRequestInspector($inspector);

        // Ensure to disable increment hit counter event listener
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['redirects.hitCount'] = true;

        $matchedRecord = BackendUtility::getRecord('sys_redirect', 1);
        $this->get(EventDispatcherInterface::class)->dispatch(
            new RedirectWasHitEvent(
                new ServerRequest('https://acme.com/foo'),
                new RedirectResponse($matchedRecord['target'], $matchedRecord['target_statuscode']),
                $matchedRecord,
                new Uri($matchedRecord['target']),
            )
        );

        self::assertSame(1, $numberOfRequestFired);
        self::assertSame('https://acme.com/foo', $payloadSourceUrl);
        self::assertSame('https://example.com/bar', $payloadTargetUrl);
        self::assertSame(301, $payloadStatusCode);
        self::assertSame(1, $payloadRedirectUid);
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
