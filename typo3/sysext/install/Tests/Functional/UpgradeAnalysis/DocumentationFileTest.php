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

namespace TYPO3\CMS\Install\Tests\Functional\UpgradeAnalysis;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Install\UpgradeAnalysis\DocumentationFile;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class DocumentationFileTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public function setUp(): void
    {
        parent::setUp();

        $content_12345 = [
            '====',
            'Breaking: #12345 - Issue',
            '====',
            '',
            'some text content',
        ];
        $content_98574 = [
            '====',
            'Important: #98574 - Issue',
            '====',
            '',
            'Something else',
            '',
            '.. index:: unittest',
        ];
        $content_13579 = [
            '====',
            'Breaking: #13579 - Issue',
            '====',
            '',
            'Some more content',
        ];
        $currentVersion = (int)explode('.', VersionNumberUtility::getNumericTypo3Version())[0];
        $publicPath = Environment::getPublicPath();
        mkdir($publicPath . '/Changelog');
        mkdir($publicPath . '/Changelog/1.2');
        file_put_contents($publicPath . '/Changelog/1.2/Breaking-12345-Issue.rst', implode("\n", $content_12345));
        mkdir($publicPath . '/Changelog/2.0');
        file_put_contents($publicPath . '/Changelog/2.0/Important-98574-Issue.rst', implode("\n", $content_98574));
        mkdir($publicPath . '/Changelog/' . ($currentVersion - 3) . '.0');
        file_put_contents($publicPath . '/Changelog/' . ($currentVersion - 3) . '.0/Important-98574-Issue.rst', implode("\n", $content_98574));
        mkdir($publicPath . '/Changelog/' . ($currentVersion - 2) . '.0');
        file_put_contents($publicPath . '/Changelog/' . ($currentVersion - 2) . '.0/Important-98574-Issue.rst', implode("\n", $content_98574));
        mkdir($publicPath . '/Changelog/' . ($currentVersion - 1) . '.0');
        file_put_contents($publicPath . '/Changelog/' . ($currentVersion - 1) . '.0/Important-98574-Issue.rst', implode("\n", $content_98574));
        mkdir($publicPath . '/Changelog/' . $currentVersion . '.0');
        file_put_contents($publicPath . '/Changelog/' . $currentVersion . '.0/Breaking-13579-Issue.rst', implode("\n", $content_13579));
        file_put_contents($publicPath . '/Changelog/' . $currentVersion . '.0/Important-13579-Issue.rst', implode("\n", $content_13579));
        file_put_contents($publicPath . '/Changelog/' . $currentVersion . '.0/Index.rst', '');
    }

    public function tearDown(): void
    {
        GeneralUtility::rmdir(Environment::getPublicPath() . '/Changelog', true);
        parent::tearDown();
    }

    /**
     * Data provider with invalid dir path. They should raise an exception and don't process.
     */
    public static function invalidDirProvider(): array
    {
        return [
            ['root' => '/'],
            ['etc' => '/etc'],
            ['etc/passwd' => '/etc/passwd'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidDirProvider
     */
    public function findDocumentationFilesThrowsExceptionIfPathIsNotInGivenChangelogDir(string $path): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1485425530);
        $subject = new DocumentationFile();
        $subject->findDocumentationFiles($path);
    }

    /**
     * Data provider with invalid file paths. They should raise an exception and don't process.
     */
    public static function invalidFilesProvider(): array
    {
        return [
            ['/etc/passwd' => '/etc/passwd'],
            ['root' => '/'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidFilesProvider
     */
    public function getListEntryThrowsExceptionForFilesNotBelongToChangelogDir(string $path): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1485425531);
        $subject = new DocumentationFile();
        $subject->getListEntry($path);
    }

    /**
     * @test
     */
    public function findDocumentationFilesReturnsArrayOfFilesForTheLastThreeMajorVersions(): void
    {
        $currentVersion = (int)explode('.', VersionNumberUtility::getNumericTypo3Version())[0];
        $expected = [
            0 => $currentVersion-2 . '.0',
            1 => $currentVersion-1 . '.0',
            2 => $currentVersion . '.0',
        ];
        $subject = new DocumentationFile(Environment::getPublicPath() . '/Changelog');
        self::assertEquals($expected, $subject->findDocumentationDirectories(Environment::getPublicPath() . '/Changelog'));
    }

    /**
     * @test
     */
    public function findDocumentsRespectsFilesWithSameIssueNumber(): void
    {
        $currentVersion = (int)explode('.', VersionNumberUtility::getNumericTypo3Version())[0];
        $subject = new DocumentationFile(Environment::getPublicPath() . '/Changelog');
        self::assertCount(2, $subject->findDocumentationFiles(Environment::getPublicPath() . '/Changelog/' . $currentVersion . '.0'));
    }

    /**
     * @test
     */
    public function extractingTagsProvidesTagsAsDesired(): void
    {
        $expected = [
            'unittest',
            'Important',
        ];
        $subject = new DocumentationFile(Environment::getPublicPath() . '/Changelog');
        $result = $subject->findDocumentationFiles(Environment::getPublicPath() . '/Changelog/2.0');
        $firstResult = current($result);
        self::assertEquals($expected, $firstResult['tags']);
    }
}
