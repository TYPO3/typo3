<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\Import;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Import;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

/**
 * Test case
 */
class GroupFileAndFileReferenceItemTest extends AbstractImportExportTestCase
{
    /**
     * @var array
     */
    protected $additionalFoldersToCreate = [
        '/uploads/tx_impexpgroupfiles'
    ];

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Extensions/impexp_group_files'
    ];

    /**
     * @test
     */
    public function importGroupFileAndFileReferenceItem()
    {
        $subject = GeneralUtility::makeInstance(Import::class);
        $subject->init();

        $subject->loadFile(
            __DIR__ . '/../Fixtures/XmlImports/impexp-group-file-and-file_reference-item.xml',
            1
        );
        $subject->importData(0);

        $this->testFilesToDelete[] = PATH_site . 'fileadmin/user_upload/typo3_image5.jpg';
        $this->testFilesToDelete[] = PATH_site . 'uploads/tx_impexpgroupfiles/typo3_image4.jpg';

        $this->assertCSVDataSet('EXT:impexp/Tests/Functional/Fixtures/DatabaseAssertions/importGroupFileAndFileReferenceItem.csv');

        $this->assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image5.jpg', PATH_site . 'fileadmin/user_upload/typo3_image5.jpg');
        $this->assertFileEquals(__DIR__ . '/../Fixtures/Folders/uploads/tx_impexpgroupfiles/typo3_image4.jpg', PATH_site . 'uploads/tx_impexpgroupfiles/typo3_image4.jpg');
    }

    /**
     * @test
     */
    public function importGroupFileAndFileReferenceItemWithRootLevelOnlyAllowed()
    {
        $GLOBALS['TCA']['tx_impexpgroupfiles_item']['ctrl']['rootLevel'] = 1;

        $subject = GeneralUtility::makeInstance(Import::class);
        $subject->init();

        $subject->loadFile(
            __DIR__ . '/../Fixtures/XmlImports/impexp-group-file-and-file_reference-item.xml',
            1
        );
        $subject->importData(0);

        $this->testFilesToDelete[] = PATH_site . 'fileadmin/user_upload/typo3_image5.jpg';
        $this->testFilesToDelete[] = PATH_site . 'uploads/tx_impexpgroupfiles/typo3_image4.jpg';

        $this->assertCSVDataSet('EXT:impexp/Tests/Functional/Fixtures/DatabaseAssertions/importGroupFileAndFileReferenceItemWithRootLevelOnlyAllowed.csv');

        $this->assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image5.jpg', PATH_site . 'fileadmin/user_upload/typo3_image5.jpg');
        $this->assertFileEquals(__DIR__ . '/../Fixtures/Folders/uploads/tx_impexpgroupfiles/typo3_image4.jpg', PATH_site . 'uploads/tx_impexpgroupfiles/typo3_image4.jpg');
    }

    /**
     * @test
     */
    public function importGroupFileAndFileReferenceItemWithRootLevelNotAllowed()
    {
        $GLOBALS['TCA']['tx_impexpgroupfiles_item']['ctrl']['rootLevel'] = 0;

        $subject = GeneralUtility::makeInstance(Import::class);
        $subject->init();

        $subject->loadFile(
            __DIR__ . '/../Fixtures/XmlImports/impexp-group-file-and-file_reference-item-on-pid-zero.xml',
            1
        );
        $subject->importData(0);

        $this->testFilesToDelete[] = PATH_site . 'fileadmin/user_upload/typo3_image5.jpg';

        $this->assertCSVDataSet('EXT:impexp/Tests/Functional/Fixtures/DatabaseAssertions/importGroupFileAndFileReferenceItemWithRootLevelNotAllowed.csv');

        $this->assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image5.jpg', PATH_site . 'fileadmin/user_upload/typo3_image5.jpg');
        $this->assertFileNotExists(PATH_site . 'uploads/tx_impexpgroupfiles/typo3_image4.jpg');

        $expectedErrors = [
            'Error: Record type tx_impexpgroupfiles_item is not allowed on pid 0'
        ];
        $errors = $subject->errorLog;
        $this->assertSame($expectedErrors, $errors);
    }

    /**
     * @test
     */
    public function importGroupFileAndFileReferenceItemWithRootLevelAndPagesAllowed()
    {
        $GLOBALS['TCA']['tx_impexpgroupfiles_item']['ctrl']['rootLevel'] = -1;

        $subject = GeneralUtility::makeInstance(Import::class);
        $subject->init();

        $subject->loadFile(
            __DIR__ . '/../Fixtures/XmlImports/impexp-group-file-and-file_reference-item.xml',
            1
        );
        $subject->importData(0);

        $this->testFilesToDelete[] = PATH_site . 'fileadmin/user_upload/typo3_image5.jpg';
        $this->testFilesToDelete[] = PATH_site . 'uploads/tx_impexpgroupfiles/typo3_image4.jpg';

        $this->assertCSVDataSet('EXT:impexp/Tests/Functional/Fixtures/DatabaseAssertions/importGroupFileAndFileReferenceItemWithRootLevelAndPagesAllowed.csv');

        $this->assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image5.jpg', PATH_site . 'fileadmin/user_upload/typo3_image5.jpg');
        $this->assertFileEquals(__DIR__ . '/../Fixtures/Folders/uploads/tx_impexpgroupfiles/typo3_image4.jpg', PATH_site . 'uploads/tx_impexpgroupfiles/typo3_image4.jpg');
    }

    /**
     * @test
     */
    public function importGroupFileAndFileReferenceItemButImagesNotIncluded()
    {
        $subject = GeneralUtility::makeInstance(Import::class);
        $subject->init();

        $subject->loadFile(
            __DIR__ . '/../Fixtures/XmlImports/impexp-group-file-and-file_reference-item.xml',
            1
        );
        $subject->importData(0);

        $this->testFilesToDelete[] = PATH_site . 'fileadmin/user_upload/typo3_image5.jpg';
        $this->testFilesToDelete[] = PATH_site . 'uploads/tx_impexpgroupfiles/typo3_image4.jpg';

        $this->assertCSVDataSet('EXT:impexp/Tests/Functional/Fixtures/DatabaseAssertions/importGroupFileAndFileReferenceItem.csv');

        $this->assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image5.jpg', PATH_site . 'fileadmin/user_upload/typo3_image5.jpg');
        $this->assertFileEquals(__DIR__ . '/../Fixtures/Folders/uploads/tx_impexpgroupfiles/typo3_image4.jpg', PATH_site . 'uploads/tx_impexpgroupfiles/typo3_image4.jpg');
    }
}
