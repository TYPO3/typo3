<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\Processor;

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
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\DataProcessing\GalleryProcessor;

/**
 * Tests for  GalleryProcessor
 */
class GalleryProcessorTest extends UnitTestCase
{
    /**
     * @var ContentObjectRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contentObjectRenderer;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->contentObjectRenderer = $this->getMock(
            ContentObjectRenderer::class,
            ['dummy']
        );
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException
     */
    public function processThrowsExceptionWhenFilesProcessedDataKeyIsNotFound()
    {
        $processor = new GalleryProcessor();
        $processor->process(
            $this->contentObjectRenderer,
            [],
            [],
            []
        );
    }

    /**
     * Gallery position test data provider
     */
    public function galleryPositionDataProvider()
    {
        return [
            'Default: horizontal above' => [
                [],
                [
                    'horizontal' => 'center',
                    'vertical' => 'above',
                    'noWrap' => false
                ]
            ],
            'right above' => [
                ['mediaOrientation' => 1],
                [
                    'horizontal' => 'right',
                    'vertical' => 'above',
                    'noWrap' => false
                ]
            ],
            'left above' => [
                ['mediaOrientation' => 2],
                [
                    'horizontal' => 'left',
                    'vertical' => 'above',
                    'noWrap' => false
                ]
            ],
            'center below' => [
                ['mediaOrientation' => 8],
                [
                    'horizontal' => 'center',
                    'vertical' => 'below',
                    'noWrap' => false
                ]
            ],
            'right below' => [
                ['mediaOrientation' => 9],
                [
                    'horizontal' => 'right',
                    'vertical' => 'below',
                    'noWrap' => false
                ]
            ],
            'left below' => [
                ['mediaOrientation' => 10],
                [
                    'horizontal' => 'left',
                    'vertical' => 'below',
                    'noWrap' => false
                ]
            ],
            'right intext' => [
                ['mediaOrientation' => 17],
                [
                    'horizontal' => 'right',
                    'vertical' => 'intext',
                    'noWrap' => false
                ]
            ],
            'left intext' => [
                ['mediaOrientation' => 18],
                [
                    'horizontal' => 'left',
                    'vertical' => 'intext',
                    'noWrap' => false
                ]
            ],
            'right intext no wrap' => [
                ['mediaOrientation' => 25],
                [
                    'horizontal' => 'right',
                    'vertical' => 'intext',
                    'noWrap' => true
                ]
            ],
            'left intext no wrap' => [
                ['mediaOrientation' => 26],
                [
                    'horizontal' => 'left',
                    'vertical' => 'intext',
                    'noWrap' => true
                ]
            ],

        ];
    }

    /**
     * @test
     * @dataProvider galleryPositionDataProvider
     */
    public function galleryPositionTest($processorConfiguration, $expected)
    {
        $processor = new GalleryProcessor();
        $processedData = $processor->process(
            $this->contentObjectRenderer,
            [],
            $processorConfiguration,
            ['files' => []]
        );

        $this->assertEquals($expected, $processedData['gallery']['position']);
    }

    /**
     * @test
     */
    public function maxGalleryWidthTest()
    {
        $processor = new GalleryProcessor();
        $processedData = $processor->process(
            $this->contentObjectRenderer,
            [],
            ['maxGalleryWidth' => 200, 'maxGalleryWidthInText' => 100],
            ['files' => []]
        );

        $this->assertEquals(200, $processedData['gallery']['width']);
    }

    /**
     * @test
     */
    public function maxGalleryWidthWhenInTextTest()
    {
        $processor = new GalleryProcessor();
        $processedData = $processor->process(
            $this->contentObjectRenderer,
            [],
            ['maxGalleryWidth' => 200, 'maxGalleryWidthInText' => 100, 'mediaOrientation' => 26],
            ['files' => []]
        );

        $this->assertEquals(100, $processedData['gallery']['width']);
    }

    /**
     * Count test data provider
     * @return array
     */
    public function countDataProvider()
    {
        return [
            'Default settings with 3 files' => [
                3,
                [],
                [],
                [
                    'files' => 3,
                    'columns' => 1,
                    'rows' => 3
                ]
            ],
            'NumberOfColumns set by value' => [
                3,
                [],
                ['numberOfColumns' => 2],
                [
                    'files' => 3,
                    'columns' => 2,
                    'rows' => 2
                ]
            ],
            'NumberOfColumns set in data' => [
                3,
                ['imagecols' => 3],
                [],
                [
                    'files' => 3,
                    'columns' => 3,
                    'rows' => 1
                ]
            ],
            'NumberOfColumns set in custom data field' => [
                6,
                ['my_imagecols' => 4],
                ['numberOfColumns.' => [
                    'field' => 'my_imagecols'
                ]],
                [
                    'files' => 6,
                    'columns' => 4,
                    'rows' => 2
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider countDataProvider
     */
    public function countResultTest($numberOfFiles, $data, $processorConfiguration, $expected)
    {
        $files = [];
        for ($i = 0; $i < $numberOfFiles; $i++) {
            $files[] = $this->getMock(FileReference::class, [], [], '', false);
        }
        $this->contentObjectRenderer->data = $data;
        $processor = new GalleryProcessor();
        $processedData = $processor->process(
            $this->contentObjectRenderer,
            [],
            $processorConfiguration,
            ['files' => $files]
        );

        $this->assertEquals($expected, $processedData['gallery']['count']);
    }

    /**
     * Data provider for calculateMediaWidthsAndHeightsTest
     *
     * @return array
     */
    public function calculateMediaWidthsAndHeightsDataProvider()
    {
        return [
            'Default settings' => [
                [
                    [200, 100],
                    [200, 100],
                    [200, 100],
                ],
                [],
                [
                    1 => [
                        1 => ['width' => 200, 'height' => 100]
                    ],
                    2 => [
                        1 => ['width' => 200, 'height' => 100]
                    ],
                    3 => [
                        1 => ['width' => 200, 'height' => 100]
                    ],
                ]
            ],
            'Max width set + number of columns set' => [
                [
                    [200, 100],
                    [200, 100],
                    [200, 100],
                ],
                ['maxGalleryWidth' => 200, 'numberOfColumns' => 2],
                [
                    1 => [
                        1 => ['width' => 100, 'height' => 50],
                        2 => ['width' => 100, 'height' => 50]
                    ],
                    2 => [
                        1 => ['width' => 100, 'height' => 50],
                        2 => ['width' => null, 'height' => null]
                    ],
                ]
            ],
            'Max width set, number of columns + border (padding) set' => [
                [
                    [200, 100],
                    [200, 100],
                    [200, 100],
                ],
                [
                    'maxGalleryWidth' => 200,
                    'numberOfColumns' => 2,
                    'borderEnabled' => true,
                    'borderPadding' => 4,
                    'borderWidth' => 0,
                ],
                [
                    1 => [
                        1 => ['width' => 92, 'height' => 46],
                        2 => ['width' => 92, 'height' => 46]
                    ],
                    2 => [
                        1 => ['width' => 92, 'height' => 46],
                        2 => ['width' => null, 'height' => null]
                    ],
                ]
            ],
            'Max width set, number of columns + border (width) set' => [
                [
                    [200, 100],
                    [200, 100],
                    [200, 100],
                ],
                [
                    'maxGalleryWidth' => 200,
                    'numberOfColumns' => 2,
                    'borderEnabled' => true,
                    'borderPadding' => 0,
                    'borderWidth' => 4,
                ],
                [
                    1 => [
                        1 => ['width' => 92, 'height' => 46],
                        2 => ['width' => 92, 'height' => 46]
                    ],
                    2 => [
                        1 => ['width' => 92, 'height' => 46],
                        2 => ['width' => null, 'height' => null]
                    ],
                ]
            ],
            'Max width set, number of columns + border (padding + width) set' => [
                [
                    [200, 100],
                    [200, 100],
                    [200, 100],
                ],
                [
                    'maxGalleryWidth' => 200,
                    'numberOfColumns' => 2,
                    'borderEnabled' => true,
                    'borderPadding' => 1,
                    'borderWidth' => 4,
                ],
                [
                    1 => [
                        1 => ['width' => 90, 'height' => 45],
                        2 => ['width' => 90, 'height' => 45]
                    ],
                    2 => [
                        1 => ['width' => 90, 'height' => 45],
                        2 => ['width' => null, 'height' => null]
                    ],
                ]
            ],
            'Equal height set' => [
                [
                    [200, 100],
                    [200, 300],
                    [100, 50],
                    [2020, 1000],
                    [1000, 1000],
                ],
                [
                    'maxGalleryWidth' => 500,
                    'numberOfColumns' => 3,
                    'equalMediaHeight' => 75
                ],
                [
                    1 => [
                        1 => ['width' => 150, 'height' => 75],
                        2 => ['width' => 50, 'height' => 75],
                        3 => ['width' => 150, 'height' => 75]
                    ],
                    2 => [
                        1 => ['width' => 151, 'height' => 75],
                        2 => ['width' => 75, 'height' => 75],
                        3 => ['width' => null, 'height' => null]
                    ],
                ]
            ],
            'Equal width set' => [
                [
                    [200, 100],
                    [200, 300],
                    [100, 50],
                ],
                [
                    'maxGalleryWidth' => 200,
                    'numberOfColumns' => 3,
                    'equalMediaWidth' => 75
                ],
                [
                    1 => [
                        1 => ['width' => 66, 'height' => 33],
                        2 => ['width' => 66, 'height' => 99],
                        3 => ['width' => 66, 'height' => 33]
                    ],
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider calculateMediaWidthsAndHeightsDataProvider
     */
    public function calculateMediaWidthsAndHeightsTest($testFiles, $processorConfiguration, $expected)
    {
        $files = [];
        foreach ($testFiles as $fileConfig) {
            $fileReference = $this->getMock(FileReference::class, [], [], '', false);
            $fileReference->expects($this->any())
                ->method('getProperty')
                ->will($this->returnValueMap([
                    ['width', $fileConfig[0]],
                    ['height', $fileConfig[1]]
                ]));
            $files[] = $fileReference;
        }

        $processor = new GalleryProcessor();
        $processedData = $processor->process(
            $this->contentObjectRenderer,
            [],
            $processorConfiguration,
            ['files' => $files]
        );

        foreach ($expected as $row => $columns) {
            $this->assertArrayHasKey($row, $processedData['gallery']['rows'], 'Row exists');
            foreach ($columns as $column => $dimensions) {
                $this->assertArrayHasKey($column, $processedData['gallery']['rows'][$row]['columns'], 'Column exists');
                $this->assertEquals($dimensions, $processedData['gallery']['rows'][$row]['columns'][$column]['dimensions'], 'Dimensions match');
            }
        }
    }
}
