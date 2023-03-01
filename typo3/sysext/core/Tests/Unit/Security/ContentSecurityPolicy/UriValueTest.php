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

namespace TYPO3\CMS\Core\Tests\Unit\Security\ContentSecurityPolicy;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class UriValueTest extends UnitTestCase
{
    public static function uriIsParsedAndSerializedDataProvider(): \Generator
    {
        yield ['https://www.typo3.org/uri/path.html?key=value#fragment', 'https://www.typo3.org/uri/path.html?key=value'];
        yield ['//www.typo3.org/uri/path.html?key=value#fragment', '//www.typo3.org/uri/path.html?key=value'];
        yield ['https://www.typo3.org#fragment', 'https://www.typo3.org'];
        yield ['//www.typo3.org#fragment', '//www.typo3.org'];
        yield ['https://*.typo3.org#fragment', 'https://*.typo3.org'];
        yield ['//*.typo3.org#fragment', '//*.typo3.org'];
        yield ['www.typo3.org#fragment', 'www.typo3.org'];
        yield ['*.typo3.org#fragment', '*.typo3.org'];

        yield ['https://www.typo3.org/uri/path.html?key=value'];
        yield ['https://www.typo3.org'];
        yield ['https://*.typo3.org'];
        yield ['//www.typo3.org/uri/path.html?key=value'];
        yield ['//www.typo3.org'];
        yield ['www.typo3.org'];
        yield ['*.typo3.org'];

        // expected behavior, falls back to upstream parser´
        // (since e.g. query-param is given, which is not expected here in the scope of CSP with `UriValue`)
        yield ['www.typo3.org?key=value', '/www.typo3.org?key=value'];
        yield ['*.typo3.org?key=value', '/%2A.typo3.org?key=value'];
    }

    /**
     * @test
     * @dataProvider uriIsParsedAndSerializedDataProvider
     */
    public function uriIsParsedAndSerialized(string $value, string $expectation = null): void
    {
        $uri = new UriValue($value);
        self::assertSame($expectation ?? $value, (string)$uri);
    }
}
