<?php
namespace TYPO3\CMS\Backend\Tests\Unit\View\BackendLayout;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Oliver Hader <oliver.hader@typo3.org>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testing collection of backend layouts.
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class BackendLayoutCollectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \UnexpectedValueException
	 */
	public function invalidIdentifierIsRecognizedOnCreation() {
		$identifier = uniqid('identifier__');
		new \TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection($identifier);
	}

	/**
	 * @test
	 */
	public function objectIsCreated() {
		$identifier = uniqid('identifier');
		$backendLayoutCollection = new \TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection($identifier);

		$this->assertEquals($identifier, $backendLayoutCollection->getIdentifier());
	}

	/**
	 * @test
	 * @expectedException \UnexpectedValueException
	 */
	public function invalidBackendLayoutIsRecognizedOnAdding() {
		$identifier = uniqid('identifier');
		$backendLayoutCollection = new \TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection($identifier);
		$backendLayoutIdentifier = uniqid('identifier__');
		$backendLayoutMock = $this->getMock('TYPO3\\CMS\\Backend\\View\\BackendLayout\\BackendLayout', array('getIdentifier'), array(), '', FALSE);
		$backendLayoutMock->expects($this->once())->method('getIdentifier')->will($this->returnValue($backendLayoutIdentifier));

		$backendLayoutCollection->add($backendLayoutMock);
	}

	/**
	 * @test
	 * @expectedException \LogicException
	 */
	public function duplicateBackendLayoutIsRecognizedOnAdding() {
		$identifier = uniqid('identifier');
		$backendLayoutCollection = new \TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection($identifier);
		$backendLayoutIdentifier = uniqid('identifier');
		$firstBackendLayoutMock = $this->getMock('TYPO3\\CMS\\Backend\\View\\BackendLayout\\BackendLayout', array('getIdentifier'), array(), '', FALSE);
		$firstBackendLayoutMock->expects($this->once())->method('getIdentifier')->will($this->returnValue($backendLayoutIdentifier));
		$secondBackendLayoutMock = $this->getMock('TYPO3\\CMS\\Backend\\View\\BackendLayout\\BackendLayout', array('getIdentifier'), array(), '', FALSE);
		$secondBackendLayoutMock->expects($this->once())->method('getIdentifier')->will($this->returnValue($backendLayoutIdentifier));

		$backendLayoutCollection->add($firstBackendLayoutMock);
		$backendLayoutCollection->add($secondBackendLayoutMock);
	}

	/**
	 * @test
	 */
	public function backendLayoutCanBeFetched() {
		$identifier = uniqid('identifier');
		$backendLayoutCollection = new \TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection($identifier);
		$backendLayoutIdentifier = uniqid('identifier');
		$backendLayoutMock = $this->getMock('TYPO3\\CMS\\Backend\\View\\BackendLayout\\BackendLayout', array('getIdentifier'), array(), '', FALSE);
		$backendLayoutMock->expects($this->once())->method('getIdentifier')->will($this->returnValue($backendLayoutIdentifier));

		$backendLayoutCollection->add($backendLayoutMock);

		$this->assertEquals($backendLayoutMock, $backendLayoutCollection->get($backendLayoutIdentifier));
	}

}