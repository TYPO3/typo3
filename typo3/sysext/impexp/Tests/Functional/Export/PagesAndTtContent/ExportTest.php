<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\Export\PagesAndTtContent;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;

require_once __DIR__ . '/../AbstractExportTestCase.php';

/**
 * Functional test for the ImportExport
 */
class ExportTest extends \TYPO3\CMS\Impexp\Tests\Functional\Export\AbstractExportTestCase {

	public function setUp() {
		parent::setUp();

		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/pages.xml');
		$this->importDataSet(__DIR__ . '/../../Fixtures/Database/tt_content.xml');
	}

	/**
	 * @test
	 */
	public function exportSimplePagesAndRelatedTtContent() {

		$this->export->setRecordTypesIncludeFields(
			array(
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
					't3ver_oid'
				)
			)
		);

		$this->export->export_addRecord('pages', BackendUtility::getRecord('pages', 1));
		$this->export->export_addRecord('pages', BackendUtility::getRecord('pages', 2));
		$this->export->export_addRecord('tt_content', BackendUtility::getRecord('tt_content', 1));

		$this->setPageTree(1, 1);

		// After adding ALL records we set relations:
		for ($a = 0; $a < 10; $a++) {
			$addR = $this->export->export_addDBRelations($a);
			if (!count($addR)) {
				break;
			}
		}

		$this->export->export_addFilesFromRelations();

		$out = $this->export->compileMemoryToFileContent('xml');

		$this->assertXmlStringEqualsXmlFile(__DIR__ . '/../../Fixtures/ImportExportXml/pages-and-ttcontent.xml', $out);
	}

}
