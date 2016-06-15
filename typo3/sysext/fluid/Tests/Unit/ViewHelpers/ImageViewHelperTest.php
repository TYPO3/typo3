<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

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
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Fluid\ViewHelpers\ImageViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Test case
 */
class ImageViewHelperTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function registersExpectedArgumentsInInitializeArgumentsMethod()
    {
        $mock = $this->getMockBuilder(ImageViewHelper::class)
            ->setMethods(array('registerUniversalTagAttributes', 'registerTagAttribute'))
            ->getMock();
        $mock->expects($this->at(0))->method('registerUniversalTagAttributes');
        $mock->expects($this->at(1))->method('registerTagAttribute')->with('alt', 'string', $this->anything(), false);
        $mock->expects($this->at(2))->method('registerTagAttribute')->with('ismap', 'string', $this->anything(), false);
        $mock->expects($this->at(3))->method('registerTagAttribute')->with('longdesc', 'string', $this->anything(), false);
        $mock->expects($this->at(4))->method('registerTagAttribute')->with('usemap', 'string', $this->anything(), false);
        $mock->initializeArguments();
    }

    /**
     * @test
     * @dataProvider getInvalidArguments
     * @param array $arguments
     */
    public function renderMethodThrowsExceptionOnInvalidArguments(array $arguments)
    {
        $mock = $this->getMockBuilder(ImageViewHelper::class)
            ->setMethods(array('dummy'))
            ->getMock();
        $mock->setArguments($arguments);

        $this->expectException(\TYPO3\CMS\Fluid\Core\ViewHelper\Exception::class);
        $this->expectExceptionCode(1382284106);

        $mock->render(
            isset($arguments['src']) ? $arguments['src'] : null,
            isset($arguments['width']) ? $arguments['width'] : null,
            isset($arguments['height']) ? $arguments['height'] : null,
            isset($arguments['minWidth']) ? $arguments['minWidth'] : null,
            isset($arguments['minHeight']) ? $arguments['minHeight'] : null,
            isset($arguments['maxWidth']) ? $arguments['maxWidth'] : null,
            isset($arguments['maxHeight']) ? $arguments['maxHeight'] : null,
            isset($arguments['treatIdAsReference']) ? $arguments['treatIdAsReference'] : null,
            isset($arguments['image']) ? $arguments['image'] : null,
            isset($arguments['crop']) ? $arguments['crop'] : null
        );
    }

    /**
     * @return array
     */
    public function getInvalidArguments()
    {
        return array(
            array(array('image' => null)),
            array(array('src' => null)),
            array(array('src' => 'something', 'image' => 'something')),
        );
    }

    /**
     * @test
     * @dataProvider getRenderMethodTestValues
     * @param array $arguments
     * @param array $expected
     */
    public function renderMethodCreatesExpectedTag(array $arguments, array $expected)
    {
        $image = $this->getMockBuilder(FileReference::class)
            ->setMethods(array('getProperty'))
            ->disableOriginalConstructor()
            ->getMock();
        $image->expects($this->any())->method('getProperty')->willReturnMap(array(
            array('width', $arguments['width']),
            array('height', $arguments['height']),
            array('alternative', 'alternative'),
            array('title', 'title'),
            array('crop', 'crop')
        ));
        $imageService = $this->getMockBuilder(ImageService::class)
            ->setMethods(array('getImage', 'applyProcessingInstructions', 'getImageUri'))
            ->getMock();
        $imageService->expects($this->once())->method('getImage')->willReturn($image);
        $imageService->expects($this->once())->method('applyProcessingInstructions')->with($image, $this->anything())->willReturn($image);
        $imageService->expects($this->once())->method('getImageUri')->with($image)->willReturn('test.png');
        $tagBuilder = $this->getMockBuilder(TagBuilder::class)
            ->setMethods(array('addAttribute', 'render'))
            ->getMock();
        $index = -1;
        foreach ($expected as $expectedAttribute => $expectedValue) {
            $tagBuilder->expects($this->at(++ $index))->method('addAttribute')->with($expectedAttribute, $expectedValue);
        }
        $tagBuilder->expects($this->once())->method('render');
        $mock = $this->getAccessibleMock(ImageViewHelper::class, array('dummy'), array(), '', false);
        $mock->_set('imageService', $imageService);
        $mock->_set('tag', $tagBuilder);
        $mock->setArguments($arguments);
        $mock->render(
            isset($arguments['src']) ? $arguments['src'] : null,
            isset($arguments['width']) ? $arguments['width'] : null,
            isset($arguments['height']) ? $arguments['height'] : null,
            isset($arguments['minWidth']) ? $arguments['minWidth'] : null,
            isset($arguments['minHeight']) ? $arguments['minHeight'] : null,
            isset($arguments['maxWidth']) ? $arguments['maxWidth'] : null,
            isset($arguments['maxHeight']) ? $arguments['maxHeight'] : null,
            isset($arguments['treatIdAsReference']) ? $arguments['treatIdAsReference'] : null,
            isset($arguments['image']) ? $arguments['image'] : null,
            isset($arguments['crop']) ? $arguments['crop'] : null
        );
    }

    /**
     * @return array
     */
    public function getRenderMethodTestValues()
    {
        return array(
            array(
                array(
                    'src' => 'test',
                    'width' => 100,
                    'height' => 200,
                    'minWidth' => 300,
                    'maxWidth' => 400,
                    'minHeight' => 500,
                    'maxHeight' => 600,
                    'crop' => false
                ),
                array(
                    'src' => 'test.png',
                    'width' => '100',
                    'height' => '200',
                    'alt' => 'alternative',
                    'title' => 'title'
                )
            ),
            array(
                array(
                    'src' => 'test',
                    'width' => 100,
                    'height' => 200,
                    'minWidth' => 300,
                    'maxWidth' => 400,
                    'minHeight' => 500,
                    'maxHeight' => 600,
                    'crop' => null
                ),
                array(
                    'src' => 'test.png',
                    'width' => '100',
                    'height' => '200',
                    'alt' => 'alternative',
                    'title' => 'title'
                )
            ),
        );
    }
}
