<?php
namespace TYPO3\CMS\Core\Tests\Unit\Error;

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

/**
 * testcase for the \TYPO3\CMS\Core\Error\ProductionExceptionHandler class.
 */
class ProductionExceptionHandlerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Error\ProductionExceptionHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject = null;

    /**
     * Sets up this test case.
     */
    protected function setUp()
    {
        $this->subject = $this->getMock(\TYPO3\CMS\Core\Error\ProductionExceptionHandler::class, ['discloseExceptionInformation', 'sendStatusHeaders', 'writeLogEntries'], [], '', false);
        $this->subject->expects($this->any())->method('discloseExceptionInformation')->will($this->returnValue(true));
    }

    /**
     * @test
     */
    public function echoExceptionWebEscapesExceptionMessage()
    {
        $message = '<b>b</b><script>alert(1);</script>';
        $exception = new \Exception($message);
        ob_start();
        $this->subject->echoExceptionWeb($exception);
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertContains(htmlspecialchars($message), $output);
        $this->assertNotContains($message, $output);
    }

    /**
     * @test
     */
    public function echoExceptionWebEscapesExceptionTitle()
    {
        $title = '<b>b</b><script>alert(1);</script>';
        /** @var $exception \Exception|\PHPUnit_Framework_MockObject_MockObject */
        $exception = $this->getMock('Exception', ['getTitle'], ['some message']);
        $exception->expects($this->any())->method('getTitle')->will($this->returnValue($title));
        ob_start();
        $this->subject->echoExceptionWeb($exception);
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertContains(htmlspecialchars($title), $output);
        $this->assertNotContains($title, $output);
    }
}
