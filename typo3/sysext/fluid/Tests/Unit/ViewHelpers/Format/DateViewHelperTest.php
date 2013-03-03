<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Format;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

class DateViewHelperTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var array Backup of current locale, it is manipulated in tests
	 */
	protected $backupLocales = array();

	/**
	 * @var string Backup of current timezone, it is manipulated in tests
	 */
	protected $timezone;

	public function setUp() {
		parent::setUp();
		// Store all locale categories manipulated in tests for reconstruction in tearDown
		$this->backupLocales = array(
			'LC_COLLATE' => setlocale(LC_COLLATE, 0),
			'LC_CTYPE' => setlocale(LC_CTYPE, 0),
			'LC_MONETARY' => setlocale(LC_MONETARY, 0),
			'LC_TIME' => setlocale(LC_TIME, 0),
		);
		$this->timezone = @date_default_timezone_get();
	}

	public function tearDown() {
		foreach ($this->backupLocales as $category => $locale) {
			setlocale(constant($category), $locale);
		}
		date_default_timezone_set($this->timezone);
	}

	/**
	 * @test
	 */
	public function viewHelperFormatsDateCorrectly() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\Format\DateViewHelper();
		$actualResult = $viewHelper->render(new \DateTime('1980-12-13'));
		$this->assertEquals('1980-12-13', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperFormatsDateStringCorrectly() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\Format\DateViewHelper();
		$actualResult = $viewHelper->render('1980-12-13');
		$this->assertEquals('1980-12-13', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperRespectsCustomFormat() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\Format\DateViewHelper();
		$actualResult = $viewHelper->render(new \DateTime('1980-02-01'), 'd.m.Y');
		$this->assertEquals('01.02.1980', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperReturnsEmptyStringIfNULLIsGiven() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\DateViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(NULL));
		$actualResult = $viewHelper->render();
		$this->assertEquals('', $actualResult);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
	 */
	public function viewHelperThrowsExceptionIfDateStringCantBeParsed() {
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\Format\DateViewHelper();
		$viewHelper->render('foo');
	}

	/**
	 * @test
	 */
	public function viewHelperUsesChildNodesIfDateAttributeIsNotSpecified() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\DateViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(new \DateTime('1980-12-13')));
		$actualResult = $viewHelper->render();
		$this->assertEquals('1980-12-13', $actualResult);
	}

	/**
	 * @test
	 */
	public function dateArgumentHasPriorityOverChildNodes() {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\DateViewHelper', array('renderChildren'));
		$viewHelper->expects($this->never())->method('renderChildren');
		$actualResult = $viewHelper->render('1980-12-12');
		$this->assertEquals('1980-12-12', $actualResult);
	}

	/**
	 * Data provider for viewHelperRespectsDefaultTimezoneForIntegerTimestamp
	 *
	 * @return array
	 */
	public function viewHelperRespectsDefaultTimezoneForIntegerTimestampDataProvider() {
		return array(
			'Europe/Berlin' => array(
				'Europe/Berlin',
				'2013-02-03 12:40',
			),
			'Asia/Riyadh' => array(
				'Asia/Riyadh',
				'2013-02-03 14:40',
			),
		);
	}

	/**
	 * @test
	 * @dataProvider viewHelperRespectsDefaultTimezoneForIntegerTimestampDataProvider
	 */
	public function viewHelperRespectsDefaultTimezoneForIntegerTimestamp($timezone, $expected) {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\DateViewHelper', array('renderChildren'));

		$date = 1359891658; // 2013-02-03 11:40 UTC
		$format = 'Y-m-d H:i';

		date_default_timezone_set($timezone);
		$this->assertEquals($expected, $viewHelper->render($date, $format));
	}

	/**
	 * Data provider for viewHelperRespectsDefaultTimezoneForStringTimestamp
	 *
	 * @return array
	 */
	public function viewHelperRespectsDefaultTimezoneForStringTimestampDataProvider() {
		return array(
			'Europe/Berlin UTC' => array(
				'Europe/Berlin',
				'@1359891658',
				'2013-02-03 12:40'
			),
			'Europe/Berlin Moscow' => array(
				'Europe/Berlin',
				'03/Oct/2000:14:55:36 +0400',
				'2000-10-03 12:55'
			),
			'Asia/Riyadh UTC' => array(
				'Asia/Riyadh',
				'@1359891658',
				'2013-02-03 14:40'
			),
			'Asia/Riyadh Moscow' => array(
				'Asia/Riyadh',
				'03/Oct/2000:14:55:36 +0400',
				'2000-10-03 13:55'
			),
		);
	}

	/**
	 * @dataProvider viewHelperRespectsDefaultTimezoneForStringTimestampDataProvider
	 *
	 * @test
	 */
	public function viewHelperRespectsDefaultTimezoneForStringTimestamp($timeZone, $date, $expected) {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\DateViewHelper', array('renderChildren'));
		$format = 'Y-m-d H:i';

		date_default_timezone_set($timeZone);
		$this->assertEquals($expected, $viewHelper->render($date, $format));
	}

	/**
	 * Data provider for dateViewHelperFormatsDateLocalizedDataProvider
	 *
	 * @return array
	 */
	public function dateViewHelperFormatsDateLocalizedDataProvider() {
		return array(
			'de_DE.UTF-8' => array(
				'de_DE.UTF-8',
				'03. Februar 2013'
			),
			'en_ZW.utf8' => array(
				'en_ZW.utf8',
				'03. February 2013'
			)
		);
	}

	/**
	 * @dataProvider dateViewHelperFormatsDateLocalizedDataProvider
	 *
	 * @test
	 */
	public function dateViewHelperFormatsDateLocalized($locale, $expected) {
		$viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\DateViewHelper', array('renderChildren'));
		$format = '%d. %B %Y';
		// 2013-02-03 11:40 UTC
		$timestamp = '@1359891658';

		if (!setlocale(LC_COLLATE, $locale)) {
			$this->markTestSkipped('Locale ' . $locale . ' is not available.');
		}
		$this->setLocale($locale);
		$this->assertEquals($expected, $viewHelper->render($timestamp, $format));
	}

	/**
	 * @param string $locale
	 */
	protected function setLocale($locale) {
		setlocale(LC_CTYPE, $locale);
		setlocale(LC_MONETARY, $locale);
		setlocale(LC_TIME, $locale);
	}
}

?>