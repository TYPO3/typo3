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
use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3\CMS\Fluid\ViewHelpers\Format\Nl2brViewHelper;

/**
 * Test case
 */
class Nl2brViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var Nl2brViewHelper
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(Nl2brViewHelper::class)
            ->setMethods(array('renderChildren'))
            ->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function viewHelperDoesNotModifyTextWithoutLineBreaks()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('<p class="bodytext">Some Text without line breaks</p>'));
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('<p class="bodytext">Some Text without line breaks</p>', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperConvertsLineBreaksToBRTags()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Line 1' . chr(10) . 'Line 2'));
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('Line 1<br />' . chr(10) . 'Line 2', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperConvertsWindowsLineBreaksToBRTags()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Line 1' . chr(13) . chr(10) . 'Line 2'));
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('Line 1<br />' . chr(13) . chr(10) . 'Line 2', $actualResult);
    }
}
