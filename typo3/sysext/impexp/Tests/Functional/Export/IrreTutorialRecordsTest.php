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

namespace TYPO3\CMS\Impexp\Tests\Functional\Export;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Export;
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
    public function exportIrreRecords()
    {
        $recordTypesIncludeFields = [
            'pages' => [
                'title',
                'deleted',
                'doktype',
                'hidden',
                'perms_everybody'
            ],
            'tt_content' => [
                'CType',
                'header',
                'deleted',
                'hidden',
                't3ver_oid',
                'tx_irretutorial_1nff_hotels',
                'tx_irretutorial_1ncsv_hotels'
            ],
            'tx_irretutorial_1ncsv_hotel' => [
                'cruser_id',
                'sys_language_uid',
                'l18n_parent',
                'sorting',
                'deleted',
                'hidden',
                'title',
                'offers',
            ],
            'tx_irretutorial_1ncsv_offer' => [
                'cruser_id',
                'sys_language_uid',
                'l18n_parent',
                'sorting',
                'deleted',
                'hidden',
                'title',
                'prices',
            ],
            'tx_irretutorial_1ncsv_price' => [
                'cruser_id',
                'sys_language_uid',
                'l18n_parent',
                'sorting',
                'deleted',
                'hidden',
                'title',
                'price',
            ],
            'tx_irretutorial_1nff_hotel' => [
                'cruser_id',
                'sys_language_uid',
                'l18n_parent',
                'sorting',
                'deleted',
                'hidden',
                'parentid',
                'parenttable',
                'parentidentifier',
                'title',
                'offers',
            ],
            'tx_irretutorial_1nff_offer' => [
                'cruser_id',
                'sys_language_uid',
                'l18n_parent',
                'sorting',
                'deleted',
                'hidden',
                'parentid',
                'parenttable',
                'parentidentifier',
                'title',
                'prices',
            ],
            'tx_irretutorial_1nff_price' => [
                'cruser_id',
                'sys_language_uid',
                'l18n_parent',
                'sorting',
                'deleted',
                'hidden',
                'parentid',
                'parenttable',
                'parentidentifier',
                'title',
                'price',
            ],
            'tx_irretutorial_mnasym_hotel' => [
                'cruser_id',
                'sys_language_uid',
                'l18n_parent',
                'sorting',
                'deleted',
                'hidden',
                'title',
                'offers',
            ],
            'tx_irretutorial_mnasym_hotel_offer_rel' => [
                'cruser_id',
                'sys_language_uid',
                'l18n_parent',
                'deleted',
                'hidden',
                'hotelid',
                'offerid',
                'hotelsort',
                'offersort',
                'prices',
            ],
            'tx_irretutorial_mnasym_offer' => [
                'cruser_id',
                'sys_language_uid',
                'l18n_parent',
                'sorting',
                'deleted',
                'hidden',
                'title',
                'hotels',
            ],
            'tx_irretutorial_mnasym_price' => [
                'cruser_id',
                'sys_language_uid',
                'l18n_parent',
                'sorting',
                'deleted',
                'hidden',
                'parentid',
                'title',
                'price',
            ],
            'tx_irretutorial_mnattr_hotel' => [
                'cruser_id',
                'sys_language_uid',
                'l18n_parent',
                'sorting',
                'deleted',
                'hidden',
                'title',
                'offers',
            ],
            'tx_irretutorial_mnattr_hotel_offer_rel' => [
                'cruser_id',
                'sys_language_uid',
                'l18n_parent',
                'deleted',
                'hidden',
                'hotelid',
                'offerid',
                'hotelsort',
                'offersort',
                'quality',
                'allincl',
            ],
            'tx_irretutorial_mnattr_offer' => [
                'cruser_id',
                'sys_language_uid',
                'l18n_parent',
                'sorting',
                'deleted',
                'hidden',
                'title',
                'hotels',
            ],
            'tx_irretutorial_mnmmasym_hotel' => [
                'cruser_id',
                'sys_language_uid',
                'l18n_parent',
                'sorting',
                'deleted',
                'hidden',
                'title',
                'offers',
            ],
            'tx_irretutorial_mnmmasym_hotel_offer_rel' => [
                'uid_local',
                'uid_foreign',
                'tablenames',
                'sorting',
                'sorting_foreign',
                'ident',
            ],
            'tx_irretutorial_mnmmasym_offer' => [
                'cruser_id',
                'sys_language_uid',
                'l18n_parent',
                'sorting',
                'deleted',
                'hidden',
                'title',
                'hotels',
                'prices',
            ],
            'tx_irretutorial_mnmmasym_offer_price_rel' => [
                'uid_local',
                'uid_foreign',
                'tablenames',
                'sorting',
                'sorting_foreign',
                'ident',
            ],
            'tx_irretutorial_mnmmasym_price' => [
                'cruser_id',
                'sys_language_uid',
                'l18n_parent',
                'sorting',
                'deleted',
                'hidden',
                'title',
                'price',
                'offers',
            ],
            'tx_irretutorial_mnsym_hotel' => [
                'cruser_id',
                'sys_language_uid',
                'l18n_parent',
                'sorting',
                'deleted',
                'hidden',
                'title',
                'branches',
            ],
            'tx_irretutorial_mnsym_hotel_rel' => [
                'cruser_id',
                'sys_language_uid',
                'l18n_parent',
                'deleted',
                'hidden',
                'hotelid',
                'branchid',
                'hotelsort',
                'branchsort',
            ]

        ];

        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/irre_tutorial.xml');

        $subject = GeneralUtility::makeInstance(Export::class);
        $subject->init();

        $subject->setRecordTypesIncludeFields($recordTypesIncludeFields);

        // @todo: Do not rely on BackendUtility::getRecord() in the test case itself
        $subject->export_addRecord('pages', $this->forceStringsOnRowValues(BackendUtility::getRecord('pages', 1)));
        $this->addRecordsForPid($subject, 1, array_keys($recordTypesIncludeFields));

        $this->setPageTree($subject, 1);

        // After adding ALL records we set relations:
        for ($a = 0; $a < 10; $a++) {
            $addR = $subject->export_addDBRelations($a);
            if (empty($addR)) {
                break;
            }
        }

        $subject->export_addFilesFromRelations();
        $subject->export_addFilesFromSysFilesRecords();

        $out = $subject->compileMemoryToFileContent('xml');

        self::assertXmlStringEqualsXmlFile(
            __DIR__ . '/../Fixtures/XmlExports/irre-records.xml',
            $out
        );
    }
}
