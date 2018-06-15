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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Import;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

/**
 * Test case
 */
class GroupFileAndFileReferenceItemInFlexFormTest extends AbstractImportExportTestCase
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
    public function importGroupFileAndFileReferenceItemInFlexForm()
    {
        $subject = GeneralUtility::makeInstance(Import::class);
        $subject->init();

        $subject->loadFile(
            __DIR__ . '/../Fixtures/XmlImports/impexp-group-file-and-file_reference-item-in-ff.xml',
            1
        );
        $subject->importData(0);

        $this->testFilesToDelete[] = Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image3.jpg';
        $this->testFilesToDelete[] = Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image5.jpg';
        $this->testFilesToDelete[] = Environment::getPublicPath() . '/uploads/tx_impexpgroupfiles/typo3_image4.jpg';

        $this->assertCSVDataSet('EXT:impexp/Tests/Functional/Fixtures/DatabaseAssertions/importGroupFileAndFileReferenceItemInFlexForm.csv');

        $this->assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image3.jpg', Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image3.jpg');
        $this->assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image5.jpg', Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image5.jpg');
        $this->assertFileEquals(__DIR__ . '/../Fixtures/Folders/uploads/tx_impexpgroupfiles/typo3_image4.jpg', Environment::getPublicPath() . '/uploads/tx_impexpgroupfiles/typo3_image4.jpg');
    }
}
