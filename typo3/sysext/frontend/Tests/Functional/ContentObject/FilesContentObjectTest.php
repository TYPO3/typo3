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

namespace TYPO3\CMS\Frontend\Tests\Functional\ContentObject;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FilesContentObjectTest extends FunctionalTestCase
{
    protected ?AbstractContentObject $subject;

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/frontend/Tests/Functional/Fixtures/Images' => 'fileadmin/images',
        'typo3/sysext/frontend/Tests/Functional/ContentObject/ImagesInStorage2' => 'storage2/images',
        'typo3/sysext/frontend/Tests/Functional/ContentObject/ImagesInStorage3' => 'storage3/images',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBackendUserFromFixture(1);
        $this->importCSVDataSet(__DIR__ . '/DataSet/FilesContentObjectDataSet.csv');

        $typoScriptFrontendController = new class() {
            public PageRepository $sys_page;
        };
        $typoScriptFrontendController->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $GLOBALS['TSFE'] = $typoScriptFrontendController;
        $contentObjectRenderer = GeneralUtility::getContainer()->get(ContentObjectRenderer::class);
        $request = new ServerRequest();
        $contentObjectRenderer->setRequest($request);
        $this->subject = $contentObjectRenderer->getContentObject('FILES');
    }

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
                '<p>team-t3board10.jpg</p>',
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
                '<p>team-t3board10.jpg</p>',
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
                '<p>team-t3board10.jpg</p><p>kasper-skarhoj1.jpg</p><p>typo3-logo.png</p>',
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
                '<p>kasper-skarhoj1.jpg</p><p>typo3-logo.png</p>',
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
                '<p>team-t3board10.jpg</p><p>kasper-skarhoj1.jpg</p><p>typo3-logo.png</p>',
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
                '<p>team-t3board10.jpg</p><p>kasper-skarhoj1.jpg</p>',
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
                '<p>kasper-skarhoj1.jpg</p>',
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
                '<p>team-t3board10.jpg</p><p>typo3-logo.png</p><p>kasper-skarhoj1.jpg</p>',
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
                '<p>kasper-skarhoj1.jpg</p><p>team-t3board10.jpg</p><p>typo3-logo.png</p>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderReturnsFilesForFileReferencesDataProvider
     */
    public function renderReturnsFilesForFileReferences(array $configuration, string $expected): void
    {
        self::assertSame($expected, $this->subject->render($configuration));
    }

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
                '<p>team-t3board10.jpg</p>',
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
                '<p>team-t3board10.jpg</p>',
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
                '<p>team-t3board10.jpg</p><p>kasper-skarhoj1.jpg</p><p>typo3-logo.png</p>',
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
                '<p>kasper-skarhoj1.jpg</p><p>typo3-logo.png</p>',
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
                '<p>team-t3board10.jpg</p><p>kasper-skarhoj1.jpg</p><p>typo3-logo.png</p>',
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
                '<p>team-t3board10.jpg</p><p>kasper-skarhoj1.jpg</p>',
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
                '<p>kasper-skarhoj1.jpg</p>',
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
                '<p>team-t3board10.jpg</p><p>typo3-logo.png</p><p>kasper-skarhoj1.jpg</p>',
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
                '<p>kasper-skarhoj1.jpg</p><p>team-t3board10.jpg</p><p>typo3-logo.png</p>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderReturnsFilesForFilesDataProvider
     */
    public function renderReturnsFilesForFiles(array $configuration, string $expected): void
    {
        self::assertSame($expected, $this->subject->render($configuration));
    }

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
                '<p>team-t3board10.jpg</p><p>kasper-skarhoj1.jpg</p><p>typo3-logo.png</p>',
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
                '<p>kasper-skarhoj1.jpg</p><p>typo3-logo.png</p>',
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
                '<p>team-t3board10.jpg</p><p>kasper-skarhoj1.jpg</p>',
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
                '<p>team-t3board10.jpg</p><p>kasper-skarhoj1.jpg</p><p>typo3-logo.png</p>',
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
                '<p>kasper-skarhoj1.jpg</p>',
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
                '<p>team-t3board10.jpg</p><p>kasper-skarhoj1.jpg</p><p>typo3-logo.png</p><p>file4.jpg</p><p>file5.jpg</p><p>file6.jpg</p><p>file7.jpg</p><p>file8.jpg</p><p>file9.jpg</p>',
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
                '<p>file4.jpg</p><p>file5.jpg</p><p>file6.jpg</p><p>file7.jpg</p><p>file8.jpg</p><p>file9.jpg</p>',
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
                '<p>team-t3board10.jpg</p><p>kasper-skarhoj1.jpg</p><p>typo3-logo.png</p><p>file4.jpg</p><p>file5.jpg</p><p>file6.jpg</p><p>file7.jpg</p><p>file8.jpg</p><p>file9.jpg</p>',
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
                '<p>team-t3board10.jpg</p><p>kasper-skarhoj1.jpg</p><p>typo3-logo.png</p><p>file4.jpg</p><p>file5.jpg</p>',
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
                '<p>file5.jpg</p><p>file6.jpg</p><p>file7.jpg</p>',
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
                '<p>team-t3board10.jpg</p><p>kasper-skarhoj1.jpg</p><p>typo3-logo.png</p><p>file7.jpg</p><p>file8.jpg</p><p>file9.jpg</p><p>file4.jpg</p><p>file5.jpg</p><p>file6.jpg</p>',
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
                '<p>file4.jpg</p><p>file5.jpg</p><p>file6.jpg</p><p>file7.jpg</p><p>file8.jpg</p><p>file9.jpg</p><p>kasper-skarhoj1.jpg</p><p>team-t3board10.jpg</p><p>typo3-logo.png</p>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderReturnsFilesForCollectionsDataProvider
     */
    public function renderReturnsFilesForCollections(array $configuration, string $expected): void
    {
        self::assertSame($expected, $this->subject->render($configuration));
    }

    public function renderReturnsFilesForFoldersDataProvider(): array
    {
        return [
            'One folder' => [
                [
                    'folders' => '1:images/',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>kasper-skarhoj1.jpg</p><p>team-t3board10.jpg</p><p>typo3-logo.png</p>',
            ],
            'One folder with begin' => [
                [
                    'folders' => '1:images/',
                    'begin' => '1',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>team-t3board10.jpg</p><p>typo3-logo.png</p>',
            ],
            'One folder with begin higher than allowed' => [
                [
                    'folders' => '1:images/',
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
                    'folders' => '1:images/',
                    'maxItems' => '2',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>kasper-skarhoj1.jpg</p><p>team-t3board10.jpg</p>',
            ],
            'One folder with maxItems higher than allowed' => [
                [
                    'folders' => '1:images/',
                    'maxItems' => '4',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>kasper-skarhoj1.jpg</p><p>team-t3board10.jpg</p><p>typo3-logo.png</p>',
            ],
            'One folder with begin and maxItems' => [
                [
                    'folders' => '1:images/',
                    'begin' => '1',
                    'maxItems' => '1',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>team-t3board10.jpg</p>',
            ],
            'Multiple folders' => [
                [
                    'folders' => '1:images/,2:images/,3:images/',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>kasper-skarhoj1.jpg</p><p>team-t3board10.jpg</p><p>typo3-logo.png</p><p>file4.jpg</p><p>file5.jpg</p><p>file6.jpg</p><p>file7.jpg</p><p>file8.jpg</p><p>file9.jpg</p>',
            ],
            'Multiple folders with begin' => [
                [
                    'folders' => '1:images/,2:images/,3:images/',
                    'begin' => '3',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>file4.jpg</p><p>file5.jpg</p><p>file6.jpg</p><p>file7.jpg</p><p>file8.jpg</p><p>file9.jpg</p>',
            ],
            'Multiple folders with negative begin' => [
                [
                    'folders' => '1:images/,2:images/,3:images/',
                    'begin' => '-3',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>kasper-skarhoj1.jpg</p><p>team-t3board10.jpg</p><p>typo3-logo.png</p><p>file4.jpg</p><p>file5.jpg</p><p>file6.jpg</p><p>file7.jpg</p><p>file8.jpg</p><p>file9.jpg</p>',
            ],
            'Multiple folders with maxItems' => [
                [
                    'folders' => '1:images/,2:images/,3:images/',
                    'maxItems' => '5',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>kasper-skarhoj1.jpg</p><p>team-t3board10.jpg</p><p>typo3-logo.png</p><p>file4.jpg</p><p>file5.jpg</p>',
            ],
            'Multiple folders with negative maxItems' => [
                [
                    'folders' => '1:images/,2:images/,3:images/',
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
                    'folders' => '1:images/,2:images/,3:images/',
                    'begin' => '4',
                    'maxItems' => '3',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>file5.jpg</p><p>file6.jpg</p><p>file7.jpg</p>',
            ],
            'Multiple folders unsorted' => [
                [
                    'folders' => '1:images/,3:images/,2:images/',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>kasper-skarhoj1.jpg</p><p>team-t3board10.jpg</p><p>typo3-logo.png</p><p>file7.jpg</p><p>file8.jpg</p><p>file9.jpg</p><p>file4.jpg</p><p>file5.jpg</p><p>file6.jpg</p>',
            ],
            'Multiple folders sorted by name' => [
                [
                    'folders' => '3:images/,1:images/,2:images/',
                    'sorting' => 'name',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>file4.jpg</p><p>file5.jpg</p><p>file6.jpg</p><p>file7.jpg</p><p>file8.jpg</p><p>file9.jpg</p><p>kasper-skarhoj1.jpg</p><p>team-t3board10.jpg</p><p>typo3-logo.png</p>',
            ],
            'Multiple folders recursively' => [
                [
                    'folders' => '2:images/',
                    'folders.' => [
                        'recursive' => '1',
                    ],
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>afilesub1.jpg</p><p>afilesub2.jpg</p><p>afilesub3.jpg</p><p>bfilesub1.jpg</p><p>bfilesub2.jpg</p><p>bfilesub3.jpg</p><p>file4.jpg</p><p>file5.jpg</p><p>file6.jpg</p>',
            ],
            'Multiple folders recursively, sorted by name' => [
                [
                    'folders' => '2:images/',
                    'folders.' => [
                        'recursive' => '1',
                    ],
                    'sorting' => 'name',
                    'renderObj' => 'TEXT',
                    'renderObj.' => [
                        'data' => 'file:current:name',
                        'wrap' => '<p>|</p>',
                    ],
                ],
                '<p>afilesub1.jpg</p><p>afilesub2.jpg</p><p>afilesub3.jpg</p><p>bfilesub1.jpg</p><p>bfilesub2.jpg</p><p>bfilesub3.jpg</p><p>file4.jpg</p><p>file5.jpg</p><p>file6.jpg</p>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderReturnsFilesForFoldersDataProvider
     */
    public function renderReturnsFilesForFolders(array $configuration, string $expected): void
    {
        self::assertSame($expected, $this->subject->render($configuration));
    }

    public function renderReturnsFilesForReferencesAsArrayDataProvider(): iterable
    {
        yield 'references option as array with nothing provided returns nothing' => [
            'configuration' => [
                'references.' => [
                    'fieldName' => '',
                ],
                'renderObj' => 'TEXT',
                'renderObj.' => [
                    'data' => 'file:current:name',
                    'wrap' => '<p>|</p>',
                ],
            ],
            'data' => [],
            'table' => 'tt_content',
            'expected' => '',
        ];

        yield 'references option as array and field name provided takes row of current data' => [
            'configuration' => [
                'references.' => [
                    'fieldName' => 'image',
                ],
                'renderObj' => 'TEXT',
                'renderObj.' => [
                    'data' => 'file:current:name',
                    'wrap' => '<p>|</p>',
                ],
            ],
            'data' => [
                'uid' => 298,
                'image' => 3,
            ],
            'table' => 'tt_content',
            'expected' => '<p>file4.jpg</p><p>file5.jpg</p><p>file6.jpg</p>',
        ];

        yield 'references option as array with uid provided overrides current uid, but uses same current table' => [
            'configuration' => [
                'references.' => [
                    'fieldName' => 'image',
                    'uid' => '297',
                ],
                'renderObj' => 'TEXT',
                'renderObj.' => [
                    'data' => 'file:current:name',
                    'wrap' => '<p>|</p>',
                ],
            ],
            'data' => [
                'uid' => 298,
                'image' => 3,
            ],
            'table' => 'tt_content',
            'expected' => '<p>team-t3board10.jpg</p><p>kasper-skarhoj1.jpg</p><p>typo3-logo.png</p>',
        ];

        yield 'references option as array with uid and table provided ignores current data completely' => [
            'configuration' => [
                'references.' => [
                    'fieldName' => 'media',
                    'uid' => '1',
                    'table' => 'pages',
                ],
                'renderObj' => 'TEXT',
                'renderObj.' => [
                    'data' => 'file:current:name',
                    'wrap' => '<p>|</p>',
                ],
            ],
            'data' => [
                'uid' => 298,
                'image' => 3,
            ],
            'table' => 'tt_content',
            'expected' => '<p>file7.jpg</p>',
        ];

        yield 'references option as array where uid results in nothing, falls back to current data' => [
            'configuration' => [
                'references.' => [
                    'fieldName' => 'image',
                    'uid.' => [
                        'field' => 'not_existing_field',
                    ],
                ],
                'renderObj' => 'TEXT',
                'renderObj.' => [
                    'data' => 'file:current:name',
                    'wrap' => '<p>|</p>',
                ],
            ],
            'data' => [
                'uid' => 298,
                'image' => 3,
            ],
            'table' => 'tt_content',
            'expected' => '<p>file4.jpg</p><p>file5.jpg</p><p>file6.jpg</p>',
        ];

        yield 'references option as array where table results in nothing, falls back to current data' => [
            'configuration' => [
                'references.' => [
                    'fieldName' => 'image',
                    'table' => '',
                ],
                'renderObj' => 'TEXT',
                'renderObj.' => [
                    'data' => 'file:current:name',
                    'wrap' => '<p>|</p>',
                ],
            ],
            'data' => [
                'uid' => 298,
                'image' => 3,
            ],
            'table' => 'tt_content',
            'expected' => '<p>file4.jpg</p><p>file5.jpg</p><p>file6.jpg</p>',
        ];
    }

    /**
     * @test
     * @dataProvider renderReturnsFilesForReferencesAsArrayDataProvider
     */
    public function renderReturnsFilesForReferencesAsArray(array $configuration, array $data, string $table, string $expected): void
    {
        $this->subject->getContentObjectRenderer()->start($data, $table);
        self::assertSame($expected, $this->subject->render($configuration));
    }
}
