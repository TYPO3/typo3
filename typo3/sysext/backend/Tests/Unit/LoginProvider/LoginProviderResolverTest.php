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

namespace TYPO3\CMS\Backend\Tests\Unit\LoginProvider;

use TYPO3\CMS\Backend\LoginProvider\LoginProviderResolver;
use TYPO3\CMS\Backend\LoginProvider\UsernamePasswordLoginProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class LoginProviderResolverTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders']);
    }

    /**
     * @test
     */
    public function validateAndSortLoginProvidersDetectsMissingProviderConfiguration(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1433417281);
        new LoginProviderResolver();
    }

    /**
     * @test
     */
    public function validateAndSortLoginProvidersDetectsNonArrayProviderConfiguration(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1433417281);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = 'foo';
        new LoginProviderResolver();
    }

    /**
     * @test
     */
    public function validateAndSortLoginProvidersDetectsIfNoProviderIsRegistered(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1433417281);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = [];
        new LoginProviderResolver();
    }

    /**
     * @test
     */
    public function validateAndSortLoginProvidersDetectsMissingConfigurationForProvider(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1433416043);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = [
            1433419736 => [],
        ];
        new LoginProviderResolver();
    }

    /**
     * @test
     */
    public function validateAndSortLoginProvidersDetectsWrongProvider(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1460977275);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = [
            1433419736 => [
                'provider' => \stdClass::class,
            ],
        ];
        new LoginProviderResolver();
    }

    /**
     * @test
     */
    public function validateAndSortLoginProvidersDetectsMissingLabel(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1433416044);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = [
            1433419736 => [
                'provider' => UsernamePasswordLoginProvider::class,
                'sorting' => 30,
                'icon-class' => 'foo',
            ],
        ];
        new LoginProviderResolver();
    }

    /**
     * @test
     */
    public function validateAndSortLoginProvidersDetectsMissingIconClass(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1433416045);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = [
            1433419736 => [
                'provider' => UsernamePasswordLoginProvider::class,
                'sorting' => 30,
                'label' => 'foo',
            ],
        ];
        new LoginProviderResolver();
    }

    /**
     * @test
     */
    public function validateAndSortLoginProvidersDetectsMissingSorting(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1433416046);
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = [
            1433419736 => [
                'provider' => UsernamePasswordLoginProvider::class,
                'label' => 'foo',
                'icon-class' => 'foo',
            ],
        ];
        new LoginProviderResolver();
    }

    /**
     * @test
     */
    public function loginProviderResolverRespectsConstructorArgument(): void
    {
        $loginProviders = [
            1433419736 => [
                'provider' => UsernamePasswordLoginProvider::class,
                'sorting' => 30,
                'label' => 'foo',
                'icon-class' => 'foo',
            ],
        ];

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'] = $loginProviders;
        $loginProviders[1433419736]['label'] = 'provided-by-constructor-argument';

        self::assertEquals(
            'provided-by-constructor-argument',
            (new LoginProviderResolver($loginProviders))->getLoginProviderConfigurationByIdentifier('1433419736')['label']
        );
    }

    /**
     * @test
     */
    public function hasLoginProviderTest(): void
    {
        $subject = (new LoginProviderResolver($this->getDefaultLoginProviders()));

        self::assertFalse($subject->hasLoginProvider('123321123'));
        self::assertTrue($subject->hasLoginProvider('123456789'));
        self::assertTrue($subject->hasLoginProvider('987654321'));
    }

    /**
     * @test
     */
    public function getLoginProviderConfigurationByIdentifierTest(): void
    {
        $subject = (new LoginProviderResolver($this->getDefaultLoginProviders()));

        self::assertNotEmpty($subject->getLoginProviderConfigurationByIdentifier('123456789'));
        self::assertEquals(
            30,
            $subject->getLoginProviderConfigurationByIdentifier('987654321')['sorting']
        );
    }

    /**
     * @test
     */
    public function getLoginProvidersTest(): void
    {
        $resolvedLoginProviders = (new LoginProviderResolver($this->getDefaultLoginProviders()))->getLoginProviders();

        self::assertCount(2, $resolvedLoginProviders);
        // sorting is applied 987654321 is now first
        self::assertEquals('bar', array_shift($resolvedLoginProviders)['label']);
    }

    /**
     * @test
     */
    public function getPrimaryLoginProviderIdentifierTest(): void
    {
        $subject = (new LoginProviderResolver($this->getDefaultLoginProviders()));
        self::assertEquals('987654321', $subject->getPrimaryLoginProviderIdentifier());
    }

    /**
     * @test
     */
    public function resolveLoginProviderIdentifierFromRequestTest(): void
    {
        $subject = (new LoginProviderResolver($this->getDefaultLoginProviders()));

        // First provider is returned on "empty" request
        self::assertEquals(
            '987654321',
            $subject->resolveLoginProviderIdentifierFromRequest(
                new ServerRequest(new Uri('https://example.com')),
                'be_lastLoginProvider'
            )
        );

        // First provider is returned on invalid request
        self::assertEquals(
            '987654321',
            $subject->resolveLoginProviderIdentifierFromRequest(
                (new ServerRequest(new Uri('https://example.com')))
                    ->withQueryParams(['loginProvider' => '1212121212']),
                'be_lastLoginProvider'
            )
        );

        // query params contain provider
        self::assertEquals(
            '123456789',
            $subject->resolveLoginProviderIdentifierFromRequest(
                (new ServerRequest(new Uri('https://example.com')))
                    ->withQueryParams(['loginProvider' => '123456789']),
                'be_lastLoginProvider'
            )
        );

        // cookie contains provider
        self::assertEquals(
            '123456789',
            $subject->resolveLoginProviderIdentifierFromRequest(
                (new ServerRequest(new Uri('https://example.com')))
                    ->withCookieParams(['be_lastLoginProvider' => '123456789']),
                'be_lastLoginProvider'
            )
        );

        // query params and cookie contain provider - query params precedes
        self::assertEquals(
            '123456789',
            $subject->resolveLoginProviderIdentifierFromRequest(
                (new ServerRequest(new Uri('https://example.com')))
                    ->withQueryParams(['loginProvider' => '123456789'])
                    ->withCookieParams(['be_lastLoginProvider' => '121212121']),
                'be_lastLoginProvider'
            )
        );
    }

    protected function getDefaultLoginProviders(): array
    {
        return [
            '123456789' => [
                'provider' => UsernamePasswordLoginProvider::class,
                'sorting' => 20,
                'label' => 'foo',
                'icon-class' => 'foo',
            ],
            '987654321' => [
                'provider' => UsernamePasswordLoginProvider::class,
                'sorting' => 30,
                'label' => 'bar',
                'icon-class' => 'bar',
            ],
        ];
    }
}
