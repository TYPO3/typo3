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
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Download queue test
 */
class DownloadQueueTest extends UnitTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|\TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue
     */
    protected $downloadQueueMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Domain\Model\Extension
     */
    protected $extensionMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->downloadQueueMock = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue::class, ['dummy']);
        $this->extensionMock = $this->getMockBuilder(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class)
            ->setMethods(['dummy'])
            ->getMock();
        $this->extensionMock->setExtensionKey('foobar');
        $this->extensionMock->setVersion('1.0.0');
    }

    /**
     * @test
     */
    public function addExtensionToQueueAddsExtensionToDownloadStorageArray()
    {
        $this->downloadQueueMock->addExtensionToQueue($this->extensionMock);
        $extensionStorage = $this->downloadQueueMock->_get('extensionStorage');

        self::assertArrayHasKey('foobar', $extensionStorage['download']);
    }

    /**
     * @test
     */
    public function addExtensionToQueueAddsExtensionToUpdateStorageArray()
    {
        $this->downloadQueueMock->addExtensionToQueue($this->extensionMock, 'update');
        $extensionStorage = $this->downloadQueueMock->_get('extensionStorage');

        self::assertArrayHasKey('foobar', $extensionStorage['update']);
    }

    /**
     * @test
     */
    public function addExtensionToQueueThrowsExceptionIfUnknownStackIsGiven()
    {
        $this->expectException(ExtensionManagerException::class);
        $this->expectExceptionCode(1342432103);
        $this->downloadQueueMock->addExtensionToQueue($this->extensionMock, 'unknownStack');
    }

    /**
     * @test
     */
    public function addExtensionToQueueThrowsExceptionIfExtensionWithSameKeyAndDifferentValuesAlreadyExists()
    {
        /** @var \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extensionMock2 */
        $extensionMock2 = $this->getMockBuilder(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class)
            ->setMethods(['dummy'])
            ->getMock();
        $extensionMock2->setExtensionKey('foobar');
        $extensionMock2->setVersion('1.0.3');

        $this->expectException(ExtensionManagerException::class);
        $this->expectExceptionCode(1342432101);
        $this->downloadQueueMock->addExtensionToQueue($extensionMock2);
        $this->downloadQueueMock->addExtensionToQueue($this->extensionMock);
    }

    /**
     * @test
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

        self::assertTrue(array_key_exists('foobar', $extensionStorageBefore['download']));

        $this->downloadQueueMock->removeExtensionFromQueue($this->extensionMock);
        $extensionStorageAfter = $this->downloadQueueMock->_get('extensionStorage');

        self::assertFalse(array_key_exists('foobar', $extensionStorageAfter['download']));
    }
}
