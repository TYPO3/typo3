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

namespace TYPO3\CMS\Core\Tests\Functional\Utility\File;

use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ExtendedFileUtilityTest extends FunctionalTestCase
{
    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Utility/Fixtures/Folders/' => 'fileadmin/',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet('typo3/sysext/core/Tests/Functional/Utility/Fixtures/DataSet/sys_refindex.csv');
        $this->importCSVDataSet('typo3/sysext/core/Tests/Functional/Utility/Fixtures/DataSet/sys_file.csv');
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
    }

    /**
     * @test
     */
    public function folderHasFilesInUseReturnsTrueIfItHasFilesInUse(): void
    {
        $storageRepository = $this->get(StorageRepository::class);
        $resourceStorage = $storageRepository->getDefaultStorage();
        $folder = $resourceStorage->getFolder('FolderWithUsedFile');

        $extendedFileUtility = new ExtendedFileUtility();
        $result = $extendedFileUtility->folderHasFilesInUse($folder);

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function folderHasFilesInUseReturnsFalseIfItHasNoFilesInUse(): void
    {
        $storageRepository = $this->get(StorageRepository::class);
        $resourceStorage = $storageRepository->getDefaultStorage();
        $folder = $resourceStorage->getFolder('FolderWithUnusedFile');

        $extendedFileUtility = new ExtendedFileUtility();
        $result = $extendedFileUtility->folderHasFilesInUse($folder);

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function folderHasFilesInUseReturnsFalseIfItHasNoFiles(): void
    {
        $storageRepository = $this->get(StorageRepository::class);
        $resourceStorage = $storageRepository->getDefaultStorage();
        $folder = $resourceStorage->createFolder('EmptyFolder');

        $extendedFileUtility = new ExtendedFileUtility();
        $result = $extendedFileUtility->folderHasFilesInUse($folder);

        self::assertFalse($result);
    }
}
