<?php
declare(strict_types = 1);
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

use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\NoUniqueIdentifierException;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FormPersistenceManagerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function loadThrowsExceptionIfPersistenceIdentifierHasNoYamlExtension(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1477679819);

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'dummy'
        ], [], '', false);

        $runtimeCache= $this->getMockBuilder(VariableFrontend::class)
            ->setMethods(['get', 'set'])
            ->disableOriginalConstructor()
            ->getMock();

        $runtimeCache
            ->expects($this->any())
            ->method('get')
            ->willReturn(false);

        $mockFormPersistenceManager->_set('runtimeCache', $runtimeCache);

        $input = '-1:/user_uploads/_example.php';
        $mockFormPersistenceManager->_call('load', $input);
    }

    /**
     * @test
     */
    public function loadThrowsExceptionIfPersistenceIdentifierIsAExtensionLocationWhichIsNotAllowed(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1484071985);

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'dummy'
        ], [], '', false);

        $runtimeCache= $this->getMockBuilder(VariableFrontend::class)
            ->setMethods(['get', 'set'])
            ->disableOriginalConstructor()
            ->getMock();

        $runtimeCache
            ->expects($this->any())
            ->method('get')
            ->willReturn(false);

        $mockFormPersistenceManager->_set('runtimeCache', $runtimeCache);

        $mockFormPersistenceManager->_set('formSettings', [
            'persistenceManager' => [
                'allowedExtensionPaths' => [],
            ],
        ]);

        $input = 'EXT:form/Resources/Forms/_example.form.yaml';
        $mockFormPersistenceManager->_call('load', $input);
    }

    /**
     * @test
     */
    public function saveThrowsExceptionIfPersistenceIdentifierHasNoYamlExtension(): void
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
    public function saveThrowsExceptionIfPersistenceIdentifierIsAExtensionLocationAndSaveToExtensionLocationIsNotAllowed(): void
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

        $input = 'EXT:form/Resources/Forms/_example.form.yaml';
        $mockFormPersistenceManager->_call('save', $input, []);
    }

    /**
     * @test
     */
    public function saveThrowsExceptionIfPersistenceIdentifierIsAExtensionLocationWhichIsNotAllowed(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1484073571);

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'dummy'
        ], [], '', false);

        $runtimeCache= $this->getMockBuilder(VariableFrontend::class)
            ->setMethods(['get', 'set'])
            ->disableOriginalConstructor()
            ->getMock();

        $runtimeCache
            ->expects($this->any())
            ->method('get')
            ->willReturn(false);

        $mockFormPersistenceManager->_set('runtimeCache', $runtimeCache);

        $mockFormPersistenceManager->_set('formSettings', [
            'persistenceManager' => [
                'allowSaveToExtensionPaths' => true,
                'allowedExtensionPaths' => [],
            ],
        ]);

        $input = 'EXT:form/Resources/Forms/_example.form.yaml';
        $mockFormPersistenceManager->_call('save', $input, []);
    }

    /**
     * @test
     */
    public function deleteThrowsExceptionIfPersistenceIdentifierHasNoYamlExtension(): void
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
    public function deleteThrowsExceptionIfPersistenceIdentifierFileDoesNotExists(): void
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

        $input = '-1:/user_uploads/_example.form.yaml';
        $mockFormPersistenceManager->_call('delete', $input);
    }

    /**
     * @test
     */
    public function deleteThrowsExceptionIfPersistenceIdentifierIsExtensionLocationAndDeleteFromExtensionLocationsIsNotAllowed(): void
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

        $mockFormPersistenceManager->_set('formSettings', [
            'persistenceManager' => [
                'allowDeleteFromExtensionPaths' => false,
            ],
        ]);

        $input = 'EXT:form/Resources/Forms/_example.form.yaml';
        $mockFormPersistenceManager->_call('delete', $input);
    }

    /**
     * @test
     */
    public function deleteThrowsExceptionIfPersistenceIdentifierIsExtensionLocationWhichIsNotAllowed(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1484073878);

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'exists'
        ], [], '', false);

        $runtimeCache= $this->getMockBuilder(VariableFrontend::class)
            ->setMethods(['get', 'set'])
            ->disableOriginalConstructor()
            ->getMock();

        $runtimeCache
            ->expects($this->any())
            ->method('get')
            ->willReturn(false);

        $mockFormPersistenceManager->_set('runtimeCache', $runtimeCache);

        $mockFormPersistenceManager
            ->expects($this->any())
            ->method('exists')
            ->willReturn(true);

        $mockFormPersistenceManager->_set('formSettings', [
            'persistenceManager' => [
                'allowDeleteFromExtensionPaths' => true,
                'allowedExtensionPaths' => [],
            ],
        ]);

        $input = 'EXT:form/Resources/Forms/_example.form.yaml';
        $mockFormPersistenceManager->_call('delete', $input);
    }

    /**
     * @test
     */
    public function deleteThrowsExceptionIfPersistenceIdentifierIsStorageLocationAndDeleteFromStorageIsNotAllowed(): void
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

        $file = new File(['name' => 'foo', 'identifier' => '', 'mime_type' => ''], $mockStorage);
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

        $input = '-1:/user_uploads/_example.form.yaml';
        $mockFormPersistenceManager->_call('delete', $input);
    }

    /**
     * @test
     */
    public function existsReturnsTrueIfPersistenceIdentifierIsExtensionLocationAndFileExistsAndFileHasYamlExtension(): void
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'dummy'
        ], [], '', false);

        $runtimeCache= $this->getMockBuilder(VariableFrontend::class)
            ->setMethods(['get', 'set'])
            ->disableOriginalConstructor()
            ->getMock();

        $runtimeCache
            ->expects($this->any())
            ->method('get')
            ->willReturn(false);

        $mockFormPersistenceManager->_set('runtimeCache', $runtimeCache);

        $mockFormPersistenceManager->_set('formSettings', [
            'persistenceManager' => [
                'allowedExtensionPaths' => [
                    'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/'
                ],
            ],
        ]);

        $input = 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/BlankForm.form.yaml';
        $this->assertTrue($mockFormPersistenceManager->_call('exists', $input));
    }

    /**
     * @test
     */
    public function existsReturnsFalseIfPersistenceIdentifierIsExtensionLocationAndFileExistsAndFileHasNoYamlExtension(): void
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
    public function existsReturnsFalseIfPersistenceIdentifierIsExtensionLocationAndFileExistsAndExtensionLocationIsNotAllowed(): void
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'dummy'
        ], [], '', false);

        $mockFormPersistenceManager->_set('formSettings', [
            'persistenceManager' => [
                'allowedExtensionPaths' => [],
            ],
        ]);

        $input = 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/BlankForm.yaml';
        $this->assertFalse($mockFormPersistenceManager->_call('exists', $input));
    }

    /**
     * @test
     */
    public function existsReturnsFalseIfPersistenceIdentifierIsExtensionLocationAndFileNotExistsAndFileHasYamlExtension(): void
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
    public function existsReturnsTrueIfPersistenceIdentifierIsStorageLocationAndFileExistsAndFileHasYamlExtension(): void
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

        $input = '-1:/user_uploads/_example.form.yaml';
        $this->assertTrue($mockFormPersistenceManager->_call('exists', $input));
    }

    /**
     * @test
     */
    public function existsReturnsFalseIfPersistenceIdentifierIsStorageLocationAndFileExistsAndFileNoYamlExtension(): void
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
    public function existsReturnsFalseIfPersistenceIdentifierIsStorageLocationAndFileNotExistsAndFileHasYamlExtension(): void
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
    public function getUniquePersistenceIdentifierAppendNumberIfPersistenceIdentifierExists(): void
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
        $expected = '-1:/user_uploads/example_2.form.yaml';
        $this->assertSame($expected, $mockFormPersistenceManager->_call('getUniquePersistenceIdentifier', $input, '-1:/user_uploads/'));
    }

    /**
     * @test
     */
    public function getUniquePersistenceIdentifierAppendTimestampIfPersistenceIdentifierExists(): void
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
        $expected = '#^-1:/user_uploads/example_([0-9]{10}).form.yaml$#';

        $returnValue = $mockFormPersistenceManager->_call('getUniquePersistenceIdentifier', $input, '-1:/user_uploads/');
        $this->assertEquals(1, preg_match($expected, $returnValue));
    }

    /**
     * @test
     */
    public function getUniqueIdentifierThrowsExceptionIfIdentifierExists(): void
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
    public function getUniqueIdentifierAppendTimestampIfIdentifierExists(): void
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
    public function checkForDuplicateIdentifierReturnsTrueIfIdentifierIsUsed(): void
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'listForms'
        ], [], '', false);

        $mockFormPersistenceManager
            ->expects($this->at(0))
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
    public function checkForDuplicateIdentifierReturnsFalseIfIdentifierIsUsed(): void
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'listForms'
        ], [], '', false);

        $mockFormPersistenceManager
            ->expects($this->at(0))
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
    public function retrieveFileByPersistenceIdentifierThrowsExceptionIfReadFromStorageIsNotAllowed(): void
    {
        $this->expectException(PersistenceManagerException::class);
        $this->expectExceptionCode(1471630578);

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, [
            'dummy',
        ], [], '', false);

        $storage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storage
            ->expects($this->any())
            ->method('checkFileActionPermission')
            ->willReturn(false);

        $file = new File(['name' => 'foo', 'identifier' => '', 'mime_type' => ''], $storage);

        $resourceFactory = $this->getMockBuilder(ResourceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resourceFactory
            ->expects($this->any())
            ->method('retrieveFileOrFolderObject')
            ->willReturn($file);

        $mockFormPersistenceManager->_set('resourceFactory', $resourceFactory);

        $input = '-1:/user_uploads/example.yaml';
        $mockFormPersistenceManager->_call('retrieveFileByPersistenceIdentifier', $input);
    }

    /**
     * @test
     */
    public function getOrCreateFileThrowsExceptionIfFolderNotExistsInStorage(): void
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
    public function getOrCreateFileThrowsExceptionIfWriteToStorageIsNotAllowed(): void
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

        $file = new File(['name' => 'foo', 'identifier' => '', 'mime_type' => ''], $mockStorage);
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
    public function getStorageByUidThrowsExceptionIfStorageNotExists(): void
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
    public function getStorageByUidThrowsExceptionIfStorageIsNotBrowsable(): void
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

    public function isAllowedPersistencePathReturnsPropperValuesDataProvider(): array
    {
        return [
            [
                'persistencePath' => '',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => '-1:/user_uploads',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => '-1:/user_uploads/',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => '-1:/user_uploads/example.form.yaml',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/BlankForm.form.yaml',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => [],
                'expected' => false,
            ],

            [
                'persistencePath' => '-1:/user_uploads/',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/some_path/'],
                'expected' => false,
            ],
            [
                'persistencePath' => '-1:/user_uploads/',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/user_uploads/'],
                'expected' => true,
            ],
            [
                'persistencePath' => '-1:/user_uploads',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/user_uploads/'],
                'expected' => true,
            ],
            [
                'persistencePath' => '-1:/user_uploads/',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/user_uploads'],
                'expected' => true,
            ],

            [
                'persistencePath' => 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/',
                'allowedExtensionPaths' => ['EXT:some_extension/Tests/Unit/Mvc/Persistence/Fixtures/'],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/',
                'allowedExtensionPaths' => ['EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/'],
                'allowedFileMounts' => [],
                'expected' => true,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures',
                'allowedExtensionPaths' => ['EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/'],
                'allowedFileMounts' => [],
                'expected' => true,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/',
                'allowedExtensionPaths' => ['EXT:form/Tests/Unit/Mvc/Persistence/Fixtures'],
                'allowedFileMounts' => [],
                'expected' => true,
            ],

            [
                'persistencePath' => '-1:/user_uploads/example.yaml',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/some_path/'],
                'expected' => false,
            ],
            [
                'persistencePath' => '-1:/user_uploads/example.form.yaml',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/some_path/'],
                'expected' => false,
            ],
            [
                'persistencePath' => '-1:/user_uploads/example.yaml',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/user_uploads/'],
                'expected' => false,
            ],
            [
                'persistencePath' => '-1:/user_uploads/example.yaml',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/user_uploads'],
                'expected' => false,
            ],
            [
                'persistencePath' => '-1:/user_uploads/example.form.yaml',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/user_uploads/'],
                'expected' => true,
            ],
            [
                'persistencePath' => '-1:/user_uploads/example.form.yaml',
                'allowedExtensionPaths' => [],
                'allowedFileMounts' => ['-1:/user_uploads'],
                'expected' => true,
            ],

            [
                'persistencePath' => 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/BlankForm.txt',
                'allowedExtensionPaths' => ['EXT:some_extension/Tests/Unit/Mvc/Persistence/Fixtures/'],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/BlankForm.form.yaml',
                'allowedExtensionPaths' => ['EXT:some_extension/Tests/Unit/Mvc/Persistence/Fixtures/'],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/BlankForm.txt',
                'allowedExtensionPaths' => ['EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/'],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/BlankForm.txt',
                'allowedExtensionPaths' => ['EXT:form/Tests/Unit/Mvc/Persistence/Fixtures'],
                'allowedFileMounts' => [],
                'expected' => false,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/BlankForm.form.yaml',
                'allowedExtensionPaths' => ['EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/'],
                'allowedFileMounts' => [],
                'expected' => true,
            ],
            [
                'persistencePath' => 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/BlankForm.form.yaml',
                'allowedExtensionPaths' => ['EXT:form/Tests/Unit/Mvc/Persistence/Fixtures'],
                'allowedFileMounts' => [],
                'expected' => true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider isAllowedPersistencePathReturnsPropperValuesDataProvider
     */
    public function isAllowedPersistencePathReturnsPropperValues(string $persistencePath, array $allowedExtensionPaths, array $allowedFileMounts, $expected): void
    {
        $formPersistenceManagerMock = $this->getAccessibleMock(FormPersistenceManager::class, [
            'getStorageByUid',
        ]);

        $runtimeCacheMock = $this->getMockBuilder(VariableFrontend::class)
            ->setMethods(['get', 'set'])
            ->disableOriginalConstructor()
            ->getMock();

        $runtimeCacheMock
            ->expects(self::any())
            ->method('get')
            ->willReturn(false);

        $storageMock = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storageMock
            ->expects(self::any())
            ->method('getRootLevelFolder')
            ->willReturn(new Folder($storageMock, '', ''));

        $storageMock
            ->expects(self::any())
            ->method('getFileMounts')
            ->willReturn([]);

        $storageMock
            ->expects(self::any())
            ->method('getFolder')
            ->willReturn(new Folder($storageMock, '', ''));

        $formPersistenceManagerMock
            ->expects(self::any())
            ->method('getStorageByUid')
            ->willReturn($storageMock);

        $formPersistenceManagerMock->_set('runtimeCache', $runtimeCacheMock);
        $formPersistenceManagerMock->_set('formSettings', [
            'persistenceManager' => [
                'allowedExtensionPaths' => $allowedExtensionPaths,
                'allowedFileMounts' => $allowedFileMounts,
            ],
        ]);

        self::assertEquals($expected, $formPersistenceManagerMock->_call('isAllowedPersistencePath', $persistencePath));
    }
}
