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

namespace TYPO3\CMS\Core\Tests\Unit\Site\Entity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Error\PageErrorHandler\InvalidPageErrorHandlerException;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerNotConfiguredException;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SiteTest extends UnitTestCase
{
    public static function getBaseReturnsProperUriDataProvider(): array
    {
        return [
            'URL with scheme and domain' => ['https://www.typo3.org', 'https://www.typo3.org'],
            'URL with scheme and domain and path' => ['https://www.typo3.org/howdy', 'https://www.typo3.org/howdy'],
            'URL with scheme and domain and path with trailing slash' => ['https://www.typo3.org/howdy/', 'https://www.typo3.org/howdy/'],
            'URL without scheme, but with domain' => ['www.typo3.org', '//www.typo3.org'],
            'URL without scheme, but with domain and path' => ['www.typo3.org/partner', '//www.typo3.org/partner'],
            'URL without scheme, but with domain and path and trailing slash' => ['www.typo3.org/partner/', '//www.typo3.org/partner/'],
            'URL without scheme and domain but with absolute path' => ['/partner', '/partner'],
            'URL without scheme and domain but with absolute path and trailing slash' => ['/partner/', '/partner/'],
            'URL without scheme, domain but with random path receives a scheme divider' => ['partner/', '/partner/'],
            'URL with ID query parameter' => ['/partner/?id=nice-to-see-you', '/partner/?id=nice-to-see-you'],
            'URL with unknown query parameter' => ['/partner/?in-crime=nice-to-see-you', '/partner/?in-crime=nice-to-see-you'],
        ];
    }

    #[DataProvider('getBaseReturnsProperUriDataProvider')]
    #[Test]
    public function getBaseReturnsProperUri($input, $expected): void
    {
        $subject = new Site('all-your-base-belongs-to-us', 13, [
            'base' => $input,
            'languages' => [],
        ]);
        self::assertEquals(new Uri($expected), $subject->getBase());
    }

    /**
     * Consists of three parts:
     * - input "site" base
     * - input "site language" base
     * - expected "site language" base after it is glued together
     */
    public static function getBaseForSiteLanguageReturnsProperUriDataProvider(): array
    {
        return [
            'Language as a regular path segment' => [
                'https://www.typo3.org',
                'en',
                'https://www.typo3.org/en',
            ],
            'Language with two path segments' => [
                'https://www.typo3.org',
                'us/en',
                'https://www.typo3.org/us/en',
            ],
            'Site base is added to absolute path segment' => [
                'https://www.typo3.com/microsites/',
                '/onboarding/',
                'https://www.typo3.com/microsites/onboarding/',
            ],
            'Site base is prefixed to absolute path segment' => [
                'https://www.typo3.com/microsites/',
                'onboarding/',
                'https://www.typo3.com/microsites/onboarding/',
            ],
            'Language with domain and scheme, do not care about site base' => [
                'https://www.typo3.org',
                'https://www.typo3.it',
                'https://www.typo3.it',
            ],
            'Language with domain but no scheme, do not care about site base' => [
                'blabla.car',
                'www.typo3.fr',
                '//www.typo3.fr',
            ],
        ];
    }

    /**
     * This test shows a base from a site language is properly "inheriting" the base
     * from a site if it isn't absolute.
     */
    #[DataProvider('getBaseForSiteLanguageReturnsProperUriDataProvider')]
    #[Test]
    public function getBaseForSiteLanguageReturnsProperUri($siteBase, $languageBase, $expected): void
    {
        $subject = new Site('all-of-base', 13, [
            'base' => $siteBase,
            'languages' => [
                [
                    'languageId' => 0,
                    'base' => $languageBase,
                    'locale' => 'it_IT.UTF-8',
                ],
            ],
        ]);
        self::assertEquals(new Uri($expected), $subject->getLanguageById(0)->getBase());
    }

    #[Test]
    public function getErrorHandlerThrowsExceptionOnInvalidErrorHandler(): void
    {
        $this->expectException(InvalidPageErrorHandlerException::class);
        $this->expectExceptionCode(1527432330);
        $this->expectExceptionMessage('The configured error handler "' . Random::class . '" for status code 404 must implement the PageErrorHandlerInterface.');
        $subject = new Site('aint-misbehaving', 13, [
            'languages' => [],
            'errorHandling' => [
                [
                    'errorCode' => 404,
                    'errorHandler' => 'PHP',
                    'errorPhpClassFQCN' => Random::class,
                ],
            ],
        ]);
        $subject->getErrorHandler(404);
    }

    #[Test]
    public function getErrorHandlerThrowsExceptionWhenNoErrorHandlerIsConfigured(): void
    {
        $this->expectException(PageErrorHandlerNotConfiguredException::class);
        $this->expectExceptionCode(1522495914);
        $this->expectExceptionMessage('No error handler given for the status code "404".');
        $subject = new Site('aint-misbehaving', 13, ['languages' => []]);
        $subject->getErrorHandler(404);
    }

    #[Test]
    public function getErrorHandlerThrowsExceptionWhenNoErrorHandlerForStatusCodeIsConfigured(): void
    {
        $this->expectException(PageErrorHandlerNotConfiguredException::class);
        $this->expectExceptionCode(1522495914);
        $this->expectExceptionMessage('No error handler given for the status code "404".');
        $subject = new Site('aint-misbehaving', 13, [
            'languages' => [],
            'errorHandling' => [
                [
                    'errorCode' => 403,
                    'errorHandler' => 'Does it really matter?',
                ],
            ],
        ]);
        $subject->getErrorHandler(404);
    }
}
