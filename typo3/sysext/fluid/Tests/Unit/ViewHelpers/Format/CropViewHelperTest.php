<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Format;

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
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
        $this->mockContentObject = $this->getMock(ContentObjectRenderer::class, [], [], '', false);
        $this->viewHelper = $this->getMock(\TYPO3\CMS\Fluid\ViewHelpers\Format\CropViewHelper::class, ['renderChildren']);

        $renderingContext = $this->getMock(RenderingContext::class);
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
