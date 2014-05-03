<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Domain\Model;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Susanne Moog, <susanne.moog@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Download queue test
 *
 */
class DownloadQueueTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue
	 */
	protected $downloadQueueMock;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Domain\Model\Extension
	 */
	protected $extensionMock;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->downloadQueueMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\DownloadQueue', array('dummy'));
		$this->extensionMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension', array('dummy'));
		$this->extensionMock->setExtensionKey('foobar');
		$this->extensionMock->setVersion('1.0.0');

	}

	/**
	 * @test
	 * @return void
	 */
	public function addExtensionToQueueAddsExtensionToDownloadStorageArray() {
		$this->downloadQueueMock->addExtensionToQueue($this->extensionMock);
		$extensionStorage = $this->downloadQueueMock->_get('extensionStorage');

		$this->assertArrayHasKey('foobar', $extensionStorage['download']);
	}

	/**
	 * @test
	 * @return void
	 */
	public function addExtensionToQueueAddsExtensionToUpdateStorageArray() {
		$this->downloadQueueMock->addExtensionToQueue($this->extensionMock, 'update');
		$extensionStorage = $this->downloadQueueMock->_get('extensionStorage');

		$this->assertArrayHasKey('foobar', $extensionStorage['update']);
	}

	/**
	 * @test
	 * @return void
	 */
	public function addExtensionToQueueThrowsExceptionIfUnknownStackIsGiven() {
		$this->setExpectedException('TYPO3\\CMS\\Extensionmanager\\Exception\\ExtensionManagerException', $this->any(), 1342432103);
		$this->downloadQueueMock->addExtensionToQueue($this->extensionMock, 'unknownStack');
	}

	/**
	 * @test
	 * @return void
	 */
	public function addExtensionToQueueThrowsExceptionIfExtensionWithSameKeyAndDifferentValuesAlreadyExists() {
		/** @var \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extensionMock2 */
		$extensionMock2 = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension', array('dummy'));
		$extensionMock2->setExtensionKey('foobar');
		$extensionMock2->setVersion('1.0.3');
		$this->downloadQueueMock->_set('extensionStorage', array('foobar' => $extensionMock2));

		$this->setExpectedException('TYPO3\\CMS\\Extensionmanager\\Exception\\ExtensionManagerException', $this->any(), 1342432101);
		$this->downloadQueueMock->addExtensionToQueue($this->extensionMock);
	}

	/**
	 * @test
	 * @return void
	 */
	public function removeExtensionFromQueueRemovesExtension() {
		$extensionMock2 = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension', array('dummy'));
		$extensionMock2->_set('extensionKey', 'foobarbaz');
		$extensionMock2->_set('version', '1.0.3');
		$this->downloadQueueMock->_set('extensionStorage', array(
			'download' => array(
				'foobar' => $this->extensionMock,
				'foobarbaz' => $extensionMock2
			)
		));
		$extensionStorageBefore = $this->downloadQueueMock->_get('extensionStorage');

		$this->assertTrue(array_key_exists('foobar', $extensionStorageBefore['download']));

		$this->downloadQueueMock->removeExtensionFromQueue($this->extensionMock);
		$extensionStorageAfter = $this->downloadQueueMock->_get('extensionStorage');

		$this->assertFalse(array_key_exists('foobar', $extensionStorageAfter['download']));
	}

}
