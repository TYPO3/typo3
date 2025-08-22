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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Tests\Unit\Utility\AccessibleProxies\ExtensionManagementUtilityAccessibleProxy;
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\ExtendedSingletonClassFixture;
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\GeneralUtilityMakeInstanceInjectLoggerFixture;
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\GeneralUtilityTestClass;
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\OriginalClassFixture;
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\OtherReplacementClassFixture;
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\ReplacementClassFixture;
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\SingletonClassFixture;
use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\TwoParametersConstructorFixture;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class GeneralUtilityTest extends UnitTestCase
{
    public const NO_FIX_PERMISSIONS_ON_WINDOWS = 'fixPermissions() not available on Windows (method does nothing)';

    protected bool $resetSingletonInstances = true;

    protected bool $backupEnvironment = true;

    protected ?PackageManager $backupPackageManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupPackageManager = ExtensionManagementUtilityAccessibleProxy::getPackageManager();
    }

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
     * Helper method to create a random directory and return the path.
     * The path will be registered for deletion upon test ending
     */
    protected function getTestDirectory(string $prefix = 'root_'): string
    {
        $path = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId($prefix);
        GeneralUtility::mkdir_deep($path);
        $this->testFilesToDelete[] = $path;
        return $path;
    }

    /**
     * @return array<string, array{0: string, 1: string}>
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

    #[DataProvider('cmpIPv4DataProviderMatching')]
    #[Test]
    public function cmpIPv4ReturnsTrueForMatchingAddress(string $ip, string $list): void
    {
        self::assertTrue(GeneralUtility::cmpIPv4($ip, $list));
    }

    /**
     * Data provider for cmpIPv4ReturnsFalseForNotMatchingAddress
     *
     * @return array<string, array{0: string, 1: string}>
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

    #[DataProvider('cmpIPv4DataProviderNotMatching')]
    #[Test]
    public function cmpIPv4ReturnsFalseForNotMatchingAddress(string $ip, string $list): void
    {
        self::assertFalse(GeneralUtility::cmpIPv4($ip, $list));
    }

    ///////////////////////////
    // Tests concerning cmpIPv6
    ///////////////////////////
    /**
     * Data provider for cmpIPv6ReturnsTrueForMatchingAddress
     *
     * @return array<string, array{0: string, 1: string}>
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

    #[DataProvider('cmpIPv6DataProviderMatching')]
    #[Test]
    public function cmpIPv6ReturnsTrueForMatchingAddress(string $ip, string $list): void
    {
        self::assertTrue(GeneralUtility::cmpIPv6($ip, $list));
    }

    /**
     * Data provider for cmpIPv6ReturnsFalseForNotMatchingAddress
     *
     * @return array<string, array{0: string, 1: string}>
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

    #[DataProvider('cmpIPv6DataProviderNotMatching')]
    #[Test]
    public function cmpIPv6ReturnsFalseForNotMatchingAddress(string $ip, string $list): void
    {
        self::assertFalse(GeneralUtility::cmpIPv6($ip, $list));
    }

    /////////////////////////////////
    // Tests concerning normalizeIPv6
    /////////////////////////////////
    /**
     * Data provider for normalizeIPv6ReturnsCorrectlyNormalizedFormat
     *
     * @return array<string, array{0: string, 1: string}>
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

    #[DataProvider('normalizeCompressIPv6DataProviderCorrect')]
    #[Test]
    public function normalizeIPv6CorrectlyNormalizesAddresses(string $compressed, string $normalized): void
    {
        self::assertEquals($normalized, GeneralUtility::normalizeIPv6($compressed));
    }

    ///////////////////////////////
    // Tests concerning validIP
    ///////////////////////////////
    /**
     * Data provider for checkValidIpReturnsTrueForValidIp
     *
     * @return array<string, array{0: string}>
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

    #[DataProvider('validIpDataProvider')]
    #[Test]
    public function validIpReturnsTrueForValidIp(string $ip): void
    {
        self::assertTrue(GeneralUtility::validIP($ip));
    }

    /**
     * Data provider for checkValidIpReturnsFalseForInvalidIp
     *
     * @return array<string, array{0: string}>
     */
    public static function invalidIpDataProvider(): array
    {
        return [
            'zero string' => ['0'],
            'string' => ['test'],
            'string empty' => [''],
            'string NULL' => ['NULL'],
            'out of bounds IPv4' => ['300.300.300.300'],
            'dotted decimal notation with only two dots' => ['127.0.1'],
        ];
    }

    #[DataProvider('invalidIpDataProvider')]
    #[Test]
    public function validIpReturnsFalseForInvalidIp(string $ip): void
    {
        self::assertFalse(GeneralUtility::validIP($ip));
    }

    ///////////////////////////////
    // Tests concerning cmpFQDN
    ///////////////////////////////
    /**
     * Data provider for cmpFqdnReturnsTrue
     *
     * @return array<string, array{0: string, 1: string}>
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

    #[DataProvider('cmpFqdnValidDataProvider')]
    #[Test]
    public function cmpFqdnReturnsTrue(string $baseHost, string $list): void
    {
        self::assertTrue(GeneralUtility::cmpFQDN($baseHost, $list));
    }

    /**
     * Data provider for cmpFqdnReturnsFalse
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function cmpFqdnInvalidDataProvider(): array
    {
        return [
            'num-parts of hostname to check can only be less or equal than hostname, 1' => ['aaa.bbb.ccc.ddd.eee', 'aaa.bbb.ccc.ddd.eee.fff'],
            'num-parts of hostname to check can only be less or equal than hostname, 2' => ['aaa.bbb.ccc.ddd.eee', 'aaa.*.bbb.ccc.ddd.eee'],
        ];
    }

    #[DataProvider('cmpFqdnInvalidDataProvider')]
    #[Test]
    public function cmpFqdnReturnsFalse(string $baseHost, string $list): void
    {
        self::assertFalse(GeneralUtility::cmpFQDN($baseHost, $list));
    }

    ///////////////////////////////
    // Tests concerning inList
    ///////////////////////////////
    #[DataProvider('inListForItemContainedReturnsTrueDataProvider')]
    #[Test]
    public function inListForItemContainedReturnsTrue(string $haystack): void
    {
        self::assertTrue(GeneralUtility::inList($haystack, 'findme'));
    }

    /**
     * Data provider for inListForItemContainedReturnsTrue.
     */
    public static function inListForItemContainedReturnsTrueDataProvider(): array
    {
        return [
            'Element as second element of four items' => ['one,findme,three,four'],
            'Element at beginning of list' => ['findme,one,two'],
            'Element at end of list' => ['one,two,findme'],
            'One item list' => ['findme'],
        ];
    }

    #[DataProvider('inListForItemNotContainedReturnsFalseDataProvider')]
    #[Test]
    public function inListForItemNotContainedReturnsFalse(string $haystack): void
    {
        self::assertFalse(GeneralUtility::inList($haystack, 'findme'));
    }

    /**
     * Data provider for inListForItemNotContainedReturnsFalse.
     */
    public static function inListForItemNotContainedReturnsFalseDataProvider(): array
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
    #[DataProvider('expandListExpandsIntegerRangesDataProvider')]
    #[Test]
    public function expandListExpandsIntegerRanges(string $list, string $expectation): void
    {
        self::assertSame($expectation, GeneralUtility::expandList($list));
    }

    /**
     * Data provider for expandListExpandsIntegerRangesDataProvider
     */
    public static function expandListExpandsIntegerRangesDataProvider(): array
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

    #[Test]
    public function expandListExpandsForTwoThousandElementsExpandsOnlyToThousandElementsMaximum(): void
    {
        $list = GeneralUtility::expandList('1-2000');
        self::assertCount(1000, explode(',', $list));
    }

    ///////////////////////////////
    // Tests concerning formatSize
    ///////////////////////////////
    #[DataProvider('formatSizeDataProvider')]
    #[Test]
    public function formatSizeTranslatesBytesToHigherOrderRepresentation($size, $labels, $base, $expected): void
    {
        self::assertEquals($expected, GeneralUtility::formatSize($size, $labels, $base));
    }

    /**
     * Data provider for formatSizeTranslatesBytesToHigherOrderRepresentation
     */
    public static function formatSizeDataProvider(): array
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
    public static function splitCalcDataProvider(): array
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

    #[DataProvider('splitCalcDataProvider')]
    #[Test]
    public function splitCalcCorrectlySplitsExpression(array $expected, string $expression): void
    {
        self::assertSame($expected, GeneralUtility::splitCalc($expression, '+-*/'));
    }

    //////////////////////////////////
    // Tests concerning validEmail
    //////////////////////////////////
    /**
     * Data provider for valid validEmail's
     *
     * @return array<string, array{0: string}>
     */
    public static function validEmailValidDataProvider(): array
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
            'space between the quotes' => ['" "@example.com'],
            'quoted double dot' => ['"john..doe"@example.com'],
            'bangified host route used for uucp mailers' => ['mailhost!username@example.com'],
            '% escaped mail route to user@example.com via example.com' => ['user%example.com@example.com'],
            'local-part ending with non-alphanumeric character from the list of allowed printable characters' => ['user-@example.com'],
            'ipv4 addresses are allowed instead of domains when in square brackets, but strongly discouraged' => ['postmaster@[123.123.123.123]'],
            'ipv6 uses a different syntax' => ['postmaster@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:7334]'],
            'spaces in local quoted' => ['"yes spaces are fine"@example.com'],
        ];
    }

    #[DataProvider('validEmailValidDataProvider')]
    #[Test]
    public function validEmailReturnsTrueForValidMailAddress(string $address): void
    {
        self::assertTrue(GeneralUtility::validEmail($address));
    }

    /**
     * Data provider for invalid validEmail's
     *
     * @return array<string, array{0: string}>
     */
    public static function validEmailInvalidDataProvider(): array
    {
        return [
            'empty string' => [''],
            'integer string' => ['42'],
            'float string' => ['42.23'],
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
            'no @ character' => ['Abc.example.com'],
            'only one @ is allowed outside quotation marks' => ['A@b@c@example.com'],
            'quoted strings must be dot separated or the only element making up the local-part' => ['just"not"right@example.com'],
            'local-part is longer than 64 characters' => ['1234567890123456789012345678901234567890123456789012345678901234+x@example.com '],
            'icon characters' => ['QA[icon]CHOCOLATE[icon]@example.com'],
            'space in middle of local' => ['te at@example.com'],
            'leading space(s)' => ['    teat@example.com'],
            'space after @' => ['test@ example.com'],
            'space in domain' => ['test@ex ample.com'],
            'space before @' => ['test @example.com'],
            'unbalanced quotes' => ['unbalanced-quotes"@example.com'],
            'unbalanced quotes with leading spaces' => ['  unbalanced-quotes"@example.com'],
        ];
    }

    #[DataProvider('validEmailInvalidDataProvider')]
    #[Test]
    public function validEmailReturnsFalseForInvalidMailAddress(string $address): void
    {
        self::assertFalse(GeneralUtility::validEmail($address));
    }

    //////////////////////////////////
    // Tests concerning intExplode
    //////////////////////////////////

    public static function intExplodeDataProvider(): array
    {
        return [
            'empty string' => [
                '',
                false,
                [0],
            ],
            'empty string, remove empty' => [
                '',
                true,
                [],
            ],
            'single comma' => [
                ',',
                false,
                [0, 0],
            ],
            'single comma, remove empty' => [
                ',',
                true,
                [],
            ],
            'multi comma' => [
                ',,',
                false,
                [0, 0, 0],
            ],
            'multi comma, remove empty' => [
                ',,',
                true,
                [],
            ],
            'zero' => [
                '0',
                false,
                [0],
            ],
            'zero, remove empty' => [
                '0',
                true,
                [0],
            ],
            'convertStringToInteger' => [
                '1,foo,2',
                false,
                [1, 0, 2],
            ],
            'zerosAreKept' => [
                '0,1, 0, 2,0',
                false,
                [0, 1, 0, 2, 0],
            ],
            'emptyValuesAreKept' => [
                '0,1,, 0, 2,,0',
                false,
                [0, 1, 0, 0, 2, 0, 0],
            ],
            'emptyValuesAreRemoved' => [
                '0,1,, 0, 2,,0',
                true,
                [0, 1, 0, 2, 0],
            ],
        ];
    }

    #[DataProvider('intExplodeDataProvider')]
    #[Test]
    public function intExplodeReturnsExplodedArray(string $input, bool $removeEmpty, array $expected): void
    {
        self::assertSame($expected, GeneralUtility::intExplode(',', $input, $removeEmpty));
    }

    //////////////////////////////////
    // Tests concerning implodeArrayForUrl / explodeUrl2Array
    //////////////////////////////////
    /**
     * Data provider for implodeArrayForUrlBuildsValidParameterString
     */
    public static function implodeArrayForUrlDataProvider(): array
    {
        $valueArray = ['one' => '√', 'two' => 2];
        return [
            'Empty input' => ['foo', [], ''],
            'String parameters' => ['foo', $valueArray, '&foo[one]=%E2%88%9A&foo[two]=2'],
            'Nested array parameters' => ['foo', [$valueArray], '&foo[0][one]=%E2%88%9A&foo[0][two]=2'],
            'Keep blank parameters' => ['foo', ['one' => '√', ''], '&foo[one]=%E2%88%9A&foo[0]='],
        ];
    }

    #[DataProvider('implodeArrayForUrlDataProvider')]
    #[Test]
    public function implodeArrayForUrlBuildsValidParameterString($name, $input, $expected): void
    {
        self::assertSame($expected, GeneralUtility::implodeArrayForUrl($name, $input));
    }

    #[Test]
    public function implodeArrayForUrlCanSkipEmptyParameters(): void
    {
        $input = ['one' => '√', ''];
        $expected = '&foo[one]=%E2%88%9A';
        self::assertSame($expected, GeneralUtility::implodeArrayForUrl('foo', $input, '', true));
    }

    #[Test]
    public function implodeArrayForUrlCanUrlEncodeKeyNames(): void
    {
        $input = ['one' => '√', ''];
        $expected = '&foo%5Bone%5D=%E2%88%9A&foo%5B0%5D=';
        self::assertSame($expected, GeneralUtility::implodeArrayForUrl('foo', $input, '', false, true));
    }

    public static function explodeUrl2ArrayTransformsParameterStringToFlatArrayDataProvider(): array
    {
        return [
            'Empty string' => ['', []],
            'Simple parameter string' => ['&one=%E2%88%9A&two=2', ['one' => '√', 'two' => 2]],
            'Nested parameter string' => ['&foo[one]=%E2%88%9A&two=2', ['foo[one]' => '√', 'two' => 2]],
            'Parameter without value' => ['&one=&two=2', ['one' => '', 'two' => 2]],
            'Nested parameter without value' => ['&foo[one]=&two=2', ['foo[one]' => '', 'two' => 2]],
            'Parameter without equals sign' => ['&one&two=2', ['one' => '', 'two' => 2]],
            'Nested parameter without equals sign' => ['&foo[one]&two=2', ['foo[one]' => '', 'two' => 2]],
        ];
    }

    #[DataProvider('explodeUrl2ArrayTransformsParameterStringToFlatArrayDataProvider')]
    #[Test]
    public function explodeUrl2ArrayTransformsParameterStringToFlatArray(string $input, array $expected): void
    {
        self::assertEquals($expected, GeneralUtility::explodeUrl2Array($input));
    }

    public static function revExplodeDataProvider(): array
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

    #[DataProvider('revExplodeDataProvider')]
    #[Test]
    public function revExplodeCorrectlyExplodesStringForGivenPartsCount($delimiter, $testString, $count, $expectedArray): void
    {
        $actualArray = GeneralUtility::revExplode($delimiter, $testString, $count);
        self::assertEquals($expectedArray, $actualArray);
    }

    #[Test]
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
    #[DataProvider('trimExplodeReturnsCorrectResultDataProvider')]
    #[Test]
    public function trimExplodeReturnsCorrectResult(string $delimiter, string $testString, bool $removeEmpty, int $limit, array $expectedResult): void
    {
        self::assertSame($expectedResult, GeneralUtility::trimExplode($delimiter, $testString, $removeEmpty, $limit));
    }

    public static function trimExplodeReturnsCorrectResultDataProvider(): array
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
            'duplicate values are kept' => [
                ',',
                'a,b,a',
                true,
                0,
                ['a', 'b', 'a'],
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
    public static function getBytesFromSizeMeasurementDataProvider(): array
    {
        return [
            '100 kilo Bytes' => ['102400', '100k'],
            '100 mega Bytes' => ['104857600', '100m'],
            '100 giga Bytes' => ['107374182400', '100g'],
        ];
    }

    #[DataProvider('getBytesFromSizeMeasurementDataProvider')]
    #[Test]
    public function getBytesFromSizeMeasurementCalculatesCorrectByteValue($expected, $byteString): void
    {
        self::assertEquals($expected, GeneralUtility::getBytesFromSizeMeasurement($byteString));
    }

    //////////////////////////////////
    // Tests concerning getIndpEnv
    //////////////////////////////////
    #[Test]
    public function getIndpEnvTypo3SitePathReturnNonEmptyString(): void
    {
        self::assertTrue(strlen(GeneralUtility::getIndpEnv('TYPO3_SITE_PATH')) >= 1);
    }

    #[Test]
    public function getIndpEnvTypo3SitePathReturnsStringEndingWithSlash(): void
    {
        $result = GeneralUtility::getIndpEnv('TYPO3_SITE_PATH');
        self::assertEquals('/', $result[strlen($result) - 1]);
    }

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

    #[DataProvider('hostnameAndPortDataProvider')]
    #[Test]
    public function getIndpEnvTypo3HostOnlyParsesHostnamesAndIpAddresses($httpHost, $expectedIp): void
    {
        $_SERVER['HTTP_HOST'] = $httpHost;
        self::assertEquals($expectedIp, GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'));
    }

    #[DataProvider('hostnameAndPortDataProvider')]
    #[Test]
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
    public static function underscoredToUpperCamelCaseDataProvider(): array
    {
        return [
            'single word' => ['Blogexample', 'blogexample'],
            'multiple words' => ['BlogExample', 'blog_example'],
        ];
    }

    #[DataProvider('underscoredToUpperCamelCaseDataProvider')]
    #[Test]
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
    public static function underscoredToLowerCamelCaseDataProvider(): array
    {
        return [
            'single word' => ['minimalvalue', 'minimalvalue'],
            'multiple words' => ['minimalValue', 'minimal_value'],
        ];
    }

    #[DataProvider('underscoredToLowerCamelCaseDataProvider')]
    #[Test]
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
    public static function camelCaseToLowerCaseUnderscoredDataProvider(): array
    {
        return [
            'single word' => ['blogexample', 'blogexample'],
            'single word starting upper case' => ['blogexample', 'Blogexample'],
            'two words starting lower case' => ['minimal_value', 'minimalValue'],
            'two words starting upper case' => ['blog_example', 'BlogExample'],
        ];
    }

    #[DataProvider('camelCaseToLowerCaseUnderscoredDataProvider')]
    #[Test]
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
     * @return array<string, array{0: string}>
     */
    public static function validUrlValidResourceDataProvider(): array
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

    #[DataProvider('validUrlValidResourceDataProvider')]
    #[Test]
    public function validURLReturnsTrueForValidResource(string $url): void
    {
        self::assertTrue(GeneralUtility::isValidUrl($url));
    }

    /**
     * Data provider for invalid isValidUrl's
     *
     * @return array<string, array{0: string}>
     */
    public static function isValidUrlInvalidResourceDataProvider(): array
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

    #[DataProvider('isValidUrlInvalidResourceDataProvider')]
    #[Test]
    public function validURLReturnsFalseForInvalidResource(string $url): void
    {
        self::assertFalse(GeneralUtility::isValidUrl($url));
    }

    //////////////////////////////////
    // Tests concerning isOnCurrentHost
    //////////////////////////////////
    #[Test]
    public function isOnCurrentHostReturnsTrueWithCurrentHost(): void
    {
        $testUrl = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');
        self::assertTrue(GeneralUtility::isOnCurrentHost($testUrl));
    }

    /**
     * Data provider for invalid isOnCurrentHost's
     *
     * @return array<string, array{0: string}>
     */
    public static function checkisOnCurrentHostInvalidHostsDataProvider(): array
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

    #[DataProvider('checkisOnCurrentHostInvalidHostsDataProvider')]
    #[Test]
    public function isOnCurrentHostWithNotCurrentHostReturnsFalse(string $hostCandidate): void
    {
        self::assertFalse(GeneralUtility::isOnCurrentHost($hostCandidate));
    }

    ////////////////////////////////////////
    // Tests concerning sanitizeLocalUrl
    ////////////////////////////////////////
    /**
     * Data provider for valid sanitizeLocalUrl paths
     *
     * @return array<string, array{0: string}>
     */
    public static function sanitizeLocalUrlValidPathsDataProvider(): array
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

    #[DataProvider('sanitizeLocalUrlValidPathsDataProvider')]
    #[Test]
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
            // needs to be a subpath in order to validate ".." references
            Environment::getPublicPath() . '/subdir/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/subdir/index.php';
        self::assertEquals($path, GeneralUtility::sanitizeLocalUrl($path));
    }

    #[DataProvider('sanitizeLocalUrlValidPathsDataProvider')]
    #[Test]
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
            // needs to be a subpath in order to validate ".." references
            Environment::getPublicPath() . '/subdir/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/subdir/index.php';
        self::assertEquals(rawurlencode($path), GeneralUtility::sanitizeLocalUrl(rawurlencode($path)));
    }

    /**
     * Data provider for valid sanitizeLocalUrl's
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function sanitizeLocalUrlValidUrlsDataProvider(): array
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

    #[DataProvider('sanitizeLocalUrlValidUrlsDataProvider')]
    #[Test]
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
            Environment::getPublicPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['SCRIPT_NAME'] = $subDirectory . 'index.php';
        self::assertEquals($url, GeneralUtility::sanitizeLocalUrl($url));
    }

    #[DataProvider('sanitizeLocalUrlValidUrlsDataProvider')]
    #[Test]
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
            Environment::getPublicPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['SCRIPT_NAME'] = $subDirectory . 'index.php';
        self::assertEquals(rawurlencode($url), GeneralUtility::sanitizeLocalUrl(rawurlencode($url)));
    }

    /**
     * Data provider for invalid sanitizeLocalUrl's
     *
     * @return array<string, array{0: string}>
     */
    public static function sanitizeLocalUrlInvalidDataProvider(): array
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

    #[DataProvider('sanitizeLocalUrlInvalidDataProvider')]
    #[Test]
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
            Environment::getPublicPath() . '/typo3/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = 'typo3/index.php';
        self::assertEquals('', GeneralUtility::sanitizeLocalUrl($url));
    }

    #[DataProvider('sanitizeLocalUrlInvalidDataProvider')]
    #[Test]
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
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        self::assertEquals('', GeneralUtility::sanitizeLocalUrl($url));
    }

    #[DataProvider('sanitizeLocalUrlInvalidDataProvider')]
    #[Test]
    public function sanitizeLocalUrlDeniesEncodedInvalidUrls(string $url): void
    {
        self::assertEquals('', GeneralUtility::sanitizeLocalUrl(rawurlencode($url)));
    }

    ////////////////////////////////////////
    // Tests concerning unlink_tempfile
    ////////////////////////////////////////
    #[Test]
    public function unlink_tempfileRemovesValidFileInTypo3temp(): void
    {
        $fixtureFile = __DIR__ . '/Fixtures/clear.gif';
        $testFilename = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_') . '.gif';
        @copy($fixtureFile, $testFilename);
        GeneralUtility::unlink_tempfile($testFilename);
        $fileExists = file_exists($testFilename);
        self::assertFalse($fileExists);
    }

    #[Test]
    public function unlink_tempfileRemovesHiddenFile(): void
    {
        $fixtureFile = __DIR__ . '/Fixtures/clear.gif';
        $testFilename = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('.test_') . '.gif';
        @copy($fixtureFile, $testFilename);
        GeneralUtility::unlink_tempfile($testFilename);
        $fileExists = file_exists($testFilename);
        self::assertFalse($fileExists);
    }

    #[Test]
    public function unlink_tempfileReturnsTrueIfFileWasRemoved(): void
    {
        $fixtureFile = __DIR__ . '/Fixtures/clear.gif';
        $testFilename = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_') . '.gif';
        @copy($fixtureFile, $testFilename);
        $returnValue = GeneralUtility::unlink_tempfile($testFilename);
        self::assertTrue($returnValue);
    }

    #[Test]
    public function unlink_tempfileReturnsNullIfFileDoesNotExist(): void
    {
        $returnValue = GeneralUtility::unlink_tempfile(Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('i_do_not_exist'));
        self::assertNull($returnValue);
    }

    #[Test]
    public function unlink_tempfileReturnsNullIfFileIsNowWithinTypo3temp(): void
    {
        $returnValue = GeneralUtility::unlink_tempfile('/tmp/typo3-unit-test-unlink_tempfile');
        self::assertNull($returnValue);
    }

    //////////////////////////////////////
    // Tests concerning tempnam
    //////////////////////////////////////
    #[Test]
    public function tempnamReturnsPathStartingWithGivenPrefix(): void
    {
        $filePath = GeneralUtility::tempnam('foo');
        $this->testFilesToDelete[] = $filePath;
        $fileName = basename($filePath);
        self::assertStringStartsWith('foo', $fileName);
    }

    #[Test]
    public function tempnamReturnsPathWithoutBackslashes(): void
    {
        $filePath = GeneralUtility::tempnam('foo');
        $this->testFilesToDelete[] = $filePath;
        self::assertStringNotContainsString('\\', $filePath);
    }

    #[Test]
    public function tempnamReturnsAbsolutePathInVarPath(): void
    {
        $filePath = GeneralUtility::tempnam('foo');
        $this->testFilesToDelete[] = $filePath;
        self::assertStringStartsWith(Environment::getVarPath() . '/transient/', $filePath);
    }

    //////////////////////////////////////
    // Tests concerning removeDotsFromTS
    //////////////////////////////////////
    #[Test]
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

    //////////////////////////////////
    // Tests concerning implodeAttributes
    //////////////////////////////////

    public static function implodeAttributesDataProvider(): \Iterator
    {
        yield 'Generic input without xhtml' => [
            ['hREf' => 'https://example.com', 'title' => 'above'],
            false,
            true,
            'hREf="https://example.com" title="above"',
        ];
        yield 'Generic input' => [
            ['hREf' => 'https://example.com', 'title' => 'above'],
            true,
            true,
            'href="https://example.com" title="above"',
        ];
        yield 'Generic input keeping empty values' => [
            ['hREf' => 'https://example.com', 'title' => ''],
            true,
            true, // keep empty values
            'href="https://example.com" title=""',
        ];
        yield 'Generic input removing empty values' => [
            ['hREf' => 'https://example.com', 'title' => '', 'nomodule' => null],
            true,
            false,  // do not keep empty values
            'href="https://example.com"',
        ];
    }

    #[DataProvider('implodeAttributesDataProvider')]
    #[Test]
    public function implodeAttributesEscapesProperly(array $input, bool $xhtmlSafe, bool $keepEmptyValues, string $expected): void
    {
        self::assertSame($expected, GeneralUtility::implodeAttributes($input, $xhtmlSafe, $keepEmptyValues));
    }

    #[Test]
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

    #[Test]
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
    #[Test]
    public function getDirsReturnsArrayOfDirectoriesFromGivenDirectory(): void
    {
        $directories = GeneralUtility::get_dirs(Environment::getLegacyConfigPath() . '/');
        self::assertIsArray($directories);
    }

    #[Test]
    public function getDirsReturnsStringErrorOnPathFailure(): void
    {
        $path = 'foo';
        $result = GeneralUtility::get_dirs($path);
        $expectedResult = 'error';
        self::assertEquals($expectedResult, $result);
    }

    //////////////////////////////////
    // Tests concerning quoteJSvalue
    //////////////////////////////////
    /**
     * Data provider for quoteJSvalueTest.
     */
    public static function quoteJsValueDataProvider(): array
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

    #[DataProvider('quoteJsValueDataProvider')]
    #[Test]
    public function quoteJsValueTest(string $input, string $expected): void
    {
        self::assertSame('\'' . $expected . '\'', GeneralUtility::quoteJSvalue($input));
    }

    public static function jsonEncodeForHtmlAttributeTestDataProvider(): array
    {
        return [
            [
                ['html' => '<tag attr="\\Vendor\\Package">value</tag>'],
                true,
                // cave: `\\\\` (four) actually required for PHP only, will be `\\` (two) in HTML
                '{&quot;html&quot;:&quot;\u003Ctag attr=\u0022\\\\Vendor\\\\Package\u0022\u003Evalue\u003C\/tag\u003E&quot;}',
            ],
            [
                ['html' => '<tag attr="\\Vendor\\Package">value</tag>'],
                false,
                // cave: `\\\\` (four) actually required for PHP only, will be `\\` (two) in HTML
                '{"html":"\u003Ctag attr=\u0022\\\\Vendor\\\\Package\u0022\u003Evalue\u003C\/tag\u003E"}',
            ],
            [
                ['spaces' => '|' . chr(9) . '|' . chr(10) . '|' . chr(13) . '|'],
                false,
                '{"spaces":"|\t|\n|\r|"}',
            ],
        ];
    }

    #[DataProvider('jsonEncodeForHtmlAttributeTestDataProvider')]
    #[Test]
    public function jsonEncodeForHtmlAttributeTest($value, bool $useHtmlEntities, string $expectation): void
    {
        self::assertSame($expectation, GeneralUtility::jsonEncodeForHtmlAttribute($value, $useHtmlEntities));
    }

    public static function jsonEncodeForJavaScriptTestDataProvider(): array
    {
        return [
            [
                ['html' => '<tag attr="\\Vendor\\Package">value</tag>'],
                // cave: `\\\\` (four) actually required for PHP only, will be `\\` (two) in JavaScript
                '{"html":"\u003Ctag attr=\u0022\\\\u005CVendor\\\\u005CPackage\u0022\u003Evalue\u003C\/tag\u003E"}',
            ],
            [
                ['spaces' => '|' . chr(9) . '|' . chr(10) . '|' . chr(13) . '|'],
                '{"spaces":"|\u0009|\u000A|\u000D|"}',
            ],
        ];
    }

    #[DataProvider('jsonEncodeForJavaScriptTestDataProvider')]
    #[Test]
    public function jsonEncodeForJavaScriptTest($value, string $expectation): void
    {
        self::assertSame($expectation, GeneralUtility::jsonEncodeForJavaScript($value));
    }

    public static function sanitizeCssVariableValueDataProvider(): \Generator
    {
        yield 'double quotes' => ['url("/my-background.png")', 'url("/my-background.png")'];
        yield 'single quotes' => ["url('/my-background.png')", "url('/my-background.png')"];
        yield 'newline chars' => ["url('/my-background.png'\r\n\r\n)", "url('/my-background.png')"];
        yield 'HTML markup' => ['url(</style>)', 'url(&lt;/style&gt;)'];
    }

    #[DataProvider('sanitizeCssVariableValueDataProvider')]
    #[Test]
    public function sanitizeCssVariableValue(string $value, string $expectation): void
    {
        self::assertSame($expectation, GeneralUtility::sanitizeCssVariableValue($value));
    }

    #[Test]
    public function fixPermissionsSetsPermissionsToFile(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        // Create and prepare test file
        $filename = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        GeneralUtility::writeFileToTypo3tempDir($filename, '42');
        chmod($filename, 482);
        // Set target permissions and run method
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'] = '0660';
        $fixPermissionsResult = GeneralUtility::fixPermissions($filename);
        clearstatcache();
        self::assertTrue($fixPermissionsResult);
        self::assertEquals('0660', substr(decoct(fileperms($filename)), 2));
    }

    #[Test]
    public function fixPermissionsSetsPermissionsToHiddenFile(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        // Create and prepare test file
        $filename = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        GeneralUtility::writeFileToTypo3tempDir($filename, '42');
        chmod($filename, 482);
        // Set target permissions and run method
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'] = '0660';
        $fixPermissionsResult = GeneralUtility::fixPermissions($filename);
        clearstatcache();
        self::assertTrue($fixPermissionsResult);
        self::assertEquals('0660', substr(decoct(fileperms($filename)), 2));
    }

    #[Test]
    public function fixPermissionsSetsPermissionsToDirectory(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        // Create and prepare test directory
        $directory = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        GeneralUtility::mkdir($directory);
        chmod($directory, 1551);
        // Set target permissions and run method
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'] = '0770';
        $fixPermissionsResult = GeneralUtility::fixPermissions($directory);
        clearstatcache();
        self::assertTrue($fixPermissionsResult);
        self::assertEquals('0770', substr(decoct(fileperms($directory)), 1));
    }

    #[Test]
    public function fixPermissionsSetsPermissionsToDirectoryWithTrailingSlash(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        // Create and prepare test directory
        $directory = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        GeneralUtility::mkdir($directory);
        chmod($directory, 1551);
        // Set target permissions and run method
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'] = '0770';
        $fixPermissionsResult = GeneralUtility::fixPermissions($directory . '/');
        // Get actual permissions and clean up
        clearstatcache();
        self::assertTrue($fixPermissionsResult);
        self::assertEquals('0770', substr(decoct(fileperms($directory)), 1));
    }

    #[Test]
    public function fixPermissionsSetsPermissionsToHiddenDirectory(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        // Create and prepare test directory
        $directory = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        GeneralUtility::mkdir($directory);
        chmod($directory, 1551);
        // Set target permissions and run method
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'] = '0770';
        $fixPermissionsResult = GeneralUtility::fixPermissions($directory);
        // Get actual permissions and clean up
        clearstatcache();
        self::assertTrue($fixPermissionsResult);
        self::assertEquals('0770', substr(decoct(fileperms($directory)), 1));
    }

    #[Test]
    public function fixPermissionsCorrectlySetsPermissionsRecursive(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        // Create and prepare test directory and file structure
        $baseDirectory = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        GeneralUtility::mkdir($baseDirectory);
        chmod($baseDirectory, 1751);
        GeneralUtility::writeFileToTypo3tempDir($baseDirectory . '/file', '42');
        chmod($baseDirectory . '/file', 482);
        GeneralUtility::mkdir($baseDirectory . '/foo');
        chmod($baseDirectory . '/foo', 1751);
        GeneralUtility::writeFileToTypo3tempDir($baseDirectory . '/foo/file', '42');
        chmod($baseDirectory . '/foo/file', 482);
        GeneralUtility::mkdir($baseDirectory . '/.bar');
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
        $fixPermissionsResult = GeneralUtility::fixPermissions($baseDirectory, true);
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function fixPermissionsSetsDefaultPermissionsToFile(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        $filename = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        GeneralUtility::writeFileToTypo3tempDir($filename, '42');
        chmod($filename, 482);
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask']);
        $fixPermissionsResult = GeneralUtility::fixPermissions($filename);
        clearstatcache();
        self::assertTrue($fixPermissionsResult);
        self::assertEquals('0644', substr(decoct(fileperms($filename)), 2));
    }

    #[Test]
    public function fixPermissionsSetsDefaultPermissionsToDirectory(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        $directory = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        GeneralUtility::mkdir($directory);
        chmod($directory, 1551);
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask']);
        $fixPermissionsResult = GeneralUtility::fixPermissions($directory);
        clearstatcache();
        self::assertTrue($fixPermissionsResult);
        self::assertEquals('0755', substr(decoct(fileperms($directory)), 1));
    }

    ///////////////////////////////
    // Tests concerning mkdir
    ///////////////////////////////
    #[Test]
    public function mkdirCreatesDirectory(): void
    {
        $directory = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        $mkdirResult = GeneralUtility::mkdir($directory);
        clearstatcache();
        self::assertTrue($mkdirResult);
        self::assertDirectoryExists($directory);
    }

    #[Test]
    public function mkdirCreatesHiddenDirectory(): void
    {
        $directory = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('.test_');
        $mkdirResult = GeneralUtility::mkdir($directory);
        clearstatcache();
        self::assertTrue($mkdirResult);
        self::assertDirectoryExists($directory);
    }

    #[Test]
    public function mkdirCreatesDirectoryWithTrailingSlash(): void
    {
        $directory = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_') . '/';
        $mkdirResult = GeneralUtility::mkdir($directory);
        clearstatcache();
        self::assertTrue($mkdirResult);
        self::assertDirectoryExists($directory);
    }

    #[Test]
    public function mkdirSetsPermissionsOfCreatedDirectory(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        $directory = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        $oldUmask = umask(19);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'] = '0772';
        GeneralUtility::mkdir($directory);
        clearstatcache();
        $resultDirectoryPermissions = substr(decoct(fileperms($directory)), 1);
        umask($oldUmask);
        self::assertEquals('0772', $resultDirectoryPermissions);
    }

    /////////////////////////////////////////////
    // Tests concerning writeFileToTypo3tempDir()
    /////////////////////////////////////////////
    /**
     * when adding entries here, make sure to register any files or directories that might get created as third array item
     * they will be cleaned up after test run.
     */
    public static function invalidFilePathForTypo3tempDirDataProvider(): array
    {
        return [
            [
                Environment::getPublicPath() . '/../path/this-path-has-more-than-60-characters-in-one-base-path-you-can-even-count-more',
                'Input filepath "' . Environment::getPublicPath() . '/../path/this-path-has-more-than-60-characters-in-one-base-path-you-can-even-count-more" was generally invalid!',
                '',
            ],
            [
                Environment::getPublicPath() . '/dummy/path/this-path-has-more-than-60-characters-in-one-base-path-you-can-even-count-more',
                'Input filepath "' . Environment::getPublicPath() . '/dummy/path/this-path-has-more-than-60-characters-in-one-base-path-you-can-even-count-more" was generally invalid!',
                '',
            ],
            [
                Environment::getPublicPath() . '/dummy/path/this-path-has-more-than-60-characters-in-one-base-path-you-can-even-count-more',
                'Input filepath "' . Environment::getPublicPath() . '/dummy/path/this-path-has-more-than-60-characters-in-one-base-path-you-can-even-count-more" was generally invalid!',
                '',
            ],
            [
                '/dummy/path/awesome',
                '"/dummy/path/" was not within directory Environment::getPublicPath() + "/typo3temp/"',
                '',
            ],
            [
                Environment::getLegacyConfigPath() . '/path',
                '"' . Environment::getLegacyConfigPath() . '/" was not within directory Environment::getPublicPath() + "/typo3temp/"',
                '',
            ],
            [
                Environment::getPublicPath() . '/typo3temp/táylor/swíft',
                'Subdir, "táylor/", was NOT on the form "[[:alnum:]_]/+"',
                '',
            ],
            'Path instead of file given' => [
                Environment::getPublicPath() . '/typo3temp/dummy/path/',
                'Calculated file location didn\'t match input "' . Environment::getPublicPath() . '/typo3temp/dummy/path/".',
                Environment::getPublicPath() . '/typo3temp/dummy/',
            ],
        ];
    }

    #[DataProvider('invalidFilePathForTypo3tempDirDataProvider')]
    #[Test]
    public function writeFileToTypo3tempDirFailsWithInvalidPath(string $invalidFilePath, string $expectedResult, string $pathToCleanUp): void
    {
        if ($pathToCleanUp !== '') {
            $this->testFilesToDelete[] = $pathToCleanUp;
        }
        $result = GeneralUtility::writeFileToTypo3tempDir($invalidFilePath, 'dummy content to be written');
        self::assertSame($result, $expectedResult);
    }

    /**
     * when adding entries here, make sure to register any files or directories that might get created as second array item
     * they will be cleaned up after test run.
     */
    public static function validFilePathForTypo3tempDirDataProvider(): array
    {
        return [
            'Default text file' => [
                Environment::getVarPath() . '/tests/paranoid/android.txt',
                Environment::getVarPath() . '/tests/',
            ],
            'Html file extension' => [
                Environment::getVarPath() . '/tests/karma.html',
                Environment::getVarPath() . '/tests/',
            ],
            'No file extension' => [
                Environment::getVarPath() . '/tests/no-surprises',
                Environment::getVarPath() . '/tests/',
            ],
            'Deep directory' => [
                Environment::getVarPath() . '/tests/climbing/up/the/walls',
                Environment::getVarPath() . '/tests/',
            ],
            'File in typo3temp/var directory' => [
                Environment::getPublicPath() . '/typo3temp/var/path/foo.txt',
                Environment::getPublicPath() . '/typo3temp/var/path',
            ],
        ];
    }

    /**
     * @param non-empty-string $filePath
     * @param non-empty-string $pathToCleanUp
     */
    #[DataProvider('validFilePathForTypo3tempDirDataProvider')]
    #[Test]
    public function writeFileToTypo3tempDirWorksWithValidPath(string $filePath, string $pathToCleanUp): void
    {
        if ($pathToCleanUp !== '') {
            $this->testFilesToDelete[] = $pathToCleanUp;
        }

        $dummyContent = 'Please could you stop the noise, I\'m trying to get some rest from all the unborn chicken voices in my head.';

        $result = GeneralUtility::writeFileToTypo3tempDir($filePath, $dummyContent);

        self::assertNull($result);
        self::assertFileExists($filePath);
        self::assertStringEqualsFile($filePath, $dummyContent);
    }

    ///////////////////////////////
    // Tests concerning mkdir_deep
    ///////////////////////////////
    #[Test]
    public function mkdirDeepCreatesDirectory(): void
    {
        $directory = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        GeneralUtility::mkdir_deep($directory);
        self::assertDirectoryExists($directory);
    }

    #[Test]
    public function mkdirDeepCreatesSubdirectoriesRecursive(): void
    {
        $directory = $this->getTestDirectory() . '/typo3temp/var/tests/' . StringUtility::getUniqueId('test_');
        $subDirectory = $directory . '/foo';
        GeneralUtility::mkdir_deep($subDirectory);
        self::assertDirectoryExists($subDirectory);
    }

    /**
     * Data provider for mkdirDeepCreatesDirectoryWithDoubleSlashes.
     */
    public static function mkdirDeepCreatesDirectoryWithAndWithoutDoubleSlashesDataProvider(): array
    {
        return [
            'no double slash if concatenated with Environment::getPublicPath()' => ['fileadmin/testDir1'],
            'double slash if concatenated with Environment::getPublicPath()' => ['/fileadmin/testDir2'],
        ];
    }

    #[DataProvider('mkdirDeepCreatesDirectoryWithAndWithoutDoubleSlashesDataProvider')]
    #[Test]
    public function mkdirDeepCreatesDirectoryWithDoubleSlashes($directoryToCreate): void
    {
        $testRoot = Environment::getVarPath() . '/public/';
        $this->testFilesToDelete[] = $testRoot;
        $directory = $testRoot . $directoryToCreate;
        GeneralUtility::mkdir_deep($directory);
        self::assertDirectoryExists($directory);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function mkdirDeepDoesNotChangePermissionsOfExistingSubDirectories(): void
    {
        if (Environment::isWindows()) {
            self::markTestSkipped(self::NO_FIX_PERMISSIONS_ON_WINDOWS);
        }
        $baseDirectory = $this->getTestDirectory();
        $existingDirectory = StringUtility::getUniqueId('test_existing_') . '/';
        $newSubDirectory = StringUtility::getUniqueId('test_new_');
        @mkdir($baseDirectory . $existingDirectory);
        $this->testFilesToDelete[] = $baseDirectory . $existingDirectory;
        chmod($baseDirectory . $existingDirectory, 482);
        GeneralUtility::mkdir_deep($baseDirectory . $existingDirectory . $newSubDirectory);
        self::assertEquals(742, (int)substr(decoct(fileperms($baseDirectory . $existingDirectory)), 2));
    }

    #[Test]
    public function mkdirDeepThrowsExceptionIfDirectoryCreationFails(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1170251401);

        GeneralUtility::mkdir_deep('http://localhost');
    }

    ///////////////////////////////
    // Tests concerning rmdir
    ///////////////////////////////
    #[Test]
    public function rmdirRemovesFile(): void
    {
        $testRoot = Environment::getVarPath() . '/tests/';
        $this->testFilesToDelete[] = $testRoot;
        GeneralUtility::mkdir_deep($testRoot);
        $file = $testRoot . StringUtility::getUniqueId('file_');
        touch($file);
        GeneralUtility::rmdir($file);
        self::assertFileDoesNotExist($file);
    }

    #[Test]
    public function rmdirReturnTrueIfFileWasRemoved(): void
    {
        $file = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('file_');
        touch($file);
        self::assertTrue(GeneralUtility::rmdir($file));
    }

    #[Test]
    public function rmdirReturnFalseIfNoFileWasRemoved(): void
    {
        $file = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('file_');
        self::assertFalse(GeneralUtility::rmdir($file));
    }

    #[Test]
    public function rmdirRemovesDirectory(): void
    {
        $directory = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('directory_');
        mkdir($directory);
        GeneralUtility::rmdir($directory);
        self::assertFileDoesNotExist($directory);
    }

    #[Test]
    public function rmdirRemovesDirectoryWithTrailingSlash(): void
    {
        $directory = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('directory_') . '/';
        GeneralUtility::mkdir_deep($directory);
        GeneralUtility::rmdir($directory);
        self::assertFileDoesNotExist($directory);
    }

    #[Test]
    public function rmdirDoesNotRemoveDirectoryWithFilesAndReturnsFalseIfRecursiveDeletionIsOff(): void
    {
        $directory = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('directory_') . '/';
        GeneralUtility::mkdir_deep($directory);
        $file = StringUtility::getUniqueId('file_');
        touch($directory . $file);
        $return = GeneralUtility::rmdir($directory);
        self::assertFileExists($directory);
        self::assertFileExists($directory . $file);
        self::assertFalse($return);
    }

    #[Test]
    public function rmdirRemovesDirectoriesRecursiveAndReturnsTrue(): void
    {
        $directory = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('directory_') . '/';
        mkdir($directory);
        mkdir($directory . 'sub/');
        touch($directory . 'sub/file');
        $return = GeneralUtility::rmdir($directory, true);
        self::assertFileDoesNotExist($directory);
        self::assertTrue($return);
    }

    #[Test]
    public function rmdirRemovesLinkToDirectory(): void
    {
        $existingDirectory = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('notExists_') . '/';
        mkdir($existingDirectory);
        $symlinkName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('link_');
        symlink($existingDirectory, $symlinkName);
        GeneralUtility::rmdir($symlinkName, true);
        self::assertFalse(is_link($symlinkName));
    }

    #[Test]
    public function rmdirRemovesDeadLinkToDirectory(): void
    {
        $notExistingDirectory = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('notExists_') . '/';
        $symlinkName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('link_');
        GeneralUtility::mkdir_deep($notExistingDirectory);
        symlink($notExistingDirectory, $symlinkName);
        rmdir($notExistingDirectory);

        GeneralUtility::rmdir($symlinkName, true);
        self::assertFalse(is_link($symlinkName));
    }

    #[Test]
    public function rmdirRemovesDeadLinkToFile(): void
    {
        $testDirectory = $this->getTestDirectory() . '/';
        $notExistingFile = $testDirectory . StringUtility::getUniqueId('notExists_');
        $symlinkName = $testDirectory . StringUtility::getUniqueId('link_');
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
     * @return string A directory name prefixed with FilesInDirTests.
     */
    protected function getFilesInDirCreateTestDirectory(): string
    {
        $path = Environment::getVarPath() . '/FilesInDirTests';
        $this->testFilesToDelete[] = $path;
        mkdir($path);
        mkdir($path . '/subDirectory');
        file_put_contents($path . '/subDirectory/test.php', 'butter');
        file_put_contents($path . '/subDirectory/other.php', 'milk');
        file_put_contents($path . '/subDirectory/stuff.csv', 'honey');
        mkdir($path . '/beStylesheet');
        file_put_contents($path . '/beStylesheet/backend.css', '.topbar-site { color: red; }');
        file_put_contents($path . '/beStylesheet/backend.scss', '.topbar-site { color: green; }');
        file_put_contents($path . '/excludeMe.txt', 'cocoa nibs');
        file_put_contents($path . '/double.setup.typoscript', 'cool TS');
        file_put_contents($path . '/testB.txt', 'olive oil');
        file_put_contents($path . '/testA.txt', 'eggs');
        file_put_contents($path . '/testC.txt', 'carrots');
        file_put_contents($path . '/test.js', 'oranges');
        file_put_contents($path . '/test.css', 'apples');
        file_put_contents($path . '/.secret.txt', 'sammon');
        return $path;
    }

    #[Test]
    public function getFilesInDirFindsRegularFile(): void
    {
        $path = $this->getFilesInDirCreateTestDirectory();
        $files = GeneralUtility::getFilesInDir($path);
        self::assertContains('testA.txt', $files);
    }

    #[Test]
    public function getFilesInDirFindsHiddenFile(): void
    {
        $path = $this->getFilesInDirCreateTestDirectory();
        $files = GeneralUtility::getFilesInDir($path);
        self::assertContains('.secret.txt', $files);
    }

    #[Test]
    public function getFilesInDirOnlyFindWithMatchingExtension(): void
    {
        $path = $this->getFilesInDirCreateTestDirectory();
        $files = GeneralUtility::getFilesInDir($path . '/beStylesheet', 'css');
        self::assertContains('backend.css', $files);
        self::assertNotContains('backend.scss', $files);
    }

    /**
     * Data provider for getFilesInDirByExtensionFindsFiles
     */
    public static function fileExtensionDataProvider(): array
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

    #[DataProvider('fileExtensionDataProvider')]
    #[Test]
    public function getFilesInDirByExtensionFindsFiles($fileExtensions): void
    {
        $path = $this->getFilesInDirCreateTestDirectory();
        $files = GeneralUtility::getFilesInDir($path, $fileExtensions);
        self::assertContains('double.setup.typoscript', $files);
        self::assertContains('testA.txt', $files);
        self::assertContains('test.js', $files);
        self::assertContains('test.css', $files);
    }

    #[Test]
    public function getFilesInDirByExtensionDoesNotFindFilesWithOtherExtensions(): void
    {
        $path = $this->getFilesInDirCreateTestDirectory();
        $files = GeneralUtility::getFilesInDir($path, 'txt,js');
        self::assertContains('testA.txt', $files);
        self::assertContains('test.js', $files);
        self::assertNotContains('test.css', $files);
    }

    #[Test]
    public function getFilesInDirExcludesFilesMatchingPattern(): void
    {
        $path = $this->getFilesInDirCreateTestDirectory();
        $files = GeneralUtility::getFilesInDir($path, '', false, '', 'excludeMe.*');
        self::assertContains('test.js', $files);
        self::assertNotContains('excludeMe.txt', $files);
    }

    #[Test]
    public function getFilesInDirRespectsOrderByModificationTime(): void
    {
        $path = $this->getTestDirectory('FilesInDirTestsModificationTime');

        touch($path . '/testOne.txt', time() - 3600);
        touch($path . '/testTwo.txt', time() - 5400);
        touch($path . '/testThree.txt', time() - 1800);

        $files = GeneralUtility::getFilesInDir($path, '', false, 'mtime');
        self::assertEquals(array_values($files), [
            'testTwo.txt',
            'testOne.txt',
            'testThree.txt',
        ]);
    }

    #[Test]
    public function getFilesInDirCanPrependPath(): void
    {
        $path = $this->getFilesInDirCreateTestDirectory();
        self::assertContains(
            $path . '/testA.txt',
            GeneralUtility::getFilesInDir($path, '', true)
        );
    }

    #[Test]
    public function getFilesInDirDoesSortAlphabeticallyByDefault(): void
    {
        $path = $this->getFilesInDirCreateTestDirectory();
        self::assertSame(
            ['.secret.txt', 'double.setup.typoscript', 'excludeMe.txt', 'test.css', 'test.js', 'testA.txt', 'testB.txt', 'testC.txt'],
            array_values(GeneralUtility::getFilesInDir($path))
        );
    }

    #[Test]
    public function getFilesInDirReturnsArrayWithMd5OfElementAndPathAsArrayKey(): void
    {
        $path = $this->getFilesInDirCreateTestDirectory();
        self::assertArrayHasKey(
            md5($path . '/testA.txt'),
            GeneralUtility::getFilesInDir($path)
        );
    }

    #[Test]
    public function getFilesInDirDoesNotFindDirectories(): void
    {
        $path = $this->getFilesInDirCreateTestDirectory();
        self::assertNotContains(
            'subDirectory',
            GeneralUtility::getFilesInDir($path)
        );
    }

    /**
     * Dotfiles; current directory: '.' and parent directory: '..' must not be
     * present.
     */
    #[Test]
    public function getFilesInDirDoesNotFindDotfiles(): void
    {
        $path = $this->getFilesInDirCreateTestDirectory();
        $files = GeneralUtility::getFilesInDir($path);
        self::assertNotContains('..', $files);
        self::assertNotContains('.', $files);
    }

    ///////////////////////////////
    // Tests concerning split_fileref
    ///////////////////////////////
    #[Test]
    public function splitFileRefReturnsFileTypeNotForFolders(): void
    {
        $directoryName = StringUtility::getUniqueId('test_') . '.com';
        $directoryPath = Environment::getVarPath() . '/tests/';
        @mkdir($directoryPath, octdec($GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask']));
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

    #[Test]
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
    public static function dirnameDataProvider(): array
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
     * @param string $input the input for dirname
     * @param string $expectedValue the expected return value expected from dirname
     */
    #[DataProvider('dirnameDataProvider')]
    #[Test]
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
    public static function resolveBackPathDataProvider(): array
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
     * @param string $input the input for resolveBackPath
     * @param string $expectedValue Expected return value from resolveBackPath
     */
    #[DataProvider('resolveBackPathDataProvider')]
    #[Test]
    public function resolveBackPathWithDataProvider(string $input, string $expectedValue): void
    {
        self::assertEquals($expectedValue, GeneralUtility::resolveBackPath($input));
    }

    /////////////////////////////////////////////////////////////////////////////////////
    // Tests concerning makeInstance, setSingletonInstance, addInstance, purgeInstances
    /////////////////////////////////////////////////////////////////////////////////////
    #[Test]
    public function makeInstanceWithEmptyClassNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288965219);

        // @phpstan-ignore-next-line We're explicitly checking the behavior for a contract violation.
        GeneralUtility::makeInstance('');
    }

    #[Test]
    public function makeInstanceWithBeginningSlashInClassNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1420281366);

        GeneralUtility::makeInstance('\\TYPO3\\CMS\\Backend\\Controller\\BackendController');
    }

    #[Test]
    public function makeInstanceReturnsClassInstance(): void
    {
        self::assertInstanceOf(\stdClass::class, GeneralUtility::makeInstance(\stdClass::class));
    }

    #[Test]
    public function makeInstancePassesParametersToConstructor(): void
    {
        $instance = GeneralUtility::makeInstance(TwoParametersConstructorFixture::class, 'one parameter', 'another parameter');
        self::assertEquals('one parameter', $instance->constructorParameter1, 'The first constructor parameter has not been set.');
        self::assertEquals('another parameter', $instance->constructorParameter2, 'The second constructor parameter has not been set.');
    }

    #[Test]
    public function makeInstanceInstanciatesConfiguredImplementation(): void
    {
        GeneralUtility::flushInternalRuntimeCaches();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][OriginalClassFixture::class] = ['className' => ReplacementClassFixture::class];
        self::assertInstanceOf(ReplacementClassFixture::class, GeneralUtility::makeInstance(OriginalClassFixture::class));
    }

    #[Test]
    public function makeInstanceResolvesConfiguredImplementationsRecursively(): void
    {
        GeneralUtility::flushInternalRuntimeCaches();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][OriginalClassFixture::class] = ['className' => ReplacementClassFixture::class];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][ReplacementClassFixture::class] = ['className' => OtherReplacementClassFixture::class];
        self::assertInstanceOf(OtherReplacementClassFixture::class, GeneralUtility::makeInstance(OriginalClassFixture::class));
    }

    #[Test]
    public function makeInstanceCalledTwoTimesForNonSingletonClassReturnsDifferentInstances(): void
    {
        $className = \stdClass::class;
        self::assertNotSame(GeneralUtility::makeInstance($className), GeneralUtility::makeInstance($className));
    }

    #[Test]
    public function makeInstanceCalledTwoTimesForSingletonClassReturnsSameInstance(): void
    {
        $className = get_class($this->createMock(SingletonInterface::class));
        self::assertSame(GeneralUtility::makeInstance($className), GeneralUtility::makeInstance($className));
    }

    #[Test]
    public function makeInstanceCalledTwoTimesForSingletonClassWithPurgeInstancesInbetweenReturnsDifferentInstances(): void
    {
        $className = get_class($this->createMock(SingletonInterface::class));
        $instance = GeneralUtility::makeInstance($className);
        GeneralUtility::purgeInstances();
        self::assertNotSame($instance, GeneralUtility::makeInstance($className));
    }

    #[Test]
    public function makeInstanceInjectsLogger(): void
    {
        $instance = GeneralUtility::makeInstance(GeneralUtilityMakeInstanceInjectLoggerFixture::class);
        self::assertInstanceOf(LoggerInterface::class, $instance->getLogger());
    }

    #[Test]
    public function setSingletonInstanceForEmptyClassNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288967479);

        $instance = $this->createMock(SingletonInterface::class);
        // @phpstan-ignore-next-line We are explicitly testing with a contract violation here.
        GeneralUtility::setSingletonInstance('', $instance);
    }

    #[Test]
    public function setSingletonInstanceForClassThatIsNoSubclassOfProvidedClassThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288967686);
        $instance = $this->getMockBuilder(SingletonInterface::class)->getMock();
        $singletonClassName = get_class($this->createMock(SingletonInterface::class));
        GeneralUtility::setSingletonInstance($singletonClassName, $instance);
    }

    #[Test]
    public function setSingletonInstanceMakesMakeInstanceReturnThatInstance(): void
    {
        $instance = $this->createMock(SingletonInterface::class);
        $singletonClassName = get_class($instance);
        GeneralUtility::setSingletonInstance($singletonClassName, $instance);
        self::assertSame($instance, GeneralUtility::makeInstance($singletonClassName));
    }

    #[Test]
    public function setSingletonInstanceCalledTwoTimesMakesMakeInstanceReturnLastSetInstance(): void
    {
        $instance1 = $this->createMock(SingletonInterface::class);
        $singletonClassName = get_class($instance1);
        $instance2 = new $singletonClassName();
        GeneralUtility::setSingletonInstance($singletonClassName, $instance1);
        GeneralUtility::setSingletonInstance($singletonClassName, $instance2);
        self::assertSame($instance2, GeneralUtility::makeInstance($singletonClassName));
    }

    #[Test]
    public function getSingletonInstancesContainsPreviouslySetSingletonInstance(): void
    {
        $instance = $this->createMock(SingletonInterface::class);
        $instanceClassName = get_class($instance);
        GeneralUtility::setSingletonInstance($instanceClassName, $instance);
        $registeredSingletonInstances = GeneralUtility::getSingletonInstances();
        self::assertArrayHasKey($instanceClassName, $registeredSingletonInstances);
        self::assertSame($registeredSingletonInstances[$instanceClassName], $instance);
    }

    #[Test]
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

    #[Test]
    public function resetSingletonInstancesResetsPreviouslySetInstance(): void
    {
        $instance = $this->createMock(SingletonInterface::class);
        $instanceClassName = get_class($instance);
        GeneralUtility::setSingletonInstance($instanceClassName, $instance);
        GeneralUtility::resetSingletonInstances([]);
        $registeredSingletonInstances = GeneralUtility::getSingletonInstances();
        self::assertArrayNotHasKey($instanceClassName, $registeredSingletonInstances);
    }

    #[Test]
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

    #[Test]
    public function addInstanceForEmptyClassNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288967479);

        // @phpstan-ignore-next-line We are explicitly testing with a contract violation here.
        GeneralUtility::addInstance('', new \stdClass());
    }

    #[Test]
    public function addInstanceForClassThatIsNoSubclassOfProvidedClassThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288967686);
        $instance = $this->getMockBuilder(\stdClass::class)->getMock();
        $singletonClassName = get_class($this->createMock(\stdClass::class));
        GeneralUtility::addInstance($singletonClassName, $instance);
    }

    #[Test]
    public function addInstanceWithSingletonInstanceThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1288969325);

        $instance = $this->createMock(SingletonInterface::class);
        GeneralUtility::addInstance(get_class($instance), $instance);
    }

    #[Test]
    public function addInstanceMakesMakeInstanceReturnThatInstance(): void
    {
        $instance = $this->createMock(\stdClass::class);
        $className = get_class($instance);
        GeneralUtility::addInstance($className, $instance);
        self::assertSame($instance, GeneralUtility::makeInstance($className));
    }

    #[Test]
    public function makeInstanceCalledTwoTimesAfterAddInstanceReturnTwoDifferentInstances(): void
    {
        $instance = $this->createMock(\stdClass::class);
        $className = get_class($instance);
        GeneralUtility::addInstance($className, $instance);
        self::assertNotSame(GeneralUtility::makeInstance($className), GeneralUtility::makeInstance($className));
    }

    #[Test]
    public function addInstanceCalledTwoTimesMakesMakeInstanceReturnBothInstancesInAddingOrder(): void
    {
        $instance1 = $this->createMock(\stdClass::class);
        $className = get_class($instance1);
        GeneralUtility::addInstance($className, $instance1);
        $instance2 = new $className();
        GeneralUtility::addInstance($className, $instance2);
        self::assertSame($instance1, GeneralUtility::makeInstance($className), 'The first returned instance does not match the first added instance.');
        self::assertSame($instance2, GeneralUtility::makeInstance($className), 'The second returned instance does not match the second added instance.');
    }

    #[Test]
    public function purgeInstancesDropsAddedInstance(): void
    {
        $instance = $this->createMock(\stdClass::class);
        $className = get_class($instance);
        GeneralUtility::addInstance($className, $instance);
        GeneralUtility::purgeInstances();
        self::assertNotSame($instance, GeneralUtility::makeInstance($className));
    }

    public static function getFileAbsFileNameDataProvider(): array
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

    #[DataProvider('getFileAbsFileNameDataProvider')]
    #[Test]
    public function getFileAbsFileNameReturnsCorrectValues(string $path, string $expected): void
    {
        // build the dummy package "foo" for use in ExtensionManagementUtility::extPath('foo');
        $package = $this->getMockBuilder(Package::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPackagePath'])
            ->getMock();
        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->onlyMethods(['isPackageActive', 'getPackage', 'getActivePackages'])
            ->disableOriginalConstructor()
            ->getMock();
        $package
            ->method('getPackagePath')
            ->willReturn('/path/to/foo/');
        $packageManager
            ->method('getActivePackages')
            ->willReturn(['foo' => $package]);
        $packageManager
            ->method('isPackageActive')
            ->with(self::equalTo('foo'))
            ->willReturn(true);
        $packageManager
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
     * @return array<string, array{0: string}>
     */
    public static function validPathStrInvalidCharactersDataProvider(): array
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
     */
    #[DataProvider('validPathStrInvalidCharactersDataProvider')]
    #[Test]
    public function validPathStrDetectsInvalidCharacters(string $path): void
    {
        self::assertFalse(GeneralUtility::validPathStr($path));
    }

    /**
     * Data provider for positive values within validPathStr()
     *
     * @return array<string, array{0: string}>
     */
    public static function validPathStrDataProvider(): array
    {
        $data = [
            'normal ascii path' => ['fileadmin/templates/myfile..xml'],
            'special character' => ['fileadmin/templates/Ссылка (fce).xml'],
        ];

        return $data;
    }

    /**
     * Tests whether Unicode characters are recognized as valid file name characters.
     */
    #[DataProvider('validPathStrDataProvider')]
    #[Test]
    public function validPathStrWorksWithUnicodeFileNames(string $path): void
    {
        self::assertTrue(GeneralUtility::validPathStr($path));
    }

    /////////////////////////////////////////////////////////////////////////////////////
    // Tests concerning copyDirectory
    /////////////////////////////////////////////////////////////////////////////////////
    #[Test]
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

    #[Test]
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

    public static function callUserFunctionInvalidParameterDataProvider(): array
    {
        return [
            'Method does not exist' => [GeneralUtilityTestClass::class . '->calledUserFunction', 1294585865],
            'Class does not exist' => ['t3lib_divTest21345->user_calledUserFunction', 1294585866],
            'No method name' => [GeneralUtilityTestClass::class, 1294585867],
            'No class name' => ['->user_calledUserFunction', 1294585866],
            'No function name' => ['', 1294585867],
        ];
    }

    /**
     * @param non-empty-string $functionName
     */
    #[DataProvider('callUserFunctionInvalidParameterDataProvider')]
    #[Test]
    public function callUserFunctionWillThrowExceptionForInvalidParameters(string $functionName, int $expectedException): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode($expectedException);
        $inputData = ['foo' => 'bar'];
        GeneralUtility::callUserFunction($functionName, $inputData, $this);
    }

    #[Test]
    public function callUserFunctionCanCallClosure(): void
    {
        $inputData = ['foo' => 'bar'];
        $result = GeneralUtility::callUserFunction(static fn(): string => 'Worked fine', $inputData, $this);
        self::assertEquals('Worked fine', $result);
    }

    #[Test]
    public function callUserFunctionCanCallMethod(): void
    {
        $inputData = ['foo' => 'bar'];
        $result = GeneralUtility::callUserFunction(GeneralUtilityTestClass::class . '->user_calledUserFunction', $inputData, $this);
        self::assertEquals('Worked fine', $result);
    }

    #[Test]
    public function callUserFunctionTrimsSpaces(): void
    {
        $inputData = ['foo' => 'bar'];
        $result = GeneralUtility::callUserFunction("\t" . GeneralUtilityTestClass::class . '->user_calledUserFunction ', $inputData, $this);
        self::assertEquals('Worked fine', $result);
    }

    #[Test]
    public function callUserFunctionAcceptsClosures(): void
    {
        $inputData = ['foo' => 'bar'];
        $closure = static function ($parameters, $reference) use ($inputData) {
            $reference->assertEquals($inputData, $parameters, 'Passed data does not match expected output');
            return 'Worked fine';
        };
        self::assertEquals('Worked fine', GeneralUtility::callUserFunction($closure, $inputData, $this));
    }

    #[Test]
    public function getAllFilesAndFoldersInPathReturnsArrayWithMd5Keys(): void
    {
        $directory = $this->getTestDirectory('directory_');
        $filesAndDirectories = GeneralUtility::getAllFilesAndFoldersInPath([], $directory, '', true);
        $check = true;
        foreach ($filesAndDirectories as $md5 => $path) {
            if (!preg_match('/^[a-f0-9]{32}$/', $md5)) {
                $check = false;
            }
        }
        self::assertTrue($check);
    }

    /**
     * If the element is not empty, its contents might be treated as "something" (instead of "nothing")
     * e.g. by Fluid view helpers, which is why we want to avoid that.
     */
    #[Test]
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

    #[Test]
    public function xml2arrayUsesCache(): void
    {
        $cacheMock = $this->createMock(FrontendInterface::class);
        $cacheMock->method('getIdentifier')->willReturn('runtime');
        $cacheMock->expects(self::atLeastOnce())->method('get')->with('generalUtilityXml2Array')->willReturn(false);
        $cacheMock->expects(self::atLeastOnce())->method('set')->with('generalUtilityXml2Array', self::anything());
        $cacheManager = new CacheManager();
        $cacheManager->registerCache($cacheMock);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager);
        GeneralUtility::xml2array('<?xml version="1.0" encoding="utf-8" standalone="yes"?>', 'T3:');
    }

    /**
     * @return string[][]
     */
    public static function xml2arrayProcessHandlesWhitespacesDataProvider(): array
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

    #[DataProvider('xml2arrayProcessHandlesWhitespacesDataProvider')]
    #[Test]
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
    public static function xml2arrayProcessHandlesTagNamespacesDataProvider(): array
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

    #[DataProvider('xml2arrayProcessHandlesTagNamespacesDataProvider')]
    #[Test]
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
    public static function xml2arrayProcessHandlesDocumentTagDataProvider(): array
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

    #[DataProvider('xml2arrayProcessHandlesDocumentTagDataProvider')]
    #[Test]
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
    public static function xml2ArrayProcessHandlesBigXmlContentDataProvider(): array
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

    #[DataProvider('xml2ArrayProcessHandlesBigXmlContentDataProvider')]
    #[Test]
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
     * @return array[]
     */
    public static function xml2ArrayProcessHandlesAttributeTypesDataProvider(): array
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

    #[DataProvider('xml2ArrayProcessHandlesAttributeTypesDataProvider')]
    #[Test]
    public function xml2ArrayProcessHandlesAttributeTypes(string $input, mixed $expected): void
    {
        $result = GeneralUtility::xml2arrayProcess($input);
        self::assertSame($expected, $result['index']['vDEF']);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function locationHeaderUrlDataProvider(): array
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
     * @throws Exception
     */
    #[DataProvider('locationHeaderUrlDataProvider')]
    #[Test]
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

    #[Test]
    public function createVersionNumberedFilenameDoesNotResolveBackpathForAbsolutePathInBackend(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['versionNumberInFilename'] = true;

        $uniqueFilename = StringUtility::getUniqueId() . 'backend';
        $testFileDirectory = Environment::getVarPath() . '/tests/';
        $testFilepath = $testFileDirectory . $uniqueFilename . '.css';
        $this->testFilesToDelete[] = $testFilepath;
        GeneralUtility::mkdir_deep($testFileDirectory);
        touch($testFilepath);

        $versionedFilename = GeneralUtility::createVersionNumberedFilename($testFilepath);

        self::assertMatchesRegularExpression('/^.*\/tests\/' . $uniqueFilename . '\.[0-9]+\.css/', $versionedFilename);
    }

    #[Test]
    public function createVersionNumberedFilenameDoesNotResolveBackpathForAbsolutePath(): void
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
        $request = new ServerRequest('https://www.example.com', 'GET');
        $GLOBALS['TYPO3_REQUEST'] = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

        $GLOBALS['TYPO3_CONF_VARS']['FE']['versionNumberInFilename'] = false;

        $uniqueFilename = StringUtility::getUniqueId() . 'frontend';
        $testFileDirectory = Environment::getVarPath() . '/tests/';
        $testFilepath = $testFileDirectory . $uniqueFilename . '.css';
        $this->testFilesToDelete[] = $testFilepath;
        GeneralUtility::mkdir_deep($testFileDirectory);
        touch($testFilepath);

        $versionedFilename = GeneralUtility::createVersionNumberedFilename($testFilepath);

        self::assertMatchesRegularExpression('/^.*\/tests\/' . $uniqueFilename . '\.css\?[0-9]+/', $versionedFilename);
    }

    #[Test]
    public function createVersionNumberedFilenameKeepsInvalidAbsolutePathInFrontendAndAddsQueryString(): void
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
        $request = new ServerRequest('https://www.example.com', 'GET');
        $GLOBALS['TYPO3_REQUEST'] = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $uniqueFilename = StringUtility::getUniqueId('main_');
        $testFileDirectory = Environment::getPublicPath() . '/static/';
        $testFilepath = $testFileDirectory . $uniqueFilename . '.css';
        GeneralUtility::mkdir_deep($testFileDirectory);
        touch($testFilepath);

        $GLOBALS['TYPO3_CONF_VARS']['FE']['versionNumberInFilename'] = false;
        $incomingFileName = '/' . PathUtility::stripPathSitePrefix($testFilepath);
        $versionedFilename = GeneralUtility::createVersionNumberedFilename($incomingFileName);
        self::assertStringContainsString('.css?', $versionedFilename);
        self::assertStringStartsWith('/static/main_', $versionedFilename);

        $incomingFileName = PathUtility::stripPathSitePrefix($testFilepath);
        $versionedFilename = GeneralUtility::createVersionNumberedFilename($incomingFileName);
        self::assertStringContainsString('.css?', $versionedFilename);
        self::assertStringStartsWith('static/main_', $versionedFilename);

        GeneralUtility::rmdir($testFileDirectory, true);
    }

    #[Test]
    public function createVersionNumberedFilenameResolvesAlreadyGivenAbsolutePathInBackend(): void
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
        $request = new ServerRequest('https://www.example.com', 'GET');
        $GLOBALS['TYPO3_REQUEST'] = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $uniqueFilename = StringUtility::getUniqueId('main_');
        $testFileDirectory = Environment::getPublicPath() . '/static/';
        $testFilepath = $testFileDirectory . $uniqueFilename . '.css';
        GeneralUtility::mkdir_deep($testFileDirectory);
        touch($testFilepath);

        $GLOBALS['TYPO3_CONF_VARS']['BE']['versionNumberInFilename'] = false;
        $incomingFileName = '/' . PathUtility::stripPathSitePrefix($testFilepath);
        $versionedFilename = GeneralUtility::createVersionNumberedFilename($incomingFileName);
        self::assertStringContainsString('.css?', $versionedFilename);
        self::assertStringStartsWith('/static/main_', $versionedFilename);

        $incomingFileName = PathUtility::stripPathSitePrefix($testFilepath);
        $versionedFilename = GeneralUtility::createVersionNumberedFilename($incomingFileName);
        self::assertStringContainsString('.css?', $versionedFilename);
        self::assertStringStartsWith('static/main_', $versionedFilename);

        GeneralUtility::rmdir($testFileDirectory, true);
    }

    #[Test]
    public function getMaxUploadFileSizeReturnsPositiveInt(): void
    {
        $result = GeneralUtility::getMaxUploadFileSize();
        self::assertGreaterThan(0, $result);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function hmacReturnsHashOfProperLength(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '';
        $hmac = GeneralUtility::hmac('message');
        self::assertTrue(!empty($hmac) && is_string($hmac));
        self::assertEquals(strlen($hmac), 40);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function hmacReturnsEqualHashesForEqualInput(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '';
        $msg0 = 'message';
        $msg1 = 'message';
        self::assertEquals(GeneralUtility::hmac($msg0), GeneralUtility::hmac($msg1));
    }

    #[Test]
    #[IgnoreDeprecations]
    public function hmacReturnsNoEqualHashesForNonEqualInput(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '';
        $msg0 = 'message0';
        $msg1 = 'message1';
        self::assertNotEquals(GeneralUtility::hmac($msg0), GeneralUtility::hmac($msg1));
    }
}
