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

use TYPO3\CMS\Backend\Controller\MfaConfigurationController;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Authentication\Mfa\Provider\Totp;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class MfaConfigurationControllerTest extends FunctionalTestCase
{
    protected MfaConfigurationController $subject;
    protected ServerRequest $request;
    protected NormalizedParams $normalizedParams;

    protected array $configurationToUseInTestInstance = [
        'BE' => [
            'recommendedMfaProvider' => 'totp',
            'requireMfa' => 1,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        Bootstrap::initializeLanguageObject();

        $this->subject = new MfaConfigurationController(
            $this->get(IconFactory::class),
            $this->get(UriBuilder::class),
            $this->get(ModuleTemplateFactory::class),
        );
        $this->subject->injectMfaProviderRegistry($this->get(MfaProviderRegistry::class));

        $this->request = (new ServerRequest('https://example.com/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-backend']));
        $this->normalizedParams = new NormalizedParams([], [], '', '');
    }

    /**
     * @test
     */
    public function handleRequestReturnsBadRequestForInvalidActionTest(): void
    {
        $queryParams = [
            'action' => 'unknown',
        ];

        $request = $this->request
            ->withAttribute('normalizedParams', $this->normalizedParams)
            ->withQueryParams($queryParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals('Action not allowed', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function handleRequestFallsBackToOverviewActionIfNoActionGivenTest(): void
    {
        $request = $this->request->withAttribute('normalizedParams', $this->normalizedParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        self::assertEquals(200, $response->getStatusCode());
        $response->getBody()->rewind();
        self::assertStringContainsString('Multi-factor Authentication Overview', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function handleRequestShowsAllRegisteredProvidersTest(): void
    {
        $request = $this->request->withAttribute('normalizedParams', $this->normalizedParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        self::assertEquals(200, $response->getStatusCode());

        $response->getBody()->rewind();
        $responseContent = $response->getBody()->getContents();
        foreach (GeneralUtility::makeInstance(MfaProviderRegistry::class)->getProviders() as $provider) {
            self::assertStringContainsString('id="' . $provider->getIdentifier() . '-provider"', $responseContent);
        }
    }

    /**
     * @test
     */
    public function handleRequestAddsInformationAboutMfaBeingRequiredAndRecommendedTest(): void
    {
        $request = $this->request->withAttribute('normalizedParams', $this->normalizedParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        self::assertEquals(200, $response->getStatusCode());
        $response->getBody()->rewind();
        $responseContent = $response->getBody()->getContents();
        self::assertStringContainsString('Multi-factor authentication required', $responseContent);
        self::assertMatchesRegularExpression('/<div.*class="card card-size-fixed-small border-success shadow".*id="totp-provider"/s', $responseContent);
    }

    /**
     * @test
     */
    public function handleRequestIndicatesDefaultProviderTest(): void
    {
        $GLOBALS['BE_USER']->user['mfa'] = json_encode(['totp' => ['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB']]);
        $GLOBALS['BE_USER']->uc['mfa']['defaultProvider'] = 'totp';

        $request = $this->request->withAttribute('normalizedParams', $this->normalizedParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        self::assertEquals(200, $response->getStatusCode());
        $response->getBody()->rewind();
        self::assertMatchesRegularExpression('/<span.*title="Default provider">/s', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function handleRequestRespectsReturnUrlTest(): void
    {
        $returnUrl = Environment::getPublicPath() . '/typo3/some/module?token=123';

        $queryParams = [
            'action' => 'overview',
            'returnUrl' => $returnUrl,
        ];

        $request = $this->request
            ->withAttribute('normalizedParams', $this->normalizedParams)
            ->withQueryParams($queryParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        self::assertEquals(200, $response->getStatusCode());
        $response->getBody()->rewind();
        self::assertStringContainsString('href="' . $returnUrl . '" class="btn btn-default btn-sm " title="Go back"', $response->getBody()->getContents());
    }

    /**
     * @test
     * @dataProvider handleRequestRedirectsToOverviewOnActionProviderMismatchTestDataProvider
     */
    public function handleRequestRedirectsToOverviewOnActionProviderMismatchTest(
        string $action,
        string $provider,
        bool $providerActive,
        string $flashMessage
    ): void {
        $queryParams = [
            'action' => $action,
            'identifier' => $provider,
        ];

        if ($providerActive) {
            $GLOBALS['BE_USER']->user['mfa'] = json_encode(['totp' => ['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB']]);
        }

        $request = $this->request
            ->withAttribute('normalizedParams', $this->normalizedParams)
            ->withQueryParams($queryParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        $redirect = parse_url($response->getHeaderLine('location'));
        $query = [];
        parse_str($redirect['query'] ?? '', $query);
        $message = GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages()[0];

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/typo3/mfa', $redirect['path']);
        self::assertEquals('overview', $query['action']);
        self::assertEquals($flashMessage, $message->getMessage());
    }

    public function handleRequestRedirectsToOverviewOnActionProviderMismatchTestDataProvider(): \Generator
    {
        yield 'Empty provider' => [
            'setup',
            '',
            false,
            'Selected MFA provider was not found!',
        ];
        yield 'Invalid provider' => [
            'setup',
            'unknown',
            false,
            'Selected MFA provider was not found!',
        ];
        yield 'Inactive provider on edit' => [
            'edit',
            'totp',
            false,
            'Selected MFA provider has to be active to perform this action!',
        ];
        yield 'Inactive provider on update' => [
            'save',
            'totp',
            false,
            'Selected MFA provider has to be active to perform this action!',
        ];
        yield 'Inactive provider on deactivate' => [
            'deactivate',
            'totp',
            false,
            'Selected MFA provider has to be active to perform this action!',
        ];
        yield 'Inactive provider on unlock' => [
            'unlock',
            'totp',
            false,
            'Selected MFA provider has to be active to perform this action!',
        ];
        yield 'Active provider on setup' => [
            'setup',
            'totp',
            true,
            'Selected MFA provider has to be inactive to perform this action!',
        ];
        yield 'Active provider on activate' => [
            'activate',
            'totp',
            true,
            'Selected MFA provider has to be inactive to perform this action!',
        ];
    }

    /**
     * @test
     * @dataProvider handleRequestForwardsToCorrectActionTestDataProvider
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    public function handleRequestForwardsToCorrectActionTest(
        string $action,
        string $provider,
        bool $providerActive,
        bool $redirect,
        string $searchString
    ): void {
        $parsedBody = [];
        $queryParams = [
            'action' => $action,
            'identifier' => $provider,
        ];

        if ($providerActive) {
            $GLOBALS['BE_USER']->user['mfa'] = json_encode([
                'totp' => [
                    'active' => true,
                    'secret' => 'KRMVATZTJFZUC53FONXW2ZJB',
                    'attempts' => ($action === 'unlock' ? 3 : 0),
                ],
            ]);
        }

        if ($action === 'activate') {
            $timestamp = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');
            $parsedBody['totp'] = GeneralUtility::makeInstance(
                Totp::class,
                'KRMVATZTJFZUC53FONXW2ZJB'
            )->generateTotp((int)floor($timestamp / 30));
            $parsedBody['secret'] = 'KRMVATZTJFZUC53FONXW2ZJB';
            $parsedBody['checksum'] = GeneralUtility::hmac('KRMVATZTJFZUC53FONXW2ZJB', 'totp-setup');
        }

        $request = $this->request
            ->withAttribute('normalizedParams', $this->normalizedParams)
            ->withQueryParams($queryParams)
            ->withParsedBody($parsedBody);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        if ($redirect) {
            self::assertEquals(302, $response->getStatusCode());
            $messages = GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages()[0];
            self::assertEquals($searchString, $messages->getMessage());
        } else {
            self::assertEquals(200, $response->getStatusCode());
            $response->getBody()->rewind();
            self::assertStringContainsString($searchString, $response->getBody()->getContents());
        }
    }

    public function handleRequestForwardsToCorrectActionTestDataProvider(): \Generator
    {
        yield 'Edit provider' => [
            'edit',
            'totp',
            true,
            false,
            'Edit Time-based one-time password',
        ];
        yield 'Save provider' => [
            'save',
            'totp',
            true,
            true,
            'Successfully updated MFA provider Time-based one-time password.',
        ];
        yield 'Deactivate provider' => [
            'deactivate',
            'totp',
            true,
            true,
            'Successfully deactivated MFA provider Time-based one-time password.',
        ];
        yield 'Unlock provider' => [
            'unlock',
            'totp',
            true,
            true,
            'Successfully unlocked MFA provider Time-based one-time password.',
        ];
        yield 'Setup provider' => [
            'setup',
            'totp',
            false,
            false,
            'Set up Time-based one-time password',
        ];
        yield 'Activate provider' => [
            'activate',
            'totp',
            false,
            true,
            'Successfully activated MFA provider Time-based one-time password.',
        ];
    }

    /**
     * @test
     * @dataProvider handleRequestAddsFormOnInteractionViewsTestTestDataProvider
     */
    public function handleRequestAddsFormOnInteractionViewsTest(
        string $action,
        bool $providerActive,
        string $providerContent
    ): void {
        $queryParams = [
            'action' => $action,
            'identifier' => 'totp',
        ];

        if ($providerActive) {
            $GLOBALS['BE_USER']->user['mfa'] = json_encode(['totp' => ['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB']]);
        }

        $request = $this->request
            ->withAttribute('normalizedParams', $this->normalizedParams)
            ->withQueryParams($queryParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->handleRequest($request);

        $response->getBody()->rewind();
        $responseContent = $response->getBody()->getContents();

        self::assertEquals(200, $response->getStatusCode());
        self::assertMatchesRegularExpression('/<a.*href="\/typo3\/mfa.*title="Close">/s', $responseContent);
        self::assertMatchesRegularExpression('/<button.*name="save".*form="mfaConfigurationController">/s', $responseContent);
        self::assertMatchesRegularExpression('/<form.*name="' . $action . '".*id="mfaConfigurationController">/s', $responseContent);

        // Ensure provider specific content is added as well
        self::assertMatchesRegularExpression($providerContent, $responseContent);
    }

    public function handleRequestAddsFormOnInteractionViewsTestTestDataProvider(): \Generator
    {
        yield 'Edit provider' => ['edit', true, '/<input.*id="name"/s'];
        yield 'Setup provider' => ['setup', false, '/<input.*id="totp"/s'];
    }
}
