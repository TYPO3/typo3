<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\Export\IrreTutorialRecords;

/**
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

use TYPO3\CMS\Backend\Utility\BackendUtility;

require_once __DIR__ . '/../AbstractExportTestCase.php';

/**
 * Functional test for the ImportExport
 */
class ExportTest extends \TYPO3\CMS\Impexp\Tests\Functional\Export\AbstractExportTestCase {

	/**
	 * @var array
	 */
	protected $testExtensionsToLoad = array(
		'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/irre_tutorial',
	);

	public function setUp() {
		parent::setUp();

		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/irre_tutorial.xml');
	}

	/**
	 * @test
	 */
	public function exportIrreRecords() {

		$recordTypesIncludeFields = array(
			'pages' => array(
				'title',
				'deleted',
				'doktype',
				'hidden',
				'perms_everybody'
			),
			'tt_content' => array(
				'CType',
				'header',
				'deleted',
				'hidden',
				't3ver_oid',
				'tx_irretutorial_1nff_hotels',
				'tx_irretutorial_1ncsv_hotels'
			),
			'tx_irretutorial_1ncsv_hotel' => array(
				'cruser_id',
				'sys_language_uid',
				'l18n_parent',
				'sorting',
				'deleted',
				'hidden',
				'title',
				'offers',
			),
			'tx_irretutorial_1ncsv_offer' => array(
				'cruser_id',
				'sys_language_uid',
				'l18n_parent',
				'sorting',
				'deleted',
				'hidden',
				'title',
				'prices',
			),
			'tx_irretutorial_1ncsv_price' => array(
				'cruser_id',
				'sys_language_uid',
				'l18n_parent',
				'sorting',
				'deleted',
				'hidden',
				'title',
				'price',
			),
			'tx_irretutorial_1nff_hotel' => array(
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
			),
			'tx_irretutorial_1nff_offer' => array(
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
			),
			'tx_irretutorial_1nff_price' => array(
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
			),
			'tx_irretutorial_mnasym_hotel' => array(
				'cruser_id',
				'sys_language_uid',
				'l18n_parent',
				'sorting',
				'deleted',
				'hidden',
				'title',
				'offers',
			),
			'tx_irretutorial_mnasym_hotel_offer_rel' => array(
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
			),
			'tx_irretutorial_mnasym_offer' => array(
				'cruser_id',
				'sys_language_uid',
				'l18n_parent',
				'sorting',
				'deleted',
				'hidden',
				'title',
				'hotels',
			),
			'tx_irretutorial_mnasym_price' => array(
				'cruser_id',
				'sys_language_uid',
				'l18n_parent',
				'sorting',
				'deleted',
				'hidden',
				'parentid',
				'title',
				'price',
			),
			'tx_irretutorial_mnattr_hotel' => array(
				'cruser_id',
				'sys_language_uid',
				'l18n_parent',
				'sorting',
				'deleted',
				'hidden',
				'title',
				'offers',
			),
			'tx_irretutorial_mnattr_hotel_offer_rel' => array(
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
			),
			'tx_irretutorial_mnattr_offer' => array(
				'cruser_id',
				'sys_language_uid',
				'l18n_parent',
				'sorting',
				'deleted',
				'hidden',
				'title',
				'hotels',
			),
			'tx_irretutorial_mnmmasym_hotel' => array(
				'cruser_id',
				'sys_language_uid',
				'l18n_parent',
				'sorting',
				'deleted',
				'hidden',
				'title',
				'offers',
			),
			'tx_irretutorial_mnmmasym_hotel_offer_rel' => array(
				'uid_local',
				'uid_foreign',
				'tablenames',
				'sorting',
				'sorting_foreign',
				'ident',
			),
			'tx_irretutorial_mnmmasym_offer' => array(
				'cruser_id',
				'sys_language_uid',
				'l18n_parent',
				'sorting',
				'deleted',
				'hidden',
				'title',
				'hotels',
				'prices',
			),
			'tx_irretutorial_mnmmasym_offer_price_rel' => array(
				'uid_local',
				'uid_foreign',
				'tablenames',
				'sorting',
				'sorting_foreign',
				'ident',
			),
			'tx_irretutorial_mnmmasym_price' => array(
				'cruser_id',
				'sys_language_uid',
				'l18n_parent',
				'sorting',
				'deleted',
				'hidden',
				'title',
				'price',
				'offers',
			),
			'tx_irretutorial_mnsym_hotel' => array(
				'cruser_id',
				'sys_language_uid',
				'l18n_parent',
				'sorting',
				'deleted',
				'hidden',
				'title',
				'branches',
			),
			'tx_irretutorial_mnsym_hotel_rel' => array(
				'cruser_id',
				'sys_language_uid',
				'l18n_parent',
				'deleted',
				'hidden',
				'hotelid',
				'branchid',
				'hotelsort',
				'branchsort',
			)

		);

		$this->export->setRecordTypesIncludeFields($recordTypesIncludeFields);

		$this->export->export_addRecord('pages', BackendUtility::getRecord('pages', 1));
		$this->addRecordsForPid(1, array_keys($recordTypesIncludeFields));

		$this->setPageTree(1);

		// After adding ALL records we set relations:
		for ($a = 0; $a < 10; $a++) {
			$addR = $this->export->export_addDBRelations($a);
			if (!count($addR)) {
				break;
			}
		}

		$this->export->export_addFilesFromRelations();
		$this->export->export_addFilesFromSysFilesRecords();

		$out = $this->export->compileMemoryToFileContent('xml');

		$this->assertXmlStringEqualsXmlFile(__DIR__ . '/../../Fixtures/ImportExportXml/irre-records.xml', $out);
	}

}