<?php

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

namespace TYPO3\CMS\Impexp\Tests\Functional\Import;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Import;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

/**
 * Test case
 */
class PagesAndTtContentTest extends AbstractImportExportTestCase
{
    /**
     * @test
     */
    public function importPagesAndRelatedTtContent()
    {
        $subject = GeneralUtility::makeInstance(Import::class);
        $subject->init();

        $subject->loadFile(
            __DIR__ . '/../Fixtures/XmlImports/pages-and-ttcontent.xml',
            1
        );
        $subject->importData(0);

        $this->testFilesToDelete[] = Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image3.jpg';

        $this->assertCSVDataSet('EXT:impexp/Tests/Functional/Fixtures/DatabaseAssertions/importPagesAndRelatedTtContent.csv');

        self::assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image3.jpg', Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image3.jpg');
        self::assertFileEquals(__DIR__ . '/../Fixtures/Extensions/template_extension/Resources/Public/Templates/Empty.html', Environment::getPublicPath() . '/typo3conf/ext/template_extension/Resources/Public/Templates/Empty.html');
    }
}
