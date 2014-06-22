<?php
namespace TYPO3\CMS\Install\Tests\Unit\View;

/**
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