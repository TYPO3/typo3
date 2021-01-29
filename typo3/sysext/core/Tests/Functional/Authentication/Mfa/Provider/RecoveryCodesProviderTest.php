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

namespace TYPO3\CMS\Core\Tests\Functional\Authentication\Mfa\Provider;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderPropertyManager;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Authentication\Mfa\Provider\RecoveryCodesProvider;
use TYPO3\CMS\Core\Authentication\Mfa\Provider\Totp;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RecoveryCodesProviderTest extends FunctionalTestCase
{
    private BackendUserAuthentication $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/be_users.xml');
        $this->user = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = LanguageService::createFromUserPreferences($this->user);
    }

    /**
     * @test
     */
    public function setupFailsIfNoOtherMfaProviderIsActive(): void
    {
        $request = new ServerRequest('https://example.com', 'GET');
        $recoveryCodesManifest = $this->getContainer()->get(MfaProviderRegistry::class)->getProvider('recovery-codes');
        $propertyManager = MfaProviderPropertyManager::create($recoveryCodesManifest, $this->user);
        $subject = $this->getContainer()->get(RecoveryCodesProvider::class);
        $this->expectException(PropagateResponseException::class);
        $subject->handleRequest($request, $propertyManager, 'setup');
    }

    /**
     * @test
     */
    public function setupReturnsHtmlWithRecoveryCodes(): void
    {
        $this->setupTotp();
        $request = new ServerRequest('https://example.com', 'GET');
        $recoveryCodesManifest = $this->getContainer()->get(MfaProviderRegistry::class)->getProvider('recovery-codes');
        $propertyManager = MfaProviderPropertyManager::create($recoveryCodesManifest, $this->user);
        $subject = $this->getContainer()->get(RecoveryCodesProvider::class);
        $response = $subject->handleRequest($request, $propertyManager, 'setup');
        self::assertStringContainsString('<textarea type="text" id="recoveryCodes"', $response->getBody()->getContents());
    }

    /**
     * The user must have another MFA provider configured / activated in order to test recovery codes
     */
    protected function setupTotp(): void
    {
        $totpProvider = $this->getContainer()->get(MfaProviderRegistry::class)->getProvider('totp');
        $propertyManager = MfaProviderPropertyManager::create($totpProvider, $this->user);
        $request = new ServerRequest('https://example.com', 'POST');
        $secret = 'supersecret';
        $timestamp = $this->getContainer()->get(Context::class)->getPropertyFromAspect('date', 'timestamp');
        $totpInstance = new Totp($secret);
        $totp = $totpInstance->generateTotp((int)floor($timestamp / 30));
        $request = $request->withQueryParams(['totp' => $totp]);
        $request = $request->withParsedBody(['secret' => $secret, 'checksum' => GeneralUtility::hmac($secret, 'totp-setup')]);
        $result = $totpProvider->activate($request, $propertyManager);
        self::assertTrue($result);
    }
}
