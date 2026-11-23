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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\PublicUrl;

use League\Flysystem\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Resource\PublicUrl\PrefixPublicUrlGenerator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PrefixPublicUrlGeneratorTest extends UnitTestCase
{
    public static function publicUrlIsGeneratedBelowTheBaseUriDataProvider(): \Generator
    {
        yield 'relative base uri' => ['fileadmin/', 'a/file.pdf', 'fileadmin/a/file.pdf'];
        yield 'base uri without trailing slash' => ['fileadmin', 'file.pdf', 'fileadmin/file.pdf'];
        yield 'absolute base uri' => ['https://cdn.example.com/files/', 'file.pdf', 'https://cdn.example.com/files/file.pdf'];
        yield 'segments are encoded' => ['fileadmin/', 'my folder/my fileä#1.pdf', 'fileadmin/my%20folder/my%20file%C3%A4%231.pdf'];
        yield 'root folder' => ['fileadmin/', '', 'fileadmin/'];
        yield 'empty base uri' => ['', 'file.pdf', 'file.pdf'];
    }

    #[DataProvider('publicUrlIsGeneratedBelowTheBaseUriDataProvider')]
    #[Test]
    public function publicUrlIsGeneratedBelowTheBaseUri(string $baseUri, string $path, string $expected): void
    {
        self::assertSame($expected, new PrefixPublicUrlGenerator($baseUri)->publicUrl($path, new Config()));
    }

    public static function pathIsResolvedFromUriDataProvider(): \Generator
    {
        yield 'file below relative base uri' => ['fileadmin/', 'https://acme.com/fileadmin/a/file.pdf', '/', 'a/file.pdf'];
        yield 'relative base uri in sub directory installation' => ['fileadmin/', 'https://acme.com/cms/fileadmin/file.pdf', '/cms/', 'file.pdf'];
        yield 'base uri with absolute path' => ['/media/', 'https://acme.com/media/file.pdf', '/cms/', 'file.pdf'];
        yield 'absolute base uri' => ['https://cdn.example.com/files/', 'https://cdn.example.com/files/a/file.pdf', '/', 'a/file.pdf'];
        yield 'absolute base uri with other scheme' => ['http://cdn.example.com/files/', 'https://cdn.example.com/files/file.pdf', '/', 'file.pdf'];
        yield 'absolute base uri with port' => ['https://cdn.example.com:8443/files/', 'https://cdn.example.com:8443/files/file.pdf', '/', 'file.pdf'];
        yield 'host is compared case insensitive' => ['https://CDN.example.com/files/', 'https://cdn.example.com/files/file.pdf', '/', 'file.pdf'];
        yield 'protocol relative base uri' => ['//cdn.example.com/files/', 'https://cdn.example.com/files/file.pdf', '/', 'file.pdf'];
        yield 'encoded segments are decoded' => ['fileadmin/', 'https://acme.com/fileadmin/my%20folder/my%20file%C3%A4%231.pdf', '/', 'my folder/my fileä#1.pdf'];
        yield 'encoded base uri' => ['my%20files/', 'https://acme.com/my%20files/file.pdf', '/', 'file.pdf'];
        yield 'folder' => ['fileadmin/', 'https://acme.com/fileadmin/folder/', '/', 'folder/'];
        yield 'root folder' => ['fileadmin/', 'https://acme.com/fileadmin/', '/', ''];
    }

    #[DataProvider('pathIsResolvedFromUriDataProvider')]
    #[Test]
    public function pathIsResolvedFromUri(string $baseUri, string $uri, string $sitePath, string $expected): void
    {
        self::assertSame($expected, new PrefixPublicUrlGenerator($baseUri)->resolvePath(new Uri($uri), $sitePath));
    }

    public static function uriOutsideOfBaseUriIsNotResolvedDataProvider(): \Generator
    {
        yield 'other path' => ['fileadmin/', 'https://acme.com/some/page'];
        yield 'path sharing the prefix' => ['fileadmin/', 'https://acme.com/fileadmin-other/file.pdf'];
        yield 'base uri without trailing slash' => ['fileadmin/', 'https://acme.com/fileadmin'];
        yield 'relative base uri outside of sub directory installation' => ['fileadmin/', 'https://acme.com/fileadmin/file.pdf', '/cms/'];
        yield 'other host' => ['https://cdn.example.com/files/', 'https://acme.com/files/file.pdf'];
        yield 'other port' => ['https://cdn.example.com/files/', 'https://cdn.example.com:8443/files/file.pdf'];
        yield 'empty base uri would cover every path' => ['', 'https://acme.com/file.pdf'];
        yield 'base uri of the host root would cover every path' => ['https://acme.com/', 'https://acme.com/file.pdf'];
        yield 'dot segment' => ['fileadmin/', 'https://acme.com/fileadmin/./file.pdf'];
        yield 'dot dot segment' => ['fileadmin/', 'https://acme.com/fileadmin/../typo3conf/file.pdf'];
        yield 'encoded dot dot segment' => ['fileadmin/', 'https://acme.com/fileadmin/%2E%2E/typo3conf/file.pdf'];
        yield 'encoded slash' => ['fileadmin/', 'https://acme.com/fileadmin/a%2F..%2Ffile.pdf'];
        yield 'encoded backslash' => ['fileadmin/', 'https://acme.com/fileadmin/a%5C..%5Cfile.pdf'];
        yield 'empty segment' => ['fileadmin/', 'https://acme.com/fileadmin//file.pdf'];
        yield 'null byte' => ['fileadmin/', 'https://acme.com/fileadmin/file%00.pdf'];
    }

    #[DataProvider('uriOutsideOfBaseUriIsNotResolvedDataProvider')]
    #[Test]
    public function uriOutsideOfBaseUriIsNotResolved(string $baseUri, string $uri, string $sitePath = '/'): void
    {
        self::assertNull(new PrefixPublicUrlGenerator($baseUri)->resolvePath(new Uri($uri), $sitePath));
    }

    #[Test]
    public function generatedUrlIsResolvedToTheSamePath(): void
    {
        $subject = new PrefixPublicUrlGenerator('https://cdn.example.com/files/');
        $path = 'a b/ü%#?.pdf';
        self::assertSame($path, $subject->resolvePath(new Uri($subject->publicUrl($path, new Config())), '/'));
    }
}
