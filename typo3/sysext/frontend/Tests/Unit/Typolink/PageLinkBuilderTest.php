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

namespace TYPO3\CMS\Frontend\Tests\Unit\Typolink;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\PageLinkBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PageLinkBuilderTest extends UnitTestCase
{
    public static function getQueryArgumentsExcludesParametersDataProvider(): \Generator
    {
        $enc = self::rawUrlEncodeSquareBracketsInUrl(...);
        yield 'nested exclude from untrusted args' => [
            $enc('&key1=value1&key2=value2&key3[key31]=value31&key3[key32][key321]=value321&key3[key32][key322]=value322'),
            'untrusted',
            [
                'exclude' => implode(',', ['key1', 'key3[key31]', 'key3[key32][key321]']),
            ],
            $enc('&key2=value2&key3[key32][key322]=value322'),
        ];
        yield 'URL encoded value' => [
            '&param=1&param%25=2&param%2525=3',
            'untrusted',
            [
                // internally: URL-decoded representation
                'exclude' => 'param,param%,param%25',
            ],
            '',
        ];
    }

    #[DataProvider('getQueryArgumentsExcludesParametersDataProvider')]
    #[Test]
    public function getQueryArgumentsExcludesParameters(string $queryString, string $queryInformation, array $configuration, string $expectedResult): void
    {
        parse_str($queryString, $queryParameters);
        $request = new ServerRequest('https://example.com');
        $request = $request->withQueryParams($queryParameters);
        $request = $request->withAttribute('routing', new PageArguments(1, '', $queryParameters, [], []));
        $cObj = new ContentObjectRenderer();
        $cObj->setRequest($request);
        $subject = $this->getAccessibleMock(PageLinkBuilder::class, null, [], '', false);
        $subject->_set('contentObjectRenderer', $cObj);
        $actualResult = $subject->_call('getQueryArguments', $queryInformation, $configuration);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * Encodes square brackets in URL for a better readability in these tests.
     */
    private static function rawUrlEncodeSquareBracketsInUrl(string $string): string
    {
        return str_replace(['[', ']'], ['%5B', '%5D'], $string);
    }
}
