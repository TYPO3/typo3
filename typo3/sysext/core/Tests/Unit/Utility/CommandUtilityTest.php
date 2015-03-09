<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

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
use TYPO3\CMS\Core\Utility\CommandUtility;

/**
 * Test case for class \TYPO3\CMS\Core\Utility\CommandUtility
 */
class CommandUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Data provider for getConfiguredApps
	 *
	 * @return array
	 */
	public function getConfiguredAppsDataProvider() {
		$defaultExpected = array(
			'perl' => array(
				'app' => 'perl',
				'path' => '/usr/bin/',
				'valid' => TRUE
			),
			'unzip' => array(
				'app' => 'unzip',
				'path' => '/usr/local/bin/',
				'valid' => TRUE
			),
		);
		return array(
			'returns empty array for empty string' => array(
				'',
				array()
			),
			'separated by comma' => array(
				'perl=/usr/bin/perl,unzip=/usr/local/bin/unzip',
				$defaultExpected
			),
			'separated by new line' => array(
				'perl=/usr/bin/perl ' . LF . ' unzip=/usr/local/bin/unzip',
				$defaultExpected
			),
			'separated by new line with spaces' => array(
				'perl = /usr/bin/perl ' . LF . ' unzip = /usr/local/bin/unzip',
				$defaultExpected
			),
			'separated by new line with spaces and empty rows' => array(
				LF . 'perl = /usr/bin/perl ' . LF . LF . ' unzip = /usr/local/bin/unzip' . LF,
				$defaultExpected
			),
			'separated by char(10)' => array(
				'perl=/usr/bin/perl' . '\'.chr(10).\'' . 'unzip=/usr/local/bin/unzip',
				$defaultExpected
			),
			'separated by LF as string' => array(
				'perl=/usr/bin/perl' . '\' . LF . \'' . 'unzip=/usr/local/bin/unzip',
				$defaultExpected
			)
		);
	}

	/**
	 * @dataProvider getConfiguredAppsDataProvider
	 * @param array $globalsBinSetup
	 * @param array $expected
	 * @test
	 */
	public function getConfiguredApps($globalsBinSetup, $expected) {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['binSetup'] = $globalsBinSetup;
		$commandUtilityMock = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Utility\\CommandUtility', array('dummy'));
		$result = $commandUtilityMock->_call('getConfiguredApps');
		$this->assertSame($expected, $result);
	}

}
