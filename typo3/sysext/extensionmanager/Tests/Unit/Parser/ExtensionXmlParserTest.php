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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Parser;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Parser\ExtensionXmlParser;
use TYPO3\CMS\Extensionmanager\Tests\Unit\Fixtures\ExtensionXmlParserObserverFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ExtensionXmlParserTest extends UnitTestCase
{
    #[DataProvider('isValidVersionNumberDataProvider')]
    #[Test]
    public function isValidVersionNumber(string $versionNumber, bool $isValid): void
    {
        $subject = $this->getAccessibleMock(ExtensionXmlParser::class, null);
        $subject->_set('version', $versionNumber);

        self::assertEquals($isValid, $subject->isValidVersionNumber());
    }

    public static function isValidVersionNumberDataProvider(): \Generator
    {
        yield 'Successive zeros are not allowed' => [
            '00.2.3',
            false,
        ];
        yield 'Version premodifiers are not allowed' => [
            'v11.2.3',
            false,
        ];
        yield 'Version postmodifiers are not allowed' => [
            '11.2.3-pre-release',
            false,
        ];
        yield 'Characters are not allowed in general' => [
            '11.a.3',
            false,
        ];
        yield 'More than three characters are not allowed' => [
            '11.2.3999',
            false,
        ];
        yield 'Version most use three segements (major, minor, patch)' => [
            '11.2',
            false,
        ];
        yield 'Successive separators are not allowed' => [
            '11..2',
            false,
        ];
        yield 'Leading separator is not allowed' => [
            '.11.2',
            false,
        ];
        yield 'Invalid separator' => [
            '11-2-3',
            false,
        ];
        yield 'Missing separator' => [
            '1123',
            false,
        ];
        yield 'Valid version number' => [
            '11.2.3',
            true,
        ];
    }

    #[Test]
    public function parseXmlOnMissingFileThrowsException(): void
    {
        $subject = $this->getAccessibleMock(ExtensionXmlParser::class, null);

        $this->expectException(ExtensionManagerException::class);
        $this->expectExceptionCode(1342640689);
        $subject->parseXml('/path/to/missing/file');
    }

    #[Test]
    public function parseXmlWithValidXmlWorks(): void
    {
        $subject = $this->getAccessibleMock(ExtensionXmlParser::class, null);

        $observer = new ExtensionXmlParserObserverFixture();
        $subject->attach($observer);
        $subject->parseXml(__DIR__ . '/../Fixtures/valid-extensions.xml');

        $expectedStructureFromFixture = [
            0 => [
                'extkey' => 'news',
                'version' => '1.0.0',
                'alldownloadcounter' => 9001,
                'downloadcounteR' => 9001,
                'title' => 'News',
                'ownerusername' => 'supergeorg',
                'authorname' => 'Best Georg',
                'authoremail' => 'support@example.com',
                'authorcompany' => 'The Georg Company',
                'lastuploaddate' => 1215424196,
                't3xfilemd5' => '8b2bcd42d41f54564b150d3d1ed33030',
                'state' => 'stable',
                'reviewstate' => 0,
                'category' => 'plugin',
                'description' => 'It\'s news.',
                'dependencies' => 'a:1:{s:7:"depends";a:3:{s:5:"typo3";s:9:"4.1-0.0.0";s:3:"php";s:9:"5.1-0.0.0";s:3:"cms";s:0:"";}}',
                'uploadcomment' => 'New news, extra extra. Read all about it.',
                'documentationlink' => 'https://docs.typo3.org/typo3cms/extensions/news',
                'distributionimage' => '',
                'distributionwelcomeimage' => '',
            ],
            1 => [
                'extkey' => 'news',
                'version' => '0.1.0',
                'alldownloadcounter' => 9001,
                'downloadcounteR' => 1,
                'title' => 'News',
                'ownerusername' => 'supergeorg',
                'authorname' => 'Best Georg',
                'authoremail' => 'support@example.com',
                'authorcompany' => 'The Georg Company',
                'lastuploaddate' => 1215424196,
                't3xfilemd5' => '8b2bcd42d41f54564b150d3d1ed33030',
                'state' => 'beta',
                'reviewstate' => 0,
                'category' => 'plugin',
                'description' => 'It\'s news.',
                'dependencies' => 'a:1:{s:7:"depends";a:3:{s:5:"typo3";s:9:"4.1-0.0.0";s:3:"php";s:9:"5.1-0.0.0";s:3:"cms";s:0:"";}}',
                'uploadcomment' => 'New news, extra extra. Read all about it.',
                'documentationlink' => 'https://docs.typo3.org/typo3cms/extensions/news',
                'distributionimage' => '',
                'distributionwelcomeimage' => '',
            ],
            2 => [
                'extkey' => 'fake-news',
                'version' => '1.0.0',
                'alldownloadcounter' => 123123123,
                'downloadcounteR' => 123123124,
                'title' => 'Fake News',
                'ownerusername' => 'anyone',
                'authorname' => 'Mirror Universe Georg',
                'authoremail' => 'support@example.com',
                'authorcompany' => 'Starship Fakeprice',
                'lastuploaddate' => 1215424196,
                't3xfilemd5' => '8b2bcd42d41f54564b150d3d1ed33030',
                'state' => 'stable',
                'reviewstate' => 0,
                'category' => 'plugin',
                'description' => 'It\'s fake news.',
                'dependencies' => 'a:1:{s:7:"depends";a:3:{s:5:"typo3";s:9:"4.1-0.0.0";s:3:"php";s:9:"5.1-0.0.0";s:3:"cms";s:0:"";}}',
                'uploadcomment' => 'Be kind.',
                'documentationlink' => 'https://docs.typo3.org/typo3cms/extensions/news',
                'distributionimage' => '',
                'distributionwelcomeimage' => '',
            ],
        ];

        self::assertEquals($expectedStructureFromFixture, $observer->rows);
    }

    #[Test]
    public function parseXmlWithInvalidXmlThrowsException(): void
    {
        $subject = $this->getAccessibleMock(ExtensionXmlParser::class, null);

        $observer = new ExtensionXmlParserObserverFixture();
        $subject->attach($observer);

        $this->expectException(ExtensionManagerException::class);
        $this->expectExceptionCode(1342640703);
        $subject->parseXml(__DIR__ . '/../Fixtures/invalid-extensions.xml');
    }
}
