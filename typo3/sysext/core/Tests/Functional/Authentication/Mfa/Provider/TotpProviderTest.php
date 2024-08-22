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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderManifestInterface;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderPropertyManager;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Authentication\Mfa\MfaViewType;
use TYPO3\CMS\Core\Authentication\Mfa\Provider\Totp;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TotpProviderTest extends FunctionalTestCase
{
    private BackendUserAuthentication $user;
    private HashService $hashService;
    private MfaProviderManifestInterface $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->user = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->user);
        $this->hashService = $this->get(HashService::class);
        $this->subject = $this->get(MfaProviderRegistry::class)->getProvider('totp');
    }

    #[Test]
    public function canProcessTest(): void
    {
        self::assertFalse($this->subject->canProcess(new ServerRequest('https://example.com', 'POST')));

        // Add necessary query parameter
        self::assertTrue($this->subject->canProcess(
            (new ServerRequest('https://example.com', 'POST'))
                ->withQueryParams(['totp' => '123456'])
        ));
    }

    #[Test]
    public function isActiveTest(): void
    {
        // No provider entry exists
        self::assertFalse($this->subject->isActive(MfaProviderPropertyManager::create($this->subject, $this->user)));

        // Active state missing
        $this->setupUser(['secret' => 'KRMVATZTJFZUC53FONXW2ZJB']);
        self::assertFalse($this->subject->isActive(MfaProviderPropertyManager::create($this->subject, $this->user)));

        // Secret missing
        $this->setupUser(['active' => true]);
        self::assertFalse($this->subject->isActive(MfaProviderPropertyManager::create($this->subject, $this->user)));

        // Active provider
        $this->setupUser(['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB']);
        self::assertTrue($this->subject->isActive(MfaProviderPropertyManager::create($this->subject, $this->user)));
    }

    #[Test]
    public function isLockedTest(): void
    {
        // No provider entry exists
        self::assertFalse($this->subject->isLocked(MfaProviderPropertyManager::create($this->subject, $this->user)));

        // Provider is not locked
        $this->setupUser(['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB', 'attempts' => 0]);
        self::assertFalse($this->subject->isLocked(MfaProviderPropertyManager::create($this->subject, $this->user)));

        // Lock provider by setting attempts=3
        $this->setupUser(['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB', 'attempts' => 3]);
        self::assertTrue($this->subject->isLocked(MfaProviderPropertyManager::create($this->subject, $this->user)));
    }

    #[Test]
    public function verifyTest(): void
    {
        $request = (new ServerRequest('https://example.com', 'POST'));
        $timestamp = $this->get(Context::class)->getPropertyFromAspect('date', 'timestamp');
        $totp = (new Totp('KRMVATZTJFZUC53FONXW2ZJB'))->generateTotp((int)floor($timestamp / 30));

        // Provider is inactive (secret missing)
        $this->setupUser(['active' => true]);
        self::assertFalse(
            $this->subject->verify(
                $request->withQueryParams(['totp' => $totp]),
                MfaProviderPropertyManager::create($this->subject, $this->user)
            )
        );

        // Provider is locked (attempts=3)
        $this->setupUser(['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB', 'attempts' => 3]);
        self::assertFalse(
            $this->subject->verify(
                $request->withQueryParams(['totp' => $totp]),
                MfaProviderPropertyManager::create($this->subject, $this->user)
            )
        );

        // Wrong totp
        $this->setupUser(['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB', 'attempts' => 0]);
        self::assertFalse(
            $this->subject->verify(
                $request->withQueryParams(['totp' => '123456']),
                MfaProviderPropertyManager::create($this->subject, $this->user)
            )
        );

        // Correct totp
        self::assertTrue(
            $this->subject->verify(
                $request->withQueryParams(['totp' => $totp]),
                MfaProviderPropertyManager::create($this->subject, $this->user)
            )
        );
    }

    #[Test]
    public function activateTest(): void
    {
        $request = (new ServerRequest('https://example.com', 'POST'));
        $propertyManager = MfaProviderPropertyManager::create($this->subject, $this->user);

        // Wrong totp
        self::assertFalse($this->subject->activate($request->withParsedBody(['totp' => '123456']), $propertyManager));

        // Setup form data to activate provider
        $secret = 'KRMVATZTJFZUC53FONXW2ZJB';
        $timestamp = $this->get(Context::class)->getPropertyFromAspect('date', 'timestamp');
        $parsedBody = [
            'totp' => (new Totp($secret))->generateTotp((int)floor($timestamp / 30)),
            'secret' => $secret,
            'checksum' => $this->hashService->hmac($secret, 'totp-setup'),

        ];
        self::assertTrue($this->subject->activate($request->withParsedBody($parsedBody), $propertyManager));
        self::assertTrue($propertyManager->getProperty('active'));
        self::assertEquals('KRMVATZTJFZUC53FONXW2ZJB', $propertyManager->getProperty('secret'));
    }

    #[Test]
    public function deactivateTest(): void
    {
        $request = (new ServerRequest('https://example.com', 'POST'));

        // No provider entry exists
        self::assertFalse($this->subject->deactivate($request, MfaProviderPropertyManager::create($this->subject, $this->user)));

        // Only an active provider can be deactivated
        $this->setupUser(['active' => false, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB']);
        self::assertFalse($this->subject->deactivate($request, MfaProviderPropertyManager::create($this->subject, $this->user)));

        // Active provider is deactivated
        $this->setupUser(['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB']);
        self::assertTrue($this->subject->deactivate($request, MfaProviderPropertyManager::create($this->subject, $this->user)));
    }

    #[Test]
    public function unlockTest(): void
    {
        $request = (new ServerRequest('https://example.com', 'POST'));

        // No provider entry exists
        self::assertFalse($this->subject->unlock($request, MfaProviderPropertyManager::create($this->subject, $this->user)));

        // Provider is inactive (missing secret)
        $this->setupUser(['active' => true, 'attempts' => 3]);
        self::assertFalse($this->subject->unlock($request, MfaProviderPropertyManager::create($this->subject, $this->user)));

        // Provider is not locked
        $this->setupUser(['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB', 'attempts' => 0]);
        self::assertFalse($this->subject->unlock($request, MfaProviderPropertyManager::create($this->subject, $this->user)));

        // Active and locked provider is unlocked
        $this->setupUser(['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB', 'attempts' => 3]);
        self::assertTrue($this->subject->unlock($request, MfaProviderPropertyManager::create($this->subject, $this->user)));
    }

    #[Test]
    public function updateTest(): void
    {
        $request = (new ServerRequest('https://example.com', 'POST'));

        // No provider entry exists
        self::assertFalse($this->subject->update($request, MfaProviderPropertyManager::create($this->subject, $this->user)));

        // Provider is inactive (missing secret)
        $this->setupUser(['active' => true, 'attempts' => 0]);
        self::assertFalse($this->subject->update($request, MfaProviderPropertyManager::create($this->subject, $this->user)));

        // Provider is locked (attempts=3)
        $this->setupUser(['active' => true, 'attempts' => 3]);
        self::assertFalse($this->subject->update($request, MfaProviderPropertyManager::create($this->subject, $this->user)));

        // Active and unlocked provider is updated
        $this->setupUser(['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB', 'attempts' => 0]);
        $request = $request->withParsedBody(['name' => 'some name']);
        self::assertTrue($this->subject->update($request, MfaProviderPropertyManager::create($this->subject, $this->user)));
    }

    #[Test]
    public function setupViewTest(): void
    {
        $request = (new ServerRequest('https://example.com', 'POST'))->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $propertyManager = MfaProviderPropertyManager::create($this->subject, $this->user);
        $response = $this->subject->handleRequest($request, $propertyManager, MfaViewType::SETUP)->getBody()->getContents();

        self::assertMatchesRegularExpression('/<input.*id="totp"/s', $response);
        self::assertMatchesRegularExpression('/<input.*id="secret"/s', $response);
        self::assertMatchesRegularExpression('/<div.*id="qr-code"/s', $response);
        self::assertMatchesRegularExpression('/<typo3-mfa-totp-url-info-button.*url="otpauth:\/\//s', $response);
    }

    #[Test]
    public function editViewTest(): void
    {
        $request = (new ServerRequest('https://example.com', 'POST'))->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->setupUser(['name' => 'some name', 'updated' => 1616099471, 'lastUsed' => 1616099472]);
        $propertyManager = MfaProviderPropertyManager::create($this->subject, $this->user);
        $response = $this->subject->handleRequest($request, $propertyManager, MfaViewType::EDIT)->getBody()->getContents();

        self::assertMatchesRegularExpression('/<td>.*Name.*<td>.*some name/s', $response);
        self::assertMatchesRegularExpression('/<td>.*Last updated.*<td>.*2021-03-18/s', $response);
        self::assertMatchesRegularExpression('/<td>.*Last used.*<td>.*2021-03-18/s', $response);
    }

    #[Test]
    public function authViewTest(): void
    {
        $request = (new ServerRequest('https://example.com', 'POST'))->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->setupUser(['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB', 'attempts' => 0]);
        $propertyManager = MfaProviderPropertyManager::create($this->subject, $this->user);
        $response = $this->subject->handleRequest($request, $propertyManager, MfaViewType::AUTH)->getBody()->getContents();

        self::assertMatchesRegularExpression('/<input.*id="totp"/s', $response);

        // Lock the provider by setting attempts=3
        $this->setupUser(['active' => true, 'secret' => 'KRMVATZTJFZUC53FONXW2ZJB', 'attempts' => 3]);
        $propertyManager = MfaProviderPropertyManager::create($this->subject, $this->user);
        $response = $this->subject->handleRequest($request, $propertyManager, MfaViewType::AUTH)->getBody()->getContents();

        self::assertStringContainsString('The maximum attempts for this provider are exceeded.', $response);
    }

    protected function setupUser(array $properties = []): void
    {
        $this->user->user['mfa'] = json_encode(['totp' => $properties]);
    }
}
