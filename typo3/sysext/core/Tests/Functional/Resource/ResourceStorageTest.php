<?php
namespace TYPO3\CMS\Core\Tests\Functional\Resource;

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
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class ResourceStorageTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    protected function tearDown()
    {
        // cleanup manually created folders
        foreach (glob(PATH_site . 'fileadmin/*') as $folderToRemove) {
            GeneralUtility::rmdir($folderToRemove, true);
        }
    }

    /**
     * @test
     */
    public function getNestedProcessingFolderTest()
    {
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_file_storage.xml');
        $this->setUpBackendUserFromFixture(1);
        $subject = (new StorageRepository())->findByUid(1);
        $subject->setEvaluatePermissions(false);

        GeneralUtility::mkdir_deep(PATH_site . 'fileadmin/_processed_');
        GeneralUtility::mkdir_deep(PATH_site . 'fileadmin/adirectory');
        GeneralUtility::mkdir_deep(PATH_site . 'typo3temp/assets/_processed_/');
        file_put_contents(PATH_site . 'fileadmin/adirectory/bar.txt', 'myData');
        clearstatcache();
        $subject->addFileMount('/adirectory/', ['read_only' => false]);
        $file = ResourceFactory::getInstance()->getFileObjectFromCombinedIdentifier('1:/adirectory/bar.txt');

        $rootProcessingFolder = $subject->getProcessingFolder();
        $processingFolder = $subject->getProcessingFolder($file);

        $this->assertInstanceOf(Folder::class, $processingFolder);
        $this->assertNotEquals($rootProcessingFolder, $processingFolder);

        for ($i = ResourceStorage::PROCESSING_FOLDER_LEVELS; $i>0; $i--) {
            $processingFolder = $processingFolder->getParentFolder();
        }
        $this->assertEquals($rootProcessingFolder, $processingFolder);
    }

    /**
     * @param string $targetDirectory
     * @param string $fileMountFolder
     * @param bool $isFileMountReadOnly
     * @param bool $checkWriteAccess
     * @param bool $expectedResult
     * @test
     * @dataProvider isWithinFileMountBoundariesDataProvider
     */
    public function isWithinFileMountBoundariesRespectsReadOnlyFileMounts($targetDirectory, $fileMountFolder, $isFileMountReadOnly, $checkWriteAccess, $expectedResult)
    {
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_file_storage.xml');
        $fileName = 'bar.txt';
        $this->setUpBackendUserFromFixture(1);
        GeneralUtility::mkdir_deep(PATH_site . 'fileadmin/_processed_');
        GeneralUtility::mkdir_deep(PATH_site . 'fileadmin/' . $targetDirectory);
        if ($fileMountFolder !== $targetDirectory) {
            GeneralUtility::mkdir_deep(PATH_site . 'fileadmin/' . $fileMountFolder);
        }
        file_put_contents(PATH_site . 'fileadmin/' . $targetDirectory . '/' . $fileName, 'myData');
        clearstatcache();
        $file = ResourceFactory::getInstance()->getFileObjectFromCombinedIdentifier('1:/' . $targetDirectory . '/' . $fileName);

        $subject = (new StorageRepository())->findByUid(1);
        $subject->setEvaluatePermissions(true);

        // read_only = true -> no write access for user, so checkinf for second argument true should assert false
        $subject->addFileMount('/' . $fileMountFolder . '/', ['read_only' => $isFileMountReadOnly]);
        $this->assertSame($expectedResult, $subject->isWithinFileMountBoundaries($file, $checkWriteAccess));
    }

    /**
     * @return array
     */
    public function isWithinFileMountBoundariesDataProvider()
    {
        return [
            'Access to file in ro file mount denied for write request' => [
                '$targetDirectory' => 'fooBaz',
                '$fileMountFolder' => 'fooBaz',
                '$isFileMountReadOnly' => true,
                '$checkWriteAccess' => true,
                '$expectedResult' => false,
            ],
            'Access to file in ro file mount allowed for read request' => [
                '$targetDirectory' => 'fooBaz',
                '$fileMountFolder' => 'fooBaz',
                '$isFileMountReadOnly' => true,
                '$checkWriteAccess' => false,
                '$expectedResult' => true,
            ],
            'Access to file in rw file mount allowed for write request' => [
                '$targetDirectory' => 'fooBaz',
                '$fileMountFolder' => 'fooBaz',
                '$isFileMountReadOnly' => false,
                '$checkWriteAccess' => true,
                '$expectedResult' => true,
            ],
            'Access to file in rw file mount allowed for read request' => [
                '$targetDirectory' => 'fooBaz',
                '$fileMountFolder' => 'fooBaz',
                '$isFileMountReadOnly' => false,
                '$checkWriteAccess' => false,
                '$expectedResult' => true,
            ],
            'Access to file not in file mount denied for write request' => [
                '$targetDirectory' => 'fooBaz',
                '$fileMountFolder' => 'barBaz',
                '$isFileMountReadOnly' => false,
                '$checkWriteAccess' => true,
                '$expectedResult' => false,
            ],
            'Access to file not in file mount denied for read request' => [
                '$targetDirectory' => 'fooBaz',
                '$fileMountFolder' => 'barBaz',
                '$isFileMountReadOnly' => false,
                '$checkWriteAccess' => false,
                '$expectedResult' => false,
            ],
        ];
    }

    /**
     * @test
     */
    public function getProcessingRootFolderTest()
    {
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_file_storage.xml');
        $this->setUpBackendUserFromFixture(1);

        $subject = (new StorageRepository())->findByUid(1);
        $processingFolder = $subject->getProcessingFolder();

        $this->assertInstanceOf(Folder::class, $processingFolder);
    }

    /**
     * @test
     */
    public function getRoleReturnsDefaultForRegularFolders()
    {
        $folderIdentifier = $this->getUniqueId();
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_file_storage.xml');
        $this->setUpBackendUserFromFixture(1);

        $subject = (new StorageRepository())->findByUid(1);
        $folder = new Folder($subject, '/foo/' . $folderIdentifier . '/', $folderIdentifier);

        $role = $subject->getRole($folder);

        $this->assertSame(FolderInterface::ROLE_DEFAULT, $role);
    }

    /**
     * @test
     */
    public function replaceFileFailsIfLocalFileDoesNotExist()
    {
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_file_storage.xml');
        $this->setUpBackendUserFromFixture(1);
        $subject = (new StorageRepository())->findByUid(1);

        GeneralUtility::mkdir_deep(PATH_site . 'fileadmin/foo');
        file_put_contents(PATH_site . 'fileadmin/foo/bar.txt', 'myData');
        clearstatcache();
        $file = ResourceFactory::getInstance()->getFileObjectFromCombinedIdentifier('1:/foo/bar.txt');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1325842622);
        $subject->replaceFile($file, PATH_site . $this->getUniqueId());
    }

    /**
     * @test
     */
    public function createFolderThrowsExceptionIfParentFolderDoesNotExist()
    {
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/sys_file_storage.xml');
        $this->setUpBackendUserFromFixture(1);
        $subject = (new StorageRepository())->findByUid(1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1325689164);
        $subject->createFolder('newFolder', new Folder($subject, '/foo/', 'foo'));
    }
}
