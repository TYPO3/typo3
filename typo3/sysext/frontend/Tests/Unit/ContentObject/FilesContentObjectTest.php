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

namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject;

use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Resource\Collection\StaticFileCollection;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileCollectionRepository;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\FilesContentObject;
use TYPO3\CMS\Frontend\ContentObject\TextContentObject;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Resource\FileCollector;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FilesContentObjectTest extends UnitTestCase
{

    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var \TYPO3\CMS\Frontend\ContentObject\FilesContentObject|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $subject;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['SIM_ACCESS_TIME'] = 0;
        $packageManagerMock = $this->getMockBuilder(PackageManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $templateService = $this->getMockBuilder(TemplateService::class)
            ->setConstructorArgs([null, $packageManagerMock])
            ->setMethods(['getFileName', 'linkData'])
            ->getMock();
        $tsfe = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $tsfe->tmpl = $templateService;

        $contentObjectRenderer = new ContentObjectRenderer($tsfe);
        $contentObjectRenderer->setContentObjectClassMap([
            'FILES' => FilesContentObject::class,
            'TEXT' => TextContentObject::class,
        ]);
        $this->subject = $this->getMockBuilder(FilesContentObject::class)
            ->setMethods(['getFileCollector'])
            ->setConstructorArgs([$contentObjectRenderer])
            ->getMock();
    }

    /**
     * @return array
     */
    public function renderReturnsFilesForFileReferencesDataProvider(): array
    {
        return [
            'One file reference' => [
                [
                    'references' => '1',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p>',
            ],
            'One file reference with begin higher than allowed' => [
                [
                    'references' => '1',
                    'begin' => '1',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '',
            ],
            'One file reference with maxItems higher than allowed' => [
                [
                    'references' => '1',
                    'maxItems' => '2',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p>',
            ],
            'Multiple file references' => [
                [
                    'references' => '1,2,3',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p>',
            ],
            'Multiple file references with begin' => [
                [
                    'references' => '1,2,3',
                    'begin' => '1',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 2</p><p>File 3</p>',
            ],
            'Multiple file references with negative begin' => [
                [
                    'references' => '1,2,3',
                    'begin' => '-1',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p>',
            ],
            'Multiple file references with maxItems' => [
                [
                    'references' => '1,2,3',
                    'maxItems' => '2',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p>',
            ],
            'Multiple file references with negative maxItems' => [
                [
                    'references' => '1,2,3',
                    'maxItems' => '-2',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '',
            ],
            'Multiple file references with begin and maxItems' => [
                [
                    'references' => '1,2,3',
                    'begin' => '1',
                    'maxItems' => '1',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 2</p>',
            ],
            'Multiple file references unsorted' => [
                [
                    'references' => '1,3,2',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 3</p><p>File 2</p>',
            ],
            'Multiple file references sorted by name' => [
                [
                    'references' => '3,1,2',
                    'sorting' => 'name',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderReturnsFilesForFileReferencesDataProvider
     * @param array $configuration
     * @param string $expected
     */
    public function renderReturnsFilesForFileReferences(array $configuration, string $expected): void
    {
        $fileReferenceMap = [];
        for ($i = 1; $i < 4; $i++) {
            $fileReference = $this->createMock(FileReference::class);
            $fileReference->expects(self::any())
                ->method('getName')
                ->willReturn('File ' . $i);
            $fileReference->expects(self::any())
                ->method('hasProperty')
                ->with('name')
                ->willReturn(true);
            $fileReference->expects(self::any())
                ->method('getProperty')
                ->with('name')
                ->willReturn('File ' . $i);

            $fileReferenceMap[] = [$i, $fileReference];
        }

        $fileRepository = $this->createMock(FileRepository::class);
        $fileRepository->expects(self::any())
            ->method('findFileReferenceByUid')
            ->willReturnMap($fileReferenceMap);
        $fileCollector = $this->getMockBuilder(FileCollector::class)
            ->setMethods(['getFileRepository'])
            ->getMock();
        $fileCollector->expects(self::any())
            ->method('getFileRepository')
            ->willReturn($fileRepository);

        $this->subject->expects(self::any())
            ->method('getFileCollector')
            ->willReturn($fileCollector);

        self::assertSame($expected, $this->subject->render($configuration));
    }

    /**
     * @return array
     */
    public function renderReturnsFilesForFilesDataProvider(): array
    {
        return [
            'One file' => [
                [
                    'files' => '1',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p>',
            ],
            'One file with begin higher than allowed' => [
                [
                    'files' => '1',
                    'begin' => '1',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '',
            ],
            'One file with maxItems higher than allowed' => [
                [
                    'files' => '1',
                    'maxItems' => '2',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p>',
            ],
            'Multiple files' => [
                [
                    'files' => '1,2,3',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p>',
            ],
            'Multiple files with begin' => [
                [
                    'files' => '1,2,3',
                    'begin' => '1',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 2</p><p>File 3</p>',
            ],
            'Multiple files with negative begin' => [
                [
                    'files' => '1,2,3',
                    'begin' => '-1',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p>',
            ],
            'Multiple files with maxItems' => [
                [
                    'files' => '1,2,3',
                    'maxItems' => '2',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p>',
            ],
            'Multiple files with negative maxItems' => [
                [
                    'files' => '1,2,3',
                    'maxItems' => '-2',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '',
            ],
            'Multiple files with begin and maxItems' => [
                [
                    'files' => '1,2,3',
                    'begin' => '1',
                    'maxItems' => '1',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 2</p>',
            ],
            'Multiple files unsorted' => [
                [
                    'files' => '1,3,2',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 3</p><p>File 2</p>',
            ],
            'Multiple files sorted by name' => [
                [
                    'files' => '3,1,2',
                    'sorting' => 'name',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderReturnsFilesForFilesDataProvider
     * @param array $configuration
     * @param string $expected
     */
    public function renderReturnsFilesForFiles(array $configuration, string $expected): void
    {
        $fileMap = [];
        for ($i = 1; $i < 4; $i++) {
            $file = $this->createMock(File::class);
            $file->expects(self::any())
                ->method('getName')
                ->willReturn('File ' . $i);
            $file->expects(self::any())
                ->method('hasProperty')
                ->with('name')
                ->willReturn(true);
            $file->expects(self::any())
                ->method('getProperty')
                ->with('name')
                ->willReturn('File ' . $i);

            $fileMap[] = [$i, [], $file];
        }

        $resourceFactory = $this->createMock(ResourceFactory::class);
        $resourceFactory->expects(self::any())
            ->method('getFileObject')
            ->willReturnMap($fileMap);
        $fileCollector = $this->getMockBuilder(FileCollector::class)
            ->setMethods(['getResourceFactory'])
            ->getMock();
        $fileCollector->expects(self::any())
            ->method('getResourceFactory')
            ->willReturn($resourceFactory);

        $this->subject->expects(self::any())
            ->method('getFileCollector')
            ->willReturn($fileCollector);

        self::assertSame($expected, $this->subject->render($configuration));
    }

    /**
     * @return array
     */
    public function renderReturnsFilesForCollectionsDataProvider(): array
    {
        return [
            'One collection' => [
                [
                    'collections' => '1',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p>',
            ],
            'One collection with begin' => [
                [
                    'collections' => '1',
                    'begin' => '1',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 2</p><p>File 3</p>',
            ],
            'One collection with begin higher than allowed' => [
                [
                    'collections' => '1',
                    'begin' => '3',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '',
            ],
            'One collection with maxItems' => [
                [
                    'collections' => '1',
                    'maxItems' => '2',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p>',
            ],
            'One collection with maxItems higher than allowed' => [
                [
                    'collections' => '1',
                    'maxItems' => '4',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p>',
            ],
            'One collections with begin and maxItems' => [
                [
                    'collections' => '1',
                    'begin' => '1',
                    'maxItems' => '1',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 2</p>',
            ],
            'Multiple collections' => [
                [
                    'collections' => '1,2,3',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p><p>File 4</p><p>File 5</p><p>File 6</p><p>File 7</p><p>File 8</p><p>File 9</p>',
            ],
            'Multiple collections with begin' => [
                [
                    'collections' => '1,2,3',
                    'begin' => '3',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 4</p><p>File 5</p><p>File 6</p><p>File 7</p><p>File 8</p><p>File 9</p>',
            ],
            'Multiple collections with negative begin' => [
                [
                    'collections' => '1,2,3',
                    'begin' => '-3',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p><p>File 4</p><p>File 5</p><p>File 6</p><p>File 7</p><p>File 8</p><p>File 9</p>',
            ],
            'Multiple collections with maxItems' => [
                [
                    'collections' => '1,2,3',
                    'maxItems' => '5',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p><p>File 4</p><p>File 5</p>',
            ],
            'Multiple collections with negative maxItems' => [
                [
                    'collections' => '1,2,3',
                    'maxItems' => '-5',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '',
            ],
            'Multiple collections with begin and maxItems' => [
                [
                    'collections' => '1,2,3',
                    'begin' => '4',
                    'maxItems' => '3',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 5</p><p>File 6</p><p>File 7</p>',
            ],
            'Multiple collections unsorted' => [
                [
                    'collections' => '1,3,2',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p><p>File 7</p><p>File 8</p><p>File 9</p><p>File 4</p><p>File 5</p><p>File 6</p>',
            ],
            'Multiple collections sorted by name' => [
                [
                    'collections' => '3,1,2',
                    'sorting' => 'name',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p><p>File 4</p><p>File 5</p><p>File 6</p><p>File 7</p><p>File 8</p><p>File 9</p>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderReturnsFilesForCollectionsDataProvider
     * @param array $configuration
     * @param string $expected
     */
    public function renderReturnsFilesForCollections(array $configuration, string $expected): void
    {
        $collectionMap = [];
        $fileCount = 1;
        for ($i = 1; $i < 4; $i++) {
            $fileReferenceArray = [];
            for ($j = 1; $j < 4; $j++) {
                $fileReference = $this->createMock(FileReference::class);
                $fileReference->expects(self::any())
                    ->method('getName')
                    ->willReturn('File ' . $fileCount);
                $fileReference->expects(self::any())
                    ->method('hasProperty')
                    ->with('name')
                    ->willReturn(true);
                $fileReference->expects(self::any())
                    ->method('getProperty')
                    ->with('name')
                    ->willReturn('File ' . $fileCount);

                $fileReferenceArray[] = $fileReference;
                $fileCount++;
            }

            $collection = $this->createMock(StaticFileCollection::class);
            $collection->expects(self::any())
                ->method('getItems')
                ->willReturn($fileReferenceArray);

            $collectionMap[] = [$i, $collection];
        }

        $collectionRepository = $this->getMockBuilder(FileCollectionRepository::class)->getMock();
        $collectionRepository->expects(self::any())
            ->method('findByUid')
            ->willReturnMap($collectionMap);
        $fileCollector = $this->getMockBuilder(FileCollector::class)
            ->setMethods(['getFileCollectionRepository'])
            ->getMock();
        $fileCollector->expects(self::any())
            ->method('getFileCollectionRepository')
            ->willReturn($collectionRepository);
        $this->subject->expects(self::any())
            ->method('getFileCollector')
            ->willReturn($fileCollector);

        self::assertSame($expected, $this->subject->render($configuration));
    }

    /**
     * @return array
     */
    public function renderReturnsFilesForFoldersDataProvider(): array
    {
        return [
            'One folder' => [
                [
                    'folders' => '1:myfolder/',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p>',
            ],
            'One folder with begin' => [
                [
                    'folders' => '1:myfolder/',
                    'begin' => '1',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 2</p><p>File 3</p>',
            ],
            'One folder with begin higher than allowed' => [
                [
                    'folders' => '1:myfolder/',
                    'begin' => '3',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '',
            ],
            'One folder with maxItems' => [
                [
                    'folders' => '1:myfolder/',
                    'maxItems' => '2',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p>',
            ],
            'One folder with maxItems higher than allowed' => [
                [
                    'folders' => '1:myfolder/',
                    'maxItems' => '4',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p>',
            ],
            'One folder with begin and maxItems' => [
                [
                    'folders' => '1:myfolder/',
                    'begin' => '1',
                    'maxItems' => '1',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 2</p>',
            ],
            'Multiple folders' => [
                [
                    'folders' => '1:myfolder/,2:myfolder/,3:myfolder/',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p><p>File 4</p><p>File 5</p><p>File 6</p><p>File 7</p><p>File 8</p><p>File 9</p>',
            ],
            'Multiple folders with begin' => [
                [
                    'folders' => '1:myfolder/,2:myfolder/,3:myfolder/',
                    'begin' => '3',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 4</p><p>File 5</p><p>File 6</p><p>File 7</p><p>File 8</p><p>File 9</p>',
            ],
            'Multiple folders with negative begin' => [
                [
                    'folders' => '1:myfolder/,2:myfolder/,3:myfolder/',
                    'begin' => '-3',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p><p>File 4</p><p>File 5</p><p>File 6</p><p>File 7</p><p>File 8</p><p>File 9</p>',
            ],
            'Multiple folders with maxItems' => [
                [
                    'folders' => '1:myfolder/,2:myfolder/,3:myfolder/',
                    'maxItems' => '5',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p><p>File 4</p><p>File 5</p>',
            ],
            'Multiple folders with negative maxItems' => [
                [
                    'folders' => '1:myfolder/,2:myfolder/,3:myfolder/',
                    'maxItems' => '-5',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '',
            ],
            'Multiple folders with begin and maxItems' => [
                [
                    'folders' => '1:myfolder/,2:myfolder/,3:myfolder/',
                    'begin' => '4',
                    'maxItems' => '3',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 5</p><p>File 6</p><p>File 7</p>',
            ],
            'Multiple folders unsorted' => [
                [
                    'folders' => '1:myfolder/,3:myfolder/,2:myfolder/',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p><p>File 7</p><p>File 8</p><p>File 9</p><p>File 4</p><p>File 5</p><p>File 6</p>',
            ],
            'Multiple folders sorted by name' => [
                [
                    'folders' => '3:myfolder/,1:myfolder/,2:myfolder/',
                    'sorting' => 'name',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p><p>File 4</p><p>File 5</p><p>File 6</p><p>File 7</p><p>File 8</p><p>File 9</p>',
            ],
            'Multiple folders recursively' => [
                [
                    'folders' => '1:myfolder/',
                    'folders.' => [
                        'recursive' => '1'
                    ],
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 7</p><p>File 8</p><p>File 9</p><p>File 1</p><p>File 2</p><p>File 3</p><p>File 4</p><p>File 5</p><p>File 6</p>',
                true
            ],
            'Multiple folders recursively, sorted by name' => [
                [
                    'folders' => '1:myfolder/',
                    'folders.' => [
                        'recursive' => '1'
                    ],
                    'sorting' => 'name',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>File 1</p><p>File 2</p><p>File 3</p><p>File 4</p><p>File 5</p><p>File 6</p><p>File 7</p><p>File 8</p><p>File 9</p>',
                true
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderReturnsFilesForFoldersDataProvider
     * @param array $configuration
     * @param string $expected
     * @param bool $recursive
     */
    public function renderReturnsFilesForFolders(array $configuration, string $expected, bool $recursive = false): void
    {
        $folderMap = [];
        $folders = [];
        $fileCount = 1;
        $filesArrayForFolder = [];
        for ($i = 1; $i < 4; $i++) {
            $filesArrayForFolder[$i] = [];
            for ($j = 1; $j < 4; $j++) {
                $file = $this->createMock(File::class);
                $file->expects(self::any())
                    ->method('getName')
                    ->willReturn('File ' . $fileCount);
                $file->expects(self::any())
                    ->method('hasProperty')
                    ->with('name')
                    ->willReturn(true);
                $file->expects(self::any())
                    ->method('getProperty')
                    ->with('name')
                    ->willReturn('File ' . $fileCount);

                $filesArrayForFolder[$i][] = $file;
                $fileCount++;
            }

            $folder = $this->createMock(Folder::class);

            if ($recursive) {
                if ($i < 3) {
                    $folders[$i] = $folder;
                    $folderMap[$i] = ['1:myfolder/mysubfolder-' . $i . '/', $folder];
                } else {
                    $folder->expects(self::any())
                        ->method('getSubfolders')
                        ->willReturn($folders);
                    $folderMap[$i] = ['1:myfolder/', $folder];
                }
            } else {
                $folderMap[$i] = [$i . ':myfolder/', $folder];
            }
        }
        foreach ($folderMap as $i => $folderMapInfo) {
            if ($i < 3 || !$recursive) {
                $folderMapInfo[1]->expects(self::any())
                    ->method('getFiles')
                    ->willReturn($filesArrayForFolder[$i]);
            } else {
                $recursiveFiles = array_merge(
                    $filesArrayForFolder[3],
                    $filesArrayForFolder[1],
                    $filesArrayForFolder[2]
                );
                $folderMapInfo[1]->expects(self::any())
                    ->method('getFiles')
                    ->willReturn($recursiveFiles);
            }
        }

        $resourceFactory = $this->createMock(ResourceFactory::class);
        $resourceFactory->expects(self::any())
            ->method('getFolderObjectFromCombinedIdentifier')
            ->willReturnMap($folderMap);
        $fileCollector = $this->getMockBuilder(FileCollector::class)
            ->setMethods(['getResourceFactory'])
            ->getMock();
        $fileCollector->expects(self::any())
            ->method('getResourceFactory')
            ->willReturn($resourceFactory);

        $this->subject->expects(self::any())
            ->method('getFileCollector')
            ->willReturn($fileCollector);

        self::assertSame($expected, $this->subject->render($configuration));
    }
}
