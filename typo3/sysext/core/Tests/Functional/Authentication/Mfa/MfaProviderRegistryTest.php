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

namespace TYPO3\CMS\Core\Tests\Functional\Authentication\Mfa;

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderManifest;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class MfaProviderRegistryTest extends FunctionalTestCase
{
    protected MfaProviderRegistry $subject;
    protected AbstractUserAuthentication $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->getContainer()->get(MfaProviderRegistry::class);

        // Add two providers, which both are not active and unlocked
        $this->user = $this->setUpBackendUserFromFixture(1);
        $this->user->user['mfa'] = json_encode([
            'recovery-codes' => [
                'active' => false,
                'codes' => ['some-code'],
            ],
            'totp' => [
                'active' => false,
                'secret' => 'KRMVATZTJFZUC53FONXW2ZJB',
                'attempts' => 0,
            ],
        ]);
    }

    public function registerProviderTest(): void
    {
        self::assertCount(2, $this->subject->getProviders());

        $this->subject->registerProvider(
            new MfaProviderManifest(
                'some-provider',
                'Some provider',
                'Some provider description',
                'Setup instructions for some-provider',
                'actions-music',
                true,
                'SomeProvider',
                $this->getContainer()
            )
        );

        self::assertCount(3, $this->subject->getProviders());
        self::assertTrue($this->subject->hasProvider('some-provider'));
        self::assertInstanceOf(MfaProviderManifest::class, $this->subject->getProvider('some-provider'));
    }

    /**
     * @test
     */
    public function getProviderThrowsExceptionOnInvalidIdentifierTest(): void
    {
        $this->expectExceptionCode(1610994735);
        $this->expectException(\InvalidArgumentException::class);
        $this->subject->getProvider('unknown-provider');
    }

    /**
     * @test
     */
    public function hasActiveProvidersTest(): void
    {
        self::assertFalse($this->subject->hasActiveProviders($this->user));
        $this->activateProvider('totp');
        self::assertTrue($this->subject->hasActiveProviders($this->user));
    }

    /**
     * @test
     */
    public function getActiveProvidersTest(): void
    {
        self::assertCount(0, $this->subject->getActiveProviders($this->user));

        $this->activateProvider('totp');
        $result = $this->subject->getActiveProviders($this->user);

        self::assertCount(1, $result);
        self::assertEquals('totp', array_key_first($result));

        $this->activateProvider('recovery-codes');
        $result = $this->subject->getActiveProviders($this->user);

        self::assertCount(2, $result);
        self::assertEquals('recovery-codes', array_key_last($result));
    }

    /**
     * @test
     */
    public function getFirstAuthenticationAwareProviderTest(): void
    {
        self::assertNull($this->subject->getFirstAuthenticationAwareProvider($this->user));

        $this->activateProvider('recovery-codes');
        // Recovery codes can NOT be a authentication aware provider, without another provider being active
        self::assertNull($this->subject->getFirstAuthenticationAwareProvider($this->user));

        $this->activateProvider('totp');
        self::assertEquals(
            'totp',
            $this->subject->getFirstAuthenticationAwareProvider($this->user)->getIdentifier()
        );

        // @todo This does no significance, until we have another "full" provider
        $this->setDefaultProvider('totp');
        self::assertEquals(
            'totp',
            $this->subject->getFirstAuthenticationAwareProvider($this->user)->getIdentifier()
        );
    }

    /**
     * @test
     */
    public function hasLockedProvidersTest(): void
    {
        self::assertFalse($this->subject->hasLockedProviders($this->user));
        $this->lockProvider('totp');
        self::assertTrue($this->subject->hasLockedProviders($this->user));
    }

    /**
     * @test
     */
    public function getLockedProvidersTest(): void
    {
        self::assertCount(0, $this->subject->getLockedProviders($this->user));

        $this->lockProvider('totp');
        $result = $this->subject->getLockedProviders($this->user);

        self::assertCount(1, $result);
        self::assertEquals('totp', array_key_first($result));

        $this->lockProvider('recovery-codes');
        $result = $this->subject->getLockedProviders($this->user);

        self::assertCount(2, $result);
        self::assertEquals('recovery-codes', array_key_last($result));
    }

    /**
     * @test
     */
    public function allowedProvidersItemsProcFuncTest(): void
    {
        $parameters = [];
        $this->subject->allowedProvidersItemsProcFunc($parameters);

        self::assertEquals(
            [
                'items' => [
                    [
                        'LLL:EXT:core/Resources/Private/Language/locallang_mfa_provider.xlf:totp.title',
                        'totp',
                        'actions-qrcode',
                        null,
                        'LLL:EXT:core/Resources/Private/Language/locallang_mfa_provider.xlf:totp.description',
                    ],
                    [
                        'LLL:EXT:core/Resources/Private/Language/locallang_mfa_provider.xlf:recoveryCodes.title',
                        'recovery-codes',
                        'content-text-columns',
                        null,
                        'LLL:EXT:core/Resources/Private/Language/locallang_mfa_provider.xlf:recoveryCodes.description',
                    ],
                ],
            ],
            $parameters
        );
    }

    protected function activateProvider(string $provider): void
    {
        $mfa = json_decode($this->user->user['mfa'], true);
        $mfa[$provider]['active'] = true;
        $this->user->user['mfa'] = json_encode($mfa);
    }

    protected function lockProvider(string $provider): void
    {
        $mfa = json_decode($this->user->user['mfa'], true);
        $mfa[$provider]['attempts'] = 3;
        $this->user->user['mfa'] = json_encode($mfa);
    }

    protected function setDefaultProvider(string $defaultProvider): void
    {
        $this->user->uc['mfa']['defaultProvider'] = $defaultProvider;
    }
}
