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

namespace TYPO3\CMS\Core\Tests\Unit\Authentication\Pipeline;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\AuthenticationService;
use TYPO3\CMS\Core\Authentication\Pipeline\LegacyAuthServiceDetector;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class LegacyAuthServiceDetectorTest extends UnitTestCase
{
    private array $originalServices;
    private array $originalConfiguration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalServices = $GLOBALS['T3_SERVICES'] ?? [];
        $this->originalConfiguration = $GLOBALS['TYPO3_CONF_VARS'] ?? [];
    }

    protected function tearDown(): void
    {
        $GLOBALS['T3_SERVICES'] = $this->originalServices;
        $GLOBALS['TYPO3_CONF_VARS'] = $this->originalConfiguration;
        parent::tearDown();
    }

    public static function authServicesDataProvider(): \Generator
    {
        yield 'only the core service' => [
            [AuthenticationService::class => ['available' => true, 'className' => AuthenticationService::class]],
            [],
            false,
        ];
        yield 'core service disabled' => [
            [AuthenticationService::class => ['available' => false, 'className' => AuthenticationService::class]],
            [],
            true,
        ];
        yield 'no service registered' => [
            [],
            [],
            true,
        ];
        yield 'available third-party service' => [
            [AuthenticationService::class => ['available' => true, 'className' => AuthenticationService::class], 'tx_sso' => ['available' => true]],
            [],
            true,
        ];
        yield 'disabled third-party service' => [
            [AuthenticationService::class => ['available' => true, 'className' => AuthenticationService::class], 'tx_sso' => ['available' => false]],
            [],
            false,
        ];
        yield 'third-party service without availability' => [
            [AuthenticationService::class => ['available' => true, 'className' => AuthenticationService::class], 'tx_sso' => []],
            [],
            false,
        ];
        yield 'chain behavior modified' => [
            [AuthenticationService::class => ['available' => true, 'className' => AuthenticationService::class]],
            ['BE_alwaysFetchUser' => true],
            true,
        ];
    }

    #[Test]
    public function requiresLegacyServiceChainWhenTheCoreServiceClassIsReplaced(): void
    {
        $GLOBALS['T3_SERVICES']['auth'] = [AuthenticationService::class => ['available' => true, 'className' => 'Vendor\\Sso\\AuthenticationService']];

        self::assertTrue(new LegacyAuthServiceDetector()->requiresLegacyServiceChain());
    }

    #[Test]
    public function requiresLegacyServiceChainWhenTheCoreServiceIsXclassed(): void
    {
        $GLOBALS['T3_SERVICES']['auth'] = [AuthenticationService::class => ['available' => true, 'className' => AuthenticationService::class]];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][AuthenticationService::class]['className'] = 'Vendor\\Sso\\AuthenticationService';

        self::assertTrue(new LegacyAuthServiceDetector()->requiresLegacyServiceChain());
    }

    #[Test]
    public function requiresLegacyServiceChainWhenPostUserLookUpHookIsRegistered(): void
    {
        $GLOBALS['T3_SERVICES']['auth'] = [AuthenticationService::class => ['available' => true, 'className' => AuthenticationService::class]];
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postUserLookUp'][] = 'Vendor\\Sso\\Hook->postUserLookUp';

        self::assertTrue(new LegacyAuthServiceDetector()->requiresLegacyServiceChain());
    }

    #[Test]
    #[DataProvider('authServicesDataProvider')]
    public function requiresLegacyServiceChainUnlessOnlyTheCoreServiceIsAvailable(array $authServices, array $setup, bool $expected): void
    {
        $GLOBALS['T3_SERVICES']['auth'] = $authServices;
        $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup'] = $setup;

        self::assertSame($expected, new LegacyAuthServiceDetector()->requiresLegacyServiceChain());
    }
}
