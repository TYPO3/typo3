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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Authentication\PasswordReset;
use TYPO3\CMS\Backend\Controller\ResetPasswordController;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\View\AuthenticationStyleInformation;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ResetPasswordControllerTest extends FunctionalTestCase
{
    protected ResetPasswordController $subject;
    protected ServerRequestInterface $request;

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'backend' => [
                'loginHighlightColor' => '#abcdef',
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $passwordResetMock = $this->createMock(PasswordReset::class);
        $passwordResetMock->method('isEnabled')->willReturn(true);
        $passwordResetMock->method('isValidResetTokenFromRequest')->with(self::anything())->willReturn(true);
        $passwordResetMock->method('resetPassword')->with(self::anything(), self::anything())->willReturn(true);

        $this->subject = new ResetPasswordController(
            $this->get(Context::class),
            $this->get(Locales::class),
            $this->get(Features::class),
            $this->get(UriBuilder::class),
            $this->get(PageRenderer::class),
            $passwordResetMock,
            $this->get(Typo3Information::class),
            $this->get(AuthenticationStyleInformation::class),
            new ExtensionConfiguration(),
            $this->get(BackendViewFactory::class),
        );

        $this->request = (new ServerRequest('https://example.com/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-backend']));

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->initializeUserSessionManager();
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
    }

    /**
     * @test
     */
    public function throwsPropagateResponseExceptionOnLoggedInUser(): void
    {
        $backendUser = new BackendUserAuthentication();
        $backendUser->user['uid'] = 13;
        GeneralUtility::makeInstance(Context::class)->setAspect('backend.user', new UserAspect($backendUser));

        $this->expectExceptionCode(1618342858);
        $this->expectException(PropagateResponseException::class);
        $GLOBALS['TYPO3_REQUEST'] = $this->request;
        $this->subject->forgetPasswordFormAction($this->request);
    }

    /**
     * @test
     */
    public function customStylingIsApplied(): void
    {
        $request = $this->request;
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $response = $this->subject->forgetPasswordFormAction($request)->getBody()->__toString();
        self::assertStringContainsString('/*loginHighlightColor*/', $response);
        self::assertMatchesRegularExpression('/\.btn-login { background-color: #abcdef; }.*\.card-login \.card-footer { border-color: #abcdef; }/s', $response);
    }

    /**
     * @test
     */
    public function queryArgumentsAreKept(): void
    {
        $queryParams = [
          'loginProvider'  => '123456789',
          'redirect' => 'web_list',
          'redirectParams' => 'id=123',
        ];
        $request = $this->request->withQueryParams($queryParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        // Both views supply "go back" links which should contain the defined queryParams
        $expected = htmlspecialchars(http_build_query($queryParams));

        self::assertStringContainsString($expected, $this->subject->forgetPasswordFormAction($request)->getBody()->__toString());
        self::assertStringContainsString($expected, $this->subject->initiatePasswordResetAction($request)->getBody()->__toString());
        self::assertStringContainsString($expected, $this->subject->passwordResetAction($request)->getBody()->__toString());
    }

    /**
     * @test
     */
    public function initiatePasswordResetPreventsTimeBasedInformationDisclosure(): void
    {
        $start = microtime(true);
        $request = $this->request;
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $this->subject->initiatePasswordResetAction($request);
        self::assertGreaterThan(0.2, microtime(true) - $start);
    }

    /**
     * @test
     */
    public function initiatePasswordResetValidatesGivenEmailAddress(): void
    {
        $request = $this->request->withParsedBody(['email' =>'email..email@example.com']);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        self::assertStringContainsString(
            'The entered email address is invalid. Please try again.',
            $this->subject->initiatePasswordResetAction($request)->getBody()->__toString()
        );
    }

    /**
     * @test
     */
    public function resetPasswordFormUrlContainsQueryParameters(): void
    {
        $queryParams = [
          't'  => 'some-token-123',
          'i' => 'some-identifier-456',
          'e' => '1618401660',
        ];
        $request = $this->request->withQueryParams($queryParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        // Expect the form action to contain the necessary reset query params
        $expected = '<form action="/typo3/login/password-reset/finish?' . htmlspecialchars(http_build_query($queryParams));

        self::assertStringContainsString($expected, $this->subject->passwordResetAction($request)->getBody()->__toString());
    }
}
