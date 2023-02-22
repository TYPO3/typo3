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

namespace TYPO3\CMS\Frontend\Tests\Functional\Authentication;

use GuzzleHttp\Cookie\SetCookie;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Security\Nonce;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FrontendUserAuthenticationTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const ROOT_PAGE_ID = 1;
    protected const LANGUAGE_PRESETS = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet('typo3/sysext/frontend/Tests/Functional/Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'frontend_authentication',
            $this->buildSiteConfiguration(self::ROOT_PAGE_ID, '/'),
        );
        $this->setUpFrontendRootPage(self::ROOT_PAGE_ID, ['typo3/sysext/frontend/Tests/Functional/Fixtures/TypoScript/page.typoscript']);
    }

    /**
     * @test
     */
    public function feSessionsAreNotStoredForAnonymousSessions(): void
    {
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));

        self::assertStringNotContainsString('fe_typo_user', $response->getHeaderLine('Set-Cookie'));
        $this->assertCSVDataSet('typo3/sysext/frontend/Tests/Functional/Authentication/Fixtures/fe_sessions_empty.csv');
    }

    /**
     * @test
     */
    public function canCreateNewAndExistingSessionWithValidRequestToken(): void
    {
        $this->importCSVDataSet('typo3/sysext/frontend/Tests/Functional/Authentication/Fixtures/fe_users.csv');

        $nonce = Nonce::create();
        $requestToken = RequestToken::create('core/user-auth/fe')->toHashSignedJwt($nonce);
        $request = (new InternalRequest())
            ->withPageId(self::ROOT_PAGE_ID)
            ->withMethod('POST')
            ->withParsedBody(
                [
                    'user' => 'testuser',
                    'pass' => 'test',
                    'logintype' => 'login',
                    '__RequestToken' => $requestToken,
                ]
            )
            ->withCookieParams([123 => 'bogus', 'typo3nonce_' . $nonce->getSigningIdentifier()->name => $nonce->toHashSignedJwt()]);

        $response = $this->executeFrontendSubRequest($request);

        self::assertStringContainsString('fe_typo_user', $response->getHeaderLine('Set-Cookie'));
        $this->assertCSVDataSet('typo3/sysext/frontend/Tests/Functional/Authentication/Fixtures/fe_sessions_filled.csv');

        // Now check whether the existing session is retrieved by providing the retrieved JWT token in the cookie params.
        $cookie = SetCookie::fromString($response->getHeaderLine('Set-Cookie'));
        $request = (new InternalRequest())
            ->withPageId(self::ROOT_PAGE_ID)
            ->withCookieParams([$cookie->getName() => $cookie->getValue()]);

        $frontendUserAuthentication = new FrontendUserAuthentication();
        $frontendUserAuthentication->setLogger(new NullLogger());
        $frontendUserAuthentication->start($request);

        self::assertNotNull($frontendUserAuthentication->user);
        self::assertEquals('testuser', $frontendUserAuthentication->user['username']);
    }
}
