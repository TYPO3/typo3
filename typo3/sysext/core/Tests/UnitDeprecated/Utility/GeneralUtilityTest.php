<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Utility;

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

use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the \TYPO3\CMS\Core\Utility\ClientUtility class.
 */
class GeneralUtilityTest extends UnitTestCase
{
    public function splitHeaderLinesDataProvider(): array
    {
        return [
            'one-line, single header' => [
                ['Content-Security-Policy:default-src \'self\'; img-src https://*; child-src \'none\';'],
                ['Content-Security-Policy' => 'default-src \'self\'; img-src https://*; child-src \'none\';']
            ],
            'one-line, multiple headers' => [
                [
                    'Content-Security-Policy:default-src \'self\'; img-src https://*; child-src \'none\';',
                    'Content-Security-Policy-Report-Only:default-src https:; report-uri /csp-violation-report-endpoint/'
                ],
                [
                    'Content-Security-Policy' => 'default-src \'self\'; img-src https://*; child-src \'none\';',
                    'Content-Security-Policy-Report-Only' => 'default-src https:; report-uri /csp-violation-report-endpoint/'
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider splitHeaderLinesDataProvider
     * @param array $headers
     * @param array $expectedHeaders
     */
    public function splitHeaderLines(array $headers, array $expectedHeaders): void
    {
        $stream = $this->prophesize(StreamInterface::class);
        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->willReturn($stream);
        $requestFactory = $this->prophesize(RequestFactory::class);
        $requestFactory->request(Argument::cetera())->willReturn($response);

        GeneralUtility::addInstance(RequestFactory::class, $requestFactory->reveal());
        GeneralUtility::getUrl('http://example.com', 0, $headers);

        $requestFactory->request(Argument::any(), Argument::any(), ['headers' => $expectedHeaders])
            ->shouldHaveBeenCalled();
    }

    ///////////////////////////////
    // Tests concerning unQuoteFilenames
    ///////////////////////////////

    /**
     * Data provider for unQuoteFilenamesUnquotesFileNames
     */
    public function unQuoteFilenamesUnquotesFileNamesDataProvider()
    {
        return [
            // Some theoretical tests first
            [
                '',
                [],
                []
            ],
            [
                'aa bb "cc" "dd"',
                ['aa', 'bb', '"cc"', '"dd"'],
                ['aa', 'bb', 'cc', 'dd']
            ],
            [
                'aa bb "cc dd"',
                ['aa', 'bb', '"cc dd"'],
                ['aa', 'bb', 'cc dd']
            ],
            [
                '\'aa bb\' "cc dd"',
                ['\'aa bb\'', '"cc dd"'],
                ['aa bb', 'cc dd']
            ],
            [
                '\'aa bb\' cc "dd"',
                ['\'aa bb\'', 'cc', '"dd"'],
                ['aa bb', 'cc', 'dd']
            ],
            // Now test against some real world examples
            [
                '/opt/local/bin/gm.exe convert +profile \'*\' -geometry 170x136!  -negate "C:/Users/Someuser.Domain/Documents/Htdocs/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]" "C:/Users/Someuser.Domain/Documents/Htdocs/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif"',
                [
                    '/opt/local/bin/gm.exe',
                    'convert',
                    '+profile',
                    '\'*\'',
                    '-geometry',
                    '170x136!',
                    '-negate',
                    '"C:/Users/Someuser.Domain/Documents/Htdocs/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]"',
                    '"C:/Users/Someuser.Domain/Documents/Htdocs/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif"'
                ],
                [
                    '/opt/local/bin/gm.exe',
                    'convert',
                    '+profile',
                    '*',
                    '-geometry',
                    '170x136!',
                    '-negate',
                    'C:/Users/Someuser.Domain/Documents/Htdocs/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]',
                    'C:/Users/Someuser.Domain/Documents/Htdocs/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif'
                ]
            ],
            [
                'C:/opt/local/bin/gm.exe convert +profile \'*\' -geometry 170x136!  -negate "C:/Program Files/Apache2/htdocs/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]" "C:/Program Files/Apache2/htdocs/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif"',
                [
                    'C:/opt/local/bin/gm.exe',
                    'convert',
                    '+profile',
                    '\'*\'',
                    '-geometry',
                    '170x136!',
                    '-negate',
                    '"C:/Program Files/Apache2/htdocs/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]"',
                    '"C:/Program Files/Apache2/htdocs/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif"'
                ],
                [
                    'C:/opt/local/bin/gm.exe',
                    'convert',
                    '+profile',
                    '*',
                    '-geometry',
                    '170x136!',
                    '-negate',
                    'C:/Program Files/Apache2/htdocs/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]',
                    'C:/Program Files/Apache2/htdocs/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif'
                ]
            ],
            [
                '/usr/bin/gm convert +profile \'*\' -geometry 170x136!  -negate "/Shared Items/Data/Projects/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]" "/Shared Items/Data/Projects/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif"',
                [
                    '/usr/bin/gm',
                    'convert',
                    '+profile',
                    '\'*\'',
                    '-geometry',
                    '170x136!',
                    '-negate',
                    '"/Shared Items/Data/Projects/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]"',
                    '"/Shared Items/Data/Projects/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif"'
                ],
                [
                    '/usr/bin/gm',
                    'convert',
                    '+profile',
                    '*',
                    '-geometry',
                    '170x136!',
                    '-negate',
                    '/Shared Items/Data/Projects/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]',
                    '/Shared Items/Data/Projects/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif'
                ]
            ],
            [
                '/usr/bin/gm convert +profile \'*\' -geometry 170x136!  -negate "/Network/Servers/server01.internal/Projects/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]" "/Network/Servers/server01.internal/Projects/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif"',
                [
                    '/usr/bin/gm',
                    'convert',
                    '+profile',
                    '\'*\'',
                    '-geometry',
                    '170x136!',
                    '-negate',
                    '"/Network/Servers/server01.internal/Projects/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]"',
                    '"/Network/Servers/server01.internal/Projects/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif"'
                ],
                [
                    '/usr/bin/gm',
                    'convert',
                    '+profile',
                    '*',
                    '-geometry',
                    '170x136!',
                    '-negate',
                    '/Network/Servers/server01.internal/Projects/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]',
                    '/Network/Servers/server01.internal/Projects/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif'
                ]
            ],
            [
                '/usr/bin/gm convert +profile \'*\' -geometry 170x136!  -negate \'/Network/Servers/server01.internal/Projects/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]\' \'/Network/Servers/server01.internal/Projects/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif\'',
                [
                    '/usr/bin/gm',
                    'convert',
                    '+profile',
                    '\'*\'',
                    '-geometry',
                    '170x136!',
                    '-negate',
                    '\'/Network/Servers/server01.internal/Projects/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]\'',
                    '\'/Network/Servers/server01.internal/Projects/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif\''
                ],
                [
                    '/usr/bin/gm',
                    'convert',
                    '+profile',
                    '*',
                    '-geometry',
                    '170x136!',
                    '-negate',
                    '/Network/Servers/server01.internal/Projects/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif[0]',
                    '/Network/Servers/server01.internal/Projects/typo3temp/var/transient/61401f5c16c63d58e1d92e8a2449f2fe_maskNT.gif'
                ]
            ]
        ];
    }

    /**
     * Tests if the commands are exploded and unquoted correctly
     *
     * @dataProvider unQuoteFilenamesUnquotesFileNamesDataProvider
     * @test
     */
    public function unQuoteFilenamesUnquotesFileNames($source, $expectedQuoted, $expectedUnquoted)
    {
        $actualQuoted = GeneralUtility::unQuoteFilenames($source);
        $actualUnquoted = GeneralUtility::unQuoteFilenames($source, true);
        $this->assertEquals($expectedQuoted, $actualQuoted, 'The exploded command does not match the expected');
        $this->assertEquals($expectedUnquoted, $actualUnquoted, 'The exploded and unquoted command does not match the expected');
    }

    /**
     * Data provider for explodeUrl2ArrayTransformsParameterStringToNestedArray
     *
     * @return array
     */
    public function explodeUrl2ArrayDataProvider()
    {
        return [
            'Empty input' => [[], ''],
            'String parameters' => [['foo' => ['one' => '√', 'two' => 2]], '&foo[one]=%E2%88%9A&foo[two]=2'],
            'Nested array parameters' => [['foo' => [['one' => '√', 'two' => 2]]], '&foo[0][one]=%E2%88%9A&foo[0][two]=2'],
            'Keep blank parameters' => [['foo' => ['one' => '√', '']], '&foo[one]=%E2%88%9A&foo[0]=']
        ];
    }

    /**
     * @test
     * @dataProvider explodeUrl2ArrayDataProvider
     */
    public function explodeUrl2ArrayTransformsParameterStringToNestedArray($expected, $input)
    {
        $this->assertEquals($expected, GeneralUtility::explodeUrl2Array($input, true));
    }
}
