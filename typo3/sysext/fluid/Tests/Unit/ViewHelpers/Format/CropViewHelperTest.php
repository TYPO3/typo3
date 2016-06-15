<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Format;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Test case
 */
class CropViewHelperTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Format\CropViewHelper
     */
    protected $viewHelper;

    /**
     * @var ContentObjectRenderer
     */
    protected $mockContentObject;

    protected function setUp()
    {
        parent::setUp();
        $this->mockContentObject = $this->createMock(ContentObjectRenderer::class);
        $this->viewHelper = $this->getMockBuilder(\TYPO3\CMS\Fluid\ViewHelpers\Format\CropViewHelper::class)
            ->setMethods(array('renderChildren'))
            ->getMock();

        $renderingContext = $this->createMock(\TYPO3\CMS\Fluid\Tests\Unit\Core\Rendering\RenderingContextFixture::class);
        $this->viewHelper->setRenderingContext($renderingContext);
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Some Content'));
    }

    /**
     * @test
     */
    public function viewHelperCallsCropHtmlByDefault()
    {
        $this->mockContentObject->expects($this->once())->method('cropHTML')->with('Some Content', '123|...|1')->will($this->returnValue('Cropped Content'));
        GeneralUtility::addInstance(ContentObjectRenderer::class, $this->mockContentObject);
        $actualResult = $this->viewHelper->render(123);
        $this->assertEquals('Cropped Content', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperCallsCropHtmlByDefault2()
    {
        $this->mockContentObject->expects($this->once())->method('cropHTML')->with('Some Content', '-321|custom suffix|1')->will($this->returnValue('Cropped Content'));
        GeneralUtility::addInstance(ContentObjectRenderer::class, $this->mockContentObject);
        $actualResult = $this->viewHelper->render(-321, 'custom suffix');
        $this->assertEquals('Cropped Content', $actualResult);
    }

    /**
     * @test
     */
    public function respectWordBoundariesCanBeDisabled()
    {
        $this->mockContentObject->expects($this->once())->method('cropHTML')->with('Some Content', '123|...|')->will($this->returnValue('Cropped Content'));
        GeneralUtility::addInstance(ContentObjectRenderer::class, $this->mockContentObject);
        $actualResult = $this->viewHelper->render(123, '...', false);
        $this->assertEquals('Cropped Content', $actualResult);
    }

    /**
     * @test
     */
    public function respectHtmlCanBeDisabled()
    {
        $this->mockContentObject->expects($this->once())->method('crop')->with('Some Content', '123|...|1')->will($this->returnValue('Cropped Content'));
        GeneralUtility::addInstance(ContentObjectRenderer::class, $this->mockContentObject);
        $actualResult = $this->viewHelper->render(123, '...', true, false);
        $this->assertEquals('Cropped Content', $actualResult);
    }
}
