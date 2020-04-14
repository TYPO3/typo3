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

namespace TYPO3\CMS\Install\Tests\Unit\UpgradeAnalysis;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Prophecy\Argument;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Install\UpgradeAnalysis\DocumentationFile;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DocumentationFileTest extends UnitTestCase
{
    /**
     * @var DocumentationFile
     */
    protected $documentationFileService;

    /**
     * @var  vfsStreamDirectory
     */
    protected $docRoot;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * set up test environment
     */
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
        $content_45678 = [
            '====',
            'Important: #45678 - Issue',
            '====',
            '',
            'Some more text content',
        ];

        $content_98574 = [
            '====',
            'Important: #98574 - Issue',
            '====',
            '',
            'Something else',
            '',
            '.. index:: unittest'
        ];
        $content_13579 = [
            '====',
            'Breaking: #13579 - Issue',
            '====',
            '',
            'Some more content'
        ];

        $currentVersion = (int)explode('.', VersionNumberUtility::getNumericTypo3Version())[0];
        $structure = [
            'Changelog' => [
                '1.2' => [
                    'Breaking-12345-Issue.rst' => implode("\n", $content_12345),
                    'Important-45678-Issue.rst' => implode("\n", $content_45678),

                ],
                '2.0' => [
                    'Important-98574-Issue.rst' => implode("\n", $content_98574),
                ],
                $currentVersion-3 . '.0' => [
                    'Important-98574-Issue.rst' => implode("\n", $content_98574),
                ],
                $currentVersion-2 . '.0' => [
                    'Important-98574-Issue.rst' => implode("\n", $content_98574),
                ],
                $currentVersion-1 . '.0' => [
                    'Important-98574-Issue.rst' => implode("\n", $content_98574),
                ],
                $currentVersion . '.0' => [
                    'Important-98574-Issue.rst' => implode("\n", $content_98574),
                ],
                'master' => [
                    'Breaking-13579-Issue.rst' => implode("\n", $content_13579),
                    'Important-13579-Issue.rst' => implode("\n", $content_13579),
                    'Index.rst' => '',
                ],
            ],
        ];

        $this->docRoot = vfsStream::setup('root', null, $structure);

        $this->registry = $this->prophesize(Registry::class);
        $this->documentationFileService = new DocumentationFile(
            $this->registry->reveal(),
            vfsStream::url('root/Changelog')
        );
    }

    /**
     * dataprovider with invalid dir path. They should raise an exception and don't process.
     * @return array
     */
    public function invalidDirProvider()
    {
        return [
            [
                'root' => '/'
            ],
            [
                'etc' => '/etc'
            ],
            [
                'etc/passwd' => '/etc/passwd'
            ],
        ];
    }

    /**
     * @dataProvider invalidDirProvider
     * @test
     */
    public function findDocumentationFilesThrowsExceptionIfPathIsNotInGivenChangelogDir(string $path)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1485425530);
        $documentationFileService = new DocumentationFile($this->registry->reveal());
        $documentationFileService->findDocumentationFiles($path);
    }

    /**
     * @test
     */
    public function findDocumentationFilesReturnsArrayOfFilesForTheLastThreeMajorVersions()
    {
        $currentVersion = (int)explode('.', VersionNumberUtility::getNumericTypo3Version())[0];
        $expected = [
            $currentVersion-2 . '.0' => [],
            $currentVersion-1 . '.0' => [],
            $currentVersion . '.0' => [],
            'master' => [],
        ];

        $result = $this->documentationFileService->findDocumentationDirectories(vfsStream::url('root/Changelog'));
        self::assertEquals(array_keys($expected), $result);
    }

    /**
     * @test
     */
    public function findDocumentsRespectsFilesWithSameIssueNumber()
    {
        $result = $this->documentationFileService->findDocumentationFiles(vfsStream::url('root/Changelog/master'));
        self::assertCount(2, $result);
    }

    /**
     * @test
     */
    public function extractingTagsProvidesTagsAsDesired()
    {
        $expected = [
            'unittest',
            'Important',
        ];
        $result = $this->documentationFileService->findDocumentationFiles(vfsStream::url('root/Changelog/2.0'));
        $firstResult = current($result);
        self::assertEquals($expected, $firstResult['tags']);
    }

    /**
     * @test
     */
    public function filesAreFilteredByUsersChoice()
    {
        $ignoredFiles = ['vfs://root/Changelog/1.2/Breaking-12345-Issue.rst'];
        $this->registry->get(
            'upgradeAnalysisIgnoreFilter',
            'ignoredDocumentationFiles',
            Argument::any()
        )->willReturn($ignoredFiles);

        $result = $this->documentationFileService->findDocumentationFiles(vfsStream::url('root/Changelog/1.2'));
        self::assertArrayNotHasKey(12345, $result);
    }

    /**
     * @return array
     */
    public function invalidFilesProvider(): array
    {
        return [
            ['/etc/passwd' => '/etc/passwd'],
            ['root' => '/'],
        ];
    }

    /**
     * @dataProvider invalidFilesProvider
     * @param string $path
     * @test
     */
    public function getListEntryThrowsExceptionForFilesNotBelongToChangelogDir(string $path)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1485425531);
        $this->documentationFileService->getListEntry($path);
    }
}
