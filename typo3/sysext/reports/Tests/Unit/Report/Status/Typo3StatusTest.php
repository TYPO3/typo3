<?php
namespace TYPO3\CMS\Reports\Tests\Unit\Report\Status;

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

/**
 * Test case
 */
class Typo3StatusTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Set up
	 */
	public function setUp() {
		$GLOBALS['LANG'] = $this->getMock('TYPO3\\CMS\\Lang\\LanguageService', array(), array(), '', FALSE);
	}

	/**
	 * @test
	 */
	public function getStatusReturnsOldXclassStatusObjectWithSeverityOkIfNoOldXclassExists() {
		$GLOBALS['TYPO3_CONF_VARS']['BE']['XCLASS'] = array();
		$GLOBALS['TYPO3_CONF_VARS']['FE']['XCLASS'] = array();
		$fixture = new \TYPO3\CMS\Reports\Report\Status\Typo3Status;
		$result = $fixture->getStatus();
		$statusObject = $result['oldXclassStatus'];
		$this->assertSame(\TYPO3\CMS\Reports\Status::OK, $statusObject->getSeverity());
	}

	/**
	 * @test
	 */
	public function getStatusReturnsOldXclassStatusObjectWithSeverityNoticeIfOldXclassExists() {
		$GLOBALS['TYPO3_CONF_VARS']['BE']['XCLASS'] = array('foo' => 'bar');
		$fixture = new \TYPO3\CMS\Reports\Report\Status\Typo3Status;
		$result = $fixture->getStatus();
		$statusObject = $result['oldXclassStatus'];
		$this->assertSame(\TYPO3\CMS\Reports\Status::NOTICE, $statusObject->getSeverity());
	}

	/**
	 * @test
	 */
	public function getStatusReturnsXclassStatusObjectWithSeverityOkIfNoXclassExists() {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'] = array();
		$fixture = new \TYPO3\CMS\Reports\Report\Status\Typo3Status;
		$result = $fixture->getStatus();
		$statusObject = $result['registeredXclass'];
		$this->assertSame(\TYPO3\CMS\Reports\Status::OK, $statusObject->getSeverity());
	}

	/**
	 * @test
	 */
	public function getStatusReturnsXclassStatusObjectWithSeverityNoticeIfXclassExists() {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'] = array(
			'foo' => array(
				'className' => 'bar',
			)
		);
		$fixture = new \TYPO3\CMS\Reports\Report\Status\Typo3Status;
		$result = $fixture->getStatus();
		$statusObject = $result['registeredXclass'];
		$this->assertSame(\TYPO3\CMS\Reports\Status::NOTICE, $statusObject->getSeverity());
	}
}
