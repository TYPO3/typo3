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

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Tests\Unit\Utility\AccessibleProxies\ExtensionManagementUtilityAccessibleProxy;
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\ExtendedSingletonClassFixture;
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\GeneralUtilityFilesystemFixture;
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\GeneralUtilityFixture;
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\GeneralUtilityMakeInstanceInjectLoggerFixture;
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\OriginalClassFixture;
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\OtherReplacementClassFixture;
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\ReplacementClassFixture;
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\SingletonClassFixture;
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\TwoParametersConstructorFixture;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class GeneralUtilityTest extends UnitTestCase
{
    public const NO_FIX_PERMISSIONS_ON_WINDOWS = 'fixPermissions() not available on Windows (method does nothing)';

    use ProphecyTrait;

    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var bool Restore Environment after tests
     */
    protected $backupEnvironment = true;

    protected ?PackageManager $backupPackageManager;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->backupPackageManager = ExtensionManagementUtilityAccessibleProxy::getPackageManager();
    }

    /**
     * Tear down
     */
    protected function tearDown(): void
    {
        GeneralUtility::flushInternalRuntimeCaches();
        if ($this->backupPackageManager) {
            ExtensionManagementUtilityAccessibleProxy::setPackageManager($this->backupPackageManager);
        }
        parent::tearDown();
    }

    /**
     * Helper method to test for an existing internet connection.
     * Some tests are skipped if there is no working uplink.
     *
     * @return bool $isConnected
     */
    public function isConnected(): bool
    {
        $isConnected = false;
        $connected = @fsockopen('typo3.org', 80);
        if ($connected) {
            $isConnected = true;
            fclose($connected);
        }
        return $isConnected;
    }

    /**
     * Helper method to create a random directory in the virtual file system
     * and return the path.
     *
     * @param string $prefix
     * @return string
     */
    protected function getVirtualTestDir(string $prefix = 'root_'): string
    {
        $root = vfsStream::setup();
        $path = $root->url() . '/typo3temp/var/tests/' . StringUtility::getUniqueId($prefix);
        GeneralUtility::mkdir_deep($path);
        return $path;
    }

    ///////////////////////////
    // Tests concerning _GP
    ///////////////////////////
    /**
     * @test
     * @dataProvider gpDataProvider
     */
    public function canRetrieveValueWithGP($key, $get, $post, $expected): void
    {
        $_GET = $get;
        $_POST = $post;
        self::assertSame($expected, GeneralUtility::_GP($key));
    }

    /**
     * Data provider for canRetrieveValueWithGP.
     * All test values also check whether slashes are stripped properly.
     *
     * @return array
     */
    public function gpDataProvider(): array
    {
        return [
            'No key parameter' => [null, [], [], null],
            'Key not found' => ['cake', [], [], null],
            'Value only in GET' => ['cake', ['cake' => 'li\\e'], [], 'li\\e'],
            'Value only in POST' => ['cake', [], ['cake' => 'l\\ie'], 'l\\ie'],
            'Value from POST preferred over GET' => ['cake', ['cake' => 'is a'], ['cake' => '\\lie'], '\\lie'],
            'Value can be an array' => [
                'cake',
                ['cake' => ['is a' => 'l\\ie']],
                [],
                ['is a' => 'l\\ie'],
            ],
            'Empty-ish key' => ['0', ['0' => 'zero'], ['0' => 'zero'], null],
        ];
    }

    ///////////////////////////
    // Tests concerning _GPmerged
    ///////////////////////////
    /**
     * @test
     * @dataProvider gpMergedDataProvider
     */
    public function gpMergedWillMergeArraysFromGetAndPost($get, $post, $expected): void
    {
        $_POST = $post;
        $_GET = $get;
        self::assertEquals($expected, GeneralUtility::_GPmerged('cake'));
    }

    /**
     * Data provider for gpMergedWillMergeArraysFromGetAndPost
     *
     * @return array
     */
    public function gpMergedDataProvider(): array
    {
        $fullDataArray = ['cake' => ['a' => 'is a', 'b' => 'lie']];
        $postPartData = ['cake' => ['b' => 'lie']];
        $getPartData = ['cake' => ['a' => 'is a']];
        $getPartDataModified = ['cake' => ['a' => 'is not a']];
        return [
            'Key doesn\' exist' => [['foo'], ['bar'], []],
            'No POST data' => [$fullDataArray, [], $fullDataArray['cake']],
            'No GET data' => [[], $fullDataArray, $fullDataArray['cake']],
            'POST and GET are merged' => [$getPartData, $postPartData, $fullDataArray['cake']],
            'POST is preferred over GET' => [$getPartDataModified, $fullDataArray, $fullDataArray['cake']],
        ];
    }

    ///////////////////////////////
    // Tests concerning _GET / _POST
    ///////////////////////////////
    /**
     * Data provider for canRetrieveGlobalInputsThroughGet
     * and canRetrieveGlobalInputsThroughPost
     *
     * @return array
     */
    public function getAndPostDataProvider(): array
    {
        return [
            'Requested input data doesn\'t exist' => ['cake', [], null],
            'No key will return entire input data' => [null, ['cake' => 'l\\ie'], ['cake' => 'l\\ie']],
            'Can retrieve specific input' => ['cake', ['cake' => 'l\\ie', 'foo'], 'l\\ie'],
            'Can retrieve nested input data' => ['cake', ['cake' => ['is a' => 'l\\ie']], ['is a' => 'l\\ie']],
        ];
    }

    /**
     * @test
     * @dataProvider getAndPostDataProvider
     */
    public function canRetrieveGlobalInputsThroughGet($key, $get, $expected): void
    {
        $_GET = $get;
        self::assertSame($expected, GeneralUtility::_GET($key));
    }

    /**
     * @test
     * @dataProvider getAndPostDataProvider
     */
    public function canRetrieveGlobalInputsThroughPost($key, $post, $expected): void
    {
        $_POST = $post;
        self::assertSame($expected, GeneralUtility::_POST($key));
    }

    ///////////////////////////
    // Tests concerning cmpIPv4
    ///////////////////////////
    /**
     * Data provider for cmpIPv4ReturnsTrueForMatchingAddress
     *
     * @return array Data sets
     */
    public static function cmpIPv4DataProviderMatching(): array
    {
        return [
            'host with full IP address' => ['127.0.0.1', '127.0.0.1'],
            'host with two wildcards at the end' => ['127.0.0.1', '127.0.*.*'],
            'host with wildcard at third octet' => ['127.0.0.1', '127.0.*.1'],
            'host with wildcard at second octet' => ['127.0.0.1', '127.*.0.1'],
            '/8 subnet' => ['127.0.0.1', '127.1.1.1/8'],
            '/32 subnet (match only name)' => ['127.0.0.1', '127.0.0.1/32'],
            '/30 subnet' => ['10.10.3.1', '10.10.3.3/30'],
            'host with wildcard in list with IPv4/IPv6 addresses' => ['192.168.1.1', '127.0.0.1, 1234:5678::/126, 192.168.*'],
            'host in list with IPv4/IPv6 addresses' => ['192.168.1.1', '::1, 1234:5678::/126, 192.168.1.1'],
        ];
    }

    /**
     * @test
     * @dataProvider cmpIPv4DataProviderMatching
     */
    public function cmpIPv4ReturnsTrueForMatchingAddress($ip, $list): void
    {
        self::assertTrue(GeneralUtility::cmpIPv4($ip, $list));
    }

    /**
     * Data provider for cmpIPv4ReturnsFalseForNotMatchingAddress
     *
     * @return array Data sets
     */
    public static function cmpIPv4DataProviderNotMatching(): array
    {
        return [
            'single host' => ['127.0.0.1', '127.0.0.2'],
            'single host with wildcard' => ['127.0.0.1', '127.*.1.1'],
            'single host with /32 subnet mask' => ['127.0.0.1', '127.0.0.2/32'],
            '/31 subnet' => ['127.0.0.1', '127.0.0.2/31'],
            'list with IPv4/IPv6 addresses' => ['127.0.0.1', '10.0.2.3, 192.168.1.1, ::1'],
            'list with only IPv6 addresses' => ['10.20.30.40', '::1, 1234:5678::/127'],
        ];
    }

    /**
     * @test
     * @dataProvider cmpIPv4DataProviderNotMatching
     */
    public function cmpIPv4ReturnsFalseForNotMatchingAddress($ip, $list): void
    {
        self::assertFalse(GeneralUtility::cmpIPv4($ip, $list));
    }

    ///////////////////////////
    // Tests concerning cmpIPv6
    ///////////////////////////
    /**
     * Data provider for cmpIPv6ReturnsTrueForMatchingAddress
     *
     * @return array Data sets
     */
    public static function cmpIPv6DataProviderMatching(): array
    {
        return [
            'empty address' => ['::', '::'],
            'empty with netmask in list' => ['::', '::/0'],
            'empty with netmask 0 and host-bits set in list' => ['::', '::123/0'],
            'localhost' => ['::1', '::1'],
            'localhost with leading zero blocks' => ['::1', '0:0::1'],
            'host with submask /128' => ['::1', '0:0::1/128'],
            '/16 subnet' => ['1234::1', '1234:5678::/16'],
            '/126 subnet' => ['1234:5678::3', '1234:5678::/126'],
            '/126 subnet with host-bits in list set' => ['1234:5678::3', '1234:5678::2/126'],
            'list with IPv4/IPv6 addresses' => ['1234:5678::3', '::1, 127.0.0.1, 1234:5678::/126, 192.168.1.1'],
        ];
    }

    /**
     * @test
     * @dataProvider cmpIPv6DataProviderMatching
     */
    public function cmpIPv6ReturnsTrueForMatchingAddress($ip, $list): void
    {
        self::assertTrue(GeneralUtility::cmpIPv6($ip, $list));
    }

    /**
     * Data provider for cmpIPv6ReturnsFalseForNotMatchingAddress
     *
     * @return array Data sets
     */
    public static function cmpIPv6DataProviderNotMatching(): array
    {
        return [
            'empty against localhost' => ['::', '::1'],
            'empty against localhost with /128 netmask' => ['::', '::1/128'],
            'localhost against different host' => ['::1', '::2'],
            'localhost against host with prior bits set' => ['::1', '::1:1'],
            'host against different /17 subnet' => ['1234::1', '1234:f678::/17'],
            'host against different /127 subnet' => ['1234:5678::3', '1234:5678::/127'],
            'host against IPv4 address list' => ['1234:5678::3', '127.0.0.1, 192.168.1.1'],
            'host against mixed list with IPv6 host in different subnet' => ['1234:5678::3', '::1, 1234:5678::/127'],
        ];
    }

    /**
     * @test
     * @dataProvider cmpIPv6DataProviderNotMatching
     */
    public function cmpIPv6ReturnsFalseForNotMatchingAddress($ip, $list): void
    {
        self::assertFalse(GeneralUtility::cmpIPv6($ip, $list));
    }

    /////////////////////////////////
    // Tests concerning normalizeIPv6
    /////////////////////////////////
    /**
     * Data provider for normalizeIPv6ReturnsCorrectlyNormalizedFormat
     *
     * @return array Data sets
     */
    public static function normalizeCompressIPv6DataProviderCorrect(): array
    {
        return [
            'empty' => ['::', '0000:0000:0000:0000:0000:0000:0000:0000'],
            'localhost' => ['::1', '0000:0000:0000:0000:0000:0000:0000:0001'],
            'expansion in middle 1' => ['1::2', '0001:0000:0000:0000:0000:0000:0000:0002'],
            'expansion in middle 2' => ['1:2::3', '0001:0002:0000:0000:0000:0000:0000:0003'],
            'expansion in middle 3' => ['1::2:3', '0001:0000:0000:0000:0000:0000:0002:0003'],
            'expansion in middle 4' => ['1:2::3:4:5', '0001:0002:0000:0000:0000:0003:0004:0005'],
        ];
    }

    /**
     * @test
     * @dataProvider normalizeCompressIPv6DataProviderCorrect
     */
    public function normalizeIPv6CorrectlyNormalizesAddresses($compressed, $normalized): void
    {
        self::assertEquals($normalized, GeneralUtility::normalizeIPv6($compressed));
    }

    ///////////////////////////////
    // Tests concerning validIP
    ///////////////////////////////
    /**
     * Data provider for checkValidIpReturnsTrueForValidIp
     *
     * @return array Data sets
     */
    public static function validIpDataProvider(): array
    {
        return [
            '0.0.0.0' => ['0.0.0.0'],
            'private IPv4 class C' => ['192.168.0.1'],
            'private IPv4 class A' => ['10.0.13.1'],
            'private IPv6' => ['fe80::daa2:5eff:fe8b:7dfb'],
        ];
    }

    /**
     * @test
     * @dataProvider validIpDataProvider
     */
    public function validIpReturnsTrueForValidIp($ip): void
    {
        self::assertTrue(GeneralUtility::validIP($ip));
    }

    /**
     * Data provider for checkValidIpReturnsFalseForInvalidIp
     *
     * @return array Data sets
     */
    public static function invalidIpDataProvider(): array
    {
        return [
            'null' => [null],
            'zero' => [0],
            'string' => ['test'],
            'string empty' => [''],
            'string NULL' => ['NULL'],
            'out of bounds IPv4' => ['300.300.300.300'],
            'dotted decimal notation with only two dots' => ['127.0.1'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidIpDataProvider
     */
    public function validIpReturnsFalseForInvalidIp($ip): void
    {
        self::assertFalse(GeneralUtility::validIP($ip));
    }

    ///////////////////////////////
    // Tests concerning cmpFQDN
    ///////////////////////////////
    /**
     * Data provider for cmpFqdnReturnsTrue
     *
     * @return array Data sets
     */
    public static function cmpFqdnValidDataProvider(): array
    {
        return [
            'localhost should usually resolve, IPv4' => ['127.0.0.1', '*'],
            'localhost should usually resolve, IPv6' => ['::1', '*'],
            // other testcases with resolving not possible since it would
            // require a working IPv4/IPv6-connectivity
            'aaa.bbb.ccc.ddd.eee, full' => ['aaa.bbb.ccc.ddd.eee', 'aaa.bbb.ccc.ddd.eee'],
            'aaa.bbb.ccc.ddd.eee, wildcard first' => ['aaa.bbb.ccc.ddd.eee', '*.ccc.ddd.eee'],
            'aaa.bbb.ccc.ddd.eee, wildcard last' => ['aaa.bbb.ccc.ddd.eee', 'aaa.bbb.ccc.*'],
            'aaa.bbb.ccc.ddd.eee, wildcard middle' => ['aaa.bbb.ccc.ddd.eee', 'aaa.*.eee'],
            'list-matches, 1' => ['aaa.bbb.ccc.ddd.eee', 'xxx, yyy, zzz, aaa.*.eee'],
            'list-matches, 2' => ['aaa.bbb.ccc.ddd.eee', '127:0:0:1,,aaa.*.eee,::1'],
        ];
    }

    /**
     * @test
     * @dataProvider cmpFqdnValidDataProvider
     */
    public function cmpFqdnReturnsTrue($baseHost, $list): void
    {
        self::assertTrue(GeneralUtility::cmpFQDN($baseHost, $list));
    }

    /**
     * Data provider for cmpFqdnReturnsFalse
     *
     * @return array Data sets
     */
    public static function cmpFqdnInvalidDataProvider(): array
    {
        return [
            'num-parts of hostname to check can only be less or equal than hostname, 1' => ['aaa.bbb.ccc.ddd.eee', 'aaa.bbb.ccc.ddd.eee.fff'],
            'num-parts of hostname to check can only be less or equal than hostname, 2' => ['aaa.bbb.ccc.ddd.eee', 'aaa.*.bbb.ccc.ddd.eee'],
        ];
    }

    /**
     * @test
     * @dataProvider cmpFqdnInvalidDataProvider
     */
    public function cmpFqdnReturnsFalse($baseHost, $list): void
    {
        self::assertFalse(GeneralUtility::cmpFQDN($baseHost, $list));
    }

    ///////////////////////////////
    // Tests concerning inList
    ///////////////////////////////
    /**
     * @test
     * @param string $haystack
     * @dataProvider inListForItemContainedReturnsTrueDataProvider
     */
    public function inListForItemContainedReturnsTrue(string $haystack): void
    {
        self::assertTrue(GeneralUtility::inList($haystack, 'findme'));
    }

    /**
     * Data provider for inListForItemContainedReturnsTrue.
     *
     * @return array
     */
    public function inListForItemContainedReturnsTrueDataProvider(): array
    {
        return [
            'Element as second element of four items' => ['one,findme,three,four'],
            'Element at beginning of list' => ['findme,one,two'],
            'Element at end of list' => ['one,two,findme'],
            'One item list' => ['findme'],
        ];
    }

    /**
     * @test
     * @param string $haystack
     * @dataProvider inListForItemNotContainedReturnsFalseDataProvider
     */
    public function inListForItemNotContainedReturnsFalse(string $haystack): void
    {
        self::assertFalse(GeneralUtility::inList($haystack, 'findme'));
    }

    /**
     * Data provider for inListForItemNotContainedReturnsFalse.
     *
     * @return array
     */
    public function inListForItemNotContainedReturnsFalseDataProvider(): array
    {
        return [
            'Four item list' => ['one,two,three,four'],
            'One item list' => ['one'],
            'Empty list' => [''],
        ];
    }

    ///////////////////////////////
    // Tests concerning expandList
    ///////////////////////////////
    /**
     * @test
     * @param string $list
     * @param string $expectation
     * @dataProvider expandListExpandsIntegerRangesDataProvider
     */
    public function expandListExpandsIntegerRanges(string $list, string $expectation): void
    {
        self::assertSame($expectation, GeneralUtility::expandList($list));
    }

    /**
     * Data provider for expandListExpandsIntegerRangesDataProvider
     *
     * @return array
     */
    public function expandListExpandsIntegerRangesDataProvider(): array
    {
        return [
            'Expand for the same number' => ['1,2-2,7', '1,2,7'],
            'Small range expand with parameters reversed ignores reversed items' => ['1,5-3,7', '1,7'],
            'Small range expand' => ['1,3-5,7', '1,3,4,5,7'],
            'Expand at beginning' => ['3-5,1,7', '3,4,5,1,7'],
            'Expand at end' => ['1,7,3-5', '1,7,3,4,5'],
            'Multiple small range expands' => ['1,3-5,7-10,12', '1,3,4,5,7,8,9,10,12'],
            'One item list' => ['1-5', '1,2,3,4,5'],
            'Nothing to expand' => ['1,2,3,4', '1,2,3,4'],
            'Empty list' => ['', ''],
        ];
    }

    /**
     * @test
     */
    public function expandListExpandsForTwoThousandElementsExpandsOnlyToThousandElementsMaximum(): void
    {
        $list = GeneralUtility::expandList('1-2000');
        self::assertCount(1000, explode(',', $list));
    }

    ///////////////////////////////
    // Tests concerning formatSize
    ///////////////////////////////
    /**
     * @test
     * @dataProvider formatSizeDataProvider
     */
    public function formatSizeTranslatesBytesToHigherOrderRepresentation($size, $labels, $base, $expected): void
    {
        self::assertEquals($expected, GeneralUtility::formatSize($size, $labels, $base));
    }

    /**
     * Data provider for formatSizeTranslatesBytesToHigherOrderRepresentation
     *
     * @return array
     */
    public function formatSizeDataProvider(): array
    {
        return [
            'IEC Bytes stay bytes (min)' => [1, '', 0, '1 '],
            'IEC Bytes stay bytes (max)' => [921, '', 0, '921 '],
            'IEC Kilobytes are used (min)' => [922, '', 0, '0.90 Ki'],
            'IEC Kilobytes are used (max)' => [943718, '', 0, '922 Ki'],
            'IEC Megabytes are used (min)' => [943719, '', 0, '0.90 Mi'],
            'IEC Megabytes are used (max)' => [966367641, '', 0, '922 Mi'],
            'IEC Gigabytes are used (min)' => [966367642, '', 0, '0.90 Gi'],
            'IEC Gigabytes are used (max)' => [989560464998, '', 0, '922 Gi'],
            'IEC Decimal is omitted for large kilobytes' => [31080, '', 0, '30 Ki'],
            'IEC Decimal is omitted for large megabytes' => [31458000, '', 0, '30 Mi'],
            'IEC Decimal is omitted for large gigabytes' => [32212254720, '', 0, '30 Gi'],
            'SI Bytes stay bytes (min)' => [1, 'si', 0, '1 '],
            'SI Bytes stay bytes (max)' => [899, 'si', 0, '899 '],
            'SI Kilobytes are used (min)' => [901, 'si', 0, '0.90 k'],
            'SI Kilobytes are used (max)' => [900000, 'si', 0, '900 k'],
            'SI Megabytes are used (min)' => [900001, 'si', 0, '0.90 M'],
            'SI Megabytes are used (max)' => [900000000, 'si', 0, '900 M'],
            'SI Gigabytes are used (min)' => [900000001, 'si', 0, '0.90 G'],
            'SI Gigabytes are used (max)' => [900000000000, 'si', 0, '900 G'],
            'SI Decimal is omitted for large kilobytes' => [30000, 'si', 0, '30 k'],
            'SI Decimal is omitted for large megabytes' => [30000000, 'si', 0, '30 M'],
            'SI Decimal is omitted for large gigabytes' => [30000000000, 'si', 0, '30 G'],
            'Label for bytes can be exchanged (binary unit)' => [1, ' Foo|||', 0, '1 Foo'],
            'Label for kilobytes can be exchanged (binary unit)' => [1024, '| Foo||', 0, '1.00 Foo'],
            'Label for megabytes can be exchanged (binary unit)' => [1048576, '|| Foo|', 0, '1.00 Foo'],
            'Label for gigabytes can be exchanged (binary unit)' => [1073741824, '||| Foo', 0, '1.00 Foo'],
            'Label for bytes can be exchanged (decimal unit)' => [1, ' Foo|||', 1000, '1 Foo'],
            'Label for kilobytes can be exchanged (decimal unit)' => [1000, '| Foo||', 1000, '1.00 Foo'],
            'Label for megabytes can be exchanged (decimal unit)' => [1000000, '|| Foo|', 1000, '1.00 Foo'],
            'Label for gigabytes can be exchanged (decimal unit)' => [1000000000, '||| Foo', 1000, '1.00 Foo'],
            'IEC Base is ignored' => [1024, 'iec', 1000, '1.00 Ki'],
            'SI Base is ignored' => [1000, 'si', 1024, '1.00 k'],
            'Use binary base for unexpected base' => [2048, '| Bar||', 512, '2.00 Bar'],
        ];
    }

    ///////////////////////////////
    // Tests concerning splitCalc
    ///////////////////////////////
    /**
     * Data provider for splitCalc
     *
     * @return array expected values, arithmetic expression
     */
    public function splitCalcDataProvider(): array
    {
        return [
            'empty string returns empty array' => [
                [],
                '',
            ],
            'number without operator returns array with plus and number' => [
                [['+', '42']],
                '42',
            ],
            'two numbers with asterisk return first number with plus and second number with asterisk' => [
                [['+', '42'], ['*', '31']],
                '42 * 31',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider splitCalcDataProvider
     */
    public function splitCalcCorrectlySplitsExpression(array $expected, string $expression): void
    {
        self::assertSame($expected, GeneralUtility::splitCalc($expression, '+-*/'));
    }

    ///////////////////////////////
    // Tests concerning htmlspecialchars_decode
    ///////////////////////////////
    /**
     * @test
     */
    public function htmlspecialcharsDecodeReturnsDecodedString(): void
    {
        $string = '<typo3 version="6.0">&nbsp;</typo3>';
        $encoded = htmlspecialchars($string);
        $decoded = htmlspecialchars_decode($encoded);
        self::assertEquals($string, $decoded);
    }

    //////////////////////////////////
    // Tests concerning validEmail
    //////////////////////////////////
    /**
     * Data provider for valid validEmail's
     *
     * @return array Valid email addresses
     */
    public function validEmailValidDataProvider(): array
    {
        return [
            'short mail address' => ['a@b.c'],
            'simple mail address' => ['test@example.com'],
            'uppercase characters' => ['QWERTYUIOPASDFGHJKLZXCVBNM@QWERTYUIOPASDFGHJKLZXCVBNM.NET'],
            'equal sign in local part' => ['test=mail@example.com'],
            'dash in local part' => ['test-mail@example.com'],
            'plus in local part' => ['test+mail@example.com'],
            'question mark in local part' => ['test?mail@example.com'],
            'slash in local part' => ['foo/bar@example.com'],
            'hash in local part' => ['foo#bar@example.com'],
            'dot in local part' => ['firstname.lastname@employee.2something.com'],
            'dash as local part' => ['-@foo.com'],
            'umlauts in domain part' => ['foo@äöüfoo.com'],
            'number as top level domain' => ['foo@bar.123'],
            'top level domain only' => ['test@localhost'],
            'umlauts in local part' => ['äöüfoo@bar.com'],
            'quoted @ char' => ['"Abc@def"@example.com'],
        ];
    }

    /**
     * @test
     * @dataProvider validEmailValidDataProvider
     */
    public function validEmailReturnsTrueForValidMailAddress($address): void
    {
        self::assertTrue(GeneralUtility::validEmail($address));
    }

    /**
     * Data provider for invalid validEmail's
     *
     * @return array Invalid email addresses
     */
    public function validEmailInvalidDataProvider(): array
    {
        return [
            'empty string' => [''],
            'empty array' => [[]],
            'integer' => [42],
            'float' => [42.23],
            'array' => [['foo']],
            'object' => [new \stdClass()],
            '@ sign only' => ['@'],
            'string longer than 320 characters' => [str_repeat('0123456789', 33)],
            'duplicate @' => ['test@@example.com'],
            'duplicate @ combined with further special characters in local part' => ['test!.!@#$%^&*@example.com'],
            'opening parenthesis in local part' => ['foo(bar@example.com'],
            'closing parenthesis in local part' => ['foo)bar@example.com'],
            'opening square bracket in local part' => ['foo[bar@example.com'],
            'closing square bracket as local part' => [']@example.com'],
            'dash as second level domain' => ['foo@-.com'],
            'domain part starting with dash' => ['foo@-foo.com'],
            'domain part ending with dash' => ['foo@foo-.com'],
            'dot at beginning of domain part' => ['test@.com'],
            'local part ends with dot' => ['e.x.a.m.p.l.e.@example.com'],
            'trailing whitespace' => ['test@example.com '],
            'trailing carriage return' => ['test@example.com' . CR],
            'trailing linefeed' => ['test@example.com' . LF],
            'trailing carriage return linefeed' => ['test@example.com' . CRLF],
            'trailing tab' => ['test@example.com' . "\t"],
            'prohibited input characters' => ['“mailto:test@example.com”'],
            'escaped @ char' => ['abc\@def@example.com'], // known bug, see https://github.com/egulias/EmailValidator/issues/181
        ];
    }

    /**
     * @test
     * @dataProvider validEmailInvalidDataProvider
     */
    public function validEmailReturnsFalseForInvalidMailAddress($address): void
    {
        self::assertFalse(GeneralUtility::validEmail($address));
    }

    //////////////////////////////////
    // Tests concerning intExplode
    //////////////////////////////////
    /**
     * @test
     */
    public function intExplodeConvertsStringsToInteger(): void
    {
        $testString = '1,foo,2';
        $expectedArray = [1, 0, 2];
        $actualArray = GeneralUtility::intExplode(',', $testString);
        self::assertEquals($expectedArray, $actualArray);
    }

    //////////////////////////////////
    // Tests concerning implodeArrayForUrl / explodeUrl2Array
    //////////////////////////////////
    /**
     * Data provider for implodeArrayForUrlBuildsValidParameterString
     *
     * @return array
     */
    public function implodeArrayForUrlDataProvider(): array
    {
        $valueArray = ['one' => '√', 'two' => 2];
        return [
            'Empty input' => ['foo', [], ''],
            'String parameters' => ['foo', $valueArray, '&foo[one]=%E2%88%9A&foo[two]=2'],
            'Nested array parameters' => ['foo', [$valueArray], '&foo[0][one]=%E2%88%9A&foo[0][two]=2'],
            'Keep blank parameters' => ['foo', ['one' => '√', ''], '&foo[one]=%E2%88%9A&foo[0]='],
        ];
    }

    /**
     * @test
     * @dataProvider implodeArrayForUrlDataProvider
     */
    public function implodeArrayForUrlBuildsValidParameterString($name, $input, $expected): void
    {
        self::assertSame($expected, GeneralUtility::implodeArrayForUrl($name, $input));
    }

    /**
     * @test
     */
    public function implodeArrayForUrlCanSkipEmptyParameters(): void
    {
        $input = ['one' => '√', ''];
        $expected = '&foo[one]=%E2%88%9A';
        self::assertSame($expected, GeneralUtility::implodeArrayForUrl('foo', $input, '', true));
    }

    /**
     * @test
     */
    public function implodeArrayForUrlCanUrlEncodeKeyNames(): void
    {
        $input = ['one' => '√', ''];
        $expected = '&foo%5Bone%5D=%E2%88%9A&foo%5B0%5D=';
        self::assertSame($expected, GeneralUtility::implodeArrayForUrl('foo', $input, '', false, true));
    }

    /**
     * @test
     * @dataProvider explodeUrl2ArrayDataProvider
     */
    public function explodeUrl2ArrayTransformsParameterStringToFlatArray($input, $expected): void
    {
        self::assertEquals($expected, GeneralUtility::explodeUrl2Array($input));
    }

    /**
     * Data provider for explodeUrl2ArrayTransformsParameterStringToFlatArray
     *
     * @return array
     */
    public function explodeUrl2ArrayDataProvider(): array
    {
        return [
            'Empty string' => ['', []],
            'Simple parameter string' => ['&one=%E2%88%9A&two=2', ['one' => '√', 'two' => 2]],
            'Nested parameter string' => ['&foo[one]=%E2%88%9A&two=2', ['foo[one]' => '√', 'two' => 2]],
        ];
    }

    //////////////////////////////////
    // Tests concerning revExplode
    //////////////////////////////////

    /**
     * @return array
     */
    public function revExplodeDataProvider(): array
    {
        return [
            'limit 0 should return unexploded string' => [
                ':',
                'my:words:here',
                0,
                ['my:words:here'],
            ],
            'limit 1 should return unexploded string' => [
                ':',
                'my:words:here',
                1,
                ['my:words:here'],
            ],
            'limit 2 should return two pieces' => [
                ':',
                'my:words:here',
                2,
                ['my:words', 'here'],
            ],
            'limit 3 should return unexploded string' => [
                ':',
                'my:words:here',
                3,
                ['my', 'words', 'here'],
            ],
            'limit 0 should return unexploded string if no delimiter is contained' => [
                ':',
                'mywordshere',
                0,
                ['mywordshere'],
            ],
            'limit 1 should return unexploded string if no delimiter is contained' => [
                ':',
                'mywordshere',
                1,
                ['mywordshere'],
            ],
            'limit 2 should return unexploded string if no delimiter is contained' => [
                ':',
                'mywordshere',
                2,
                ['mywordshere'],
            ],
            'limit 3 should return unexploded string if no delimiter is contained' => [
                ':',
                'mywordshere',
                3,
                ['mywordshere'],
            ],
            'multi character delimiter is handled properly with limit 2' => [
                '[]',
                'a[b][c][d]',
                2,
                ['a[b][c', 'd]'],
            ],
            'multi character delimiter is handled properly with limit 3' => [
                '[]',
                'a[b][c][d]',
                3,
                ['a[b', 'c', 'd]'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider revExplodeDataProvider
     */
    public function revExplodeCorrectlyExplodesStringForGivenPartsCount($delimiter, $testString, $count, $expectedArray): void
    {
        $actualArray = GeneralUtility::revExplode($delimiter, $testString, $count);
        self::assertEquals($expectedArray, $actualArray);
    }

    /**
     * @test
     */
    public function revExplodeRespectsLimitThreeWhenExploding(): void
    {
        $testString = 'even:more:of:my:words:here';
        $expectedArray = ['even:more:of:my', 'words', 'here'];
        $actualArray = GeneralUtility::revExplode(':', $testString, 3);
        self::assertEquals($expectedArray, $actualArray);
    }

    //////////////////////////////////
    // Tests concerning trimExplode
    //////////////////////////////////
    /**
     * @test
     * @dataProvider trimExplodeReturnsCorrectResultDataProvider
     *
     * @param string $delimiter
     * @param string $testString
     * @param bool $removeEmpty
     * @param int $limit
     * @param array $expectedResult
     */
    public function trimExplodeReturnsCorrectResult(string $delimiter, string $testString, bool $removeEmpty, int $limit, array $expectedResult): void
    {
        self::assertSame($expectedResult, GeneralUtility::trimExplode($delimiter, $testString, $removeEmpty, $limit));
    }

    /**
     * @return array
     */
    public function trimExplodeReturnsCorrectResultDataProvider(): array
    {
        return [
            'spaces at element start and end' => [
                ',',
                ' a , b , c ,d ,,  e,f,',
                false,
                0,
                ['a', 'b', 'c', 'd', '', 'e', 'f', ''],
            ],
            'removes newline' => [
                ',',
                ' a , b , ' . LF . ' ,d ,,  e,f,',
                true,
                0,
                ['a', 'b', 'd', 'e', 'f'],
            ],
            'removes empty elements' => [
                ',',
                'a , b , c , ,d ,, ,e,f,',
                true,
                0,
                ['a', 'b', 'c', 'd', 'e', 'f'],
            ],
            'keeps remaining results with empty items after reaching limit with positive parameter' => [
                ',',
                ' a , b , c , , d,, ,e ',
                false,
                3,
                ['a', 'b', 'c , , d,, ,e'],
            ],
            'keeps remaining results without empty items after reaching limit with positive parameter' => [
                ',',
                ' a , b , c , , d,, ,e ',
                true,
                3,
                ['a', 'b', 'c , d,e'],
            ],
            'keeps remaining results with empty items after reaching limit with negative parameter' => [
                ',',
                ' a , b , c , d, ,e, f , , ',
                false,
                -3,
                ['a', 'b', 'c', 'd', '', 'e'],
            ],
            'keeps remaining results without empty items after reaching limit with negative parameter' => [
                ',',
                ' a , b , c , d, ,e, f , , ',
                true,
                -3,
                ['a', 'b', 'c'],
            ],
            'returns exact results without reaching limit with positive parameter' => [
                ',',
                ' a , b , , c , , , ',
                true,
                4,
                ['a', 'b', 'c'],
            ],
            'keeps zero as string' => [
                ',',
                'a , b , c , ,d ,, ,e,f, 0 ,',
                true,
                0,
                ['a', 'b', 'c', 'd', 'e', 'f', '0'],
            ],
            'keeps whitespace inside elements' => [
                ',',
                'a , b , c , ,d ,, ,e,f, g h ,',
                true,
                0,
                ['a', 'b', 'c', 'd', 'e', 'f', 'g h'],
            ],
            'can use internal regex delimiter as explode delimiter' => [
                '/',
                'a / b / c / /d // /e/f/ g h /',
                true,
                0,
                ['a', 'b', 'c', 'd', 'e', 'f', 'g h'],
            ],
            'can use whitespaces as delimiter' => [
                ' ',
                '* * * * *',
                true,
                0,
                ['*', '*', '*', '*', '*'],
            ],
            'can use words as delimiter' => [
                'All',
                'HelloAllTogether',
                true,
                0,
                ['Hello', 'Together'],
            ],
            'can use word with appended and prepended spaces as delimiter' => [
                ' all   ',
                'Hello all   together',
                true,
                0,
                ['Hello', 'together'],
            ],
            'can use word with appended and prepended spaces as delimiter and do not remove empty' => [
                ' all   ',
                'Hello all   together     all      there all       all   are  all    none',
                false,
                0,
                ['Hello', 'together', 'there', '', 'are', 'none'],
            ],
            'can use word with appended and prepended spaces as delimiter, do not remove empty and limit' => [
                ' all   ',
                'Hello all   together     all      there all       all   are  all    none',
                false,
                5,
                ['Hello', 'together', 'there', '', 'are  all    none'],
            ],
            'can use word with appended and prepended spaces as delimiter, do not remove empty, limit and multiple delimiter in last' => [
                ' all   ',
                'Hello all   together     all      there all       all   are  all    none',
                false,
                4,
                ['Hello', 'together', 'there', 'all   are  all    none'],
            ],
            'can use word with appended and prepended spaces as delimiter, remove empty and limit' => [
                ' all   ',
                'Hello all   together     all      there all       all   are  all    none',
                true,
                4,
                ['Hello', 'together', 'there', 'are  all    none'],
            ],
            'can use word with appended and prepended spaces as delimiter, remove empty and limit and multiple delimiter in last' => [
                ' all   ',
                'Hello all   together     all      there all       all   are  all    none',
                true,
                5,
                ['Hello', 'together', 'there', 'are' , 'none'],
            ],
            'can use words as delimiter and do not remove empty' => [
                'all  there',
                'Helloall  theretogether  all  there    all  there   are   all  there     none',
                false,
                0,
                ['Hello', 'together', '', 'are', 'none'],
            ],
            'can use words as delimiter, do not remove empty and limit' => [
                'all  there',
                'Helloall  theretogether  all  there    all  there    are   all  there     none',
                false,
                4,
                ['Hello', 'together', '', 'are   all  there     none'],
            ],
            'can use words as delimiter, do not remove empty, limit and multiple delimiter in last' => [
                'all  there',
                'Helloall  theretogether  all  there    all  there    are   all  there     none',
                false,
                3,
                ['Hello', 'together', 'all  there    are   all  there     none'],
            ],
            'can use words as delimiter, remove empty' => [
                'all  there',
                'Helloall  theretogether  all  there    all  there    are   all  there     none',
                true,
                0,
                ['Hello', 'together', 'are', 'none'],
            ],
            'can use words as delimiter, remove empty and limit' => [
                'all  there',
                'Helloall  theretogether  all  there    all  there    are   all  there     none',
                true,
                3,
                ['Hello', 'together', 'are   all  there     none'],
            ],
            'can use words as delimiter, remove empty and limit and multiple delimiter in last' => [
                'all  there',
                'Helloall  theretogether  all  there    all  there    are   all  there     none',
                true,
                4,
                ['Hello', 'together', 'are' , 'none'],
            ],
            'can use new line as delimiter' => [
                LF,
                "Hello\nall\ntogether",
                true,
                0,
                ['Hello', 'all', 'together'],
            ],
            'works with whitespace separator' => [
                "\t",
                " a  b \t c  \t  \t    d  \t  e     \t u j   \t s",
                false,
                0,
                ['a  b', 'c', '', 'd', 'e', 'u j', 's'],
            ],
            'works with whitespace separator and limit' => [
                "\t",
                " a  b \t c  \t  \t    d  \t  e     \t u j   \t s",
                false,
                4,
                ['a  b', 'c', '', "d  \t  e     \t u j   \t s"],
            ],
            'works with whitespace separator and remove empty' => [
                "\t",
                " a  b \t c  \t  \t    d  \t  e     \t u j   \t s",
                true,
                0,
                ['a  b', 'c', 'd', 'e', 'u j', 's'],
            ],
            'works with whitespace separator remove empty and limit' => [
                "\t",
                " a  b \t c  \t  \t    d  \t  e     \t u j   \t s",
                true,
                3,
                ['a  b', 'c', "d  \t  e     \t u j   \t s"],
            ],
        ];
    }

    //////////////////////////////////
    // Tests concerning getBytesFromSizeMeasurement
    //////////////////////////////////
    /**
     * Data provider for getBytesFromSizeMeasurement
     *
     * @return array expected value, input string
     */
    public function getBytesFromSizeMeasurementDataProvider(): array
    {
        return [
            '100 kilo Bytes' => ['102400', '100k'],
            '100 mega Bytes' => ['104857600', '100m'],
            '100 giga Bytes' => ['107374182400', '100g'],
        ];
    }

    /**
     * @test
     * @dataProvider getBytesFromSizeMeasurementDataProvider
     */
    public function getBytesFromSizeMeasurementCalculatesCorrectByteValue($expected, $byteString): void
    {
        self::assertEquals($expected, GeneralUtility::getBytesFromSizeMeasurement($byteString));
    }

    //////////////////////////////////
    // Tests concerning getIndpEnv
    //////////////////////////////////
    /**
     * @test
     */
    public function getIndpEnvTypo3SitePathReturnNonEmptyString(): void
    {
        self::assertTrue(strlen(GeneralUtility::getIndpEnv('TYPO3_SITE_PATH')) >= 1);
    }

    /**
     * @test
     * @requires OSFAMILY Linux|Darwin (path starts with a drive on Windows)
     */
    public function getIndpEnvTypo3SitePathReturnsStringStartingWithSlash(): void
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            false,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getBackendPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $result = GeneralUtility::getIndpEnv('TYPO3_SITE_PATH');
        self::assertEquals('/', $result[0]);
    }

    /**
     * @test
     * @requires OSFAMILY Windows
     */
    public function getIndpEnvTypo3SitePathReturnsStringStartingWithDrive(): void
    {
        $result = GeneralUtility::getIndpEnv('TYPO3_SITE_PATH');
        self::assertMatchesRegularExpression('/^[a-z]:\//i', $result);
    }

    /**
     * @test
     */
    public function getIndpEnvTypo3SitePathReturnsStringEndingWithSlash(): void
    {
        $result = GeneralUtility::getIndpEnv('TYPO3_SITE_PATH');
        self::assertEquals('/', $result[strlen($result) - 1]);
    }

    /**
     * @return array
     */
    public static function hostnameAndPortDataProvider(): array
    {
        return [
            'localhost ipv4 without port' => ['127.0.0.1', '127.0.0.1', ''],
            'localhost ipv4 with port' => ['127.0.0.1:81', '127.0.0.1', '81'],
            'localhost ipv6 without port' => ['[::1]', '[::1]', ''],
            'localhost ipv6 with port' => ['[::1]:81', '[::1]', '81'],
            'ipv6 without port' => ['[2001:DB8::1]', '[2001:DB8::1]', ''],
            'ipv6 with port' => ['[2001:DB8::1]:81', '[2001:DB8::1]', '81'],
            'hostname without port' => ['lolli.did.this', 'lolli.did.this', ''],
            'hostname with port' => ['lolli.did.this:42', 'lolli.did.this', '42'],
        ];
    }

    /**
     * @test
     * @dataProvider hostnameAndPortDataProvider
     */
    public function getIndpEnvTypo3HostOnlyParsesHostnamesAndIpAddresses($httpHost, $expectedIp): void
    {
        $_SERVER['HTTP_HOST'] = $httpHost;
        self::assertEquals($expectedIp, GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'));
    }

    /**
     * @test
     * @dataProvider hostnameAndPortDataProvider
     */
    public function getIndpEnvTypo3PortParsesHostnamesAndIpAddresses($httpHost, $dummy, $expectedPort): void
    {
        $_SERVER['HTTP_HOST'] = $httpHost;
        self::assertEquals($expectedPort, GeneralUtility::getIndpEnv('TYPO3_PORT'));
    }

    //////////////////////////////////
    // Tests concerning underscoredToUpperCamelCase
    //////////////////////////////////
    /**
     * Data provider for underscoredToUpperCamelCase
     *
     * @return array expected, input string
     */
    public function underscoredToUpperCamelCaseDataProvider(): array
    {
        return [
            'single word' => ['Blogexample', 'blogexample'],
            'multiple words' => ['BlogExample', 'blog_example'],
        ];
    }

    /**
     * @test
     * @dataProvider underscoredToUpperCamelCaseDataProvider
     */
    public function underscoredToUpperCamelCase($expected, $inputString): void
    {
        self::assertEquals($expected, GeneralUtility::underscoredToUpperCamelCase($inputString));
    }

    //////////////////////////////////
    // Tests concerning underscoredToLowerCamelCase
    //////////////////////////////////
    /**
     * Data provider for underscoredToLowerCamelCase
     *
     * @return array expected, input string
     */
    public function underscoredToLowerCamelCaseDataProvider(): array
    {
        return [
            'single word' => ['minimalvalue', 'minimalvalue'],
            'multiple words' => ['minimalValue', 'minimal_value'],
        ];
    }

    /**
     * @test
     * @dataProvider underscoredToLowerCamelCaseDataProvider
     */
    public function underscoredToLowerCamelCase($expected, $inputString): void
    {
        self::assertEquals($expected, GeneralUtility::underscoredToLowerCamelCase($inputString));
    }

    //////////////////////////////////
    // Tests concerning camelCaseToLowerCaseUnderscored
    //////////////////////////////////
    /**
     * Data provider for camelCaseToLowerCaseUnderscored
     *
     * @return array expected, input string
     */
    public function camelCaseToLowerCaseUnderscoredDataProvider(): array
    {
        return [
            'single word' => ['blogexample', 'blogexample'],
            'single word starting upper case' => ['blogexample', 'Blogexample'],
            'two words starting lower case' => ['minimal_value', 'minimalValue'],
            'two words starting upper case' => ['blog_example', 'BlogExample'],
        ];
    }

    /**
     * @test
     * @dataProvider camelCaseToLowerCaseUnderscoredDataProvider
     */
    public function camelCaseToLowerCaseUnderscored($expected, $inputString): void
    {
        self::assertEquals($expected, GeneralUtility::camelCaseToLowerCaseUnderscored($inputString));
    }

    //////////////////////////////////
    // Tests concerning isValidUrl
    //////////////////////////////////
    /**
     * Data provider for valid isValidUrl's
     *
     * @return array Valid resource
     */
    public function validUrlValidResourceDataProvider(): array
    {
        return [
            'http' => ['http://www.example.org/'],
            'http without trailing slash' => ['http://qwe'],
            'http directory with trailing slash' => ['http://www.example/img/dir/'],
            'http directory without trailing slash' => ['http://www.example/img/dir'],
            'http index.html' => ['http://example.com/index.html'],
            'http index.php' => ['http://www.example.com/index.php'],
            'http test.png' => ['http://www.example/img/test.png'],
            'http username password querystring and anchor' => ['https://user:pw@www.example.org:80/path?arg=value#fragment'],
            'file' => ['file:///tmp/test.c'],
            'file directory' => ['file://foo/bar'],
            'ftp directory' => ['ftp://ftp.example.com/tmp/'],
            'mailto' => ['mailto:foo@bar.com'],
            'news' => ['news:news.php.net'],
            'telnet' => ['telnet://192.0.2.16:80/'],
            'ldap' => ['ldap://[2001:db8::7]/c=GB?objectClass?one'],
            'http punycode domain name' => ['http://www.xn--bb-eka.at'],
            'http punicode subdomain' => ['http://xn--h-zfa.oebb.at'],
            'http domain-name umlauts' => ['http://www.öbb.at'],
            'http subdomain umlauts' => ['http://äh.oebb.at'],
        ];
    }

    /**
     * @test
     * @dataProvider validUrlValidResourceDataProvider
     */
    public function validURLReturnsTrueForValidResource($url): void
    {
        self::assertTrue(GeneralUtility::isValidUrl($url));
    }

    /**
     * Data provider for invalid isValidUrl's
     *
     * @return array Invalid resource
     */
    public function isValidUrlInvalidResourceDataProvider(): array
    {
        return [
            'http missing colon' => ['http//www.example/wrong/url/'],
            'http missing slash' => ['http:/www.example'],
            'hostname only' => ['www.example.org/'],
            'file missing protocol specification' => ['/tmp/test.c'],
            'slash only' => ['/'],
            'string http://' => ['http://'],
            'string http:/' => ['http:/'],
            'string http:' => ['http:'],
            'string http' => ['http'],
            'empty string' => [''],
            'string -1' => ['-1'],
            'string array()' => ['array()'],
            'random string' => ['qwe'],
            'http directory umlauts' => ['http://www.oebb.at/äöü/'],
            'prohibited input characters' => ['https://{$unresolved_constant}'],
        ];
    }

    /**
     * @test
     * @dataProvider isValidUrlInvalidResourceDataProvider
     */
    public function validURLReturnsFalseForInvalidResource($url): void
    {
        self::assertFalse(GeneralUtility::isValidUrl($url));
    }

    //////////////////////////////////
    // Tests concerning isOnCurrentHost
    //////////////////////////////////
    /**
     * @test
     */
    public function isOnCurrentHostReturnsTrueWithCurrentHost(): void
    {
        $testUrl = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
        self::assertTrue(GeneralUtility::isOnCurrentHost($testUrl));
    }

    /**
     * Data provider for invalid isOnCurrentHost's
     *
     * @return array Invalid Hosts
     */
    public function checkisOnCurrentHostInvalidHosts(): array
    {
        return [
            'empty string' => [''],
            'arbitrary string' => ['arbitrary string'],
            'localhost IP' => ['127.0.0.1'],
            'relative path' => ['./relpath/file.txt'],
            'absolute path' => ['/abspath/file.txt?arg=value'],
            'different host' => [GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . '.example.org'],
        ];
    }

    ////////////////////////////////////////
    // Tests concerning sanitizeLocalUrl
    ////////////////////////////////////////
    /**
     * Data provider for valid sanitizeLocalUrl paths
     *
     * @return array Valid url
     */
    public function sanitizeLocalUrlValidPathsDataProvider(): array
    {
        return [
            'alt_intro.php' => ['alt_intro.php'],
            'alt_intro.php?foo=1&bar=2' => ['alt_intro.php?foo=1&bar=2'],
            '../index.php' => ['../index.php'],
            '../typo3/alt_intro.php' => ['../typo3/alt_intro.php'],
            '../~userDirectory/index.php' => ['../~userDirectory/index.php'],
            '../typo3/index.php?var1=test-case&var2=~user' => ['../typo3/index.php?var1=test-case&var2=~user'],
            Environment::getPublicPath() . '/typo3/alt_intro.php' => [Environment::getPublicPath() . '/typo3/alt_intro.php'],
        ];
    }

    /**
     * @test
     * @param string $path
     * @dataProvider sanitizeLocalUrlValidPathsDataProvider
     */
    public function sanitizeLocalUrlAcceptsNotEncodedValidPaths(string $path): void
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            false,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getBackendPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        self::assertEquals($path, GeneralUtility::sanitizeLocalUrl($path));
    }

    /**
     * @test
     * @param string $path
     * @dataProvider sanitizeLocalUrlValidPathsDataProvider
     */
    public function sanitizeLocalUrlAcceptsEncodedValidPaths(string $path): void
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            false,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getBackendPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        self::assertEquals(rawurlencode($path), GeneralUtility::sanitizeLocalUrl(rawurlencode($path)));
    }

    /**
     * Data provider for valid sanitizeLocalUrl's
     *
     * @return array Valid url
     */
    public function sanitizeLocalUrlValidUrlsDataProvider(): array
    {
        return [
            '/cms/typo3/alt_intro.php' => [
                '/cms/typo3/alt_intro.php',
                'localhost',
                '/cms/',
            ],
            '/cms/index.php' => [
                '/cms/index.php',
                'localhost',
                '/cms/',
            ],
            'http://localhost/typo3/alt_intro.php' => [
                'http://localhost/typo3/alt_intro.php',
                'localhost',
                '',
            ],
            'http://localhost/cms/typo3/alt_intro.php' => [
                'http://localhost/cms/typo3/alt_intro.php',
                'localhost',
                '/cms/',
            ],
        ];
    }

    /**
     * @test
     * @param string $url
     * @param string $host
     * @param string $subDirectory
     * @dataProvider sanitizeLocalUrlValidUrlsDataProvider
     */
    public function sanitizeLocalUrlAcceptsNotEncodedValidUrls(string $url, string $host, string $subDirectory): void
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            false,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getBackendPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['SCRIPT_NAME'] = $subDirectory . 'typo3/index.php';
        self::assertEquals($url, GeneralUtility::sanitizeLocalUrl($url));
    }

    /**
     * @test
     * @param string $url
     * @param string $host
     * @param string $subDirectory
     * @dataProvider sanitizeLocalUrlValidUrlsDataProvider
     */
    public function sanitizeLocalUrlAcceptsEncodedValidUrls(string $url, string $host, string $subDirectory): void
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            false,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getBackendPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['SCRIPT_NAME'] = $subDirectory . 'typo3/index.php';
        self::assertEquals(rawurlencode($url), GeneralUtility::sanitizeLocalUrl(rawurlencode($url)));
    }

    /**
     * Data provider for invalid sanitizeLocalUrl's
     *
     * @return array Valid url
     */
    public function sanitizeLocalUrlInvalidDataProvider(): array
    {
        return [
            'empty string' => [''],
            'http domain' => ['http://www.google.de/'],
            'https domain' => ['https://www.google.de/'],
            'domain without schema' => ['//www.google.de/'],
            'XSS attempt' => ['" onmouseover="alert(123)"'],
            'invalid URL, UNC path' => ['\\\\foo\\bar\\'],
            'invalid URL, HTML break out attempt' => ['" >blabuubb'],
            'base64 encoded string' => ['data:%20text/html;base64,PHNjcmlwdD5hbGVydCgnWFNTJyk8L3NjcmlwdD4='],
        ];
    }

    /**
     * @param string $url
     * @test
     * @dataProvider sanitizeLocalUrlInvalidDataProvider
     */
    public function sanitizeLocalUrlDeniesPlainInvalidUrlsInBackendContext(string $url): void
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            false,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getBackendPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = 'typo3/index.php';
        self::assertEquals('', GeneralUtility::sanitizeLocalUrl($url));
    }

    /**
     * @param string $url
     * @test
     * @dataProvider sanitizeLocalUrlInvalidDataProvider
     */
    public function sanitizeLocalUrlDeniesPlainInvalidUrlsInFrontendContext(string $url): void
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            false,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getPublicPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = 'index.php';
        self::assertEquals('', GeneralUtility::sanitizeLocalUrl($url));
    }

    /**
     * @test
     * @dataProvider sanitizeLocalUrlInvalidDataProvider
     */
    public function sanitizeLocalUrlDeniesEncodedInvalidUrls($url): void
    {
        self::assertEquals('', GeneralUtility::sanitizeLocalUrl(rawurlencode($url)));
    }

    ////////////////////////////////////////
    // Tests concerning unlink_tempfile
    ////////////////////////////////////////

    /**
     * @test
     */
    public function unlink_tempfileRemovesValidFileInTypo3temp(): void
    {
        $fixtureFile = __DIR__ . '/Fixtures/clear.gif';
        $testFilename = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_') . '.gif';
        @copy($fixtureFile, $testFilename);
        GeneralUtility::unlink_tempfile($testFilename);
        $fileExists = file_exists($testFilename);
        self::assertFalse($fileExists);
    }

    /**
     * @test
     */
    public function unlink_tempfileRemovesHiddenFile(): void
    {
        $fixtureFile = __DIR__ . '/Fixtures/clear.gif';
        $testFilename = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('.test_') . '.gif';
        @copy($fixtureFile, $testFilename);
        GeneralUtility::unlink_tempfile($testFilename);
        $fileExists = file_exists($testFilename);
        self::assertFalse($fileExists);
    }

    /**
     * @test
     */
    public function unlink_tempfileReturnsTrueIfFileWasRemoved(): void
    {
        $fixtureFile = __DIR__ . '/Fixtures/clear.gif';
        $testFilename = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_') . '.gif';
        @copy($fixtureFile, $testFilename);
        $returnValue = GeneralUtility::unlink_tempfile($testFilename);
        self::assertTrue($returnValue);
    }

    /**
     * @test
     */
    public function unlink_tempfileReturnsNullIfFileDoesNotExist(): void
    {
        $returnValue = GeneralUtility::unlink_tempfile(Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('i_do_not_exist'));
        self::assertNull($returnValue);
    }

    /**
     * @test
     */
    public function unlink_tempfileReturnsNullIfFileIsNowWithinTypo3temp(): void
    {
        $returnValue = GeneralUtility::unlink_tempfile('/tmp/typo3-unit-test-unlink_tempfile');
        self::assertNull($returnValue);
    }

    //////////////////////////////////////
    // Tests concerning tempnam
    //////////////////////////////////////

    /**
     * @test
     */
    public function tempnamReturnsPathStartingWithGivenPrefix(): void
    {
        $filePath = GeneralUtility::tempnam('foo');
        $this->testFilesToDelete[] = $filePath;
        $fileName = basename($filePath);
        self::assertStringStartsWith('foo', $fileName);
    }

    /**
     * @test
     */
    public function tempnamReturnsPathWithoutBackslashes(): void
    {
        $filePath = GeneralUtility::tempnam('foo');
        $this->testFilesToDelete[] = $filePath;
        self::assertStringNotContainsString('\\', $filePath);
    }

    /**
     * @test
     */
    public function tempnamReturnsAbsolutePathInVarPath(): void
    {
        $filePath = GeneralUtility::tempnam('foo');
        $this->testFilesToDelete[] = $filePath;
        self::assertStringStartsWith(Environment::getVarPath() . '/transient/', $filePath);
    }

    //////////////////////////////////////
    // Tests concerning removeDotsFromTS
    //////////////////////////////////////
    /**
     * @test
     */
    public function removeDotsFromTypoScriptSucceedsWithDottedArray(): void
    {
        $typoScript = [
            'propertyA.' => [
                'keyA.' => [
                    'valueA' => 1,
                ],
                'keyB' => 2,
            ],
            'propertyB' => 3,
        ];
        $expectedResult = [
            'propertyA' => [
                'keyA' => [
                    'valueA' => 1,
                ],
                'keyB' => 2,
            ],
            'propertyB' => 3,
        ];
        self::assertEquals($expectedResult, GeneralUtility::removeDotsFromTS($typoScript));
    }

    /**
     * @test
     */
    public function removeDotsFromTypoScriptOverridesSubArray(): void
    {
        $typoScript = [
            'propertyA.' => [
                'keyA' => 'getsOverridden',
                'keyA.' => [
                    'valueA' => 1,
                ],
                'keyB' => 2,
            ],
            'propertyB' => 3,
        ];
        $expectedResult = [
            'propertyA' => [
                'keyA' => [
                    'valueA' => 1,
                ],
                'keyB' => 2,
            ],
            'propertyB' => 3,
        ];
        self::assertEquals($expectedResult, GeneralUtility::removeDotsFromTS($typoScript));
    }

    /**
     * @test
     */
    public function removeDotsFromTypoScriptOverridesWithScalar(): void
    {
        $typoScript = [
            'propertyA.' => [
                'keyA.' => [
                    'valueA' => 1,
                ],
                'keyA' => 'willOverride',
                'keyB' => 2,
            ],
            'propertyB' => 3,
        ];
        $expectedResult = [
            'propertyA' => [
                'keyA' => 'willOverride',
                'keyB' => 2,
            ],
            'propertyB' => 3,
        ];
        self::assertEquals($expectedResult, GeneralUtility::removeDotsFromTS($typoScript));
    }

    //////////////////////////////////////
    // Tests concerning get_dirs
    //////////////////////////////////////
    /**
     * @test
     */
    public function getDirsReturnsArrayOfDirectoriesFromGivenDirectory(): void
    {
        $directories = GeneralUtility::get_dirs(Environment::getLegacyConfigPath() . '/');
        self::assertIsArray($directories);
    }

    /**
     * @test
     */
    public function getDirsReturnsStringErrorOnPathFailure(): void
    {
        $path = 'foo';
        $result = GeneralUtility::get_dirs($path);
        $expectedResult = 'error';
        self::assertEquals($expectedResult, $result);
    }

    //////////////////////////////////
    // Tests concerning hmac
    //////////////////////////////////
    /**
     * @test
     */
    public function hmacReturnsHashOfProperLength(): void
    {
        $hmac = GeneralUtility::hmac('message');
        self::assertTrue(!empty($hmac) && is_string($hmac));
        self::assertEquals(strlen($hmac), 40);
    }

    /**
     * @test
     */
    public function hmacReturnsEqualHashesForEqualInput(): void
    {
        $msg0 = 'message';
        $msg1 = 'message';
        self::assertEquals(GeneralUtility::hmac($msg0), GeneralUtility::hmac($msg1));
    }

    /**
     * @test
     */
    public function hmacReturnsNoEqualHashesForNonEqualInput(): void
    {
        $msg0 = 'message0';
        $msg1 = 'message1';
        self::assertNotEquals(GeneralUtility::hmac($msg0), GeneralUtility::hmac($msg1));
    }

    //////////////////////////////////
    // Tests concerning quoteJSvalue
    //////////////////////////////////
    /**
     * Data provider for quoteJSvalueTest.
     *
     * @return array
     */
    public function quoteJsValueDataProvider(): array
    {
        return [
            'Immune characters are returned as is' => [
                '._,',
                '._,',
            ],
            'Alphanumerical characters are returned as is' => [
                'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
                'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            ],
            'Angle brackets and ampersand are encoded' => [
                '<>&',
                '\\u003C\\u003E\\u0026',
            ],
            'Quotes and backslashes are encoded' => [
                '"\'\\',
                '\\u0022\\u0027\\u005C',
            ],
            'Forward slashes are escaped' => [
                '</script>',
                '\\u003C\\/script\\u003E',
            ],
            'Empty string stays empty' => [
                '',
                '',
            ],
            'Exclamation mark and space are properly encoded' => [
                'Hello World!',
                'Hello\\u0020World\\u0021',
            ],
            'Whitespaces are properly encoded' => [
                "\t" . LF . CR . ' ',
                '\\u0009\\u000A\\u000D\\u0020',
            ],
            'Null byte is properly encoded' => [
                "\0",
                '\\u0000',
            ],
            'Umlauts are properly encoded' => [
                'ÜüÖöÄä',
                '\\u00dc\\u00fc\\u00d6\\u00f6\\u00c4\\u00e4',
            ],
        ];
    }

    /**
     * @test
     * @param string $input
     * @param string $expected
     * @dataProvider quoteJsValueDataProvider
     */
    public function quoteJsValueTest(string $input, string $expected): void
    {
        self::assertSame('\'' . $expected . '\'', GeneralUtility::quoteJSvalue($input));
    }

    ///////////////////////////////
    // Tests concerning fixPermissions
    ///////////////////////////////
    /**
     * @test
     * @requires function posix_getegid
     */
    public function fixPermissionsSetsGroup(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        // Create and prepare test file
        $filename = $this->getVirtualTestDir() . '/' . StringUtility::getUniqueId('test_');
        GeneralUtilityFilesystemFixture::writeFileToTypo3tempDir($filename, '42');
        $currentGroupId = posix_getegid();
        // Set target group and run method
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['createGroup'] = $currentGroupId;
        GeneralUtilityFilesystemFixture::fixPermissions($filename);
        clearstatcache();
        self::assertEquals($currentGroupId, filegroup($filename));
    }

    /**
     * @test
     */
    public function fixPermissionsSetsPermissionsToFile(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        // Create and prepare test file
        $filename = $this->getVirtualTestDir() . '/' . StringUtility::getUniqueId('test_');
        GeneralUtilityFilesystemFixture::writeFileToTypo3tempDir($filename, '42');
        chmod($filename, 482);
        // Set target permissions and run method
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'] = '0660';
        $fixPermissionsResult = GeneralUtilityFilesystemFixture::fixPermissions($filename);
        clearstatcache();
        self::assertTrue($fixPermissionsResult);
        self::assertEquals('0660', substr(decoct(fileperms($filename)), 2));
    }

    /**
     * @test
     */
    public function fixPermissionsSetsPermissionsToHiddenFile(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        // Create and prepare test file
        $filename = $this->getVirtualTestDir() . '/' . StringUtility::getUniqueId('test_');
        GeneralUtilityFilesystemFixture::writeFileToTypo3tempDir($filename, '42');
        chmod($filename, 482);
        // Set target permissions and run method
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'] = '0660';
        $fixPermissionsResult = GeneralUtilityFilesystemFixture::fixPermissions($filename);
        clearstatcache();
        self::assertTrue($fixPermissionsResult);
        self::assertEquals('0660', substr(decoct(fileperms($filename)), 2));
    }

    /**
     * @test
     */
    public function fixPermissionsSetsPermissionsToDirectory(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        // Create and prepare test directory
        $directory = $this->getVirtualTestDir() . '/' . StringUtility::getUniqueId('test_');
        GeneralUtilityFilesystemFixture::mkdir($directory);
        chmod($directory, 1551);
        // Set target permissions and run method
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'] = '0770';
        $fixPermissionsResult = GeneralUtilityFilesystemFixture::fixPermissions($directory);
        clearstatcache();
        self::assertTrue($fixPermissionsResult);
        self::assertEquals('0770', substr(decoct(fileperms($directory)), 1));
    }

    /**
     * @test
     */
    public function fixPermissionsSetsPermissionsToDirectoryWithTrailingSlash(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        // Create and prepare test directory
        $directory = $this->getVirtualTestDir() . '/' . StringUtility::getUniqueId('test_');
        GeneralUtilityFilesystemFixture::mkdir($directory);
        chmod($directory, 1551);
        // Set target permissions and run method
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'] = '0770';
        $fixPermissionsResult = GeneralUtilityFilesystemFixture::fixPermissions($directory . '/');
        // Get actual permissions and clean up
        clearstatcache();
        self::assertTrue($fixPermissionsResult);
        self::assertEquals('0770', substr(decoct(fileperms($directory)), 1));
    }

    /**
     * @test
     */
    public function fixPermissionsSetsPermissionsToHiddenDirectory(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        // Create and prepare test directory
        $directory = $this->getVirtualTestDir() . '/' . StringUtility::getUniqueId('test_');
        GeneralUtilityFilesystemFixture::mkdir($directory);
        chmod($directory, 1551);
        // Set target permissions and run method
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'] = '0770';
        $fixPermissionsResult = GeneralUtilityFilesystemFixture::fixPermissions($directory);
        // Get actual permissions and clean up
        clearstatcache();
        self::assertTrue($fixPermissionsResult);
        self::assertEquals('0770', substr(decoct(fileperms($directory)), 1));
    }

    /**
     * @test
     */
    public function fixPermissionsCorrectlySetsPermissionsRecursive(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        // Create and prepare test directory and file structure
        $baseDirectory = $this->getVirtualTestDir() . '/' . StringUtility::getUniqueId('test_');
        GeneralUtilityFilesystemFixture::mkdir($baseDirectory);
        chmod($baseDirectory, 1751);
        GeneralUtilityFilesystemFixture::writeFileToTypo3tempDir($baseDirectory . '/file', '42');
        chmod($baseDirectory . '/file', 482);
        GeneralUtilityFilesystemFixture::mkdir($baseDirectory . '/foo');
        chmod($baseDirectory . '/foo', 1751);
        GeneralUtilityFilesystemFixture::writeFileToTypo3tempDir($baseDirectory . '/foo/file', '42');
        chmod($baseDirectory . '/foo/file', 482);
        GeneralUtilityFilesystemFixture::mkdir($baseDirectory . '/.bar');
        chmod($baseDirectory . '/.bar', 1751);
        // Use this if writeFileToTypo3tempDir is fixed to create hidden files in subdirectories
        // \TYPO3\CMS\Core\Utility\GeneralUtility::writeFileToTypo3tempDir($baseDirectory . '/.bar/.file', '42');
        // \TYPO3\CMS\Core\Utility\GeneralUtility::writeFileToTypo3tempDir($baseDirectory . '/.bar/..file2', '42');
        touch($baseDirectory . '/.bar/.file', 42);
        chmod($baseDirectory . '/.bar/.file', 482);
        touch($baseDirectory . '/.bar/..file2', 42);
        chmod($baseDirectory . '/.bar/..file2', 482);
        // Set target permissions and run method
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'] = '0660';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'] = '0770';
        $fixPermissionsResult = GeneralUtilityFilesystemFixture::fixPermissions($baseDirectory, true);
        // Get actual permissions
        clearstatcache();
        $resultBaseDirectoryPermissions = substr(decoct(fileperms($baseDirectory)), 1);
        $resultBaseFilePermissions = substr(decoct(fileperms($baseDirectory . '/file')), 2);
        $resultFooDirectoryPermissions = substr(decoct(fileperms($baseDirectory . '/foo')), 1);
        $resultFooFilePermissions = substr(decoct(fileperms($baseDirectory . '/foo/file')), 2);
        $resultBarDirectoryPermissions = substr(decoct(fileperms($baseDirectory . '/.bar')), 1);
        $resultBarFilePermissions = substr(decoct(fileperms($baseDirectory . '/.bar/.file')), 2);
        $resultBarFile2Permissions = substr(decoct(fileperms($baseDirectory . '/.bar/..file2')), 2);
        // Test if everything was ok
        self::assertTrue($fixPermissionsResult);
        self::assertEquals('0770', $resultBaseDirectoryPermissions);
        self::assertEquals('0660', $resultBaseFilePermissions);
        self::assertEquals('0770', $resultFooDirectoryPermissions);
        self::assertEquals('0660', $resultFooFilePermissions);
        self::assertEquals('0770', $resultBarDirectoryPermissions);
        self::assertEquals('0660', $resultBarFilePermissions);
        self::assertEquals('0660', $resultBarFile2Permissions);
    }

    /**
     * @test
     */
    public function fixPermissionsDoesNotSetPermissionsToNotAllowedPath(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        // Create and prepare test file
        $filename = Environment::getVarPath() . '/tests/../../../typo3temp/var/tests/' . StringUtility::getUniqueId('test_');
        // Set target permissions and run method
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'] = '0660';
        $fixPermissionsResult = GeneralUtility::fixPermissions($filename);
        self::assertFalse($fixPermissionsResult);
    }

    /**
     * @test
     */
    public function fixPermissionsSetsPermissionsWithRelativeFileReference(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        $filename = 'typo3temp/var/tests/' . StringUtility::getUniqueId('test_');
        GeneralUtility::writeFileToTypo3tempDir(Environment::getPublicPath() . '/' . $filename, '42');
        $this->testFilesToDelete[] = Environment::getPublicPath() . '/' . $filename;
        chmod(Environment::getPublicPath() . '/' . $filename, 482);
        // Set target permissions and run method
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'] = '0660';
        $fixPermissionsResult = GeneralUtility::fixPermissions($filename);
        clearstatcache();
        self::assertTrue($fixPermissionsResult);
        self::assertEquals('0660', substr(decoct(fileperms(Environment::getPublicPath() . '/' . $filename)), 2));
    }

    /**
     * @test
     */
    public function fixPermissionsSetsDefaultPermissionsToFile(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        $filename = $this->getVirtualTestDir() . '/' . StringUtility::getUniqueId('test_');
        GeneralUtilityFilesystemFixture::writeFileToTypo3tempDir($filename, '42');
        chmod($filename, 482);
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask']);
        $fixPermissionsResult = GeneralUtilityFilesystemFixture::fixPermissions($filename);
        clearstatcache();
        self::assertTrue($fixPermissionsResult);
        self::assertEquals('0644', substr(decoct(fileperms($filename)), 2));
    }

    /**
     * @test
     */
    public function fixPermissionsSetsDefaultPermissionsToDirectory(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        $directory = $this->getVirtualTestDir() . '/' . StringUtility::getUniqueId('test_');
        GeneralUtilityFilesystemFixture::mkdir($directory);
        chmod($directory, 1551);
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask']);
        $fixPermissionsResult = GeneralUtilityFilesystemFixture::fixPermissions($directory);
        clearstatcache();
        self::assertTrue($fixPermissionsResult);
        self::assertEquals('0755', substr(decoct(fileperms($directory)), 1));
    }

    ///////////////////////////////
    // Tests concerning mkdir
    ///////////////////////////////
    /**
     * @test
     */
    public function mkdirCreatesDirectory(): void
    {
        $directory = $this->getVirtualTestDir() . '/' . StringUtility::getUniqueId('test_');
        $mkdirResult = GeneralUtilityFilesystemFixture::mkdir($directory);
        clearstatcache();
        self::assertTrue($mkdirResult);
        self::assertDirectoryExists($directory);
    }

    /**
     * @test
     */
    public function mkdirCreatesHiddenDirectory(): void
    {
        $directory = $this->getVirtualTestDir() . '/' . StringUtility::getUniqueId('.test_');
        $mkdirResult = GeneralUtilityFilesystemFixture::mkdir($directory);
        clearstatcache();
        self::assertTrue($mkdirResult);
        self::assertDirectoryExists($directory);
    }

    /**
     * @test
     */
    public function mkdirCreatesDirectoryWithTrailingSlash(): void
    {
        $directory = $this->getVirtualTestDir() . '/' . StringUtility::getUniqueId('test_') . '/';
        $mkdirResult = GeneralUtilityFilesystemFixture::mkdir($directory);
        clearstatcache();
        self::assertTrue($mkdirResult);
        self::assertDirectoryExists($directory);
    }

    /**
     * @test
     */
    public function mkdirSetsPermissionsOfCreatedDirectory(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        $directory = $this->getVirtualTestDir() . '/' . StringUtility::getUniqueId('test_');
        $oldUmask = umask(19);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'] = '0772';
        GeneralUtilityFilesystemFixture::mkdir($directory);
        clearstatcache();
        $resultDirectoryPermissions = substr(decoct(fileperms($directory)), 1);
        umask($oldUmask);
        self::assertEquals('0772', $resultDirectoryPermissions);
    }

    /**
     * @test
     */
    public function mkdirSetsGroupOwnershipOfCreatedDirectory(): void
    {
        $swapGroup = $this->checkGroups(__FUNCTION__);
        if ($swapGroup !== false) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['createGroup'] = $swapGroup;
            $directory = $this->getVirtualTestDir() . '/' . StringUtility::getUniqueId('mkdirtest_');
            GeneralUtilityFilesystemFixture::mkdir($directory);
            clearstatcache();
            $resultDirectoryGroup = filegroup($directory);
            self::assertEquals($resultDirectoryGroup, $swapGroup);
        }
    }

    ///////////////////////////////
    // Helper function for filesystem ownership tests
    ///////////////////////////////
    /**
     * Check if test on filesystem group ownership can be done in this environment
     * If so, return second group of webserver user
     *
     * @param string $methodName calling method name
     * @return mixed FALSE if test cannot be run, int group id of the second group of webserver user
     * @requires function posix_getegid
     * @requires function posix_getgroups
     */
    private function checkGroups(string $methodName)
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
            return false;
        }
        $groups = posix_getgroups();
        if (count($groups) <= 1) {
            self::markTestSkipped($methodName . '() test cannot be done when the web server user is only member of 1 group.');
            return false;
        }
        $secondaryGroups = array_diff($groups, [posix_getegid()]);
        return array_shift($secondaryGroups);
    }

    /////////////////////////////////////////////
    // Tests concerning writeFileToTypo3tempDir()
    /////////////////////////////////////////////

    /**
     * @return array
     */
    public function invalidFilePathForTypo3tempDirDataProvider(): array
    {
        return [
            [
                Environment::getPublicPath() . '/../path/this-path-has-more-than-60-characters-in-one-base-path-you-can-even-count-more',
                'Input filepath "' . Environment::getPublicPath() . '/../path/this-path-has-more-than-60-characters-in-one-base-path-you-can-even-count-more" was generally invalid!',
            ],
            [
                Environment::getPublicPath() . '/dummy/path/this-path-has-more-than-60-characters-in-one-base-path-you-can-even-count-more',
                'Input filepath "' . Environment::getPublicPath() . '/dummy/path/this-path-has-more-than-60-characters-in-one-base-path-you-can-even-count-more" was generally invalid!',
            ],
            [
                Environment::getPublicPath() . '/dummy/path/this-path-has-more-than-60-characters-in-one-base-path-you-can-even-count-more',
                'Input filepath "' . Environment::getPublicPath() . '/dummy/path/this-path-has-more-than-60-characters-in-one-base-path-you-can-even-count-more" was generally invalid!',
            ],
            [
                '/dummy/path/awesome',
                '"/dummy/path/" was not within directory Environment::getPublicPath() + "/typo3temp/"',
            ],
            [
                Environment::getLegacyConfigPath() . '/path',
                '"' . Environment::getLegacyConfigPath() . '/" was not within directory Environment::getPublicPath() + "/typo3temp/"',
            ],
            [
                Environment::getPublicPath() . '/typo3temp/táylor/swíft',
                'Subdir, "táylor/", was NOT on the form "[[:alnum:]_]/+"',
            ],
            'Path instead of file given' => [
                Environment::getPublicPath() . '/typo3temp/dummy/path/',
                'Calculated file location didn\'t match input "' . Environment::getPublicPath() . '/typo3temp/dummy/path/".',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidFilePathForTypo3tempDirDataProvider
     * @param string $invalidFilePath
     * @param string $expectedResult
     */
    public function writeFileToTypo3tempDirFailsWithInvalidPath(string $invalidFilePath, string $expectedResult): void
    {
        $result = GeneralUtility::writeFileToTypo3tempDir($invalidFilePath, 'dummy content to be written');
        self::assertSame($result, $expectedResult);
    }

    /**
     * @return array
     */
    public function validFilePathForTypo3tempDirDataProvider(): array
    {
        return [
            'Default text file' => [
                Environment::getVarPath() . '/paranoid/android.txt',
            ],
            'Html file extension' => [
                Environment::getVarPath() . '/karma.html',
            ],
            'No file extension' => [
                Environment::getVarPath() . '/no-surprises',
            ],
            'Deep directory' => [
                Environment::getVarPath() . '/climbing/up/the/walls',
            ],
            'File in typo3temp/var directory' => [
                Environment::getPublicPath() . '/typo3temp/var/path/foo.txt',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider validFilePathForTypo3tempDirDataProvider
     * @param string $filePath
     */
    public function writeFileToTypo3tempDirWorksWithValidPath(string $filePath): void
    {
        $dummyContent = 'Please could you stop the noise, I\'m trying to get some rest from all the unborn chicken voices in my head.';

        $this->testFilesToDelete[] = $filePath;

        $result = GeneralUtility::writeFileToTypo3tempDir($filePath, $dummyContent);

        self::assertNull($result);
        self::assertFileExists($filePath);
        self::assertStringEqualsFile($filePath, $dummyContent);
    }

    ///////////////////////////////
    // Tests concerning mkdir_deep
    ///////////////////////////////
    /**
     * @test
     */
    public function mkdirDeepCreatesDirectory(): void
    {
        $directory = $this->getVirtualTestDir() . '/' . StringUtility::getUniqueId('test_');
        GeneralUtility::mkdir_deep($directory);
        self::assertDirectoryExists($directory);
    }

    /**
     * @test
     */
    public function mkdirDeepCreatesSubdirectoriesRecursive(): void
    {
        $directory = $this->getVirtualTestDir() . 'typo3temp/var/tests/' . StringUtility::getUniqueId('test_');
        $subDirectory = $directory . '/foo';
        GeneralUtility::mkdir_deep($subDirectory);
        self::assertDirectoryExists($subDirectory);
    }

    /**
     * Data provider for mkdirDeepCreatesDirectoryWithDoubleSlashes.
     * @return array
     */
    public function mkdirDeepCreatesDirectoryWithAndWithoutDoubleSlashesDataProvider(): array
    {
        return [
            'no double slash if concatenated with Environment::getPublicPath()' => ['fileadmin/testDir1'],
            'double slash if concatenated with Environment::getPublicPath()' => ['/fileadmin/testDir2'],
        ];
    }

    /**
     * @test
     * @dataProvider mkdirDeepCreatesDirectoryWithAndWithoutDoubleSlashesDataProvider
     */
    public function mkdirDeepCreatesDirectoryWithDoubleSlashes($directoryToCreate): void
    {
        vfsStream::setup('root', null, ['public' => []]);
        GeneralUtility::mkdir_deep('vfs://root/public/' . $directoryToCreate);
        self::assertDirectoryExists('vfs://root/public/' . $directoryToCreate);
    }

    /**
     * @test
     */
    public function mkdirDeepFixesPermissionsOfCreatedDirectory(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        $directory = StringUtility::getUniqueId('mkdirdeeptest_');
        $oldUmask = umask(19);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'] = '0777';
        GeneralUtility::mkdir_deep(Environment::getVarPath() . '/tests/' . $directory);
        $this->testFilesToDelete[] = Environment::getVarPath() . '/tests/' . $directory;
        clearstatcache();
        umask($oldUmask);
        self::assertEquals('777', substr(decoct(fileperms(Environment::getVarPath() . '/tests/' . $directory)), -3, 3));
    }

    /**
     * @test
     */
    public function mkdirDeepFixesPermissionsOnNewParentDirectory(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        $directory = StringUtility::getUniqueId('mkdirdeeptest_');
        $subDirectory = $directory . '/bar';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'] = '0777';
        $oldUmask = umask(19);
        GeneralUtility::mkdir_deep(Environment::getVarPath() . '/tests/' . $subDirectory);
        $this->testFilesToDelete[] = Environment::getVarPath() . '/tests/' . $directory;
        clearstatcache();
        umask($oldUmask);
        self::assertEquals('777', substr(decoct(fileperms(Environment::getVarPath() . '/tests/' . $directory)), -3, 3));
    }

    /**
     * @test
     */
    public function mkdirDeepDoesNotChangePermissionsOfExistingSubDirectories(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        $baseDirectory = Environment::getVarPath() . '/tests/';
        $existingDirectory = StringUtility::getUniqueId('test_existing_') . '/';
        $newSubDirectory = StringUtility::getUniqueId('test_new_');
        @mkdir($baseDirectory . $existingDirectory);
        $this->testFilesToDelete[] = $baseDirectory . $existingDirectory;
        chmod($baseDirectory . $existingDirectory, 482);
        GeneralUtility::mkdir_deep($baseDirectory . $existingDirectory . $newSubDirectory);
        self::assertEquals(742, (int)substr(decoct(fileperms($baseDirectory . $existingDirectory)), 2));
    }

    /**
     * @test
     */
    public function mkdirDeepSetsGroupOwnershipOfCreatedDirectory(): void
    {
        $swapGroup = $this->checkGroups(__FUNCTION__);
        if ($swapGroup !== false) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['createGroup'] = $swapGroup;
            $directory = StringUtility::getUniqueId('mkdirdeeptest_');
            GeneralUtility::mkdir_deep(Environment::getVarPath() . '/tests/' . $directory);
            $this->testFilesToDelete[] = Environment::getVarPath() . '/tests/' . $directory;
            clearstatcache();
            $resultDirectoryGroup = filegroup(Environment::getVarPath() . '/tests/' . $directory);
            self::assertEquals($resultDirectoryGroup, $swapGroup);
        }
    }

    /**
     * @test
     */
    public function mkdirDeepSetsGroupOwnershipOfCreatedParentDirectory(): void
    {
        $swapGroup = $this->checkGroups(__FUNCTION__);
        if ($swapGroup !== false) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['createGroup'] = $swapGroup;
            $directory = StringUtility::getUniqueId('mkdirdeeptest_');
            $subDirectory = $directory . '/bar';
            GeneralUtility::mkdir_deep(Environment::getVarPath() . '/tests/' . $subDirectory);
            $this->testFilesToDelete[] = Environment::getVarPath() . '/tests/' . $directory;
            clearstatcache();
            $resultDirectoryGroup = filegroup(Environment::getVarPath() . '/tests/' . $directory);
            self::assertEquals($resultDirectoryGroup, $swapGroup);
        }
    }

    /**
     * @test
     */
    public function mkdirDeepSetsGroupOwnershipOnNewSubDirectory(): void
    {
        $swapGroup = $this->checkGroups(__FUNCTION__);
        if ($swapGroup !== false) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['createGroup'] = $swapGroup;
            $directory = StringUtility::getUniqueId('mkdirdeeptest_');
            $subDirectory = $directory . '/bar';
            GeneralUtility::mkdir_deep(Environment::getVarPath() . '/tests/' . $subDirectory);
            $this->testFilesToDelete[] = Environment::getVarPath() . '/tests/' . $directory;
            clearstatcache();
            $resultDirectoryGroup = filegroup(Environment::getVarPath() . '/tests/' . $directory);
            self::assertEquals($resultDirectoryGroup, $swapGroup);
        }
    }

    /**
     * @test
     */
    public function mkdirDeepCreatesDirectoryInVfsStream(): void
    {
        vfsStreamWrapper::register();
        $baseDirectory = StringUtility::getUniqueId('test_');
        vfsStreamWrapper::setRoot(new vfsStreamDirectory($baseDirectory));
        GeneralUtility::mkdir_deep('vfs://' . $baseDirectory . '/sub');
        self::assertDirectoryExists('vfs://' . $baseDirectory . '/sub');
    }

    /**
     * @test
     */
    public function mkdirDeepThrowsExceptionIfDirectoryCreationFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1170251401);

        GeneralUtility::mkdir_deep('http://localhost');
    }

    /**
     * @test
     */
    public function mkdirDeepThrowsExceptionIfBaseDirectoryIsNotOfTypeString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1303662955);

        GeneralUtility::mkdir_deep([]);
    }

    ///////////////////////////////
    // Tests concerning rmdir
    ///////////////////////////////

    /**
     * @test
     */
    public function rmdirRemovesFile(): void
    {
        $file = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('file_');
        touch($file);
        GeneralUtility::rmdir($file);
        self::assertFileDoesNotExist($file);
    }

    /**
     * @test
     */
    public function rmdirReturnTrueIfFileWasRemoved(): void
    {
        $file = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('file_');
        touch($file);
        self::assertTrue(GeneralUtility::rmdir($file));
    }

    /**
     * @test
     */
    public function rmdirReturnFalseIfNoFileWasRemoved(): void
    {
        $file = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('file_');
        self::assertFalse(GeneralUtility::rmdir($file));
    }

    /**
     * @test
     */
    public function rmdirRemovesDirectory(): void
    {
        $directory = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('directory_');
        mkdir($directory);
        GeneralUtility::rmdir($directory);
        self::assertFileDoesNotExist($directory);
    }

    /**
     * @test
     */
    public function rmdirRemovesDirectoryWithTrailingSlash(): void
    {
        $directory = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('directory_') . '/';
        mkdir($directory);
        GeneralUtility::rmdir($directory);
        self::assertFileDoesNotExist($directory);
    }

    /**
     * @test
     */
    public function rmdirDoesNotRemoveDirectoryWithFilesAndReturnsFalseIfRecursiveDeletionIsOff(): void
    {
        $directory = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('directory_') . '/';
        mkdir($directory);
        $file = StringUtility::getUniqueId('file_');
        touch($directory . $file);
        $this->testFilesToDelete[] = $directory;
        $return = GeneralUtility::rmdir($directory);
        self::assertFileExists($directory);
        self::assertFileExists($directory . $file);
        self::assertFalse($return);
    }

    /**
     * @test
     */
    public function rmdirRemovesDirectoriesRecursiveAndReturnsTrue(): void
    {
        $directory = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('directory_') . '/';
        mkdir($directory);
        mkdir($directory . 'sub/');
        touch($directory . 'sub/file');
        $return = GeneralUtility::rmdir($directory, true);
        self::assertFileDoesNotExist($directory);
        self::assertTrue($return);
    }

    /**
     * @test
     */
    public function rmdirRemovesLinkToDirectory(): void
    {
        $existingDirectory = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('notExists_') . '/';
        mkdir($existingDirectory);
        $this->testFilesToDelete[] = $existingDirectory;
        $symlinkName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('link_');
        symlink($existingDirectory, $symlinkName);
        GeneralUtility::rmdir($symlinkName, true);
        self::assertFalse(is_link($symlinkName));
    }

    /**
     * @test
     */
    public function rmdirRemovesDeadLinkToDirectory(): void
    {
        $notExistingDirectory = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('notExists_') . '/';
        $symlinkName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('link_');
        mkdir($notExistingDirectory);
        symlink($notExistingDirectory, $symlinkName);
        rmdir($notExistingDirectory);

        GeneralUtility::rmdir($symlinkName, true);
        self::assertFalse(is_link($symlinkName));
    }

    /**
     * @test
     */
    public function rmdirRemovesDeadLinkToFile(): void
    {
        $notExistingFile = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('notExists_');
        $symlinkName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('link_');
        touch($notExistingFile);
        symlink($notExistingFile, $symlinkName);
        unlink($notExistingFile);
        GeneralUtility::rmdir($symlinkName, true);
        self::assertFalse(is_link($symlinkName));
    }

    ///////////////////////////////////
    // Tests concerning getFilesInDir
    ///////////////////////////////////

    /**
     * Helper method to create test directory.
     *
     * @return string A unique directory name prefixed with test_.
     */
    protected function getFilesInDirCreateTestDirectory(): string
    {
        $structure = [
            'subDirectory' => [
                'test.php' => 'butter',
                'other.php' => 'milk',
                'stuff.csv' => 'honey',
            ],
            'excludeMe.txt' => 'cocoa nibs',
            'double.setup.typoscript' => 'cool TS',
            'testB.txt' => 'olive oil',
            'testA.txt' => 'eggs',
            'testC.txt' => 'carrots',
            'test.js' => 'oranges',
            'test.css' => 'apples',
            '.secret.txt' => 'sammon',
        ];
        vfsStream::setup('test', null, $structure);
        $vfsUrl = vfsStream::url('test');

        // set random values for mtime
        foreach ($structure as $structureLevel1Key => $structureLevel1Content) {
            $newMtime = random_int(0, mt_getrandmax());
            if (is_array($structureLevel1Content)) {
                foreach ($structureLevel1Content as $structureLevel2Key => $structureLevel2Content) {
                    touch($vfsUrl . '/' . $structureLevel1Key . '/' . $structureLevel2Key, $newMtime);
                }
            } else {
                touch($vfsUrl . '/' . $structureLevel1Key, $newMtime);
            }
        }

        return $vfsUrl;
    }

    /**
     * @test
     */
    public function getFilesInDirFindsRegularFile(): void
    {
        $vfsStreamUrl = $this->getFilesInDirCreateTestDirectory();
        $files = GeneralUtility::getFilesInDir($vfsStreamUrl);
        self::assertContains('testA.txt', $files);
    }

    /**
     * @test
     */
    public function getFilesInDirFindsHiddenFile(): void
    {
        $vfsStreamUrl = $this->getFilesInDirCreateTestDirectory();
        $files = GeneralUtility::getFilesInDir($vfsStreamUrl);
        self::assertContains('.secret.txt', $files);
    }

    /**
     * Data provider for getFilesInDirByExtensionFindsFiles
     *
     * @return array
     */
    public function fileExtensionDataProvider(): array
    {
        return [
            'no space' => [
                'setup.typoscript,txt,js,css',
            ],
            'spaces' => [
                'setup.typoscript, txt, js, css',
            ],
            'mixed' => [
                'setup.typoscript , txt,js, css',
            ],
            'wild' => [
                'setup.typoscript,  txt,     js  ,         css',
            ],
        ];
    }

    /**
     * @dataProvider fileExtensionDataProvider
     * @test
     */
    public function getFilesInDirByExtensionFindsFiles($fileExtensions): void
    {
        $vfsStreamUrl = $this->getFilesInDirCreateTestDirectory();
        $files = GeneralUtility::getFilesInDir($vfsStreamUrl, $fileExtensions);
        self::assertContains('double.setup.typoscript', $files);
        self::assertContains('testA.txt', $files);
        self::assertContains('test.js', $files);
        self::assertContains('test.css', $files);
    }

    /**
     * @test
     */
    public function getFilesInDirByExtensionDoesNotFindFilesWithOtherExtensions(): void
    {
        $vfsStreamUrl = $this->getFilesInDirCreateTestDirectory();
        $files = GeneralUtility::getFilesInDir($vfsStreamUrl, 'txt,js');
        self::assertContains('testA.txt', $files);
        self::assertContains('test.js', $files);
        self::assertNotContains('test.css', $files);
    }

    /**
     * @test
     */
    public function getFilesInDirExcludesFilesMatchingPattern(): void
    {
        $vfsStreamUrl = $this->getFilesInDirCreateTestDirectory();
        $files = GeneralUtility::getFilesInDir($vfsStreamUrl, '', false, '', 'excludeMe.*');
        self::assertContains('test.js', $files);
        self::assertNotContains('excludeMe.txt', $files);
    }

    /**
     * @test
     */
    public function getFilesInDirCanPrependPath(): void
    {
        $vfsStreamUrl = $this->getFilesInDirCreateTestDirectory();
        self::assertContains(
            $vfsStreamUrl . '/testA.txt',
            GeneralUtility::getFilesInDir($vfsStreamUrl, '', true)
        );
    }

    /**
     * @test
     */
    public function getFilesInDirDoesSortAlphabeticallyByDefault(): void
    {
        $vfsStreamUrl = $this->getFilesInDirCreateTestDirectory();
        self::assertSame(
            array_values(GeneralUtility::getFilesInDir($vfsStreamUrl, '', false)),
            ['.secret.txt', 'double.setup.typoscript', 'excludeMe.txt', 'test.css', 'test.js', 'testA.txt', 'testB.txt', 'testC.txt']
        );
    }

    /**
     * @test
     */
    public function getFilesInDirCanOrderByMtime(): void
    {
        $vfsStreamUrl = $this->getFilesInDirCreateTestDirectory();
        $files = [];
        $iterator = new \DirectoryIterator($vfsStreamUrl);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isFile()) {
                $files[$fileinfo->getFilename()] = $fileinfo->getMTime();
            }
        }
        asort($files);
        self::assertSame(
            array_values(GeneralUtility::getFilesInDir($vfsStreamUrl, '', false, 'mtime')),
            array_keys($files)
        );
    }

    /**
     * @test
     */
    public function getFilesInDirReturnsArrayWithMd5OfElementAndPathAsArrayKey(): void
    {
        $vfsStreamUrl = $this->getFilesInDirCreateTestDirectory();
        self::assertArrayHasKey(
            md5($vfsStreamUrl . '/testA.txt'),
            GeneralUtility::getFilesInDir($vfsStreamUrl)
        );
    }

    /**
     * @test
     */
    public function getFilesInDirDoesNotFindDirectories(): void
    {
        $vfsStreamUrl = $this->getFilesInDirCreateTestDirectory();
        self::assertNotContains(
            'subDirectory',
            GeneralUtility::getFilesInDir($vfsStreamUrl)
        );
    }

    /**
     * Dotfiles; current directory: '.' and parent directory: '..' must not be
     * present.
     *
     * @test
     */
    public function getFilesInDirDoesNotFindDotfiles(): void
    {
        $vfsStreamUrl = $this->getFilesInDirCreateTestDirectory();
        $files = GeneralUtility::getFilesInDir($vfsStreamUrl);
        self::assertNotContains('..', $files);
        self::assertNotContains('.', $files);
    }

    ///////////////////////////////
    // Tests concerning split_fileref
    ///////////////////////////////
    /**
     * @test
     */
    public function splitFileRefReturnsFileTypeNotForFolders(): void
    {
        $directoryName = StringUtility::getUniqueId('test_') . '.com';
        $directoryPath = Environment::getVarPath() . '/tests/';
        $directory = $directoryPath . $directoryName;
        mkdir($directory, octdec($GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask']));
        $fileInfo = GeneralUtility::split_fileref($directory);
        $directoryCreated = is_dir($directory);
        rmdir($directory);
        self::assertTrue($directoryCreated);
        self::assertIsArray($fileInfo);
        self::assertEquals($directoryPath, $fileInfo['path']);
        self::assertEquals($directoryName, $fileInfo['file']);
        self::assertEquals($directoryName, $fileInfo['filebody']);
        self::assertEquals('', $fileInfo['fileext']);
        self::assertArrayNotHasKey('realFileext', $fileInfo);
    }

    /**
     * @test
     */
    public function splitFileRefReturnsFileTypeForFilesWithoutPathSite(): void
    {
        $testFile = 'fileadmin/media/someFile.png';
        $fileInfo = GeneralUtility::split_fileref($testFile);
        self::assertIsArray($fileInfo);
        self::assertEquals('fileadmin/media/', $fileInfo['path']);
        self::assertEquals('someFile.png', $fileInfo['file']);
        self::assertEquals('someFile', $fileInfo['filebody']);
        self::assertEquals('png', $fileInfo['fileext']);
    }

    /////////////////////////////
    // Tests concerning dirname
    /////////////////////////////
    /**
     * @see dirnameWithDataProvider
     * @return array|array[]
     */
    public function dirnameDataProvider(): array
    {
        return [
            'absolute path with multiple part and file' => ['/dir1/dir2/script.php', '/dir1/dir2'],
            'absolute path with one part' => ['/dir1/', '/dir1'],
            'absolute path to file without extension' => ['/dir1/something', '/dir1'],
            'relative path with one part and file' => ['dir1/script.php', 'dir1'],
            'relative one-character path with one part and file' => ['d/script.php', 'd'],
            'absolute zero-part path with file' => ['/script.php', ''],
            'empty string' => ['', ''],
        ];
    }

    /**
     * @test
     * @dataProvider dirnameDataProvider
     * @param string $input the input for dirname
     * @param string $expectedValue the expected return value expected from dirname
     */
    public function dirnameWithDataProvider(string $input, string $expectedValue): void
    {
        self::assertEquals($expectedValue, GeneralUtility::dirname($input));
    }

    /////////////////////////////////////
    // Tests concerning resolveBackPath
    /////////////////////////////////////
    /**
     * @see resolveBackPathWithDataProvider
     * @return array|array[]
     */
    public function resolveBackPathDataProvider(): array
    {
        return [
            'empty path' => ['', ''],
            'this directory' => ['./', './'],
            'relative directory without ..' => ['dir1/dir2/dir3/', 'dir1/dir2/dir3/'],
            'relative path without ..' => ['dir1/dir2/script.php', 'dir1/dir2/script.php'],
            'absolute directory without ..' => ['/dir1/dir2/dir3/', '/dir1/dir2/dir3/'],
            'absolute path without ..' => ['/dir1/dir2/script.php', '/dir1/dir2/script.php'],
            'only one directory upwards without trailing slash' => ['..', '..'],
            'only one directory upwards with trailing slash' => ['../', '../'],
            'one level with trailing ..' => ['dir1/..', ''],
            'one level with trailing ../' => ['dir1/../', ''],
            'two levels with trailing ..' => ['dir1/dir2/..', 'dir1'],
            'two levels with trailing ../' => ['dir1/dir2/../', 'dir1/'],
            'leading ../ without trailing /' => ['../dir1', '../dir1'],
            'leading ../ with trailing /' => ['../dir1/', '../dir1/'],
            'leading ../ and inside path' => ['../dir1/dir2/../dir3/', '../dir1/dir3/'],
            'one times ../ in relative directory' => ['dir1/../dir2/', 'dir2/'],
            'one times ../ in absolute directory' => ['/dir1/../dir2/', '/dir2/'],
            'one times ../ in relative path' => ['dir1/../dir2/script.php', 'dir2/script.php'],
            'one times ../ in absolute path' => ['/dir1/../dir2/script.php', '/dir2/script.php'],
            'consecutive ../' => ['dir1/dir2/dir3/../../../dir4', 'dir4'],
            'distributed ../ with trailing /' => ['dir1/../dir2/dir3/../', 'dir2/'],
            'distributed ../ without trailing /' => ['dir1/../dir2/dir3/..', 'dir2'],
            'multiple distributed and consecutive ../ together' => ['dir1/dir2/dir3/dir4/../../dir5/dir6/dir7/../dir8/', 'dir1/dir2/dir5/dir6/dir8/'],
            'dirname with leading ..' => ['dir1/..dir2/dir3/', 'dir1/..dir2/dir3/'],
            'dirname with trailing ..' => ['dir1/dir2../dir3/', 'dir1/dir2../dir3/'],
            'more times upwards than downwards in directory' => ['dir1/../../', '../'],
            'more times upwards than downwards in path' => ['dir1/../../script.php', '../script.php'],
        ];
    }

    /**
     * @test
     * @dataProvider resolveBackPathDataProvider
     * @param string $input the input for resolveBackPath
     * @param string $expectedValue Expected return value from resolveBackPath
     */
    public function resolveBackPathWithDataProvider(string $input, string $expectedValue): void
    {
        self::assertEquals($expectedValue, GeneralUtility::resolveBackPath($input));
    }

    /////////////////////////////////////////////////////////////////////////////////////
    // Tests concerning makeInstance, setSingletonInstance, addInstance, purgeInstances
    /////////////////////////////////////////////////////////////////////////////////////
    /**
     * @test
     */
    public function makeInstanceWithEmptyClassNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288965219);

        GeneralUtility::makeInstance('');
    }

    /**
     * @test
     */
    public function makeInstanceWithNullClassNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288965219);

        GeneralUtility::makeInstance(null);
    }

    /**
     * @test
     */
    public function makeInstanceWithZeroStringClassNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288965219);

        GeneralUtility::makeInstance(0);
    }

    /**
     * @test
     */
    public function makeInstanceWithEmptyArrayThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288965219);

        GeneralUtility::makeInstance([]);
    }

    /**
     * @test
     */
    public function makeInstanceWithNonEmptyArrayThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288965219);

        GeneralUtility::makeInstance(['foo']);
    }

    /**
     * @test
     */
    public function makeInstanceWithBeginningSlashInClassNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1420281366);

        GeneralUtility::makeInstance('\\TYPO3\\CMS\\Backend\\Controller\\BackendController');
    }

    /**
     * @test
     */
    public function makeInstanceReturnsClassInstance(): void
    {
        $className = get_class($this->getMockBuilder('foo')->getMock());
        self::assertInstanceOf($className, GeneralUtility::makeInstance($className));
    }

    /**
     * @test
     */
    public function makeInstancePassesParametersToConstructor(): void
    {
        $instance = GeneralUtility::makeInstance(TwoParametersConstructorFixture::class, 'one parameter', 'another parameter');
        self::assertEquals('one parameter', $instance->constructorParameter1, 'The first constructor parameter has not been set.');
        self::assertEquals('another parameter', $instance->constructorParameter2, 'The second constructor parameter has not been set.');
    }

    /**
     * @test
     */
    public function makeInstanceInstanciatesConfiguredImplementation(): void
    {
        GeneralUtilityFixture::resetFinalClassNameCache();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][OriginalClassFixture::class] = ['className' => ReplacementClassFixture::class];
        self::assertInstanceOf(ReplacementClassFixture::class, GeneralUtility::makeInstance(OriginalClassFixture::class));
    }

    /**
     * @test
     */
    public function makeInstanceResolvesConfiguredImplementationsRecursively(): void
    {
        GeneralUtilityFixture::resetFinalClassNameCache();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][OriginalClassFixture::class] = ['className' => ReplacementClassFixture::class];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][ReplacementClassFixture::class] = ['className' => OtherReplacementClassFixture::class];
        self::assertInstanceOf(OtherReplacementClassFixture::class, GeneralUtility::makeInstance(OriginalClassFixture::class));
    }

    /**
     * @test
     */
    public function makeInstanceCalledTwoTimesForNonSingletonClassReturnsDifferentInstances(): void
    {
        $className = get_class($this->getMockBuilder('foo')->getMock());
        self::assertNotSame(GeneralUtility::makeInstance($className), GeneralUtility::makeInstance($className));
    }

    /**
     * @test
     */
    public function makeInstanceCalledTwoTimesForSingletonClassReturnsSameInstance(): void
    {
        $className = get_class($this->createMock(SingletonInterface::class));
        self::assertSame(GeneralUtility::makeInstance($className), GeneralUtility::makeInstance($className));
    }

    /**
     * @test
     */
    public function makeInstanceCalledTwoTimesForSingletonClassWithPurgeInstancesInbetweenReturnsDifferentInstances(): void
    {
        $className = get_class($this->createMock(SingletonInterface::class));
        $instance = GeneralUtility::makeInstance($className);
        GeneralUtility::purgeInstances();
        self::assertNotSame($instance, GeneralUtility::makeInstance($className));
    }

    /**
     * @test
     */
    public function makeInstanceInjectsLogger(): void
    {
        $instance = GeneralUtility::makeInstance(GeneralUtilityMakeInstanceInjectLoggerFixture::class);
        self::assertInstanceOf(LoggerInterface::class, $instance->getLogger());
    }

    /**
     * @test
     */
    public function setSingletonInstanceForEmptyClassNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288967479);

        $instance = $this->createMock(SingletonInterface::class);
        GeneralUtility::setSingletonInstance('', $instance);
    }

    /**
     * @test
     */
    public function setSingletonInstanceForClassThatIsNoSubclassOfProvidedClassThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288967686);

        $instance = $this->getMockBuilder(SingletonInterface::class)
            ->addMethods(['foo'])
            ->getMock();
        $singletonClassName = get_class($this->createMock(SingletonInterface::class));
        GeneralUtility::setSingletonInstance($singletonClassName, $instance);
    }

    /**
     * @test
     */
    public function setSingletonInstanceMakesMakeInstanceReturnThatInstance(): void
    {
        $instance = $this->createMock(SingletonInterface::class);
        $singletonClassName = get_class($instance);
        GeneralUtility::setSingletonInstance($singletonClassName, $instance);
        self::assertSame($instance, GeneralUtility::makeInstance($singletonClassName));
    }

    /**
     * @test
     */
    public function setSingletonInstanceCalledTwoTimesMakesMakeInstanceReturnLastSetInstance(): void
    {
        $instance1 = $this->createMock(SingletonInterface::class);
        $singletonClassName = get_class($instance1);
        $instance2 = new $singletonClassName();
        GeneralUtility::setSingletonInstance($singletonClassName, $instance1);
        GeneralUtility::setSingletonInstance($singletonClassName, $instance2);
        self::assertSame($instance2, GeneralUtility::makeInstance($singletonClassName));
    }

    /**
     * @test
     */
    public function getSingletonInstancesContainsPreviouslySetSingletonInstance(): void
    {
        $instance = $this->createMock(SingletonInterface::class);
        $instanceClassName = get_class($instance);
        GeneralUtility::setSingletonInstance($instanceClassName, $instance);
        $registeredSingletonInstances = GeneralUtility::getSingletonInstances();
        self::assertArrayHasKey($instanceClassName, $registeredSingletonInstances);
        self::assertSame($registeredSingletonInstances[$instanceClassName], $instance);
    }

    /**
     * @test
     */
    public function setSingletonInstanceReturnsFinalClassNameWithOverriddenClass(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][SingletonClassFixture::class]['className'] = ExtendedSingletonClassFixture::class;
        $anotherInstance = new ExtendedSingletonClassFixture();
        GeneralUtility::makeInstance(SingletonClassFixture::class);
        GeneralUtility::setSingletonInstance(SingletonClassFixture::class, $anotherInstance);
        $result = GeneralUtility::makeInstance(SingletonClassFixture::class);
        self::assertSame($anotherInstance, $result);
        self::assertEquals(ExtendedSingletonClassFixture::class, get_class($anotherInstance));
    }

    /**
     * @test
     */
    public function resetSingletonInstancesResetsPreviouslySetInstance(): void
    {
        $instance = $this->createMock(SingletonInterface::class);
        $instanceClassName = get_class($instance);
        GeneralUtility::setSingletonInstance($instanceClassName, $instance);
        GeneralUtility::resetSingletonInstances([]);
        $registeredSingletonInstances = GeneralUtility::getSingletonInstances();
        self::assertArrayNotHasKey($instanceClassName, $registeredSingletonInstances);
    }

    /**
     * @test
     */
    public function resetSingletonInstancesSetsGivenInstance(): void
    {
        $instance = $this->createMock(SingletonInterface::class);
        $instanceClassName = get_class($instance);
        GeneralUtility::resetSingletonInstances(
            [$instanceClassName => $instance]
        );
        $registeredSingletonInstances = GeneralUtility::getSingletonInstances();
        self::assertArrayHasKey($instanceClassName, $registeredSingletonInstances);
        self::assertSame($registeredSingletonInstances[$instanceClassName], $instance);
    }

    /**
     * @test
     */
    public function addInstanceForEmptyClassNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288967479);

        $instance = $this->getMockBuilder('foo')->getMock();
        GeneralUtility::addInstance('', $instance);
    }

    /**
     * @test
     */
    public function addInstanceForClassThatIsNoSubclassOfProvidedClassThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288967686);

        $instance = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['bar'])
            ->getMock();
        $singletonClassName = get_class($this->createMock(\stdClass::class));
        GeneralUtility::addInstance($singletonClassName, $instance);
    }

    /**
     * @test
     */
    public function addInstanceWithSingletonInstanceThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288969325);

        $instance = $this->createMock(SingletonInterface::class);
        GeneralUtility::addInstance(get_class($instance), $instance);
    }

    /**
     * @test
     */
    public function addInstanceMakesMakeInstanceReturnThatInstance(): void
    {
        $instance = $this->createMock('stdClass');
        $className = get_class($instance);
        GeneralUtility::addInstance($className, $instance);
        self::assertSame($instance, GeneralUtility::makeInstance($className));
    }

    /**
     * @test
     */
    public function makeInstanceCalledTwoTimesAfterAddInstanceReturnTwoDifferentInstances(): void
    {
        $instance = $this->createMock('stdClass');
        $className = get_class($instance);
        GeneralUtility::addInstance($className, $instance);
        self::assertNotSame(GeneralUtility::makeInstance($className), GeneralUtility::makeInstance($className));
    }

    /**
     * @test
     */
    public function addInstanceCalledTwoTimesMakesMakeInstanceReturnBothInstancesInAddingOrder(): void
    {
        $instance1 = $this->createMock('stdClass');
        $className = get_class($instance1);
        GeneralUtility::addInstance($className, $instance1);
        $instance2 = new $className();
        GeneralUtility::addInstance($className, $instance2);
        self::assertSame($instance1, GeneralUtility::makeInstance($className), 'The first returned instance does not match the first added instance.');
        self::assertSame($instance2, GeneralUtility::makeInstance($className), 'The second returned instance does not match the second added instance.');
    }

    /**
     * @test
     */
    public function purgeInstancesDropsAddedInstance(): void
    {
        $instance = $this->createMock('stdClass');
        $className = get_class($instance);
        GeneralUtility::addInstance($className, $instance);
        GeneralUtility::purgeInstances();
        self::assertNotSame($instance, GeneralUtility::makeInstance($className));
    }

    /**
     * @return array
     */
    public function getFileAbsFileNameDataProvider(): array
    {
        return [
            'relative path is prefixed with public path' => [
                'fileadmin/foo.txt',
                Environment::getPublicPath() . '/fileadmin/foo.txt',
            ],
            'relative path, referencing current directory is prefixed with public path' => [
                './fileadmin/foo.txt',
                Environment::getPublicPath() . '/./fileadmin/foo.txt',
            ],
            'relative paths with back paths are not allowed and returned empty' => [
                '../fileadmin/foo.txt',
                '',
            ],
            'absolute paths with back paths are not allowed and returned empty' => [
                Environment::getPublicPath() . '/../sysext/core/Resources/Public/Icons/Extension.png',
                '',
            ],
            'allowed absolute paths are returned as is' => [
                Environment::getPublicPath() . '/fileadmin/foo.txt',
                Environment::getPublicPath() . '/fileadmin/foo.txt',
            ],
            'disallowed absolute paths are returned empty' => [
                '/somewhere/fileadmin/foo.txt',
                '',
            ],
            'EXT paths are resolved to absolute paths' => [
                'EXT:foo/Resources/Private/Templates/Home.html',
                '/path/to/foo/Resources/Private/Templates/Home.html',
            ],
        ];
    }

    /**
     * @param string $path
     * @param string $expected
     * @test
     * @dataProvider getFileAbsFileNameDataProvider
     */
    public function getFileAbsFileNameReturnsCorrectValues(string $path, string $expected): void
    {
        // build the dummy package "foo" for use in ExtensionManagementUtility::extPath('foo');
        $package = $this->getMockBuilder(Package::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPackagePath'])
            ->getMock();
        /** @var PackageManager|\PHPUnit\Framework\MockObject\MockObject $packageManager */
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->onlyMethods(['isPackageActive', 'getPackage', 'getActivePackages'])
            ->disableOriginalConstructor()
            ->getMock();
        $package->expects(self::any())
            ->method('getPackagePath')
            ->willReturn('/path/to/foo/');
        $packageManager->expects(self::any())
            ->method('getActivePackages')
            ->willReturn(['foo' => $package]);
        $packageManager->expects(self::any())
            ->method('isPackageActive')
            ->with(self::equalTo('foo'))
            ->willReturn(true);
        $packageManager->expects(self::any())
            ->method('getPackage')
            ->with('foo')
            ->willReturn($package);
        ExtensionManagementUtility::setPackageManager($packageManager);

        $result = GeneralUtility::getFileAbsFileName($path);
        self::assertEquals($expected, $result);
    }

    /**
     * Data provider for validPathStrDetectsInvalidCharacters.
     *
     * @return array
     */
    public function validPathStrInvalidCharactersDataProvider(): array
    {
        $data = [
            'double slash in path' => ['path//path'],
            'backslash in path' => ['path\\path'],
            'directory up in path' => ['path/../path'],
            'directory up at the beginning' => ['../path'],
            'NUL character in path' => ['path' . "\0" . 'path'],
            'BS character in path' => ['path' . chr(8) . 'path'],
            'invalid UTF-8-sequence' => ["\xc0" . 'path/path'],
            'Could be overlong NUL in some UTF-8 implementations, invalid in RFC3629' => ["\xc0\x80" . 'path/path'],
        ];

        // Mixing with regular utf-8
        $utf8Characters = 'Ссылка/';
        foreach ($data as $key => $value) {
            $data[$key . ' with UTF-8 characters prepended'] = [$utf8Characters . $value[0]];
            $data[$key . ' with UTF-8 characters appended'] = [$value[0] . $utf8Characters];
        }

        // Encoding with UTF-16
        foreach ($data as $key => $value) {
            $data[$key . ' encoded with UTF-16'] = [mb_convert_encoding($value[0], 'UTF-16')];
        }

        return $data;
    }

    /**
     * Tests whether invalid characters are detected.
     *
     * @param string $path
     * @dataProvider validPathStrInvalidCharactersDataProvider
     * @test
     */
    public function validPathStrDetectsInvalidCharacters(string $path): void
    {
        self::assertFalse(GeneralUtility::validPathStr($path));
    }

    /**
     * Data provider for positive values within validPathStr()
     */
    public function validPathStrDataProvider(): array
    {
        $data = [
            'normal ascii path' => ['fileadmin/templates/myfile..xml'],
            'special character' => ['fileadmin/templates/Ссылка (fce).xml'],
        ];

        return $data;
    }

    /**
     * Tests whether Unicode characters are recognized as valid file name characters.
     *
     * @dataProvider validPathStrDataProvider
     * @test
     */
    public function validPathStrWorksWithUnicodeFileNames($path): void
    {
        self::assertTrue(GeneralUtility::validPathStr($path));
    }

    /////////////////////////////////////////////////////////////////////////////////////
    // Tests concerning copyDirectory
    /////////////////////////////////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function copyDirectoryCopiesFilesAndDirectoriesWithRelativePaths(): void
    {
        $sourceDirectory = 'typo3temp/var/tests/' . StringUtility::getUniqueId('test_') . '/';
        $absoluteSourceDirectory = Environment::getPublicPath() . '/' . $sourceDirectory;
        $this->testFilesToDelete[] = $absoluteSourceDirectory;
        GeneralUtility::mkdir($absoluteSourceDirectory);

        $targetDirectory = 'typo3temp/var/tests/' . StringUtility::getUniqueId('test_') . '/';
        $absoluteTargetDirectory = Environment::getPublicPath() . '/' . $targetDirectory;
        $this->testFilesToDelete[] = $absoluteTargetDirectory;

        GeneralUtility::writeFileToTypo3tempDir($absoluteSourceDirectory . 'file', '42');
        GeneralUtility::mkdir($absoluteSourceDirectory . 'foo');
        GeneralUtility::writeFileToTypo3tempDir($absoluteSourceDirectory . 'foo/file', '42');

        GeneralUtility::copyDirectory($sourceDirectory, $targetDirectory);

        self::assertFileExists($absoluteTargetDirectory . 'file');
        self::assertFileExists($absoluteTargetDirectory . 'foo/file');
    }

    /**
     * @test
     */
    public function copyDirectoryCopiesFilesAndDirectoriesWithAbsolutePaths(): void
    {
        $sourceDirectory = 'typo3temp/var/tests/' . StringUtility::getUniqueId('test_') . '/';
        $absoluteSourceDirectory = Environment::getPublicPath() . '/' . $sourceDirectory;
        $this->testFilesToDelete[] = $absoluteSourceDirectory;
        GeneralUtility::mkdir($absoluteSourceDirectory);

        $targetDirectory = 'typo3temp/var/tests/' . StringUtility::getUniqueId('test_') . '/';
        $absoluteTargetDirectory = Environment::getPublicPath() . '/' . $targetDirectory;
        $this->testFilesToDelete[] = $absoluteTargetDirectory;

        GeneralUtility::writeFileToTypo3tempDir($absoluteSourceDirectory . 'file', '42');
        GeneralUtility::mkdir($absoluteSourceDirectory . 'foo');
        GeneralUtility::writeFileToTypo3tempDir($absoluteSourceDirectory . 'foo/file', '42');

        GeneralUtility::copyDirectory($absoluteSourceDirectory, $absoluteTargetDirectory);

        self::assertFileExists($absoluteTargetDirectory . 'file');
        self::assertFileExists($absoluteTargetDirectory . 'foo/file');
    }

    /////////////////////////////////////////////////////////////////////////////////////
    // Tests concerning deprecation log
    /////////////////////////////////////////////////////////////////////////////////////

    ///////////////////////////////////////////////////
    // Tests concerning callUserFunction
    ///////////////////////////////////////////////////

    /**
     * @test
     * @dataProvider callUserFunctionInvalidParameterDataprovider
     * @param string $functionName
     * @param int $expectedException
     */
    public function callUserFunctionWillThrowExceptionForInvalidParameters(string $functionName, int $expectedException): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode($expectedException);

        $inputData = ['foo' => 'bar'];
        GeneralUtility::callUserFunction($functionName, $inputData, $this);
    }

    /**
     * Data provider for callUserFunctionInvalidParameterDataprovider and
     * callUserFunctionWillThrowExceptionForInvalidParameters.
     *
     * @return array
     */
    public function callUserFunctionInvalidParameterDataprovider(): array
    {
        return [
            'Function is not prefixed' => [self::class . '->calledUserFunction', 1294585865],
            'Class doesn\'t exists' => ['t3lib_divTest21345->user_calledUserFunction', 1294585866],
            'No method name' => [self::class, 1294585867],
            'No class name' => ['->user_calledUserFunction', 1294585866],
            'No function name' => ['', 1294585867],
        ];
    }

    /**
     * Above tests already showed that the prefix is checked properly,
     * therefore this test skips the prefix and enables to inline the instantly
     * created function (who's name doesn't have a prefix).
     *
     * @test
     */
    public function callUserFunctionCanCallFunction(): void
    {
        $inputData = ['foo' => 'bar'];
        $result = GeneralUtility::callUserFunction(static function () {
            return 'Worked fine';
        }, $inputData, $this, '');
        self::assertEquals('Worked fine', $result);
    }

    /**
     * @test
     */
    public function callUserFunctionCanCallMethod(): void
    {
        $inputData = ['foo' => 'bar'];
        $result = GeneralUtility::callUserFunction(self::class . '->user_calledUserFunction', $inputData, $this);
        self::assertEquals('Worked fine', $result);
    }

    /**
     * @return string
     */
    public function user_calledUserFunction(): string
    {
        return 'Worked fine';
    }

    /**
     * @test
     */
    public function callUserFunctionAcceptsClosures(): void
    {
        $inputData = ['foo' => 'bar'];
        $closure = static function ($parameters, $reference) use ($inputData) {
            $reference->assertEquals($inputData, $parameters, 'Passed data doesn\'t match expected output');
            return 'Worked fine';
        };
        self::assertEquals('Worked fine', GeneralUtility::callUserFunction($closure, $inputData, $this));
    }

    /**
     * @test
     */
    public function callUserFunctionTrimsSpaces(): void
    {
        $inputData = ['foo' => 'bar'];
        $result = GeneralUtility::callUserFunction("\t" . self::class . '->user_calledUserFunction ', $inputData, $this);
        self::assertEquals('Worked fine', $result);
    }

    /**
     * @test
     */
    public function getAllFilesAndFoldersInPathReturnsArrayWithMd5Keys(): void
    {
        $directory = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('directory_');
        mkdir($directory);
        $filesAndDirectories = GeneralUtility::getAllFilesAndFoldersInPath([], $directory, '', true);
        $check = true;
        foreach ($filesAndDirectories as $md5 => $path) {
            if (!preg_match('/^[a-f0-9]{32}$/', $md5)) {
                $check = false;
            }
        }
        GeneralUtility::rmdir($directory);
        self::assertTrue($check);
    }

    /**
     * If the element is not empty, its contents might be treated as "something" (instead of "nothing")
     * e.g. by Fluid view helpers, which is why we want to avoid that.
     *
     * @test
     */
    public function array2xmlConvertsEmptyArraysToElementWithoutContent(): void
    {
        $input = [
            'el' => [],
        ];

        $output = GeneralUtility::array2xml($input);

        self::assertEquals('<phparray>
	<el type="array"></el>
</phparray>', $output);
    }

    /**
     * @test
     */
    public function xml2arrayUsesCache(): void
    {
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('runtime')->willReturn($cacheProphecy->reveal());
        $cacheProphecy->get('generalUtilityXml2Array')->shouldBeCalled()->willReturn(false);
        $cacheProphecy->set('generalUtilityXml2Array', Argument::cetera())->shouldBeCalled();
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
        GeneralUtility::xml2array('<?xml version="1.0" encoding="utf-8" standalone="yes"?>', 'T3:');
    }

    /**
     * @return string[][]
     */
    public function xml2arrayProcessHandlesWhitespacesDataProvider(): array
    {
        $headerVariants = [
            'utf-8' => '<?xml version="1.0" encoding="utf-8" standalone="yes"?>',
            'UTF-8' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>',
            'no-encoding' => '<?xml version="1.0" standalone="yes"?>',
            'iso-8859-1' => '<?xml version="1.0" encoding="iso-8859-1" standalone="yes"?>',
            'ISO-8859-1' => '<?xml version="1.0" encoding="ISO-8859-1" standalone="yes"?>',
        ];
        $data = [];
        foreach ($headerVariants as $identifier => $headerVariant) {
            $data += [
                'inputWithoutWhitespaces-' . $identifier => [
                    $headerVariant . '<T3FlexForms>
                        <data>
                            <field index="settings.persistenceIdentifier">
                                <value index="vDEF">egon</value>
                            </field>
                        </data>
                    </T3FlexForms>',
                ],
                'inputWithPrecedingWhitespaces-' . $identifier => [
                    CR . ' ' . $headerVariant . '<T3FlexForms>
                        <data>
                            <field index="settings.persistenceIdentifier">
                                <value index="vDEF">egon</value>
                            </field>
                        </data>
                    </T3FlexForms>',
                ],
                'inputWithTrailingWhitespaces-' . $identifier => [
                    $headerVariant . '<T3FlexForms>
                        <data>
                            <field index="settings.persistenceIdentifier">
                                <value index="vDEF">egon</value>
                            </field>
                        </data>
                    </T3FlexForms>' . CR . ' ',
                ],
                'inputWithPrecedingAndTrailingWhitespaces-' . $identifier => [
                    CR . ' ' . $headerVariant . '<T3FlexForms>
                        <data>
                            <field index="settings.persistenceIdentifier">
                                <value index="vDEF">egon</value>
                            </field>
                        </data>
                    </T3FlexForms>' . CR . ' ',
                ],
            ];
        }
        return $data;
    }

    /**
     * @test
     * @dataProvider xml2arrayProcessHandlesWhitespacesDataProvider
     */
    public function xml2arrayProcessHandlesWhitespaces(string $input): void
    {
        $expected = [
            'data' => [
                'settings.persistenceIdentifier' => [
                    'vDEF' => 'egon',
                ],
            ],
        ];
        self::assertSame($expected, GeneralUtility::xml2arrayProcess($input));
    }

    /**
     * @return string[][]
     */
    public function xml2arrayProcessHandlesTagNamespacesDataProvider(): array
    {
        return [
            'inputWithNameSpaceOnRootLevel' => [
                '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
                <T3:T3FlexForms>
                    <data>
                        <field index="settings.persistenceIdentifier">
                            <value index="vDEF">egon</value>
                        </field>
                    </data>
                </T3:T3FlexForms>',
            ],
            'inputWithNameSpaceOnNonRootLevel' => [
                '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
                <T3FlexForms>
                    <data>
                        <T3:field index="settings.persistenceIdentifier">
                            <value index="vDEF">egon</value>
                        </T3:field>
                    </data>
                </T3FlexForms>',
            ],
            'inputWithNameSpaceOnRootAndNonRootLevel' => [
                '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
                <T3:T3FlexForms>
                    <data>
                        <T3:field index="settings.persistenceIdentifier">
                            <value index="vDEF">egon</value>
                        </T3:field>
                    </data>
                </T3:T3FlexForms>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider xml2arrayProcessHandlesTagNamespacesDataProvider
     */
    public function xml2arrayProcessHandlesTagNamespaces(string $input): void
    {
        $expected = [
            'data' => [
                'settings.persistenceIdentifier' => [
                    'vDEF' => 'egon',
                ],
            ],
        ];
        self::assertSame($expected, GeneralUtility::xml2arrayProcess($input, 'T3:'));
    }

    /**
     * @return array[]
     */
    public function xml2arrayProcessHandlesDocumentTagDataProvider(): array
    {
        return [
            'input' => [
                '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
                <T3FlexForms>
                    <data>
                        <field index="settings.persistenceIdentifier">
                            <value index="vDEF">egon</value>
                        </field>
                    </data>
                </T3FlexForms>',
                'T3FlexForms',
            ],
            'input-with-root-namespace' => [
                '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
                <T3:T3FlexForms>
                    <data>
                        <field index="settings.persistenceIdentifier">
                            <value index="vDEF">egon</value>
                        </field>
                    </data>
                </T3:T3FlexForms>',
                'T3:T3FlexForms',
            ],
            'input-with-namespace' => [
                '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
                <T3FlexForms>
                    <data>
                        <T3:field index="settings.persistenceIdentifier">
                            <value index="vDEF">egon</value>
                        </T3:field>
                    </data>
                </T3FlexForms>',
                'T3FlexForms',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider xml2arrayProcessHandlesDocumentTagDataProvider
     */
    public function xml2arrayProcessHandlesDocumentTag(string $input, string $docTag): void
    {
        $expected = [
            'data' => [
                'settings.persistenceIdentifier' => [
                    'vDEF' => 'egon',
                ],
            ],
            '_DOCUMENT_TAG' => $docTag,
        ];
        self::assertSame($expected, GeneralUtility::xml2arrayProcess($input, '', true));
    }

    /**
     * @return array[]
     */
    public function xml2ArrayProcessHandlesBigXmlContentDataProvider(): array
    {
        return [
            '1mb' => [
                '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
                <T3:T3FlexForms>
                    <data>
                        <field index="settings.persistenceIdentifier">
                            <value index="vDEF">' . str_repeat('1', 1024 * 1024) . '</value>
                        </field>
                    </data>
                </T3:T3FlexForms>',
                str_repeat('1', 1024 * 1024),
            ],
            '5mb' => [
                '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
                <T3:T3FlexForms>
                    <data>
                        <field index="settings.persistenceIdentifier">
                            <value index="vDEF">' . str_repeat('1', 5 * 1024 * 1024) . '</value>
                        </field>
                    </data>
                </T3:T3FlexForms>',
                str_repeat('1', 5 * 1024 * 1024),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider xml2ArrayProcessHandlesBigXmlContentDataProvider
     * @param string $input
     * @param string $testValue
     */
    public function xml2ArrayProcessHandlesBigXmlContent(string $input, string $testValue): void
    {
        $expected = [
            'data' => [
                'settings.persistenceIdentifier' => [
                    'vDEF' => $testValue,
                ],
            ],
        ];
        self::assertSame($expected, GeneralUtility::xml2arrayProcess($input));
    }

    /**
     * @todo: The parser run into a memory issue with files bigger 10 MB
     * @todo: This special tests documents the issue. If fixed, this test
     * @todo: should become a data set of xml2ArrayHandlesBigXmlFilesDataProvider()
     * @todo: This test does not pass in all environments. It should be evaluated whether this test is really needed or should be removed.
     *
     * @see https://forge.typo3.org/issues/83580
     *
     * @test
     */
    public function xml2ArrayFailsWithXmlContentBiggerThanTenMegabytes(): void
    {
        self::markTestSkipped('This test does not pass in all environments. It should be evaluated whether this test is really needed or should be removed.');
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('runtime')->willReturn($cacheProphecy->reveal());
        $cacheProphecy->get('generalUtilityXml2Array')->shouldBeCalled()->willReturn(false);
        $cacheProphecy->set('generalUtilityXml2Array', Argument::cetera())->shouldBeCalled();
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
        $input = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
            <T3:T3FlexForms>
                <data>
                    <field index="settings.persistenceIdentifier">
                        <value index="vDEF">' . str_repeat('1', 10 * 1024 * 1024) . '</value>
                    </field>
                </data>
            </T3:T3FlexForms>';
        self::assertStringContainsString('No memory', GeneralUtility::xml2array($input));
    }

    /**
     * @return array[]
     */
    public function xml2ArrayProcessHandlesAttributeTypesDataProvider(): array
    {
        $prefix = '<?xml version="1.0" encoding="utf-8" standalone="yes"?><T3FlexForms><field index="index">';
        $suffix = '</field></T3FlexForms>';
        return [
            'no-type string' => [
                $prefix . '<value index="vDEF">foo bar</value>' . $suffix,
                'foo bar',
            ],
            'no-type integer' => [
                $prefix . '<value index="vDEF">123</value>' . $suffix,
                '123',
            ],
            'no-type double' => [
                $prefix . '<value index="vDEF">1.23</value>' . $suffix,
                '1.23',
            ],
            'integer integer' => [
                $prefix . '<value index="vDEF" type="integer">123</value>' . $suffix,
                123,
            ],
            'integer double' => [
                $prefix . '<value index="vDEF" type="integer">1.23</value>' . $suffix,
                1,
            ],
            'double integer' => [
                $prefix . '<value index="vDEF" type="double">123</value>' . $suffix,
                123.0,
            ],
            'double double' => [
                $prefix . '<value index="vDEF" type="double">1.23</value>' . $suffix,
                1.23,
            ],
            'boolean 0' => [
                $prefix . '<value index="vDEF" type="boolean">0</value>' . $suffix,
                false,
            ],
            'boolean 1' => [
                $prefix . '<value index="vDEF" type="boolean">1</value>' . $suffix,
                true,
            ],
            'boolean true' => [
                $prefix . '<value index="vDEF" type="boolean">true</value>' . $suffix,
                true,
            ],
            'boolean false' => [
                $prefix . '<value index="vDEF" type="boolean">false</value>' . $suffix,
                true, // sic(!)
            ],
            'NULL' => [
                $prefix . '<value index="vDEF" type="NULL"></value>' . $suffix,
                null,
            ],
            'NULL string' => [
                $prefix . '<value index="vDEF" type="NULL">foo bar</value>' . $suffix,
                null,
            ],
            'NULL integer' => [
                $prefix . '<value index="vDEF" type="NULL">123</value>' . $suffix,
                null,
            ],
            'NULL double' => [
                $prefix . '<value index="vDEF" type="NULL">1.23</value>' . $suffix,
                null,
            ],
            'array' => [
                $prefix . '<value index="vDEF" type="array"></value>' . $suffix,
                [],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider xml2ArrayProcessHandlesAttributeTypesDataProvider
     * @param string $input
     * @param $expected
     */
    public function xml2ArrayProcessHandlesAttributeTypes(string $input, $expected): void
    {
        $result = GeneralUtility::xml2arrayProcess($input);
        self::assertSame($expected, $result['index']['vDEF']);
    }

    public function locationHeaderUrlDataProvider(): array
    {
        return [
            'simple relative path' => [
                'foo',
                'foo.bar.test',
                'http://foo.bar.test/foo',
            ],
            'path beginning with slash' => [
                '/foo',
                'foo.bar.test',
                'http://foo.bar.test/foo',
            ],
            'path with full domain and https scheme' => [
                'https://example.com/foo',
                'foo.bar.test',
                'https://example.com/foo',
            ],
            'path with full domain and http scheme' => [
                'http://example.com/foo',
                'foo.bar.test',
                'http://example.com/foo',
            ],
            'path with full domain and relative scheme' => [
                '//example.com/foo',
                'foo.bar.test',
                '//example.com/foo',
            ],
        ];
    }

    /**
     * @test
     * @param string $path
     * @param string $host
     * @param string $expected
     * @dataProvider locationHeaderUrlDataProvider
     * @throws \TYPO3\CMS\Core\Exception
     */
    public function locationHeaderUrl(string $path, string $host, string $expected): void
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            false,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $result = GeneralUtility::locationHeaderUrl($path);
        self::assertSame($expected, $result);
    }
}
