<?php

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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Fluid\ViewHelpers\ImageViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Test case
 */
class ImageViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var ImageViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new ImageViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @return array
     */
    public function getInvalidArguments()
    {
        return [
            [['image' => null]],
            [['src' => null]],
            [['src' => '']],
            [['src' => 'something', 'image' => 'something']],
        ];
    }

    /**
     * @test
     * @dataProvider getInvalidArguments
     * @param array $arguments
     */
    public function renderMethodThrowsExceptionOnInvalidArguments(array $arguments)
    {
        $this->setArgumentsUnderTest($this->viewHelper, $arguments);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(1382284106);

        $this->viewHelper->render();
    }

    /**
     * @return array
     */
    public function getRenderMethodTestValues()
    {
        return [
            [
                [
                    'src' => 'test',
                    'width' => 100,
                    'height' => 200,
                    'minWidth' => 300,
                    'maxWidth' => 400,
                    'minHeight' => 500,
                    'maxHeight' => 600,
                    'crop' => false
                ],
                [
                    'src' => 'test.png',
                    'width' => '100',
                    'height' => '200',
                    'alt' => 'alternative',
                    'title' => 'title'
                ]
            ],
            [
                [
                    'src' => 'test',
                    'width' => 100,
                    'height' => 200,
                    'minWidth' => 300,
                    'maxWidth' => 400,
                    'minHeight' => 500,
                    'maxHeight' => 600,
                    'crop' => null
                ],
                [
                    'src' => 'test.png',
                    'width' => '100',
                    'height' => '200',
                    'alt' => 'alternative',
                    'title' => 'title'
                ]
            ],
            [
                [
                    'src' => 'test',
                    'width' => 100,
                    'height' => 200,
                    'minWidth' => 300,
                    'maxWidth' => 400,
                    'minHeight' => 500,
                    'maxHeight' => 600,
                    'crop' => null,
                    'fileExtension' => 'jpg'
                ],
                [
                    'src' => 'test.jpg',
                    'width' => '100',
                    'height' => '200',
                    'alt' => 'alternative',
                    'title' => 'title'
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getRenderMethodTestValues
     * @param array $arguments
     * @param array $expected
     */
    public function renderMethodCreatesExpectedTag(array $arguments, array $expected)
    {
        $this->setArgumentsUnderTest($this->viewHelper, $arguments);

        $image = $this->getAccessibleMock(FileReference::class, ['getProperty', 'hasProperty'], [], '', false);
        $image->expects(self::any())->method('hasProperty')->willReturn(true);
        $image->expects(self::any())->method('getProperty')->willReturnMap([
            ['width', $arguments['width']],
            ['height', $arguments['height']],
            ['alternative', 'alternative'],
            ['title', 'title'],
            ['crop', 'crop']
        ]);
        $originalFile = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $originalFile->expects(self::any())->method('getProperties')->willReturn([]);

        $processedFile = $this->getMockBuilder(ProcessedFile::class)
            ->disableOriginalConstructor()
            ->getMock();

        $processedFile->expects(self::any())->method('getProperty')->willReturnMap([
            ['width', $arguments['width']],
            ['height', $arguments['height']],
        ]);

        $image->_set('originalFile', $originalFile);
        $image->_set('propertiesOfFileReference', []);
        $imageService = $this->createMock(ImageService::class);
        $imageService->expects(self::once())->method('getImage')->willReturn($image);
        $imageService->expects(self::once())->method('applyProcessingInstructions')->with($image, self::anything())->willReturn($processedFile);
        $imageService->expects(self::once())->method('getImageUri')->with($processedFile)->willReturn($expected['src']);

        $this->viewHelper->injectImageService($imageService);

        $tagBuilder = $this->getMockBuilder(TagBuilder::class)
            ->setMethods(['addAttribute', 'render'])
            ->getMock();
        $index = -1;
        foreach ($expected as $expectedAttribute => $expectedValue) {
            $tagBuilder->expects(self::at(++ $index))->method('addAttribute')->with($expectedAttribute, $expectedValue);
        }
        $tagBuilder->expects(self::once())->method('render');
        $this->viewHelper->setTagBuilder($tagBuilder);

        $this->viewHelper->render();
    }

    /**
     * @return array
     */
    public function getRenderMethodTestValuesWithoutFallbackProperties()
    {
        return [
            [
                [
                    'src' => 'test',
                    'width' => 100,
                    'height' => 200,
                    'minWidth' => 300,
                    'maxWidth' => 400,
                    'minHeight' => 500,
                    'maxHeight' => 600,
                    'crop' => false
                ],
                [
                    'src' => 'test.png',
                    'width' => '100',
                    'height' => '200',
                    'alt' => ''
                ]
            ],
            [
                [
                    'src' => 'test',
                    'width' => 100,
                    'height' => 200,
                    'minWidth' => 300,
                    'maxWidth' => 400,
                    'minHeight' => 500,
                    'maxHeight' => 600,
                    'crop' => null
                ],
                [
                    'src' => 'test.png',
                    'width' => '100',
                    'height' => '200',
                    'alt' => '',
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getRenderMethodTestValuesWithoutFallbackProperties
     * @param array $arguments
     * @param array $expected
     */
    public function renderMethodCreatesExpectedTagWithoutFallbackProperties(array $arguments, array $expected)
    {
        $this->setArgumentsUnderTest($this->viewHelper, $arguments);

        $image = $this->getAccessibleMock(FileReference::class, ['getProperty', 'hasProperty'], [], '', false);
        $image->expects(self::any())->method('hasProperty')->willReturn(false);

        $e = new \InvalidArgumentException('', 1556282257);
        $image->expects(self::any())->method('getProperty')->willThrowException($e);

        $originalFile = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $originalFile->expects(self::any())->method('getProperties')->willReturn([]);

        $processedFile = $this->getMockBuilder(ProcessedFile::class)
            ->disableOriginalConstructor()
            ->getMock();

        $processedFile->expects(self::any())->method('getProperty')->willReturnMap([
            ['width', $arguments['width']],
            ['height', $arguments['height']],
        ]);

        $image->_set('originalFile', $originalFile);
        $image->_set('propertiesOfFileReference', []);
        $imageService = $this->createMock(ImageService::class);
        $imageService->expects(self::once())->method('getImage')->willReturn($image);
        $imageService->expects(self::once())->method('applyProcessingInstructions')->with($image, self::anything())->willReturn($processedFile);
        $imageService->expects(self::once())->method('getImageUri')->with($processedFile)->willReturn('test.png');

        $this->viewHelper->injectImageService($imageService);

        $tagBuilder = $this->getMockBuilder(TagBuilder::class)
            ->setMethods(['addAttribute', 'render'])
            ->getMock();
        $index = -1;
        foreach ($expected as $expectedAttribute => $expectedValue) {
            $tagBuilder->expects(self::at(++ $index))->method('addAttribute')->with($expectedAttribute, $expectedValue);
        }
        $tagBuilder->expects(self::once())->method('render');
        $this->viewHelper->setTagBuilder($tagBuilder);

        $this->viewHelper->render();
    }
}
