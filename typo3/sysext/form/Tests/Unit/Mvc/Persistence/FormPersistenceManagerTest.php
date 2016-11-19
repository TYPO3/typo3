<?php
namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Persistence;

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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\NoUniqueIdentifierException;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;

/**
 * Test case
 */
class FormPersistenceManagerTest extends UnitTestCase
{

    /**
     * @test
     */
    public function loadThrowsExceptionIfPersistenceIdentifierHasNoYamlExtension()
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1477679819);

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'dummy'
        ], [], '', false);

        $input = '-1:/user_uploads/_example.php';
        $mockFormPersistenceManager->_call('load', $input);
    }

    /**
     * @test
     */
    public function saveThrowsExceptionIfPersistenceIdentifierHasNoYamlExtension()
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1477679820);

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'dummy'
        ], [], '', false);

        $input = '-1:/user_uploads/_example.php';
        $mockFormPersistenceManager->_call('save', $input, []);
    }

    /**
     * @test
     */
    public function saveThrowsExceptionIfPersistenceIdentifierIsAExtensionLocationAndSaveToExtensionLocationIsNotAllowed()
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1477680881);

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'dummy'
        ], [], '', false);

        $mockFormPersistenceManager->_set('formSettings', [
            'persistenceManager' => [
                'allowSaveToExtensionPaths' => false,
            ],
        ]);

        $input = 'EXT:form/Resources/Forms/_example.yaml';
        $mockFormPersistenceManager->_call('save', $input, []);
    }

    /**
     * @test
     */
    public function deleteThrowsExceptionIfPersistenceIdentifierHasNoYamlExtension()
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1472239534);

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'dummy'
        ], [], '', false);

        $input = '-1:/user_uploads/_example.php';
        $mockFormPersistenceManager->_call('delete', $input);
    }

    /**
     * @test
     */
    public function deleteThrowsExceptionIfPersistenceIdentifierFileDoesNotExists()
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1472239535);

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'exists'
        ], [], '', false);

        $mockFormPersistenceManager
            ->expects($this->any())
            ->method('exists')
            ->willReturn(false);

        $input = '-1:/user_uploads/_example.yaml';
        $mockFormPersistenceManager->_call('delete', $input);
    }

    /**
     * @test
     */
    public function deleteThrowsExceptionIfPersistenceIdentifierIsExtensionLocation()
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1472239536);

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'exists'
        ], [], '', false);

        $mockFormPersistenceManager
            ->expects($this->any())
            ->method('exists')
            ->willReturn(true);

        $input = 'EXT:form/Resources/Forms/_example.yaml';
        $mockFormPersistenceManager->_call('delete', $input);
    }

    /**
     * @test
     */
    public function deleteThrowsExceptionIfPersistenceIdentifierIsStorageLocationAndDeleteFromStorageIsNotAllowed()
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1472239516);

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'getStorageByUid',
            'exists'
        ], [], '', false);

        $mockStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockStorage
            ->expects($this->any())
            ->method('checkFileActionPermission')
            ->willReturn(false);

        $file = new File(['identifier' => '', 'mime_type' => ''], $mockStorage);
        $mockStorage
            ->expects($this->any())
            ->method('getFile')
            ->willReturn($file);

        $mockFormPersistenceManager
            ->expects($this->any())
            ->method('getStorageByUid')
            ->willReturn($mockStorage);

        $mockFormPersistenceManager
            ->expects($this->any())
            ->method('exists')
            ->willReturn(true);

        $input = '-1:/user_uploads/_example.yaml';
        $mockFormPersistenceManager->_call('delete', $input);
    }

    /**
     * @test
     */
    public function existsReturnsTrueIfPersistenceIdentifierIsExtensionLocationAndFileExistsAndFileHasYamlExtension()
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'dummy'
        ], [], '', false);

        $input = 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/BlankForm.yaml';
        $this->assertTrue($mockFormPersistenceManager->_call('exists', $input));
    }

    /**
     * @test
     */
    public function existsReturnsFalseIfPersistenceIdentifierIsExtensionLocationAndFileExistsAndFileHasNoYamlExtension()
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'dummy'
        ], [], '', false);

        $input = 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/BlankForm.txt';
        $this->assertFalse($mockFormPersistenceManager->_call('exists', $input));
    }

    /**
     * @test
     */
    public function existsReturnsFalseIfPersistenceIdentifierIsExtensionLocationAndFileNotExistsAndFileHasYamlExtension()
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'dummy'
        ], [], '', false);

        $input = 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/_BlankForm.yaml';
        $this->assertFalse($mockFormPersistenceManager->_call('exists', $input));
    }

    /**
     * @test
     */
    public function existsReturnsTrueIfPersistenceIdentifierIsStorageLocationAndFileExistsAndFileHasYamlExtension()
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'getStorageByUid'
        ], [], '', false);

        $mockStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStorage
            ->expects($this->any())
            ->method('hasFile')
            ->willReturn(true);

        $mockFormPersistenceManager
            ->expects($this->any())
            ->method('getStorageByUid')
            ->willReturn($mockStorage);

        $input = '-1:/user_uploads/_example.yaml';
        $this->assertTrue($mockFormPersistenceManager->_call('exists', $input));
    }

    /**
     * @test
     */
    public function existsReturnsFalseIfPersistenceIdentifierIsStorageLocationAndFileExistsAndFileNoYamlExtension()
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'getStorageByUid'
        ], [], '', false);

        $mockStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStorage
            ->expects($this->any())
            ->method('hasFile')
            ->willReturn(true);

        $mockFormPersistenceManager
            ->expects($this->any())
            ->method('getStorageByUid')
            ->willReturn($mockStorage);

        $input = '-1:/user_uploads/_example.php';
        $this->assertFalse($mockFormPersistenceManager->_call('exists', $input));
    }

    /**
     * @test
     */
    public function existsReturnsFalseIfPersistenceIdentifierIsStorageLocationAndFileNotExistsAndFileHasYamlExtension()
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'getStorageByUid'
        ], [], '', false);

        $mockStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStorage
            ->expects($this->any())
            ->method('hasFile')
            ->willReturn(false);

        $mockFormPersistenceManager
            ->expects($this->any())
            ->method('getStorageByUid')
            ->willReturn($mockStorage);

        $input = '-1:/user_uploads/_example.yaml';
        $this->assertFalse($mockFormPersistenceManager->_call('exists', $input));
    }

    /**
     * @test
     */
    public function getUniquePersistenceIdentifierAppendNumberIfPersistenceIdentifierExists()
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'exists'
        ], [], '', false);

        $mockFormPersistenceManager
            ->expects($this->at(0))
            ->method('exists')
            ->willReturn(true);

        $mockFormPersistenceManager
            ->expects($this->at(1))
            ->method('exists')
            ->willReturn(true);

        $mockFormPersistenceManager
            ->expects($this->at(2))
            ->method('exists')
            ->willReturn(false);

        $input = 'example';
        $expected = '-1:/user_uploads/example_2.yaml';
        $this->assertSame($expected, $mockFormPersistenceManager->_call('getUniquePersistenceIdentifier', $input, '-1:/user_uploads/'));
    }

    /**
     * @test
     */
    public function getUniquePersistenceIdentifierAppendTimestampIfPersistenceIdentifierExists()
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'exists'
        ], [], '', false);

        for ($attempts = 0; $attempts <= 99; $attempts++) {
            $mockFormPersistenceManager
                ->expects($this->at($attempts))
                ->method('exists')
                ->willReturn(true);
        }

        $mockFormPersistenceManager
            ->expects($this->at(100))
            ->method('exists')
            ->willReturn(false);

        $input = 'example';
        $expected = '#^-1:/user_uploads/example_([0-9]{10}).yaml$#';

        $returnValue = $mockFormPersistenceManager->_call('getUniquePersistenceIdentifier', $input, '-1:/user_uploads/');
        $this->assertEquals(1, preg_match($expected, $returnValue));
    }

    /**
     * @test
     */
    public function getUniqueIdentifierThrowsExceptionIfIdentifierExists()
    {
        $this->expectException(NoUniqueIdentifierException::class);
        $this->expectExceptionCode(1477688567);

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'checkForDuplicateIdentifier'
        ], [], '', false);

        $mockFormPersistenceManager
            ->expects($this->any())
            ->method('checkForDuplicateIdentifier')
            ->willReturn(true);

        $input = 'example';
        $mockFormPersistenceManager->_call('getUniqueIdentifier', $input);
    }

    /**
     * @test
     */
    public function getUniqueIdentifierAppendTimestampIfIdentifierExists()
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'checkForDuplicateIdentifier'
        ], [], '', false);

        for ($attempts = 0; $attempts <= 99; $attempts++) {
            $mockFormPersistenceManager
                ->expects($this->at($attempts))
                ->method('checkForDuplicateIdentifier')
                ->willReturn(true);
        }

        $mockFormPersistenceManager
            ->expects($this->at(100))
            ->method('checkForDuplicateIdentifier')
            ->willReturn(false);

        $input = 'example';
        $expected = '#^example_([0-9]{10})$#';

        $returnValue = $mockFormPersistenceManager->_call('getUniqueIdentifier', $input);
        $this->assertEquals(1, preg_match($expected, $returnValue));
    }

    /**
     * @test
     */
    public function checkForDuplicateIdentifierReturnsTrueIfIdentifierIsUsed()
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'listForms'
        ], [], '', false);

        $mockFormPersistenceManager
            ->expects($this->at($attempts))
            ->method('listForms')
            ->willReturn([
                0 => [
                    'identifier' => 'example',
                ],
            ]);

        $input = 'example';
        $this->assertTrue($mockFormPersistenceManager->_call('checkForDuplicateIdentifier', $input));
    }

    /**
     * @test
     */
    public function checkForDuplicateIdentifierReturnsFalseIfIdentifierIsUsed()
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'listForms'
        ], [], '', false);

        $mockFormPersistenceManager
            ->expects($this->at($attempts))
            ->method('listForms')
            ->willReturn([
                0 => [
                    'identifier' => 'example',
                ],
            ]);

        $input = 'other-example';
        $this->assertFalse($mockFormPersistenceManager->_call('checkForDuplicateIdentifier', $input));
    }

    /**
     * @test
     */
    public function getFileByIdentifierThrowsExceptionIfReadFromStorageIsNotAllowed()
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1471630578);

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'getStorageByUid',
        ], [], '', false);

        $mockStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockStorage
            ->expects($this->any())
            ->method('checkFileActionPermission')
            ->willReturn(false);

        $file = new File(['identifier' => '', 'mime_type' => ''], $mockStorage);
        $mockStorage
            ->expects($this->any())
            ->method('getFile')
            ->willReturn($file);

        $mockFormPersistenceManager
            ->expects($this->any())
            ->method('getStorageByUid')
            ->willReturn($mockStorage);

        $input = '-1:/user_uploads/example.yaml';
        $mockFormPersistenceManager->_call('getFileByIdentifier', $input);
    }

    /**
     * @test
     */
    public function getOrCreateFileThrowsExceptionIfFolderNotExistsInStorage()
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1471630579);

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'getStorageByUid',
        ], [], '', false);

        $mockStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockStorage
            ->expects($this->any())
            ->method('hasFolder')
            ->willReturn(false);

        $mockFormPersistenceManager
            ->expects($this->any())
            ->method('getStorageByUid')
            ->willReturn($mockStorage);

        $input = '-1:/user_uploads/example.yaml';
        $mockFormPersistenceManager->_call('getOrCreateFile', $input);
    }

    /**
     * @test
     */
    public function getOrCreateFileThrowsExceptionIfWriteToStorageIsNotAllowed()
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1471630580);

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'getStorageByUid',
        ], [], '', false);

        $mockStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockStorage
            ->expects($this->any())
            ->method('hasFolder')
            ->willReturn(true);

        $mockStorage
            ->expects($this->any())
            ->method('checkFileActionPermission')
            ->willReturn(false);

        $file = new File(['identifier' => '', 'mime_type' => ''], $mockStorage);
        $mockStorage
            ->expects($this->any())
            ->method('getFile')
            ->willReturn($file);

        $mockFormPersistenceManager
            ->expects($this->any())
            ->method('getStorageByUid')
            ->willReturn($mockStorage);

        $input = '-1:/user_uploads/example.yaml';
        $mockFormPersistenceManager->_call('getOrCreateFile', $input);
    }

    /**
     * @test
     */
    public function getStorageByUidThrowsExceptionIfStorageNotExists()
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1471630581);

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'dummy',
        ], [], '', false);

        $mockStorageRepository = $this->getMockBuilder(StorageRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockStorageRepository
            ->expects($this->any())
            ->method('findByUid')
            ->willReturn(null);

        $mockFormPersistenceManager->_set('storageRepository', $mockStorageRepository);
        $mockFormPersistenceManager->_call('getStorageByUid', -1);
    }

    /**
     * @test
     */
    public function getStorageByUidThrowsExceptionIfStorageIsNotBrowsable()
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1471630581);

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'dummy',
        ], [], '', false);

        $mockStorageRepository = $this->getMockBuilder(StorageRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockStorage
            ->expects($this->any())
            ->method('isBrowsable')
            ->willReturn(false);

        $mockStorageRepository
            ->expects($this->any())
            ->method('findByUid')
            ->willReturn($mockStorage);

        $mockFormPersistenceManager->_set('storageRepository', $mockStorageRepository);
        $mockFormPersistenceManager->_call('getStorageByUid', -1);
    }
}
