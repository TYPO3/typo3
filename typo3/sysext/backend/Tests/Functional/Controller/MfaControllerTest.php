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
use TYPO3\CMS\Backend\Controller\MfaController;
use TYPO3\CMS\Backend\Routing\Route;
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
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class MfaControllerTest extends FunctionalTestCase
{
    use ProphecyTrait;

    protected MfaController $subject;
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
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        Bootstrap::initializeLanguageObject();

        $this->subject = new MfaController(
            $this->get(UriBuilder::class),
            $this->get(AuthenticationStyleInformation::class),
            $this->get(PageRenderer::class),
            $this->get(ExtensionConfiguration::class),
            new Logger('testing'),
            $this->get(BackendViewFactory::class)
        );
        $this->subject->injectMfaProviderRegistry($this->get(MfaProviderRegistry::class));

        $this->request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-backend']));
    }

    /**
     * @test
     */
    public function handleRequestThrowsExceptionOnInvalidActionTest(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1611879244);

        $request = $this->request->withQueryParams(['action' => 'unknown']);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $this->subject->handleRequest($request);
    }

    /**
     * @test
     */
    public function handleRequestThrowsExceptionOnMissingProviderTest(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1611879242);

        $request = $this->request->withQueryParams(['action' => 'auth']);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $this->subject->handleRequest($request);
    }

    /**
     * @test
     */
    public function handleRequestThrowsExceptionOnInactiveProviderTest(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1611879242);

        $queryParams = [
            'action' => 'auth',
            'identifier' => 'totp',
        ];

        $request = $this->request->withQueryParams($queryParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $this->subject->handleRequest($request);
    }

    /**
     * @test
     */
    public function handleRequestReturnsAuthViewTest(): void
    {
        $GLOBALS['BE_USER']->user['mfa'] = json_encode([
            'totp' => ['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB'],
        ]);

        $queryParams = [
            'action' => 'auth',
            'identifier' => 'totp',
        ];

        $request = $this->request->withQueryParams($queryParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        self::assertEquals(200, $response->getStatusCode());

        $responseContent = $response->getBody()->__toString();

        // Auth view for provider is renderer
        self::assertStringContainsString('Time-based one-time password', $responseContent);
        self::assertMatchesRegularExpression('/<form.*name="verify".*id="mfaController">/s', $responseContent);

        // Ensure provider specific content is added as well
        self::assertMatchesRegularExpression('/<input.*id="totp"/s', $responseContent);
    }

    /**
     * @test
     */
    public function handleRequestReturnsLockedAuthViewTest(): void
    {
        $GLOBALS['BE_USER']->user['mfa'] = json_encode([
            'totp' => ['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB', 'attempts' => 3],
        ]);

        $queryParams = [
            'action' => 'auth',
            'identifier' => 'totp',
        ];

        $request = $this->request->withQueryParams($queryParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertStringContainsString('This provider is temporarily locked!', $response->getBody()->__toString());
    }

    /**
     * @test
     */
    public function handleRequestReturnsAlternativeProvidersInAuthViewTest(): void
    {
        $GLOBALS['BE_USER']->user['mfa'] = json_encode([
            'totp' => ['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB'],
            'recovery-codes' => ['active' => true, 'codes' => ['some-code']],
        ]);

        $queryParams = [
            'action' => 'auth',
            'identifier' => 'totp',
        ];

        $request = $this->request->withQueryParams($queryParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        self::assertEquals(200, $response->getStatusCode());

        $responseContent = $response->getBody()->__toString();
        self::assertStringContainsString('Alternative providers', $responseContent);
        self::assertMatchesRegularExpression('/<a.*title="Use Recovery codes"/s', $responseContent);
    }

    /**
     * @test
     */
    public function handleRequestRedirectsToLoginOnInvalidRequestTest(): void
    {
        $GLOBALS['BE_USER']->user['mfa'] = json_encode([
            'totp' => ['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB'],
        ]);

        $queryParams = [
            'action' => 'verify',
            'identifier' => 'totp',
        ];

        // The "totp" parameter is missing, therefore the TotpProvider will return false on ->canProcess()
        $request = $this->request->withQueryParams($queryParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/typo3/login', parse_url($response->getHeaderLine('location'))['path']);
    }

    /**
     * @test
     */
    public function handleRequestRedirectsToLoginOnLockedProviderRequestTest(): void
    {
        $GLOBALS['BE_USER']->user['mfa'] = json_encode([
            'totp' => ['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB', 'attempts' => 3],
        ]);

        $queryParams = [
            'action' => 'verify',
            'identifier' => 'totp',
            'totp' => '123456',
        ];

        $request = $this->request->withQueryParams($queryParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/typo3/login', parse_url($response->getHeaderLine('location'))['path']);
    }

    /**
     * @test
     */
    public function handleRequestRedirectsToAuthViewOnUnsuccessfulAuthenticationTest(): void
    {
        $GLOBALS['BE_USER']->user['mfa'] = json_encode([
            'totp' => ['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB'],
        ]);

        $queryParams = [
            'action' => 'verify',
            'identifier' => 'totp',
            'totp' => '123456',
        ];

        $request = $this->request->withQueryParams($queryParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/typo3/auth/mfa', parse_url($response->getHeaderLine('location'))['path']);
    }

    /**
     * @test
     */
    public function handleRequestSetsSessionKeyOnSuccessfulAuthenticationTest(): void
    {
        $GLOBALS['BE_USER']->user['mfa'] = json_encode([
            'totp' => ['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB'],
        ]);

        $timestamp = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');
        $totp = GeneralUtility::makeInstance(
            Totp::class,
            'KRMVATZTJFZUC53FONXW2ZJB'
        )->generateTotp((int)floor($timestamp / 30));

        $queryParams = [
            'action' => 'verify',
            'identifier' => 'totp',
            'totp' => $totp,
        ];

        // Ensure mfa session key is not set
        self::assertFalse((bool)$GLOBALS['BE_USER']->getSessionData('mfa'));

        $request = $this->request->withQueryParams($queryParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        // Session key is set - User is authenticated
        self::assertTrue($GLOBALS['BE_USER']->getSessionData('mfa'));

        // Redirect back to login
        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/typo3/login', parse_url($response->getHeaderLine('location'))['path']);
    }

    /**
     * @test
     */
    public function handleRequestRedirectsToLoginOnCancelTest(): void
    {
        $request = $this->request->withQueryParams(['action' => 'cancel']);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/typo3/login', parse_url($response->getHeaderLine('location'))['path']);
    }
}
