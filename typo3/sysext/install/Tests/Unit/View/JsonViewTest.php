<?php
namespace TYPO3\CMS\Install\Tests\Unit\View;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Tests for the custom json view class
 */
class JsonViewTest extends UnitTestCase {
	/**
	 * @test
	 */
	public function transformStatusArrayToArrayReturnsArray() {
		$jsonView = $this->getAccessibleMock('TYPO3\\CMS\\Install\\View\\JsonView', array('dummy'));
		$this->assertInternalType('array', $jsonView->_call('transformStatusMessagesToArray'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\Status\Exception
	 */
	public function transformStatusArrayToArrayThrowsExceptionIfArrayContainsNotAMessageInterfaceMessage() {
		$jsonView = $this->getAccessibleMock('TYPO3\\CMS\\Install\\View\\JsonView', array('dummy'));
		$jsonView->_call('transformStatusMessagesToArray', array('foo'));
	}

	/**
	 * @test
	 */
	public function transformStatusToArrayCreatesArrayFromStatusMessage() {
		$status = $this->getMock('TYPO3\\CMS\\Install\\Status\\StatusInterface');
		$status->expects($this->once())->method('getSeverity')->will($this->returnValue('aSeverity'));
		$status->expects($this->once())->method('getTitle')->will($this->returnValue('aTitle'));
		$status->expects($this->once())->method('getMessage')->will($this->returnValue('aMessage'));
		$jsonView = $this->getAccessibleMock('TYPO3\\CMS\\Install\\View\\JsonView', array('dummy'));
		$return = $jsonView->_call('transformStatusToArray', $status);
		$this->assertSame('aSeverity', $return['severity']);
		$this->assertSame('aTitle', $return['title']);
		$this->assertSame('aMessage', $return['message']);
	}
}