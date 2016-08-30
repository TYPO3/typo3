<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Format;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3\CMS\Fluid\ViewHelpers\Format\RawViewHelper;

/**
 * Test case
 */
class RawViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var RawViewHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getMock(RawViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->subject);
    }

    /**
     * @test
     */
    public function viewHelperDeactivatesEscapingInterceptor()
    {
        $this->assertFalse($this->subject->isEscapingInterceptorEnabled());
    }

    /**
     * @test
     */
    public function renderReturnsUnmodifiedValueIfSpecified()
    {
        $value = 'input value " & äöüß@';
        $this->subject->expects($this->never())->method('renderChildren');
        $actualResult = $this->subject->render($value);
        $this->assertEquals($value, $actualResult);
    }

    /**
     * @test
     */
    public function renderReturnsUnmodifiedChildNodesIfNoValueIsSpecified()
    {
        $childNodes = 'input value " & äöüß@';
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue($childNodes));
        $actualResult = $this->subject->render();
        $this->assertEquals($childNodes, $actualResult);
    }
}
