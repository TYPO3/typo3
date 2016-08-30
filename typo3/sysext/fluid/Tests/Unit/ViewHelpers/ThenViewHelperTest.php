<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Fluid\ViewHelpers\ThenViewHelper;

/**
 * Test case
 */
class ThenViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var ThenViewHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getMock(ThenViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->subject);
    }

    /**
     * @test
     */
    public function renderRendersChildren()
    {
        $this->subject->expects($this->once())->method('renderChildren')->will($this->returnValue('foo'));
        $actualResult = $this->subject->render();
        $this->assertEquals('foo', $actualResult);
    }
}
