<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Domain\Model;

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
 * Download queue test
 *
 */
class DownloadQueueTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
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
    protected function setUp()
    {
        $this->downloadQueueMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue::class, ['dummy']);
        $this->extensionMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class, ['dummy']);
        $this->extensionMock->setExtensionKey('foobar');
        $this->extensionMock->setVersion('1.0.0');
    }

    /**
     * @test
     * @return void
     */
    public function addExtensionToQueueAddsExtensionToDownloadStorageArray()
    {
        $this->downloadQueueMock->addExtensionToQueue($this->extensionMock);
        $extensionStorage = $this->downloadQueueMock->_get('extensionStorage');

        $this->assertArrayHasKey('foobar', $extensionStorage['download']);
    }

    /**
     * @test
     * @return void
     */
    public function addExtensionToQueueAddsExtensionToUpdateStorageArray()
    {
        $this->downloadQueueMock->addExtensionToQueue($this->extensionMock, 'update');
        $extensionStorage = $this->downloadQueueMock->_get('extensionStorage');

        $this->assertArrayHasKey('foobar', $extensionStorage['update']);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
     * @expectedExceptionCode 1342432103
     */
    public function addExtensionToQueueThrowsExceptionIfUnknownStackIsGiven()
    {
        $this->downloadQueueMock->addExtensionToQueue($this->extensionMock, 'unknownStack');
    }

    /**
     * @test
     * @return void
     * @expectedException \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
     * @expectedExceptionCode 1342432101
     */
    public function addExtensionToQueueThrowsExceptionIfExtensionWithSameKeyAndDifferentValuesAlreadyExists()
    {
        /** @var \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extensionMock2 */
        $extensionMock2 = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class, ['dummy']);
        $extensionMock2->setExtensionKey('foobar');
        $extensionMock2->setVersion('1.0.3');

        $this->downloadQueueMock->addExtensionToQueue($extensionMock2);
        $this->downloadQueueMock->addExtensionToQueue($this->extensionMock);
    }

    /**
     * @test
     * @return void
     */
    public function removeExtensionFromQueueRemovesExtension()
    {
        $extensionMock2 = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class, ['dummy']);
        $extensionMock2->_set('extensionKey', 'foobarbaz');
        $extensionMock2->_set('version', '1.0.3');
        $this->downloadQueueMock->_set('extensionStorage', [
            'download' => [
                'foobar' => $this->extensionMock,
                'foobarbaz' => $extensionMock2
            ]
        ]);
        $extensionStorageBefore = $this->downloadQueueMock->_get('extensionStorage');

        $this->assertTrue(array_key_exists('foobar', $extensionStorageBefore['download']));

        $this->downloadQueueMock->removeExtensionFromQueue($this->extensionMock);
        $extensionStorageAfter = $this->downloadQueueMock->_get('extensionStorage');

        $this->assertFalse(array_key_exists('foobar', $extensionStorageAfter['download']));
    }
}
