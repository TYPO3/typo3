<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject;

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
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Resource\Collection\StaticFileCollection;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileCollectionRepository;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\FilesContentObject;
use TYPO3\CMS\Frontend\ContentObject\TextContentObject;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Frontend\Resource\FileCollector;

/**
 * Testcase for TYPO3\CMS\Frontend\ContentObject\FilesContentObject
 */
class FilesContentObjectTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Frontend\ContentObject\FilesContentObject|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject = null;

    /**
     * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected $tsfe = null;

    /**
     * Set up
     */
    protected function setUp()
    {
        $templateService = $this->getMock(TemplateService::class, ['getFileName', 'linkData']);
        $this->tsfe = $this->getMock(TypoScriptFrontendController::class, ['dummy'], [], '', false);
        $this->tsfe->tmpl = $templateService;
        $this->tsfe->config = [];
        $this->tsfe->page = [];
        $sysPageMock = $this->getMock(PageRepository::class, ['getRawRecord']);
        $this->tsfe->sys_page = $sysPageMock;
        $GLOBALS['TSFE'] = $this->tsfe;
        $GLOBALS['TSFE']->csConvObj = new CharsetConverter();
        $GLOBALS['TSFE']->renderCharset = 'utf-8';

        $contentObjectRenderer = new ContentObjectRenderer();
        $contentObjectRenderer->setContentObjectClassMap([
            'FILES' => FilesContentObject::class,
            'TEXT' => TextContentObject::class,
        ]);
        $this->subject = $this->getMock(FilesContentObject::class, ['getFileCollector'], [$contentObjectRenderer]);
    }

    /**
     * @return array
     */
    public function renderReturnsFilesForFileReferencesDataProvider()
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
     */
    public function renderReturnsFilesForFileReferences($configuration, $expected)
    {
        $fileReferenceMap = [];
        for ($i = 1; $i < 4; $i++) {
            $fileReference = $this->getMock(FileReference::class, [], [], '', false);
            $fileReference->expects($this->any())
                ->method('getName')
                ->will($this->returnValue('File ' . $i));
            $fileReference->expects($this->any())
                ->method('hasProperty')
                ->with('name')
                ->will($this->returnValue(true));
            $fileReference->expects($this->any())
                ->method('getProperty')
                ->with('name')
                ->will($this->returnValue('File ' . $i));

            $fileReferenceMap[] = [$i, $fileReference];
        }

        $fileRepository = $this->getMock(\TYPO3\CMS\Core\Resource\FileRepository::class);
        $fileRepository->expects($this->any())
            ->method('findFileReferenceByUid')
            ->will($this->returnValueMap($fileReferenceMap));
        $fileCollector = $this->getMock(FileCollector::class, ['getFileRepository']);
        $fileCollector->expects($this->any())
            ->method('getFileRepository')
            ->will($this->returnValue($fileRepository));

        $this->subject->expects($this->any())
            ->method('getFileCollector')
            ->will($this->returnValue($fileCollector));

        $this->assertSame($expected, $this->subject->render($configuration));
    }

    /**
     * @return array
     */
    public function renderReturnsFilesForFilesDataProvider()
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
     */
    public function renderReturnsFilesForFiles($configuration, $expected)
    {
        $fileMap = [];
        for ($i = 1; $i < 4; $i++) {
            $file = $this->getMock(File::class, [], [], '', false);
            $file->expects($this->any())
                ->method('getName')
                ->will($this->returnValue('File ' . $i));
            $file->expects($this->any())
                ->method('hasProperty')
                ->with('name')
                ->will($this->returnValue(true));
            $file->expects($this->any())
                ->method('getProperty')
                ->with('name')
                ->will($this->returnValue('File ' . $i));

            $fileMap[] = [$i, [], $file];
        }

        $resourceFactory = $this->getMock(ResourceFactory::class);
        $resourceFactory->expects($this->any())
            ->method('getFileObject')
            ->will($this->returnValueMap($fileMap));
        $fileCollector = $this->getMock(FileCollector::class, ['getResourceFactory']);
        $fileCollector->expects($this->any())
            ->method('getResourceFactory')
            ->will($this->returnValue($resourceFactory));

        $this->subject->expects($this->any())
            ->method('getFileCollector')
            ->will($this->returnValue($fileCollector));

        $this->assertSame($expected, $this->subject->render($configuration));
    }

    /**
     * @return array
     */
    public function renderReturnsFilesForCollectionsDataProvider()
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
     */
    public function renderReturnsFilesForCollections($configuration, $expected)
    {
        $collectionMap = [];
        $fileCount = 1;
        for ($i = 1; $i < 4; $i++) {
            $fileReferenceArray = [];
            for ($j = 1; $j < 4; $j++) {
                $fileReference = $this->getMock(FileReference::class, [], [], '', false);
                $fileReference->expects($this->any())
                    ->method('getName')
                    ->will($this->returnValue('File ' . $fileCount));
                $fileReference->expects($this->any())
                    ->method('hasProperty')
                    ->with('name')
                    ->will($this->returnValue(true));
                $fileReference->expects($this->any())
                    ->method('getProperty')
                    ->with('name')
                    ->will($this->returnValue('File ' . $fileCount));

                $fileReferenceArray[] = $fileReference;
                $fileCount++;
            }

            $collection = $this->getMock(StaticFileCollection::class, [], [], '', false);
            $collection->expects($this->any())
                ->method('getItems')
                ->will($this->returnValue($fileReferenceArray));

            $collectionMap[] = [$i, $collection];
        }

        $collectionRepository = $this->getMock(FileCollectionRepository::class);
        $collectionRepository->expects($this->any())
            ->method('findByUid')
            ->will($this->returnValueMap($collectionMap));
        $fileCollector = $this->getMock(FileCollector::class, ['getFileCollectionRepository']);
        $fileCollector->expects($this->any())
            ->method('getFileCollectionRepository')
            ->will($this->returnValue($collectionRepository));
        $this->subject->expects($this->any())
            ->method('getFileCollector')
            ->will($this->returnValue($fileCollector));

        $this->assertSame($expected, $this->subject->render($configuration));
    }

    /**
     * @return array
     */
    public function renderReturnsFilesForFoldersDataProvider()
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
     */
    public function renderReturnsFilesForFolders($configuration, $expected, $recursive = false)
    {
        $folderMap = [];
        $folders = [];
        $fileCount = 1;
        $filesArrayForFolder = [];
        for ($i = 1; $i < 4; $i++) {
            $filesArrayForFolder[$i] = [];
            for ($j = 1; $j < 4; $j++) {
                $file = $this->getMock(File::class, [], [], '', false);
                $file->expects($this->any())
                    ->method('getName')
                    ->will($this->returnValue('File ' . $fileCount));
                $file->expects($this->any())
                    ->method('hasProperty')
                    ->with('name')
                    ->will($this->returnValue(true));
                $file->expects($this->any())
                    ->method('getProperty')
                    ->with('name')
                    ->will($this->returnValue('File ' . $fileCount));

                $filesArrayForFolder[$i][] = $file;
                $fileCount++;
            }

            $folder = $this->getMock(Folder::class, [], [], '', false);

            if ($recursive) {
                if ($i < 3) {
                    $folders[$i] = $folder;
                    $folderMap[$i] = ['1:myfolder/mysubfolder-' . $i . '/', $folder];
                } else {
                    $folder->expects($this->any())
                        ->method('getSubfolders')
                        ->will($this->returnValue($folders));
                    $folderMap[$i] = ['1:myfolder/', $folder];
                }
            } else {
                $folderMap[$i] = [$i . ':myfolder/', $folder];
            }
        }
        foreach ($folderMap as $i => $folderMapInfo) {
            if ($i < 3 || !$recursive) {
                $folderMapInfo[1]->expects($this->any())
                    ->method('getFiles')
                    ->will($this->returnValue($filesArrayForFolder[$i]));
            } else {
                $recursiveFiles = array_merge($filesArrayForFolder[3], $filesArrayForFolder[1], $filesArrayForFolder[2]);
                $folderMapInfo[1]->expects($this->any())
                    ->method('getFiles')
                    ->will($this->returnValue($recursiveFiles));
            }
        }

        $resourceFactory = $this->getMock(ResourceFactory::class);
        $resourceFactory->expects($this->any())
            ->method('getFolderObjectFromCombinedIdentifier')
            ->will($this->returnValueMap($folderMap));
        $fileCollector = $this->getMock(FileCollector::class, ['getResourceFactory']);
        $fileCollector->expects($this->any())
            ->method('getResourceFactory')
            ->will($this->returnValue($resourceFactory));

        $this->subject->expects($this->any())
            ->method('getFileCollector')
            ->will($this->returnValue($fileCollector));

        $this->assertSame($expected, $this->subject->render($configuration));
    }
}
