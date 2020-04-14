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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Import;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

/**
 * Test case
 */
class IrreTutorialRecordsTest extends AbstractImportExportTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/irre_tutorial',
    ];

    /**
     * @test
     */
    public function importIrreRecords()
    {
        $subject = GeneralUtility::makeInstance(Import::class);
        $subject->init();

        $subject->loadFile(
            __DIR__ . '/../Fixtures/XmlImports/irre-records.xml',
            1
        );
        $subject->importData(0);

        $this->assertCSVDataSet('EXT:impexp/Tests/Functional/Fixtures/DatabaseAssertions/importIrreRecords.csv');
    }
}
