<?php
namespace TYPO3\CMS\Install\Tests\Unit\Status;

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

use \TYPO3\CMS\Install\Status\StatusUtility;

/**
 * Test case
 */
class StatusUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function sortBySeveritySortsGivenStatusObjects() {
		$errorMock = $this->getMock('TYPO3\\CMS\\Install\\Status\\ErrorStatus', array('dummy'));
		$warningMock = $this->getMock('TYPO3\\CMS\\Install\\Status\\WarningStatus', array('dummy'));
		$okMock = $this->getMock('TYPO3\\CMS\\Install\\Status\\OkStatus', array('dummy'));
		$infoMock = $this->getMock('TYPO3\\CMS\\Install\\Status\\InfoStatus', array('dummy'));
		$noticeMock = $this->getMock('TYPO3\\CMS\\Install\\Status\\NoticeStatus', array('dummy'));
		$statusUtility = new StatusUtility();
		$return = $statusUtility->sortBySeverity(array($noticeMock, $infoMock, $okMock, $warningMock, $errorMock));
		$this->assertSame(array($errorMock), $return['error']);
		$this->assertSame(array($warningMock), $return['warning']);
		$this->assertSame(array($okMock), $return['ok']);
		$this->assertSame(array($infoMock), $return['information']);
		$this->assertSame(array($noticeMock), $return['notice']);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\Status\Exception
	 */
	public function filterBySeverityThrowsExceptionIfObjectNotImplementingStatusInterfaceIsGiven() {
		$statusUtility = new StatusUtility();
		$statusUtility->filterBySeverity(array(new \stdClass()));
	}

	/**
	 * @test
	 */
	public function filterBySeverityReturnsSpecificSeverityOnly() {
		$errorMock = $this->getMock('TYPO3\\CMS\\Install\\Status\\ErrorStatus', array('dummy'));
		$warningMock = $this->getMock('TYPO3\\CMS\\Install\\Status\\WarningStatus', array('dummy'));
		$statusUtility = new StatusUtility();
		$return = $statusUtility->filterBySeverity(array($errorMock, $warningMock), 'error');
		$this->assertSame(array($errorMock), $return);
	}
}
