<?php
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
 * Test case for the t3lib_TCEforms class in the TYPO3 core.
 *
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCEformsTest extends tx_phpunit_testcase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * @var t3lib_TCEforms|PHPUnit_Framework_MockObject_MockObject
	 */
	protected $subject;

	/**
	 * Sets up this test case.
	 */
	protected function setUp() {
		$this->subject = $this->getMock('t3lib_TCEforms', array('dummy'), array(), '', FALSE);
	}

	/**
	 * @return array
	 */
	public function formatValueDataProvider() {
		return array(
			'format with empty format configuration' => array(
				array(
					'format' => '',
				),
				'',
				'',
			),
			'format to date' => array(
				array(
					'format' => 'date',
				),
				'1412358894',
				'03-10-2014'
			),
			'format to date with empty timestamp' => array(
				array(
					'format' => 'date',
				),
				'0',
				''
			),
			'format to date with blank timestamp' => array(
				array(
					'format' => 'date',
				),
				'',
				''
			),
			'format to date with option strftime' => array(
				array(
					'format' => 'date',
					'format.' => array(
						'option' => '%d-%m',
						'strftime' => TRUE,
					),
				),
				'1412358894',
				'03-10'
			),
			'format to date with option' => array(
				array(
					'format' => 'date',
					'format.' => array(
						'option' => 'd-m',
					),
				),
				'1412358894',
				'03-10'
			),
			'format to datetime' => array(
				array(
					'format' => 'datetime',
				),
				'1412358894',
				'17:54 03-10-2014'
			),
			'format to datetime with empty value' => array(
				array(
					'format' => 'datetime',
				),
				'',
				''
			),
			'format to time' => array(
				array(
					'format' => 'time',
				),
				'1412358894',
				'17:54'
			),
			'format to time with empty value' => array(
				array(
					'format' => 'time',
				),
				'',
				''
			),
			'format to timesec' => array(
				array(
					'format' => 'timesec',
				),
				'1412358894',
				'17:54:54'
			),
			'format to timesec with empty value' => array(
				array(
					'format' => 'timesec',
				),
				'',
				''
			),
			'format to year' => array(
				array(
					'format' => 'year',
				),
				'1412358894',
				'2014'
			),
			'format to int' => array(
				array(
					'format' => 'int',
				),
				'123.00',
				'123'
			),
			'format to int with base' => array(
				array(
					'format' => 'int',
					'format.' => array(
						'base' => 'oct',
					),
				),
				'123',
				'173'
			),
			'format to int with empty value' => array(
				array(
					'format' => 'int',
				),
				'',
				'0'
			),
			'format to float' => array(
				array(
					'format' => 'float',
				),
				'123',
				'123.00'
			),
			'format to float with precision' => array(
				array(
					'format' => 'float',
					'format.' => array(
						'precision' => '4',
					),
				),
				'123',
				'123.0000'
			),
			'format to float with empty value' => array(
				array(
					'format' => 'float',
				),
				'',
				'0.00'
			),
			'format to number' => array(
				array(
					'format' => 'number',
					'format.' => array(
						'option' => 'b',
					),
				),
				'123',
				'1111011'
			),
			'format to number with empty option' => array(
				array(
					'format' => 'number',
				),
				'123',
				''
			),
			'format to md5' => array(
				array(
					'format' => 'md5',
				),
				'joh316',
				'bacb98acf97e0b6112b1d1b650b84971'
			),
			'format to md5 with empty value' => array(
				array(
					'format' => 'md5',
				),
				'',
				'd41d8cd98f00b204e9800998ecf8427e'
			),
			'format to filesize' => array(
				array(
					'format' => 'filesize',
				),
				'100000',
				'98 K'
			),
			'format to filesize with empty value' => array(
				array(
					'format' => 'filesize',
				),
				'',
				'0 '
			),
			'format to filesize with option appendByteSize' => array(
				array(
					'format' => 'filesize',
					'format.' => array(
						'appendByteSize' => TRUE,
					),
				),
				'100000',
				'98 K (100000)'
			),
		);
	}

	/**
	 * @param array $config
	 * @param string $itemValue
	 * @param string $expectedResult
	 * @dataProvider formatValueDataProvider
	 * @test
	 */
	public function formatValueWithGivenConfiguration($config, $itemValue, $expectedResult) {
		$timezoneBackup = date_default_timezone_get();
		date_default_timezone_set('UTC');
		$result = $this->subject->formatValue($config, $itemValue);
		date_default_timezone_set($timezoneBackup);

		$this->assertEquals($expectedResult, $result);
	}

}
?>