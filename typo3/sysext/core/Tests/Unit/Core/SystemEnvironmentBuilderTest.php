<?php
namespace TYPO3\CMS\Core\Tests\Unit\Core;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Christian Kuhn <lolli@schwarzbu.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Testcase
 *
 * @author Christia Kuhn <lolli@schwarzbu.ch>
 */
class SystemEnvironmentBuilderTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Core\SystemEnvironmentBuilder|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $fixture = NULL;

	/**
	 * Set up testcase
	 *
	 * @return void
	 */
	public function setUp() {
		$this->fixture = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Core\\SystemEnvironmentBuilder', array('dummy'));
	}

	/**
	 * Tear down
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * Data provider for 'fileDenyPatternMatchesPhpExtension' test case.
	 *
	 * @return array
	 */
	public function fileDenyPatternMatchesPhpExtensionDataProvider() {
		$fileName = uniqid('filename');
		$data = array();
		$phpExtensions = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', 'php,php3,php4,php5,php6,phpsh,phtml', TRUE);
		foreach ($phpExtensions as $extension) {
			$data[] = array($fileName . '.' . $extension);
			$data[] = array($fileName . '.' . $extension . '.txt');
		}
		return $data;
	}

	/**
	 * Tests whether an accordant PHP extension is denied.
	 *
	 * @test
	 * @dataProvider fileDenyPatternMatchesPhpExtensionDataProvider
	 * @param string $phpExtension
	 */
	public function fileDenyPatternMatchesPhpExtension($phpExtension) {
		$this->assertGreaterThan(0, preg_match('/' . FILE_DENY_PATTERN_DEFAULT . '/', $phpExtension), $phpExtension);
	}

	/**
	 * @test
	 */
	public function getPathThisScriptCliReadsLocalPartFromArgv() {
		$fakedLocalPart = uniqid('Test');
		$GLOBALS['_SERVER']['argv'][0] = $fakedLocalPart;
		$this->assertStringEndsWith($fakedLocalPart, $this->fixture->_call('getPathThisScriptCli'));
	}

	/**
	 * @test
	 */
	public function getPathThisScriptCliReadsLocalPartFromEnv() {
		$fakedLocalPart = uniqid('Test');
		unset($GLOBALS['_SERVER']['argv']);
		$GLOBALS['_ENV']['_'] = $fakedLocalPart;
		$this->assertStringEndsWith($fakedLocalPart, $this->fixture->_call('getPathThisScriptCli'));
	}

	/**
	 * @test
	 */
	public function getPathThisScriptCliReadsLocalPartFromServer() {
		$fakedLocalPart = uniqid('Test');
		unset($GLOBALS['_SERVER']['argv']);
		unset($GLOBALS['_ENV']['_']);
		$GLOBALS['_SERVER']['_'] = $fakedLocalPart;
		$this->assertStringEndsWith($fakedLocalPart, $this->fixture->_call('getPathThisScriptCli'));
	}

	/**
	 * @test
	 */
	public function getPathThisScriptCliAddsCurrentWorkingDirectoryFromServerEnvironmentToLocalPathOnUnix() {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test not available on Windows OS.');
		}
		$GLOBALS['_SERVER']['argv'][0] = 'foo';
		$fakedAbsolutePart = '/' . uniqid('Absolute') . '/';
		$_SERVER['PWD'] = $fakedAbsolutePart;
		$this->assertStringStartsWith($fakedAbsolutePart, $this->fixture->_call('getPathThisScriptCli'));
	}

	/**
	 * @test
	 */
	public function getUnifiedDirectoryNameWithTrailingSlashReturnsCorrectPathOnUnix() {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test not available on Windows OS.');
		}
		$input = '/foo/bar/test.php';
		$expected = '/foo/bar/';
		$actual = $this->fixture->_call('getUnifiedDirectoryNameWithTrailingSlash', $input);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function addCorePearPathToIncludePathAddsTypo3ContribPearToPathAsFirstEntry() {
		$backupPath = get_include_path();
		$this->fixture->_call('addCorePearPathToIncludePath');
		$actualValue = get_include_path();
		set_include_path($backupPath);
		$this->assertStringStartsWith(PATH_typo3 . 'contrib/pear/' . PATH_SEPARATOR, $actualValue);
	}

	/**
	 * @test
	 */
	public function initializeGlobalVariablesUnsetsGlobalErrorArray() {
		$GLOBALS['error'] = 'foo';
		$this->fixture->_call('initializeGlobalVariables');
		$this->assertFalse(isset($GLOBALS['error']));
	}

	/**
	 * @test
	 */
	public function initializeGlobalVariablesSetsGlobalClientArray() {
		unset($GLOBALS['CLIENT']);
		$this->fixture->_call('initializeGlobalVariables');
		$this->assertArrayHasKey('CLIENT', $GLOBALS);
	}

	/**
	 * @test
	 */
	public function initializeGlobalVariablesSetsGlobalTypo3MiscArray() {
		unset($GLOBALS['TYPO3_MISC']);
		$this->fixture->_call('initializeGlobalVariables');
		$this->assertInternalType('array', $GLOBALS['TYPO3_MISC']);
	}

	/**
	 * @test
	 */
	public function initializeGlobalVariablesSetsGlobalT3VarArray() {
		unset($GLOBALS['T3_VAR']);
		$this->fixture->_call('initializeGlobalVariables');
		$this->assertInternalType('array', $GLOBALS['T3_VAR']);
	}

	/**
	 * @test
	 */
	public function initializeGlobalVariablesSetsGlobalT3ServicesArray() {
		unset($GLOBALS['T3_SERVICES']);
		$this->fixture->_call('initializeGlobalVariables');
		$this->assertInternalType('array', $GLOBALS['T3_SERVICES']);
	}

	/**
	 * Data provider for initializeGlobalTimeTrackingVariablesSetsGlobalVariables
	 *
	 * @return array
	 */
	public function initializeGlobalTimeTrackingVariablesSetsGlobalVariablesDataProvider() {
		return array(
			'PARSETIME_START' => array('PARSETIME_START'),
			'EXEC_TIME' => array('EXEC_TIME'),
			'ACCESS_TIME' => array('ACCESS_TIME'),
			'SIM_EXEC_TIME' => array('SIM_EXEC_TIME'),
			'SIM_ACCESS_TIME' => array('SIM_ACCESS_TIME')
		);
	}

	/**
	 * @test
	 * @dataProvider initializeGlobalTimeTrackingVariablesSetsGlobalVariablesDataProvider
	 * @param string $variable Variable to check for in $GLOBALS
	 */
	public function initializeGlobalTimeTrackingVariablesSetsGlobalVariables($variable) {
		unset($GLOBALS[$variable]);
		$this->fixture->_call('initializeGlobalTimeTrackingVariables');
		$this->assertTrue(isset($GLOBALS[$variable]));
	}

	/**
	 * @test
	 */
	public function initializeGlobalTimeTrackingVariablesSetsGlobalTypo3MiscMicrotimeStart() {
		unset($GLOBALS['TYPO3_MISC']['microtime_start']);
		$this->fixture->_call('initializeGlobalTimeTrackingVariables');
		$this->assertTrue(isset($GLOBALS['TYPO3_MISC']['microtime_start']));
	}

	/**
	 * @test
	 */
	public function initializeGlobalTimeTrackingVariablesRoundsAccessTimeToSixtySeconds() {
		$this->fixture->_call('initializeGlobalTimeTrackingVariables');
		$this->assertEquals(0, $GLOBALS['ACCESS_TIME'] % 60);
	}

	/**
	 * @test
	 */
	public function initializeGlobalTimeTrackingVariablesRoundsSimAccessTimeToSixtySeconds() {
		$this->fixture->_call('initializeGlobalTimeTrackingVariables');
		$this->assertEquals(0, $GLOBALS['SIM_ACCESS_TIME'] % 60);
	}

	/**
	 * @test
	 */
	public function initializeBasicErrorReportingExcludesStrict() {
		$backupReporting = error_reporting();
		$this->fixture->_call('initializeBasicErrorReporting');
		$actualReporting = error_reporting();
		error_reporting($backupReporting);
		$this->assertEquals(0, $actualReporting & E_STRICT);
	}

	/**
	 * @test
	 */
	public function initializeBasicErrorReportingExcludesNotice() {
		$backupReporting = error_reporting();
		$this->fixture->_call('initializeBasicErrorReporting');
		$actualReporting = error_reporting();
		error_reporting($backupReporting);
		$this->assertEquals(0, $actualReporting & E_NOTICE);
	}

	/**
	 * @test
	 */
	public function initializeBasicErrorReportingExcludesDeprecated() {
		$backupReporting = error_reporting();
		$this->fixture->_call('initializeBasicErrorReporting');
		$actualReporting = error_reporting();
		error_reporting($backupReporting);
		$this->assertEquals(0, $actualReporting & E_DEPRECATED);
	}

}

?>