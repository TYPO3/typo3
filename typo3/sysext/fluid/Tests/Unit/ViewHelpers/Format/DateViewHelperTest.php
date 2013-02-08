<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 *
 */
class Tx_Fluid_Tests_Unit_ViewHelpers_Format_DateViewHelperTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperFormatsDateCorrectly() {
		$viewHelper = new Tx_Fluid_ViewHelpers_Format_DateViewHelper();
		$actualResult = $viewHelper->render(new DateTime('1980-12-13'));
		$this->assertEquals('1980-12-13', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperFormatsDateStringCorrectly() {
		$viewHelper = new Tx_Fluid_ViewHelpers_Format_DateViewHelper();
		$actualResult = $viewHelper->render('1980-12-13');
		$this->assertEquals('1980-12-13', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperRespectsCustomFormat() {
		$viewHelper = new Tx_Fluid_ViewHelpers_Format_DateViewHelper();
		$actualResult = $viewHelper->render(new DateTime('1980-02-01'), 'd.m.Y');
		$this->assertEquals('01.02.1980', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperReturnsEmptyStringIfNULLIsGiven() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_DateViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(NULL));
		$actualResult = $viewHelper->render();
		$this->assertEquals('', $actualResult);
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperThrowsExceptionIfDateStringCantBeParsed() {
		$viewHelper = new Tx_Fluid_ViewHelpers_Format_DateViewHelper();
		$viewHelper->render('foo');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperUsesChildNodesIfDateAttributeIsNotSpecified() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_DateViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(new DateTime('1980-12-13')));
		$actualResult = $viewHelper->render();
		$this->assertEquals('1980-12-13', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dateArgumentHasPriorityOverChildNodes() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_DateViewHelper', array('renderChildren'));
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
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_DateViewHelper', array('renderChildren'));
		$date = 1359891658; // 2013-02-03 11:40 UTC
		$format = 'Y-m-d H:i';

		date_default_timezone_set($timezone);
		$this->assertEquals($expected, $viewHelper->render($date, $format));
	}

	/**
	 * @test
	 * @TODO: Split the single sets to a data provider
	 */
	public function viewHelperRespectsDefaultTimezoneForStringTimestamp() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_DateViewHelper', array('renderChildren'));

		$format = 'Y-m-d H:i';

		date_default_timezone_set('Europe/Berlin');
		$date = '@1359891658'; // 2013-02-03 11:40 UTC
		$expected = '2013-02-03 12:40';
		$this->assertEquals($expected, $viewHelper->render($date, $format));

		$date = '03/Oct/2000:14:55:36 +0400'; // Moscow
		$expected = '2000-10-03 12:55';
		$this->assertEquals($expected, $viewHelper->render($date, $format));

		date_default_timezone_set('Asia/Riyadh');
		$date = '@1359891658'; // 2013-02-03 11:40 UTC
		$expected = '2013-02-03 14:40';
		$this->assertEquals($expected, $viewHelper->render($date, $format));

		$date = '03/Oct/2000:14:55:36 +0400'; // Moscow
		$expected = '2000-10-03 13:55';
		$this->assertEquals($expected, $viewHelper->render($date, $format));
	}

	/**
	 * @test
	 * @TODO: Split the single sets to a data provider
	 */
	public function dateViewHelperFormatsDateLocalized() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_DateViewHelper', array('renderChildren'));
		$format = '%d. %B %Y';
		$timestamp = '@1359891658'; // 2013-02-03 11:40 UTC

		$locale = 'de_DE.UTF-8';
		if (!setlocale(LC_COLLATE, $locale)) {
			$this->markTestSkipped('Locale ' . $locale . ' is not available.');
		}
		$this->setLocale($locale);
		$expected = '03. Februar 2013';
		$this->assertEquals($expected, $viewHelper->render($timestamp, $format));

		$locale = 'en_ZW.utf8';
		if (!setlocale(LC_COLLATE, $locale)) {
			$this->markTestSkipped('Locale ' . $locale . ' is not available.');
		}
		$this->setLocale($locale);
		$expected = '03. February 2013';
		$this->assertEquals($expected, $viewHelper->render($timestamp, $format));
	}

	protected function setLocale($locale) {
		setlocale(LC_CTYPE, $locale);
		setlocale(LC_MONETARY, $locale);
		setlocale(LC_TIME, $locale);
	}
}

?>
