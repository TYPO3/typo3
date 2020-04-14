<?php

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

namespace TYPO3\CMS\Core\Tests\Unit\Http;

use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RedirectResponseTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getHeadersReturnsLocationUrlSetByConstructorArgument()
    {
        $subject = new RedirectResponse('theRedirectUrl');
        $expected = [
            'location' => [
                0 => 'theRedirectUrl',
            ]
        ];
        self::assertSame($expected, $subject->getHeaders());
    }

    /**
     * @test
     */
    public function getHeaderReturnsLocationUrlSetByConstructorArgument()
    {
        $subject = new RedirectResponse('theRedirectUrl');
        $expected = [
            0 => 'theRedirectUrl',
        ];
        self::assertSame($expected, $subject->getHeader('location'));
    }

    /**
     * @test
     */
    public function getHeadersReturnsHeaderSetByConstructorArgument()
    {
        $input = [
            'camelCasedHeaderName' => 'aHeaderValue',
            'lowercasedheadername' => 'anotherHeaderValue',
        ];
        $expected = [
            'camelCasedHeaderName' => [
                0 => 'aHeaderValue',
            ],
            'lowercasedheadername' => [
                0 => 'anotherHeaderValue',
            ],
            'location' => [
                0 => 'url'
            ],
        ];
        $subject = new RedirectResponse('url', 302, $input);
        self::assertSame($expected, $subject->getHeaders());
    }

    /**
     * @test
     */
    public function getHeaderReturnsHeaderSetByConstructorArgument()
    {
        $input = [
            'lowercasedheadername' => 'anotherHeaderValue',
        ];
        $expected = [
            0 => 'anotherHeaderValue',
        ];
        $subject = new RedirectResponse('url', 302, $input);
        self::assertSame($expected, $subject->getHeader('lowercasedheadername'));
    }

    /**
     * @test
     */
    public function getHeaderReturnsHeaderSetByConstructorArgumentLowerCased()
    {
        $input = [
            'camelCasedHeaderName' => 'aHeaderValue',
        ];
        $expected = [
            0 => 'aHeaderValue',
        ];
        $subject = new RedirectResponse('url', 302, $input);
        self::assertSame($expected, $subject->getHeader('camelCasedHeaderName'));
    }
}
