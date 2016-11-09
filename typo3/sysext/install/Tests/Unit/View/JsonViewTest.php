<?php
namespace TYPO3\CMS\Install\Tests\Unit\View;

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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Install\Status\Exception;

/**
 * Tests for the custom json view class
 */
class JsonViewTest extends UnitTestCase
{
    /**
     * @test
     */
    public function transformStatusArrayToArrayReturnsArray()
    {
        $jsonView = $this->getAccessibleMock(\TYPO3\CMS\Install\View\JsonView::class, ['dummy']);
        $this->assertInternalType('array', $jsonView->_call('transformStatusMessagesToArray'));
    }

    /**
     * @test
     */
    public function transformStatusArrayToArrayThrowsExceptionIfArrayContainsNotAMessageInterfaceMessage()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1381059600);
        $jsonView = $this->getAccessibleMock(\TYPO3\CMS\Install\View\JsonView::class, ['dummy']);
        $jsonView->_call('transformStatusMessagesToArray', ['foo']);
    }

    /**
     * @test
     */
    public function transformStatusToArrayCreatesArrayFromStatusMessage()
    {
        $status = $this->createMock(\TYPO3\CMS\Install\Status\StatusInterface::class);
        $status->expects($this->once())->method('getSeverity')->will($this->returnValue(-2));
        $status->expects($this->once())->method('getTitle')->will($this->returnValue('aTitle'));
        $status->expects($this->once())->method('getMessage')->will($this->returnValue('aMessage'));
        $jsonView = $this->getAccessibleMock(\TYPO3\CMS\Install\View\JsonView::class, ['dummy']);
        $return = $jsonView->_call('transformStatusToArray', $status);
        $this->assertSame(-2, $return['severity']);
        $this->assertSame('aTitle', $return['title']);
        $this->assertSame('aMessage', $return['message']);
    }
}
