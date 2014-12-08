<?php
namespace TYPO3\CMS\Backend\Tests\Unit;

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

use PHPUnit_Framework_MockObject_MockObject;
use TYPO3\CMS\Backend\BackendModuleRequestHandler;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class BackendModuleRequestHandlerTest
 */
class BackendModuleRequestHandlerTest extends UnitTestCase {

	/**
	 * @var BackendModuleRequestHandler|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface
	 */
	protected $subject;

	/**
	 * @var \TYPO3\CMS\Core\FormProtection\AbstractFormProtection|PHPUnit_Framework_MockObject_MockObject
	 */
	protected $formProtectionMock;

	public function setUp() {
		$this->formProtectionMock = $this->getMock('AbstractFormProtection', array('validateToken'));
		$this->subject = $this->getAccessibleMock(BackendModuleRequestHandler::class, array('boot', 'getFormProtection'), array(), '', FALSE);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionCode 1425236663
	 */
	public function moduleIndexIsCalled() {
		$GLOBALS['TBE_MODULES'] = array(
			'_PATHS' => array(
				'module_fixture' => __DIR__ . '/Fixtures/ModuleFixture/'
			)
		);
		$_GET['M'] = 'module_fixture';

		$this->formProtectionMock->expects($this->once())->method('validateToken')->will($this->returnValue(TRUE));
		$this->subject->expects($this->once())->method('boot');
		$this->subject->expects($this->once())->method('getFormProtection')->will($this->returnValue($this->formProtectionMock));

		$this->subject->handleRequest();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Exception
	 * @expectedExceptionCode 1417988921
	 */
	public function throwsExceptionIfTokenIsInvalid() {
		$this->formProtectionMock->expects($this->once())->method('validateToken')->will($this->returnValue(FALSE));
		$this->subject->expects($this->once())->method('boot');
		$this->subject->expects($this->once())->method('getFormProtection')->will($this->returnValue($this->formProtectionMock));

		$this->subject->handleRequest();
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionCode 1425236663
	 */
	public function moduleDispatcherIsCalled() {
		$GLOBALS['TBE_MODULES'] = array(
			'_PATHS' => array(
				'_dispatcher' => array(),
				'module_fixture' => __DIR__ . '/Fixtures/ModuleFixture/'
			)
		);
		$_GET['M'] = 'module_fixture';

		$this->formProtectionMock->expects($this->once())->method('validateToken')->will($this->returnValue(TRUE));
		$this->subject->expects($this->once())->method('boot');
		$this->subject->expects($this->once())->method('getFormProtection')->will($this->returnValue($this->formProtectionMock));

		$this->subject->handleRequest();
	}

}
