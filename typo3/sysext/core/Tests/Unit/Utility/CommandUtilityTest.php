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

namespace TYPO3\CMS\Core\Tests\Unit\Utility;

use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class CommandUtilityTest extends UnitTestCase
{
    /**
     * Data provider for getConfiguredApps
     *
     * @return array
     */
    public function getConfiguredAppsDataProvider()
    {
        $defaultExpected = [
            'perl' => [
                'app' => 'perl',
                'path' => '/usr/bin/',
                'valid' => true
            ],
            'unzip' => [
                'app' => 'unzip',
                'path' => '/usr/local/bin/',
                'valid' => true
            ],
        ];
        return [
            'returns empty array for empty string' => [
                '',
                []
            ],
            'separated by comma' => [
                'perl=/usr/bin/perl,unzip=/usr/local/bin/unzip',
                $defaultExpected
            ],
            'separated by new line' => [
                'perl=/usr/bin/perl ' . LF . ' unzip=/usr/local/bin/unzip',
                $defaultExpected
            ],
            'separated by new line with spaces' => [
                'perl = /usr/bin/perl ' . LF . ' unzip = /usr/local/bin/unzip',
                $defaultExpected
            ],
            'separated by new line with spaces and empty rows' => [
                LF . 'perl = /usr/bin/perl ' . LF . LF . ' unzip = /usr/local/bin/unzip' . LF,
                $defaultExpected
            ],
            'separated by char(10)' => [
                'perl=/usr/bin/perl\'.chr(10).\'unzip=/usr/local/bin/unzip',
                $defaultExpected
            ],
            'separated by LF as string' => [
                'perl=/usr/bin/perl\' . LF . \'unzip=/usr/local/bin/unzip',
                $defaultExpected
            ]
        ];
    }

    /**
     * @dataProvider getConfiguredAppsDataProvider
     * @param array $globalsBinSetup
     * @param array $expected
     * @test
     */
    public function getConfiguredApps($globalsBinSetup, $expected)
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['binSetup'] = $globalsBinSetup;
        $commandUtilityMock = $this->getAccessibleMock(CommandUtility::class, ['dummy']);
        $result = $commandUtilityMock->_call('getConfiguredApps');
        self::assertSame($expected, $result);
    }

    /**
     * Data provider unQuoteFilenameUnquotesCorrectly
     */
    public function unQuoteFilenameUnquotesCorrectlyDataProvider(): array
    {
        return [
            // Some theoretical tests first
            [
                '',
                []
            ],
            [
                'aa bb "cc" "dd"',
                ['aa', 'bb', '"cc"', '"dd"']
            ],
            [
                'aa bb "cc dd"',
                ['aa', 'bb', '"cc dd"']
            ],
            [
                '\'aa bb\' "cc dd"',
                ['\'aa bb\'', '"cc dd"']
            ],
            [
                '\'aa bb\' cc "dd"',
                ['\'aa bb\'', 'cc', '"dd"']
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
                ]
            ]
        ];
    }

    /**
     * Tests if the commands are exploded and unquoted correctly
     *
     * @dataProvider unQuoteFilenameUnquotesCorrectlyDataProvider
     * @test
     * @param string $source
     * @param array $expectedQuoted
     */
    public function unQuoteFilenameUnquotesCorrectly(string $source, array $expectedQuoted): void
    {
        $commandUtilityMock = $this->getAccessibleMock(CommandUtility::class, ['dummy']);
        $actualQuoted = $commandUtilityMock->_call('unQuoteFilenames', $source);
        self::assertEquals($expectedQuoted, $actualQuoted);
    }
}
