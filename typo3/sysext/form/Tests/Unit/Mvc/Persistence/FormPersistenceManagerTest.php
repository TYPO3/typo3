<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Persistence;

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

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['dummy'], [], '', false);

        $runtimeCache= $this->getMockBuilder(VariableFrontend::class)
            ->onlyMethods(['get', 'set'])
            ->disableOriginalConstructor()
            ->getMock();

        $runtimeCache
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

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['dummy'], [], '', false);

        $runtimeCache= $this->getMockBuilder(VariableFrontend::class)
            ->onlyMethods(['get', 'set'])
            ->disableOriginalConstructor()
            ->getMock();

        $runtimeCache
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

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['dummy'], [], '', false);

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

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['dummy'], [], '', false);

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

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['dummy'], [], '', false);

        $runtimeCache= $this->getMockBuilder(VariableFrontend::class)
            ->onlyMethods(['get', 'set'])
            ->disableOriginalConstructor()
            ->getMock();

        $runtimeCache
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

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['dummy'], [], '', false);

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

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['exists'], [], '', false);

        $mockFormPersistenceManager
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

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['exists'], [], '', false);

        $mockFormPersistenceManager
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

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['exists'], [], '', false);

        $runtimeCache= $this->getMockBuilder(VariableFrontend::class)
            ->onlyMethods(['get', 'set'])
            ->disableOriginalConstructor()
            ->getMock();

        $runtimeCache
            ->method('get')
            ->willReturn(false);

        $mockFormPersistenceManager->_set('runtimeCache', $runtimeCache);

        $mockFormPersistenceManager
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

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['getStorageByUid', 'exists'], [], '', false);

        $mockStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockStorage
            ->method('checkFileActionPermission')
            ->willReturn(false);

        $file = new File(['name' => 'foo', 'identifier' => '', 'mime_type' => ''], $mockStorage);
        $mockStorage
            ->method('getFile')
            ->willReturn($file);

        $mockFormPersistenceManager
            ->method('getStorageByUid')
            ->willReturn($mockStorage);

        $mockFormPersistenceManager
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
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['dummy'], [], '', false);

        $runtimeCache= $this->getMockBuilder(VariableFrontend::class)
            ->onlyMethods(['get', 'set'])
            ->disableOriginalConstructor()
            ->getMock();

        $runtimeCache
            ->method('get')
            ->willReturn(false);

        $mockFormPersistenceManager->_set('runtimeCache', $runtimeCache);

        $mockFormPersistenceManager->_set('formSettings', [
            'persistenceManager' => [
                'allowedExtensionPaths' => [
                    'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/',
                ],
            ],
        ]);

        $input = 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/BlankForm.form.yaml';
        self::assertTrue($mockFormPersistenceManager->_call('exists', $input));
    }

    /**
     * @test
     */
    public function existsReturnsFalseIfPersistenceIdentifierIsExtensionLocationAndFileExistsAndFileHasNoYamlExtension(): void
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['dummy'], [], '', false);
        $input = 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/BlankForm.txt';
        self::assertFalse($mockFormPersistenceManager->_call('exists', $input));
    }

    /**
     * @test
     */
    public function existsReturnsFalseIfPersistenceIdentifierIsExtensionLocationAndFileExistsAndExtensionLocationIsNotAllowed(): void
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['dummy'], [], '', false);

        $mockFormPersistenceManager->_set('formSettings', [
            'persistenceManager' => [
                'allowedExtensionPaths' => [],
            ],
        ]);

        $input = 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/BlankForm.yaml';
        self::assertFalse($mockFormPersistenceManager->_call('exists', $input));
    }

    /**
     * @test
     */
    public function existsReturnsFalseIfPersistenceIdentifierIsExtensionLocationAndFileNotExistsAndFileHasYamlExtension(): void
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['dummy'], [], '', false);
        $input = 'EXT:form/Tests/Unit/Mvc/Persistence/Fixtures/_BlankForm.yaml';
        self::assertFalse($mockFormPersistenceManager->_call('exists', $input));
    }

    /**
     * @test
     */
    public function existsReturnsTrueIfPersistenceIdentifierIsStorageLocationAndFileExistsAndFileHasYamlExtension(): void
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['getStorageByUid'], [], '', false);

        $mockStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStorage
            ->method('hasFile')
            ->willReturn(true);

        $mockFormPersistenceManager
            ->method('getStorageByUid')
            ->willReturn($mockStorage);

        $input = '-1:/user_uploads/_example.form.yaml';
        self::assertTrue($mockFormPersistenceManager->_call('exists', $input));
    }

    /**
     * @test
     */
    public function existsReturnsFalseIfPersistenceIdentifierIsStorageLocationAndFileExistsAndFileNoYamlExtension(): void
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['getStorageByUid'], [], '', false);

        $mockStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStorage
            ->method('hasFile')
            ->willReturn(true);

        $mockFormPersistenceManager
            ->method('getStorageByUid')
            ->willReturn($mockStorage);

        $input = '-1:/user_uploads/_example.php';
        self::assertFalse($mockFormPersistenceManager->_call('exists', $input));
    }

    /**
     * @test
     */
    public function existsReturnsFalseIfPersistenceIdentifierIsStorageLocationAndFileNotExistsAndFileHasYamlExtension(): void
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['getStorageByUid'], [], '', false);

        $mockStorage = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStorage
            ->method('hasFile')
            ->willReturn(false);

        $mockFormPersistenceManager
            ->method('getStorageByUid')
            ->willReturn($mockStorage);

        $input = '-1:/user_uploads/_example.yaml';
        self::assertFalse($mockFormPersistenceManager->_call('exists', $input));
    }

    /**
     * @test
     */
    public function getUniquePersistenceIdentifierAppendNumberIfPersistenceIdentifierExists(): void
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['exists'], [], '', false);

        $mockFormPersistenceManager
            ->expects(self::exactly(3))
            ->method('exists')
            ->will(self::onConsecutiveCalls(
                self::returnValue(true),
                self::returnValue(true),
                self::returnValue(false)
            ));

        $input = 'example';
        $expected = '-1:/user_uploads/example_2.form.yaml';
        $result = $mockFormPersistenceManager->_call('getUniquePersistenceIdentifier', $input, '-1:/user_uploads/');
        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function getUniquePersistenceIdentifierAppendTimestampIfPersistenceIdentifierExists(): void
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['exists'], [], '', false);

        $mockFormPersistenceManager
            ->expects(self::exactly(101))
            ->method('exists')
            ->will(self::onConsecutiveCalls(
                ...$this->returnTrue100Times()
            ));

        $input = 'example';
        $expected = '#^-1:/user_uploads/example_([0-9]{10}).form.yaml$#';

        $returnValue = $mockFormPersistenceManager->_call('getUniquePersistenceIdentifier', $input, '-1:/user_uploads/');
        self::assertMatchesRegularExpression($expected, $returnValue);
    }

    /**
     * Helper function to trigger the fallback unique identifier creation after 100 attempts
     * @return array
     */
    private function returnTrue100Times(): array
    {
        $returnValues = [];
        $returnValues = array_pad($returnValues, 100, self::returnValue(true));
        $returnValues[] = self::returnValue(false);
        return $returnValues;
    }

    /**
     * @test
     */
    public function getUniqueIdentifierThrowsExceptionIfIdentifierExists(): void
    {
        $this->expectException(NoUniqueIdentifierException::class);
        $this->expectExceptionCode(1477688567);

        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['checkForDuplicateIdentifier'], [], '', false);

        $mockFormPersistenceManager
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
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['checkForDuplicateIdentifier'], [], '', false);

        $mockFormPersistenceManager
                ->expects(self::exactly(101))
                ->method('checkForDuplicateIdentifier')
                ->will(self::onConsecutiveCalls(
                    ...$this->returnTrue100Times()
                ));

        $input = 'example';
        $expected = '#^example_([0-9]{10})$#';

        $returnValue = $mockFormPersistenceManager->_call('getUniqueIdentifier', $input);
        self::assertMatchesRegularExpression($expected, $returnValue);
    }

    /**
     * @test
     */
    public function checkForDuplicateIdentifierReturnsTrueIfIdentifierIsUsed(): void
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['listForms'], [], '', false);

        $mockFormPersistenceManager
            ->expects(self::once())
            ->method('listForms')
            ->willReturn([
                0 => [
                    'identifier' => 'example',
                ],
            ]);

        $input = 'example';
        self::assertTrue($mockFormPersistenceManager->_call('checkForDuplicateIdentifier', $input));
    }

    /**
     * @test
     */
    public function checkForDuplicateIdentifierReturnsFalseIfIdentifierIsUsed(): void
    {
        $mockFormPersistenceManager = $this->getAccessibleMock(FormPersistenceManager::class, ['listForms'], [], '', false);

        $mockFormPersistenceManager
            ->expects(self::once())
            ->method('listForms')
            ->willReturn([
                0 => [
                    'identifier' => 'example',
                ],
            ]);

        $input = 'other-example';
        self::assertFalse($mockFormPersistenceManager->_call('checkForDuplicateIdentifier', $input));
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
            ->method('checkFileActionPermission')
            ->willReturn(false);

        $file = new File(['name' => 'foo', 'identifier' => '', 'mime_type' => ''], $storage);

        $resourceFactory = $this->getMockBuilder(ResourceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resourceFactory
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
            ->method('hasFolder')
            ->willReturn(false);

        $mockFormPersistenceManager
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
            ->method('hasFolder')
            ->willReturn(true);

        $mockStorage
            ->method('checkFileActionPermission')
            ->willReturn(false);

        $file = new File(['name' => 'foo', 'identifier' => '', 'mime_type' => ''], $mockStorage);
        $mockStorage
            ->method('getFile')
            ->willReturn($file);

        $mockFormPersistenceManager
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
            ->method('isBrowsable')
            ->willReturn(false);

        $mockStorageRepository
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
        $formPersistenceManagerMock = $this->getAccessibleMock(FormPersistenceManager::class, ['getStorageByUid'], [], '', false);

        $runtimeCacheMock = $this->getMockBuilder(VariableFrontend::class)
            ->onlyMethods(['get', 'set'])
            ->disableOriginalConstructor()
            ->getMock();

        $runtimeCacheMock
            ->method('get')
            ->willReturn(false);

        $storageMock = $this->getMockBuilder(ResourceStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storageMock
            ->method('getRootLevelFolder')
            ->willReturn(new Folder($storageMock, '', ''));

        $storageMock
            ->method('getFileMounts')
            ->willReturn([]);

        $storageMock
            ->method('getFolder')
            ->willReturn(new Folder($storageMock, '', ''));

        $formPersistenceManagerMock
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
