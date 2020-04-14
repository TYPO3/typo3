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

namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Utility;

use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class GeneralUtilityTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @test
     * @dataProvider idnaEncodeDataProvider
     * @param $actual
     * @param $expected
     */
    public function idnaEncodeConvertsUnicodeCharsToASCIIString($actual, $expected)
    {
        $result = GeneralUtility::idnaEncode($actual);
        self::assertSame($expected, $result);
    }

    /**
     * Data provider for method idnaEncode in GeneralUtility class.
     * IDNA converter has to convert special chars (UTF-8) to ASCII compatible chars.
     *
     * @returns array
     */
    public function idnaEncodeDataProvider()
    {
        return [
            'empty string' => [
                '',
                ''
            ],
            'null value' => [
                null,
                ''
            ],
            'string with ascii chars' => [
                'example',
                'example'
            ],
            'domain (1) with utf8 chars' => [
                'dömäin.example',
                'xn--dmin-moa0i.example'
            ],
            'domain (2) with utf8 chars' => [
                'äaaa.example',
                'xn--aaa-pla.example'
            ],
            'domain (3) with utf8 chars' => [
                'déjà.vu.example',
                'xn--dj-kia8a.vu.example'
            ],
            'domain (4) with utf8 chars' => [
                'foo.âbcdéf.example',
                'foo.xn--bcdf-9na9b.example'
            ],
            'domain with utf8 char (german umlaut)' => [
                'exömple.com',
                'xn--exmple-xxa.com'
            ],
            'email with utf8 char (german umlaut)' => [
                'joe.doe@dömäin.de',
                'joe.doe@xn--dmin-moa0i.de'
            ]
        ];
    }

    /**
     * @return array
     */
    public function deniedFilesWithoutDenyPatternDataProvider(): array
    {
        return [
            'Nul character in file' => ['image' . "\0" . '.gif'],
            'Nul character in file with .php' => ['image.php' . "\0" . '.gif'],
            'Nul character and UTF-8 in file' => ['Ссылка' . "\0" . '.gif'],
            'Nul character and Latin-1 in file' => ['ÉÐØ' . "\0" . '.gif'],
        ];
    }

    /**
     * Tests whether verifyFilenameAgainstDenyPattern detects files with nul character without file deny pattern.
     *
     * @param string $deniedFile
     * @test
     * @dataProvider deniedFilesWithoutDenyPatternDataProvider
     */
    public function verifyNulCharacterFilesAgainstPatternWithoutFileDenyPattern(string $deniedFile)
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] = '';
        self::assertFalse(GeneralUtility::verifyFilenameAgainstDenyPattern($deniedFile));
    }

    /**
     * @return array
     */
    public function deniedFilesWithDefaultDenyPatternDataProvider(): array
    {
        $data = [
            'Nul character in file' => ['image' . "\0", '.gif'],
            'Nul character in file with .php' => ['image.php' . "\0", '.gif'],
            'Nul character and UTF-8 in file' => ['Ссылка' . "\0", '.gif'],
            'Nul character and Latin-1 in file' => ['ÉÐØ' . "\0", '.gif'],
            'Lower umlaut .php file' => ['üWithFile', '.php'],
            'Upper umlaut .php file' => ['fileWithÜ', '.php'],
            'invalid UTF-8-sequence' => ["\xc0" . 'file', '.php'],
            'Could be overlong NUL in some UTF-8 implementations, invalid in RFC3629' => ["\xc0\x80" . 'file', '.php'],
            'Regular .php file' => ['file' , '.php'],
            'Regular .php3 file' => ['file', '.php3'],
            'Regular .php5 file' => ['file', '.php5'],
            'Regular .php7 file' => ['file', '.php7'],
            'Regular .phpsh file' => ['file', '.phpsh'],
            'Regular .phtml file' => ['file', '.phtml'],
            'Regular .pht file' => ['file', '.pht'],
            'Regular .phar file' => ['file', '.phar'],
            'Regular .shtml file' => ['file', '.shtml'],
            'Regular .cgi file' => ['file', '.cgi'],
            'Regular .pl file' => ['file', '.pl'],
            'Wrapped .php file ' => ['file', '.php.txt'],
            'Wrapped .php3 file' => ['file', '.php3.txt'],
            'Wrapped .php5 file' => ['file', '.php5.txt'],
            'Wrapped .php7 file' => ['file', '.php7.txt'],
            'Wrapped .phpsh file' => ['file', '.phpsh.txt'],
            'Wrapped .phtml file' => ['file', '.phtml.txt'],
            'Wrapped .pht file' => ['file', '.pht.txt'],
            'Wrapped .phar file' => ['file', '.phar.txt'],
            'Wrapped .shtml file' => ['file', '.shtml.txt'],
            'Wrapped .cgi file' => ['file', '.cgi.txt'],
            // allowed "Wrapped .pl file" in order to allow language specific files containing ".pl."
            '.htaccess file' => ['', '.htaccess'],
        ];

        // Mixing with regular utf-8
        $utf8Characters = 'Ссылка';
        foreach ($data as $key => $value) {
            if ($value[0] === '') {
                continue;
            }
            $data[$key . ' with UTF-8 characters prepended'] = [$utf8Characters . $value[0], $value[1]];
            $data[$key . ' with UTF-8 characters appended'] = [$value[0] . $utf8Characters, $value[1]];
        }

        // combine to single value
        $data = array_map(
            function (array $values): array {
                return [implode('', $values)];
            },
            $data
        );

        // Encoding with UTF-16
        foreach ($data as $key => $value) {
            $data[$key . ' encoded with UTF-16'] = [mb_convert_encoding($value[0], 'UTF-16')];
        }

        return $data;
    }

    /**
     * Tests whether verifyFilenameAgainstDenyPattern detects denied files.
     *
     * @param string $deniedFile
     * @test
     * @dataProvider deniedFilesWithDefaultDenyPatternDataProvider
     */
    public function verifyFilenameAgainstDenyPatternDetectsNotAllowedFiles($deniedFile)
    {
        self::assertFalse(GeneralUtility::verifyFilenameAgainstDenyPattern($deniedFile));
    }

    /**
     * @return array
     */
    public function allowedFilesDataProvider(): array
    {
        return [
            'Regular .gif file' => ['image.gif'],
            'Regular uppercase .gif file' => ['IMAGE.gif'],
            'UTF-8 .gif file' => ['Ссылка.gif'],
            'Lower umlaut .jpg file' => ['üWithFile.jpg'],
            'Upper umlaut .png file' => ['fileWithÜ.png'],
            'Latin-1 .gif file' => ['ÉÐØ.gif'],
            'Wrapped .pl file' => ['file.pl.txt'],
        ];
    }

    /**
     * Tests whether verifyFilenameAgainstDenyPattern accepts allowed files.
     *
     * @param string $allowedFile
     * @test
     * @dataProvider allowedFilesDataProvider
     */
    public function verifyFilenameAgainstDenyPatternAcceptAllowedFiles(string $allowedFile)
    {
        self::assertTrue(GeneralUtility::verifyFilenameAgainstDenyPattern($allowedFile));
    }

    public function splitHeaderLinesDataProvider(): array
    {
        return [
            'multi-line headers' => [
                ['Content-Type' => 'multipart/form-data; boundary=something', 'Content-Language' => 'de-DE, en-CA'],
                ['Content-Type' => 'multipart/form-data; boundary=something', 'Content-Language' => 'de-DE, en-CA'],
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

        $requestFactory->request(Argument::any(), Argument::any(), ['headers' => $expectedHeaders])->shouldHaveBeenCalled();
    }

    /**
     * Data provider for IPv6Hex2BinCorrect
     *
     * @return array Data sets
     */
    public static function IPv6Hex2BinDataProviderCorrect()
    {
        return [
            'empty 1' => ['::', str_pad('', 16, "\x00")],
            'empty 2, already normalized' => ['0000:0000:0000:0000:0000:0000:0000:0000', str_pad('', 16, "\x00")],
            'already normalized' => ['0102:0304:0000:0000:0000:0000:0506:0078', "\x01\x02\x03\x04" . str_pad('', 8, "\x00") . "\x05\x06\x00\x78"],
            'expansion in middle 1' => ['1::2', "\x00\x01" . str_pad('', 12, "\x00") . "\x00\x02"],
            'expansion in middle 2' => ['beef::fefa', "\xbe\xef" . str_pad('', 12, "\x00") . "\xfe\xfa"],
        ];
    }

    /**
     * @test
     * @dataProvider IPv6Hex2BinDataProviderCorrect
     */
    public function IPv6Hex2BinCorrectlyConvertsAddresses($hex, $binary)
    {
        self::assertTrue(GeneralUtility::IPv6Hex2Bin($hex) === $binary);
    }

    /**
     * Data provider for IPv6Bin2HexCorrect
     *
     * @return array Data sets
     */
    public static function IPv6Bin2HexDataProviderCorrect()
    {
        return [
            'empty' => [str_pad('', 16, "\x00"), '::'],
            'non-empty front' => ["\x01" . str_pad('', 15, "\x00"), '100::'],
            'non-empty back' => [str_pad('', 15, "\x00") . "\x01", '::1'],
            'normalized' => ["\x01\x02\x03\x04" . str_pad('', 8, "\x00") . "\x05\x06\x00\x78", '102:304::506:78'],
            'expansion in middle 1' => ["\x00\x01" . str_pad('', 12, "\x00") . "\x00\x02", '1::2'],
            'expansion in middle 2' => ["\xbe\xef" . str_pad('', 12, "\x00") . "\xfe\xfa", 'beef::fefa'],
        ];
    }

    /**
     * @test
     * @dataProvider IPv6Bin2HexDataProviderCorrect
     */
    public function IPv6Bin2HexCorrectlyConvertsAddresses($binary, $hex)
    {
        self::assertEquals(GeneralUtility::IPv6Bin2Hex($binary), $hex);
    }

    /**
     * Data provider for normalizeIPv6ReturnsCorrectlyNormalizedFormat
     *
     * @return array Data sets
     */
    public static function normalizeCompressIPv6DataProviderCorrect()
    {
        return [
            'empty' => ['::', '0000:0000:0000:0000:0000:0000:0000:0000'],
            'localhost' => ['::1', '0000:0000:0000:0000:0000:0000:0000:0001'],
            'expansion in middle 1' => ['1::2', '0001:0000:0000:0000:0000:0000:0000:0002'],
            'expansion in middle 2' => ['1:2::3', '0001:0002:0000:0000:0000:0000:0000:0003'],
            'expansion in middle 3' => ['1::2:3', '0001:0000:0000:0000:0000:0000:0002:0003'],
            'expansion in middle 4' => ['1:2::3:4:5', '0001:0002:0000:0000:0000:0003:0004:0005']
        ];
    }

    /**
     * @test
     * @dataProvider normalizeCompressIPv6DataProviderCorrect
     */
    public function compressIPv6CorrectlyCompressesAddresses($compressed, $normalized)
    {
        self::assertEquals($compressed, GeneralUtility::compressIPv6($normalized));
    }

    /**
     * @test
     */
    public function compressIPv6CorrectlyCompressesAddressWithSomeAddressOnRightSide()
    {
        if (strtolower(PHP_OS) === 'darwin') {
            self::markTestSkipped('This test does not work on OSX / Darwin OS.');
        }
        self::assertEquals('::f0f', GeneralUtility::compressIPv6('0000:0000:0000:0000:0000:0000:0000:0f0f'));
    }
}
