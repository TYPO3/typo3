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
class DownloadQueueTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 * @return void
	 */
	public function addExtensionToQueueAddsExtensionToDownloadStorageArray() {
		$extensionModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension', array('dummy'));
		$extensionModelMock->_set('extensionKey', 'foobar');
		$extensionModelMock->_set('version', '1.0.0');
		$downloadQueueMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\DownloadQueue', array('dummy'));
		$downloadQueueMock->addExtensionToQueue($extensionModelMock);
		$extensionStorage = $downloadQueueMock->_get('extensionStorage');
		$this->assertArrayHasKey('foobar', $extensionStorage['download']);
	}

	/**
	 * @test
	 * @return void
	 */
	public function addExtensionToQueueAddsExtensionToUpdateStorageArray() {
		$extensionModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension', array('dummy'));
		$extensionModelMock->_set('extensionKey', 'foobar');
		$extensionModelMock->_set('version', '1.0.0');
		$downloadQueueMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\DownloadQueue', array('dummy'));
		$downloadQueueMock->addExtensionToQueue($extensionModelMock, 'update');
		$extensionStorage = $downloadQueueMock->_get('extensionStorage');
		$this->assertArrayHasKey('foobar', $extensionStorage['update']);
	}

	/**
	 * @test
	 * @return void
	 */
	public function addExtensionToQueueThrowsExceptionIfUnknownStackIsGiven() {
		/** @var $extensionModelMock \TYPO3\CMS\Extensionmanager\Domain\Model\Extension */
		$extensionModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension', array('dummy'));
		$extensionModelMock->_set('extensionKey', 'foobar');
		$extensionModelMock->_set('version', '1.0.0');
		/** @var $downloadQueueMock \TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue */
		$downloadQueueMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\DownloadQueue', array('dummy'));
		$this->setExpectedException('TYPO3\\CMS\\Extensionmanager\\Exception\\ExtensionManagerException', $this->any(), 1342432103);
		$downloadQueueMock->addExtensionToQueue($extensionModelMock, 'unknownStack');
	}

	/**
	 * @test
	 * @return void
	 */
	public function addExtensionToQueueThrowsExceptionIfExtensionWithSameKeyAndDifferentValuesAlreadyExists() {
		$extensionModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension', array('dummy'));
		$extensionModelMock->_set('extensionKey', 'foobar');
		$extensionModelMock->_set('version', '1.0.0');
		$extensionModelMock2 = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension', array('dummy'));
		$extensionModelMock2->_set('extensionKey', 'foobar');
		$extensionModelMock2->_set('version', '1.0.3');
		$downloadQueueMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\DownloadQueue', array('dummy'));
		$downloadQueueMock->_set('extensionStorage', array('foobar' => $extensionModelMock2));
		$this->setExpectedException('TYPO3\\CMS\\Extensionmanager\\Exception\\ExtensionManagerException', $this->any(), 1342432101);
		$downloadQueueMock->addExtensionToQueue($extensionModelMock);
	}

	/**
	 * @test
	 * @return void
	 */
	public function removeExtensionFromQueueRemovesExtension() {
		$extensionModelMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension', array('dummy'));
		$extensionModelMock->_set('extensionKey', 'foobar');
		$extensionModelMock->_set('version', '1.0.0');
		$extensionModelMock2 = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension', array('dummy'));
		$extensionModelMock2->_set('extensionKey', 'foobarbaz');
		$extensionModelMock2->_set('version', '1.0.3');
		$downloadQueueMock = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\DownloadQueue', array('dummy'));
		$downloadQueueMock->_set('extensionStorage', array(
			'download' => array(
				'foobar' => $extensionModelMock,
				'foobarbaz' => $extensionModelMock2
			)
		));
		$extensionStorageBefore = $downloadQueueMock->_get('extensionStorage');
		$this->assertTrue(array_key_exists('foobar', $extensionStorageBefore['download']));
		$downloadQueueMock->removeExtensionFromQueue($extensionModelMock);
		$extensionStorageAfter = $downloadQueueMock->_get('extensionStorage');
		$this->assertFalse(array_key_exists('foobar', $extensionStorageAfter['download']));
	}

}


?>