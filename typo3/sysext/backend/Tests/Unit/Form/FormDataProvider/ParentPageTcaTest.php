<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Backend\Form\FormDataProvider\ParentPageTca;

/**
 * Test case
 */
class ParentPageTcaTest extends UnitTestCase {

	/**
	 * @var ParentPageTca
	 */
	protected $subject;

	public function setUp() {
		$this->subject = new ParentPageTca();
	}

	/**
	 * @test
	 */
	public function addDataSetsTableTcaFromGlobalsPagesTcaInResult() {
		$input = [
			'tableName' => 'aTable',
			'parentPageRow' => array(),
		];
		$expected = array('foo');
		$GLOBALS['TCA']['pages'] = $expected;
		$result = $this->subject->addData($input);
		$this->assertEquals($expected, $result['vanillaParentPageTca']);
	}

}
