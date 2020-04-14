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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Format;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Format\CropViewHelper;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test case.
 */
class CropViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var CropViewHelper
     */
    protected $viewHelper;

    /**
     * @var ContentObjectRenderer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockContentObject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockContentObject = $this->createMock(ContentObjectRenderer::class);
        $this->viewHelper = new CropViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 'Some Content';
            }
        );
    }

    /**
     * @test
     */
    public function viewHelperCallsCropHtmlByDefault()
    {
        $this->mockContentObject->expects(self::once())->method('cropHTML')->with('Some Content', '123|&hellip;|1')->willReturn('Cropped Content');
        GeneralUtility::addInstance(ContentObjectRenderer::class, $this->mockContentObject);
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'maxCharacters' => '123',
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('Cropped Content', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperCallsCropHtmlByDefault2()
    {
        $this->mockContentObject->expects(self::once())->method('cropHTML')->with('Some Content', '-321|custom suffix|1')->willReturn('Cropped Content');
        GeneralUtility::addInstance(ContentObjectRenderer::class, $this->mockContentObject);
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'maxCharacters' => '-321',
                'append' => 'custom suffix',
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('Cropped Content', $actualResult);
    }

    /**
     * @test
     */
    public function respectWordBoundariesCanBeDisabled()
    {
        $this->mockContentObject->expects(self::once())->method('cropHTML')->with('Some Content', '123|...|')->willReturn('Cropped Content');
        GeneralUtility::addInstance(ContentObjectRenderer::class, $this->mockContentObject);
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'maxCharacters' => '123',
                'append' => '...',
                'respectWordBoundaries' => false,
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('Cropped Content', $actualResult);
    }

    /**
     * @test
     */
    public function respectHtmlCanBeDisabled()
    {
        $this->mockContentObject->expects(self::once())->method('crop')->with('Some Content', '123|...|1')->willReturn('Cropped Content');
        GeneralUtility::addInstance(ContentObjectRenderer::class, $this->mockContentObject);
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'maxCharacters' => '123',
                'append' => '...',
                'respectWordBoundaries' => true,
                'respectHtml' => false,
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('Cropped Content', $actualResult);
    }
}
