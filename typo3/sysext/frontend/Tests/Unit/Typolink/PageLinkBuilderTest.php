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

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\PageLinkBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
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
        $cObj = self::createStub(ContentObjectRenderer::class);
        $cObj->method('getRequest')->willReturn($request);
        $subject = $this->getAccessibleMock(PageLinkBuilder::class, null, [], '', false);
        $subject->_set('contentObjectRenderer', $cObj);
        $actualResult = $subject->_call('getQueryArguments', $queryInformation, $configuration);
        self::assertEquals($expectedResult, $actualResult);
    }

    public static function calculateQueryParametersWithQueryParametersOptionDataProvider(): \Generator
    {
        yield 'queryParameters alone' => [
            ['queryParameters' => ['foo' => 'bar', 'baz' => 'qux']],
            [],
            ['foo' => 'bar', 'baz' => 'qux'],
        ];
        yield 'queryParameters with nested array' => [
            ['queryParameters' => ['tx_news' => ['id' => '42', 'category' => '5']]],
            [],
            ['tx_news' => ['id' => '42', 'category' => '5']],
        ];
        yield 'queryParameters overrides additionalParams' => [
            [
                'additionalParams' => '&foo=old&keep=yes',
                'queryParameters' => ['foo' => 'new'],
            ],
            [],
            ['foo' => 'new', 'keep' => 'yes'],
        ];
        yield 'queryParameters deep merge with additionalParams' => [
            [
                'additionalParams' => '&tx_ext[action]=list&tx_ext[format]=html',
                'queryParameters' => ['tx_ext' => ['action' => 'show']],
            ],
            [],
            ['tx_ext' => ['action' => 'show', 'format' => 'html']],
        ];
        yield 'empty queryParameters does not affect additionalParams' => [
            [
                'additionalParams' => '&foo=bar',
                'queryParameters' => [],
            ],
            [],
            ['foo' => 'bar'],
        ];
        yield 'queryParameters with linkDetails parameters' => [
            [
                'queryParameters' => ['foo' => 'bar'],
            ],
            ['parameters' => 'from=link'],
            ['from' => 'link', 'foo' => 'bar'],
        ];
    }

    #[DataProvider('calculateQueryParametersWithQueryParametersOptionDataProvider')]
    #[Test]
    public function calculateQueryParametersWithQueryParametersOption(array $conf, array $linkDetails, array $expectedResult): void
    {
        $conf['additionalParams'] = $conf['additionalParams'] ?? '';
        $conf['queryParameters'] = (array)($conf['queryParameters'] ?? []);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('routing', new PageArguments(1, '', [], [], []));
        $cObj = self::createStub(ContentObjectRenderer::class);
        $cObj->method('getRequest')->willReturn($request);
        $cObj->method('stdWrapValue')->willReturnCallback(
            static fn(string $key, array $config) => $config[$key] ?? ''
        );
        $subject = $this->getAccessibleMock(PageLinkBuilder::class, ['calculateGlobalQueryParameters'], [], '', false);
        $subject->method('calculateGlobalQueryParameters')->willReturn('');
        $subject->_set('contentObjectRenderer', $cObj);
        $actualResult = $subject->_call('calculateQueryParameters', $conf, $linkDetails);
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
