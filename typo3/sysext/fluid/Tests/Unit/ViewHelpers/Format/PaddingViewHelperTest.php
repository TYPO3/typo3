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
use TYPO3\CMS\Fluid\ViewHelpers\Format\PaddingViewHelper;

/**
 * Test case
 */
class PaddingViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var PaddingViewHelper
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(PaddingViewHelper::class)
            ->setMethods(array('renderChildren'))
            ->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function stringsArePaddedWithBlanksByDefault()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('foo'));
        $actualResult = $this->viewHelper->render(10);
        $this->assertEquals('foo       ', $actualResult);
    }

    /**
     * @test
     */
    public function paddingStringCanBeSpecified()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('foo'));
        $actualResult = $this->viewHelper->render(10, '-=');
        $this->assertEquals('foo-=-=-=-', $actualResult);
    }

    /**
     * @test
     */
    public function stringIsNotTruncatedIfPadLengthIsBelowStringLength()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('some long string'));
        $actualResult = $this->viewHelper->render(5);
        $this->assertEquals('some long string', $actualResult);
    }

    /**
     * @test
     */
    public function integersArePaddedCorrectly()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123));
        $actualResult = $this->viewHelper->render(5, '0');
        $this->assertEquals('12300', $actualResult);
    }
}
