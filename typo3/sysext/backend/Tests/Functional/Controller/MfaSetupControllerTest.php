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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller;

use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Backend\Controller\MfaSetupController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\View\AuthenticationStyleInformation;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Authentication\Mfa\Provider\Totp;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class MfaSetupControllerTest extends FunctionalTestCase
{
    use ProphecyTrait;

    protected MfaSetupController $subject;
    protected ServerRequest $request;

    /**
     * Some tests trigger backendUser->logOff() which destroys the backend user session.
     * This backend user is also a system maintainer by default. This leads to the system
     * maintainer session being initialized twice - once from testing-framework, once from
     * system under test. The destroy operation then fails with "Session save path cannot be
     * changed after headers have already been sent". To suppress this, we simply drop the
     * system maintainer flag from this backend user.
     */
    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'systemMaintainers' => [],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBackendUserFromFixture(1);
        $GLOBALS['TYPO3_CONF_VARS']['BE']['requireMfa'] = 1;
        Bootstrap::initializeLanguageObject();

        $container = $this->getContainer();
        $this->subject = new MfaSetupController(
            $container->get(UriBuilder::class),
            $container->get(AuthenticationStyleInformation::class),
            $container->get(PageRenderer::class),
            new ExtensionConfiguration(),
            $this->prophesize(Logger::class)->reveal(),
            new BackendViewFactory($container->get(RenderingContextFactory::class), $container->get(PackageManager::class))
        );
        $this->subject->injectMfaProviderRegistry($container->get(MfaProviderRegistry::class));

        $this->request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
    }

    /**
     * @test
     */
    public function handleRequestThrowsExceptionWhenMfaWasAlreadyPassed(): void
    {
        $GLOBALS['BE_USER']->setAndSaveSessionData('mfa', true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1632154036);

        $request = $this->request;
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $this->subject->handleRequest($request);
    }

    /**
     * @test
     */
    public function handleRequestThrowsExceptionWhenInSwitchUserMode(): void
    {
        $GLOBALS['BE_USER']->setAndSaveSessionData('backuserid', 123);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1632154036);

        $request = $this->request;
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $this->subject->handleRequest($request);
    }

    /**
     * @test
     */
    public function handleRequestThrowsExceptionWhenMfaNotRequired(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['requireMfa'] = 0;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1632154036);

        $request = $this->request;
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $this->subject->handleRequest($request);
    }

    /**
     * @test
     */
    public function handleRequestThrowsExceptionWhenMfaAlreadyActivated(): void
    {
        $GLOBALS['BE_USER']->user['mfa'] = json_encode(['totp' => ['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB']]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1632154036);

        $request = $this->request;
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $this->subject->handleRequest($request);
    }

    /**
     * @test
     */
    public function handleRequestReturns404OnInvalidAction(): void
    {
        $request = $this->request->withQueryParams(['action' => 'unknown']);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        self::assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function handleRequestReturns404OnWrongHttpMethod(): void
    {
        $request = $this->request->withQueryParams(['action' => 'activate']);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        self::assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function handleRequestFallsBackToSelectionView(): void
    {
        $request = $this->request;
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        self::assertEquals(200, $response->getStatusCode());

        $responseContent = $response->getBody()->getContents();

        // Selection view is renderer
        self::assertStringContainsString('Set up MFA', $responseContent);

        // Allowed default provider is rendered
        self::assertMatchesRegularExpression('/<a.*class="list-group-item.*title="Set up Time-based one-time password".*>/s', $responseContent);

        // Non allowed default provider is not rendered
        self::assertDoesNotMatchRegularExpression('/<a.*class="list-group-item.*title="Set up Recovery codes".*>/s', $responseContent);
    }

    /**
     * @test
     */
    public function handleRequestAddsRedirectParameters(): void
    {
        $queryParams = [
            'action' => 'setup',
            'identifier' => 'totp',
            'redirect' => 'my_module',
            'redirectParams' => 'some=param',
        ];

        $request = $this->request->withQueryParams($queryParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        self::assertEquals(200, $response->getStatusCode());

        $responseContent = $response->getBody()->getContents();

        // Redirect params are kept
        self::assertMatchesRegularExpression('/<form.*action="\/typo3\/setup\/mfa.*&amp;action=activate&amp;redirect=my_module&amp;redirectParams=some%3Dparam".*>/s', $responseContent);
        self::assertMatchesRegularExpression('/<a.*title="Cancel".*href="\/typo3\/setup\/mfa.*&amp;redirect=my_module&amp;redirectParams=some%3Dparam".*>/s', $responseContent);
    }

    /**
     * @test
     */
    public function handleRequestReturnsSetupView(): void
    {
        $queryParams = [
            'action' => 'setup',
            'identifier' => 'totp',
        ];

        $request = $this->request->withQueryParams($queryParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        self::assertEquals(200, $response->getStatusCode());

        $responseContent = $response->getBody()->getContents();

        // Auth view for provider is renderer
        self::assertStringContainsString('Set up Time-based one-time password', $responseContent);

        // Ensure provider specific content is added as well
        self::assertMatchesRegularExpression('/<div.*id="qr-code".*>/s', $responseContent);
        self::assertMatchesRegularExpression('/<form.*name="setup".*id="mfaSetupController".*>/s', $responseContent);
        self::assertMatchesRegularExpression('/<input.*id="totp"/s', $responseContent);
    }

    /**
     * @test
     */
    public function handleRequestRedirectsToSetupOnMissingProvider(): void
    {
        $queryParams = [
            'action' => 'activate',
            'redirect' => 'web_list',
            'redirectParams' => 'some=param',
        ];

        $request = $this->request->withMethod('POST')->withQueryParams($queryParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);
        $redirectUrl = parse_url($response->getHeaderLine('location'));

        self::assertEquals(302, $response->getStatusCode());
        self::assertStringContainsString('/typo3/setup/mfa', $redirectUrl['path']);

        // Also redirect parameters are still kept
        self::assertStringContainsString('redirect=web_list&redirectParams=some%3Dparam', $redirectUrl['query']);
    }

    /**
     * @test
     */
    public function handleRequestRedirectsToSetupOnInvalidProvider(): void
    {
        $queryParams = [
            'action' => 'activate',
            'redirect' => 'web_list',
            'redirectParams' => 'some=param',
        ];

        $parsedBody = [
            'identifier' => 'recovery-codes',
        ];

        $request = $this->request->withMethod('POST')->withQueryParams($queryParams)->withParsedBody($parsedBody);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);
        $redirectUrl = parse_url($response->getHeaderLine('location'));

        self::assertEquals(302, $response->getStatusCode());
        self::assertStringContainsString('/typo3/setup/mfa', $redirectUrl['path']);

        // Also redirect parameters are still kept
        self::assertStringContainsString('redirect=web_list&redirectParams=some%3Dparam', $redirectUrl['query']);
    }

    /**
     * @test
     */
    public function handleRequestActivatesRequestedProvider(): void
    {
        $queryParams = [
            'action' => 'activate',
            'redirect' => 'web_list',
            'redirectParams' => 'some=param',
        ];

        $timestamp = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');
        $parsedBody = [
            'identifier' => 'totp',
            'totp' => GeneralUtility::makeInstance(
                Totp::class,
                'KRMVATZTJFZUC53FONXW2ZJB'
            )->generateTotp((int)floor($timestamp / 30)),
            'secret' => 'KRMVATZTJFZUC53FONXW2ZJB',
            'checksum' => GeneralUtility::hmac('KRMVATZTJFZUC53FONXW2ZJB', 'totp-setup'),
        ];

        $request = $this->request->withMethod('POST')->withQueryParams($queryParams)->withParsedBody($parsedBody);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);
        $redirectUrl = parse_url($response->getHeaderLine('location'));

        // Successful activation will initiate a redirect to the login endpoint
        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/typo3/login', $redirectUrl['path']);

        // Successful activation will set the "mfa" session key
        self::assertTrue($GLOBALS['BE_USER']->getSessionData('mfa'));

        // Successful activation will set "totp" as default provider
        self::assertEquals('totp', $GLOBALS['BE_USER']->uc['mfa']['defaultProvider']);

        // Successful activation will add a flash message
        self::assertEquals(
            'MFA setup successful',
            GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages()[0]->getTitle()
        );

        // Flash message properly resolves the provider title
        self::assertStringContainsString(
            'You have successfully activated MFA provider Time-based one-time password.',
            GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages()[0]->getMessage()
        );

        // Also redirect parameters are still kept
        self::assertStringContainsString('redirect=web_list&redirectParams=some%3Dparam', $redirectUrl['query']);
    }

    /**
     * @test
     */
    public function handleRequestRedirectsWithErrorOnActivationFailure(): void
    {
        $queryParams = [
            'action' => 'activate',
            'redirect' => 'web_list',
            'redirectParams' => 'some=param',
        ];

        $parsedBody = [
            'identifier' => 'totp',
            'totp' => '123456', // invalid !!!
            'secret' => 'KRMVATZTJFZUC53FONXW2ZJB',
            'checksum' => GeneralUtility::hmac('KRMVATZTJFZUC53FONXW2ZJB', 'totp-setup'),
        ];

        $request = $this->request->withMethod('POST')->withQueryParams($queryParams)->withParsedBody($parsedBody);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);
        $redirectUrl = parse_url($response->getHeaderLine('location'));

        // Failure will redirect to setup view
        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/typo3/setup/mfa', $redirectUrl['path']);

        // Failure will add "identifier" and "hasErrors" parameters
        self::assertStringContainsString('identifier=totp&hasErrors=1', $redirectUrl['query']);

        // Also redirect parameters are still kept
        self::assertStringContainsString('redirect=web_list&redirectParams=some%3Dparam', $redirectUrl['query']);
    }

    /**
     * @test
     */
    public function handleRequestCancelsSetup(): void
    {
        $queryParams = [
            'action' => 'cancel',
            'redirect' => 'web_list',
            'redirectParams' => 'some=param',
        ];

        $request = $this->request->withQueryParams($queryParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);
        $redirectUrl = parse_url($response->getHeaderLine('location'));

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/typo3/login', $redirectUrl['path']);

        // Also redirect parameters are still kept
        self::assertStringContainsString('redirect=web_list&redirectParams=some%3Dparam', $redirectUrl['query']);
    }
}
