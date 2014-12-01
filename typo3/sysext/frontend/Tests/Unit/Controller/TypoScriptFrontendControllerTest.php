<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\Controller;

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
/**
 * Testcase for TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class TypoScriptFrontendControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = $this->getAccessibleMock('\\TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', array('dummy'), array(), '', FALSE);
		$this->fixture->TYPO3_CONF_VARS = $GLOBALS['TYPO3_CONF_VARS'];
		$this->fixture->TYPO3_CONF_VARS['SYS']['encryptionKey'] = '170928423746123078941623042360abceb12341234231';
	}

	////////////////////////////////
	// Tests concerning rendering content
	////////////////////////////////
	/**
	 * @test
	 */
	public function headerAndFooterMarkersAreReplacedDuringIntProcessing() {
		$GLOBALS['TSFE'] = $this->setupTsfeMockForHeaderFooterReplacementCheck();
		$GLOBALS['TSFE']->INTincScript();
		$this->assertContains('headerData', $GLOBALS['TSFE']->content);
		$this->assertContains('footerData', $GLOBALS['TSFE']->content);
	}

	/**
	 * This is the callback that mimics a USER_INT extension
	 */
	public function INTincScript_processCallback() {
		$GLOBALS['TSFE']->additionalHeaderData[] = 'headerData';
		$GLOBALS['TSFE']->additionalFooterData[] = 'footerData';
	}

	/**
	 * Setup a \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController object only for testing the header and footer
	 * replacement during USER_INT rendering
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	protected function setupTsfeMockForHeaderFooterReplacementCheck() {
		/** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $tsfe */
		$tsfe = $this->getMock(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class, array(
			'INTincScript_process',
			'INTincScript_includeLibs',
			'INTincScript_loadJSCode',
			'setAbsRefPrefix',
		    'regeneratePageTitle'
		), array(), '', FALSE);
		$tsfe->expects($this->exactly(2))->method('INTincScript_process')->will($this->returnCallback(array($this, 'INTincScript_processCallback')));
		$tsfe->content = file_get_contents(__DIR__ . '/Fixtures/renderedPage.html');
		$tsfe->config['INTincScript_ext']['divKey'] = '679b52796e75d474ccbbed486b6837ab';
		$tsfe->config['INTincScript'] = array('INT_SCRIPT.679b52796e75d474ccbbed486b6837ab' => array());
		$GLOBALS['TT'] = new \TYPO3\CMS\Core\TimeTracker\NullTimeTracker();
		return $tsfe;
	}

	////////////////////////////////
	// Tests concerning codeString
	////////////////////////////////
	/**
	 * @test
	 */
	public function codeStringForNonEmptyStringReturns10CharacterHashAndCodedString() {
		$this->assertRegExp('/^[0-9a-f]{10}:[a-zA-Z0-9+=\\/]+$/', $this->fixture->codeString('Hello world!'));
	}

	/**
	 * @test
	 */
	public function decodingCodedStringReturnsOriginalString() {
		$clearText = 'Hello world!';
		$this->assertEquals($clearText, $this->fixture->codeString($this->fixture->codeString($clearText), TRUE));
	}

	//////////////////////
	// Tests concerning sL
	//////////////////////
	/**
	 * @test
	 */
	public function localizationReturnsUnchangedStringIfNotLocallangLabel() {
		$string = uniqid();
		$this->assertEquals($string, $this->fixture->sL($string));
	}

	//////////////////////////////////////////
	// Tests concerning roundTripCryptString
	//////////////////////////////////////////
	/**
	 * @test
	 */
	public function roundTripCryptStringCreatesStringWithSameLengthAsInputString() {
		$clearText = 'Hello world!';
		$this->assertEquals(strlen($clearText), strlen($this->fixture->_callRef('roundTripCryptString', $clearText)));
	}

	/**
	 * @test
	 */
	public function roundTripCryptStringCreatesResultDifferentFromInputString() {
		$clearText = 'Hello world!';
		$this->assertNotEquals($clearText, $this->fixture->_callRef('roundTripCryptString', $clearText));
	}

	/**
	 * @test
	 */
	public function roundTripCryptStringAppliedTwoTimesReturnsOriginalString() {
		$clearText = 'Hello world!';
		$refValue = $this->fixture->_callRef('roundTripCryptString', $clearText);
		$this->assertEquals($clearText, $this->fixture->_callRef('roundTripCryptString', $refValue));
	}
}