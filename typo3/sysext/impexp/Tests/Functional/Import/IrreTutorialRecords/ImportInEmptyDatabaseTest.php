<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\Import\IrreTutorialRecords;

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

/**
 * Functional test for the Import
 */
class ImportInEmptyDatabaseTest extends \TYPO3\CMS\Impexp\Tests\Functional\Import\AbstractImportTestCase
{
    protected $assertionDataSetDirectory = 'typo3/sysext/impexp/Tests/Functional/Import/IrreTutorialRecords/DataSet/Assertion/';

    /**
     * @test
     */
    public function importIrreRecords()
    {
        $this->import->loadFile(__DIR__ . '/../../Fixtures/ImportExportXml/irre-records.xml', 1);
        $this->import->importData(0);

        $this->assertAssertionDataSet('importIrreRecords');
    }
}
