<?php
namespace TYPO3\CMS\Backend\Tests\Functional\Form;

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

use TYPO3\CMS\Core\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class FormEngineInlineTest extends FunctionalTestCase {

	protected $testExtensionsToLoad = array(
		'typo3/sysext/backend/Tests/Functional/Fixtures/Extensions/inlinetest'
	);

	/**
	 * @var \TYPO3\CMS\Backend\Form\FormEngine|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $subject;

	/**
	 * Sets up this test case.
	 */
	public function setUp() {
		parent::setUp();
		$this->setUpBackendUserFromFixture(1);
		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/Fixtures/inlinetest.xml');
		$GLOBALS['LANG'] = GeneralUtility::makeInstance('TYPO3\\CMS\\Lang\\LanguageService');
		$GLOBALS['LANG']->init('default');
		$this->subject = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\FormEngine');
		$this->subject->initDefaultBEmode();
	}

	/**
	 * Make sure that the exclude field configuration is still valid after
	 * the rendering of an inline element.
	 *
	 * @test
	 */
	public function excludeElementsAreNotOverwrittenByInlineField() {
		$rows = $this->getDatabaseConnection()->exec_SELECTgetRows('*', 'tx_inlinetest_record', 'uid=1');
		$mainFields = $this->subject->getMainFields('tx_inlinetest_record', $rows[0]);
		$this->assertNotContains('afterinline', $mainFields);
	}
}
