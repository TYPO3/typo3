<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Configuration\TypoScript\ConditionMatching;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Oliver Hader <oliver@typo3.org>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase for class \TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher.
 *
 * @author 	Oliver Hader <oliver@typo3.org>
 */
class ConditionMatcherTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array
	 */
	private $backupGlobalVariables;

	/**
	 * @var array
	 */
	private $rootline;

	/**
	 * @var \TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher
	 */
	private $matchCondition;

	/**
	 * @var string
	 */
	private $testTableName;

	public function setUp() {
		$this->backupGlobalVariables = array(
			'_ENV' => $_ENV,
			'_GET' => $_GET,
			'_POST' => $_POST,
			'_SERVER' => $_SERVER,
			'TCA' => $GLOBALS['TCA'],
			'TYPO3_DB' => $GLOBALS['TYPO3_DB'],
			'TYPO3_CONF_VARS' => $GLOBALS['TYPO3_CONF_VARS'],
			'T3_VAR' => $GLOBALS['T3_VAR'],
			'BE_USER' => $GLOBALS['BE_USER'],
			'SOBE' => $GLOBALS['SOBE']
		);
		$this->testTableName = 'TYPO3\\CMS\\Backend\\Configuration\\TypoScript\\ConditionMatching\\ConditionMatcher_testTable';
		$this->testGlobalNamespace = uniqid('TEST');
		$GLOBALS['TCA'][$this->testTableName] = array('ctrl' => array());
		$GLOBALS[$this->testGlobalNamespace] = array();
		$this->setUpBackend();
		$this->matchCondition = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Configuration\\TypoScript\\ConditionMatching\\ConditionMatcher');
	}

	public function tearDown() {
		foreach ($this->backupGlobalVariables as $key => $data) {
			$GLOBALS[$key] = $data;
		}
		unset($this->matchCondition);
		unset($this->backupGlobalVariables);
		unset($GLOBALS[$this->testGlobalNamespace]);
	}

	private function setUpBackend() {
		$this->rootline = array(
			2 => array('uid' => 121, 'pid' => 111),
			1 => array('uid' => 111, 'pid' => 101),
			0 => array('uid' => 101, 'pid' => 0)
		);
		$GLOBALS['BE_USER'] = $this->getMock('beUserAuth', array(), array(), '', FALSE);
	}

	private function setUpDatabaseMockForDeterminePageId() {
		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_SELECTquery', 'sql_fetch_assoc', 'sql_free_result'));
		$GLOBALS['TYPO3_DB']->expects($this->any())->method('exec_SELECTquery')->will($this->returnCallback(array($this, 'determinePageIdByRecordDatabaseExecuteCallback')));
		$GLOBALS['TYPO3_DB']->expects($this->any())->method('sql_fetch_assoc')->will($this->returnCallback(array($this, 'determinePageIdByRecordDatabaseFetchCallback')));
	}

	/**
	 * Tests whether a faulty expression fails.
	 *
	 * @test
	 */
	public function simulateDisabledMatchAllConditionsFailsOnFaultyExpression() {
		$this->matchCondition->matchAll = FALSE;
		$this->assertFalse($this->matchCondition->match('[nullCondition = This expression would return FALSE in general]'));
	}

	/**
	 * Tests whether simulating positive matches for all conditions succeeds.
	 *
	 * @test
	 */
	public function simulateEnabledMatchAllConditionsSucceeds() {
		$this->matchCondition->setSimulateMatchResult(TRUE);
		$this->assertTrue($this->matchCondition->match('[nullCondition = This expression would return FALSE in general]'));
	}

	/**
	 * Tests whether simulating positive matches for specific conditions succeeds.
	 *
	 * @test
	 */
	public function simulateEnabledMatchSpecificConditionsSucceeds() {
		$testCondition = '[' . uniqid('test') . ' = Any condition to simulate a positive match]';
		$this->matchCondition->setSimulateMatchConditions(array($testCondition));
		$this->assertTrue($this->matchCondition->match($testCondition));
	}

	/**
	 * Tests whether a condition matches Internet Explorer 7 on Windows.
	 *
	 * @return 	void
	 * @test
	 */
	public function conditionMatchesInternetExplorer7Windows() {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)';
		$result = $this->matchCondition->match('[browser = msie] && [version = 7] && [system = winNT]');
		$this->assertTrue($result);
	}

	/**
	 * Tests whether a condition does not match Internet Explorer 7 on Windows.
	 *
	 * @return 	void
	 * @test
	 */
	public function conditionDoesNotMatchInternetExplorer7Windows() {
		$_SERVER['HTTP_USER_AGENT'] = 'Opera/9.25 (Windows NT 6.0; U; en)';
		$result = $this->matchCondition->match('[browser = msie] && [version = 7] && [system = winNT]');
		$this->assertFalse($result);
	}

	/**
	 * Tests whether a condition does match the iOS with the correct and more recent 'iOS'
	 *
	 * @test
	 */
	public function conditionDoesMatchIosWithCorrectSystemKey() {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7W367a Safari/531.21.10';
		$result = $this->matchCondition->match('[system = iOS]');
		$this->assertTrue($result);
	}

	/**
	 * Tests whether a condition does match the iOS with the old 'mac'
	 *
	 * @test
	 */
	public function conditionDoesMatchIosWithOldSystemKey() {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7W367a Safari/531.21.10';
		$result = $this->matchCondition->match('[system = mac]');
		$this->assertTrue($result);
	}

	/**
	 * Tests whether a condition does match Windows 2000 with the correct and more recent 'win2k'
	 *
	 * @test
	 */
	public function conditionDoesMatchWindows2kWithNewSystemKey() {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; SV1)';
		$result = $this->matchCondition->match('[system = win2k]');
		$this->assertTrue($result);
	}

	/**
	 * Tests whether a condition does match Windows 2000 with the old 'winNT'
	 *
	 * @test
	 */
	public function conditionDoesMatchWindows2kWithOldSystemKey() {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; SV1)';
		$result = $this->matchCondition->match('[system = winNT]');
		$this->assertTrue($result);
	}

	/**
	 * Tests whether a condition does match Windows NT with 'winNT'
	 *
	 * @test
	 */
	public function conditionDoesMatchWindowsNtWithSystemKey() {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 4.0)';
		$result = $this->matchCondition->match('[system = winNT]');
		$this->assertTrue($result);
	}

	/**
	 * Tests whether a condition does match Android with the correct and more recent 'android'
	 *
	 * @test
	 */
	public function conditionDoesMatchAndroidWithNewSystemKey() {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; U; Android 2.3; en-US; sdk Build/GRH55) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1';
		$result = $this->matchCondition->match('[system = android]');
		$this->assertTrue($result);
	}

	/**
	 * Tests whether a condition does match Android with the old 'linux'
	 *
	 * @test
	 */
	public function conditionDoesMatchAndroidWithOldSystemKey() {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; U; Android 2.3; en-US; sdk Build/GRH55) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1';
		$result = $this->matchCondition->match('[system = linux]');
		$this->assertTrue($result);
	}

	/**
	 * Tests whether a device type condition matches a crawler.
	 *
	 * @test
	 */
	public function deviceConditionMatchesRobot() {
		$_SERVER['HTTP_USER_AGENT'] = 'Googlebot/2.1 (+http://www.google.com/bot.html)';
		$result = $this->matchCondition->match('[device = robot]');
		$this->assertTrue($result);
	}

	/**
	 * Tests whether a device type condition does not match a crawler.
	 *
	 * @test
	 */
	public function deviceConditionDoesNotMatchRobot() {
		$_SERVER['HTTP_USER_AGENT'] = md5('Some strange user agent');
		$result = $this->matchCondition->match('[device = robot]');
		$this->assertFalse($result);
	}

	/**
	 * Tests whether the language comparison matches.
	 *
	 * @test
	 */
	public function languageConditionMatchesSingleLanguageExpression() {
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-de,de;q=0.8,en-us;q=0.5,en;q=0.3';
		$this->assertTrue($this->matchCondition->match('[language = *de*]'));
		$this->assertTrue($this->matchCondition->match('[language = *de-de*]'));
	}

	/**
	 * Tests whether the language comparison matches.
	 *
	 * @test
	 */
	public function languageConditionMatchesMultipleLanguagesExpression() {
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-de,de;q=0.8,en-us;q=0.5,en;q=0.3';
		$this->assertTrue($this->matchCondition->match('[language = *en*,*de*]'));
		$this->assertTrue($this->matchCondition->match('[language = *en-us*,*de-de*]'));
	}

	/**
	 * Tests whether the language comparison matches.
	 *
	 * @test
	 */
	public function languageConditionMatchesCompleteLanguagesExpression() {
		$this->markTestSkipped('This comparison seems to be incomplete in \TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher.');
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-de,de;q=0.8,en-us;q=0.5,en;q=0.3';
		$this->assertTrue($this->matchCondition->match('[language = de-de,de;q=0.8]'));
	}

	/**
	 * Tests whether usergroup comparison matches.
	 *
	 * @test
	 */
	public function usergroupConditionMatchesSingleGroupId() {
		$GLOBALS['BE_USER']->groupList = '13,14,15';
		$this->assertTrue($this->matchCondition->match('[usergroup = 13]'));
	}

	/**
	 * Tests whether usergroup comparison matches.
	 *
	 * @test
	 */
	public function usergroupConditionMatchesMultipleUserGroupId() {
		$GLOBALS['BE_USER']->groupList = '13,14,15';
		$this->assertTrue($this->matchCondition->match('[usergroup = 999,15,14,13]'));
	}

	/**
	 * Tests whether user comparison matches.
	 *
	 * @test
	 */
	public function loginUserConditionMatchesAnyLoggedInUser() {
		$GLOBALS['BE_USER']->user['uid'] = 13;
		$this->assertTrue($this->matchCondition->match('[loginUser = *]'));
	}

	/**
	 * Tests whether user comparison matches.
	 *
	 * @test
	 */
	public function loginUserConditionMatchesSingleLoggedInUser() {
		$GLOBALS['BE_USER']->user['uid'] = 13;
		$this->assertTrue($this->matchCondition->match('[loginUser = 13]'));
	}

	/**
	 * Tests whether user comparison matches.
	 *
	 * @test
	 */
	public function loginUserConditionDoesNotMatchSingleLoggedInUser() {
		$GLOBALS['BE_USER']->user['uid'] = 13;
		$this->assertFalse($this->matchCondition->match('[loginUser = 999]'));
	}

	/**
	 * Tests whether user comparison matches.
	 *
	 * @test
	 */
	public function loginUserConditionMatchesMultipleLoggedInUsers() {
		$GLOBALS['BE_USER']->user['uid'] = 13;
		$this->assertTrue($this->matchCondition->match('[loginUser = 999,13]'));
	}

	/**
	 * Tests whether checkinf for an admin user matches
	 *
	 * @test
	 */
	public function adminUserConditionMatchesAdminUser() {
		$GLOBALS['BE_USER']->user['uid'] = 13;
		$GLOBALS['BE_USER']->user['admin'] = 1;
		$this->assertTrue($this->matchCondition->match('[adminUser = 1]'));
	}

	/**
	 * Tests whether checkinf for an admin user matches
	 *
	 * @test
	 */
	public function adminUserConditionMatchesRegularUser() {
		$GLOBALS['BE_USER']->user['uid'] = 14;
		$GLOBALS['BE_USER']->user['admin'] = 0;
		$this->assertTrue($this->matchCondition->match('[adminUser = 0]'));
	}

	/**
	 * Tests whether checkinf for an admin user matches
	 *
	 * @test
	 */
	public function adminUserConditionDoesNotMatchRegularUser() {
		$GLOBALS['BE_USER']->user['uid'] = 14;
		$GLOBALS['BE_USER']->user['admin'] = 0;
		$this->assertFalse($this->matchCondition->match('[adminUser = 1]'));
	}

	/**
	 * Tests whether numerical comparison matches.
	 *
	 * @test
	 */
	public function globalVarConditionMatchesOnEqualExpression() {
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 = 10]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 = 10.1]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 == 10]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 == 10.1]'));
	}

	/**
	 * Tests whether numerical comparison matches.
	 *
	 * @test
	 */
	public function globalVarConditionMatchesOnEqualExpressionWithMultipleValues() {
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 = 10|20|30]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 = 10.1|20.2|30.3]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:20 = 10|20|30]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:20.2 = 10.1|20.2|30.3]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 == 10|20|30]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 == 10.1|20.2|30.3]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:20 == 10|20|30]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:20.2 == 10.1|20.2|30.3]'));
	}

	/**
	 * Tests whether numerical comparison matches.
	 *
	 * @test
	 */
	public function globalVarConditionMatchesOnNotEqualExpression() {
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 != 20]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 != 10.2]'));
	}

	/**
	 * Tests whether numerical comparison matches.
	 *
	 * @test
	 */
	public function globalVarConditionMatchesOnNotEqualExpressionWithMultipleValues() {
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 != 20|30]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 != 10.2|20.3]'));
	}

	/**
	 * Tests whether numerical comparison matches.
	 *
	 * @test
	 */
	public function globalVarConditionMatchesOnLowerThanExpression() {
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 < 20]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 < 10.2]'));
	}

	/**
	 * Tests whether numerical comparison matches.
	 *
	 * @test
	 */
	public function globalVarConditionMatchesOnLowerThanOrEqualExpression() {
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 <= 10]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 <= 20]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 <= 10.1]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 <= 10.2]'));
	}

	/**
	 * Tests whether numerical comparison matches.
	 *
	 * @test
	 */
	public function globalVarConditionMatchesOnGreaterThanExpression() {
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:20 > 10]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.2 > 10.1]'));
	}

	/**
	 * Tests whether numerical comparison matches.
	 *
	 * @test
	 */
	public function globalVarConditionMatchesOnGreaterThanOrEqualExpression() {
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 >= 10]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:20 >= 10]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 >= 10.1]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.2 >= 10.1]'));
	}

	/**
	 * Tests whether numerical comparison matches.
	 *
	 * @test
	 */
	public function globalVarConditionMatchesOnEmptyExpressionWithNoValueSet() {
		$testKey = uniqid('test');
		$this->assertTrue($this->matchCondition->match('[globalVar = GP:' . $testKey . '=]'));
		$this->assertTrue($this->matchCondition->match('[globalVar = GP:' . $testKey . ' = ]'));
	}

	/**
	 * Tests whether numerical comparison matches.
	 *
	 * @test
	 */
	public function globalVarConditionDoesNotMatchOnEmptyExpressionWithValueSetToZero() {
		$testKey = uniqid('test');
		$_GET = array();
		$_POST = array($testKey => 0);
		$this->assertFalse($this->matchCondition->match('[globalVar = GP:' . $testKey . '=]'));
		$this->assertFalse($this->matchCondition->match('[globalVar = GP:' . $testKey . ' = ]'));
	}

	/**
	 * Tests whether string comparison matches.
	 *
	 * @test
	 */
	public function globalStringConditionMatchesOnEqualExpression() {
		$this->assertTrue($this->matchCondition->match('[globalString = LIT:TYPO3.Test.Condition = TYPO3.Test.Condition]'));
		$this->assertFalse($this->matchCondition->match('[globalString = LIT:TYPO3.Test.Condition = TYPO3]'));
	}

	/**
	 * Tests whether string comparison matches.
	 *
	 * @test
	 */
	public function globalStringConditionMatchesWildcardExpression() {
		$this->assertTrue($this->matchCondition->match('[globalString = LIT:TYPO3.Test.Condition = TYPO3?Test?Condition]'));
		$this->assertTrue($this->matchCondition->match('[globalString = LIT:TYPO3.Test.Condition = TYPO3.T*t.Condition]'));
		$this->assertTrue($this->matchCondition->match('[globalString = LIT:TYPO3.Test.Condition = TYPO3?T*t?Condition]'));
	}

	/**
	 * Tests whether string comparison matches.
	 *
	 * @test
	 */
	public function globalStringConditionMatchesRegularExpression() {
		$this->assertTrue($this->matchCondition->match('[globalString = LIT:TYPO3.Test.Condition = /^[A-Za-z3.]+$/]'));
		$this->assertTrue($this->matchCondition->match('[globalString = LIT:TYPO3.Test.Condition = /^TYPO3\\..+Condition$/]'));
		$this->assertFalse($this->matchCondition->match('[globalString = LIT:TYPO3.Test.Condition = /^FALSE/]'));
	}

	/**
	 * Tests whether string comparison matches.
	 *
	 * @test
	 */
	public function globalStringConditionMatchesEmptyRegularExpression() {
		$testKey = uniqid('test');
		$_SERVER[$testKey] = '';
		$this->assertTrue($this->matchCondition->match('[globalString = _SERVER|' . $testKey . ' = /^$/]'));
	}

	/**
	 * Tests whether treeLevel comparison matches.
	 *
	 * @test
	 */
	public function treeLevelConditionMatchesSingleValue() {
		$this->matchCondition->setRootline($this->rootline);
		$this->assertTrue($this->matchCondition->match('[treeLevel = 2]'));
	}

	/**
	 * Tests whether treeLevel comparison matches.
	 *
	 * @test
	 */
	public function treeLevelConditionMatchesMultipleValues() {
		$this->matchCondition->setRootline($this->rootline);
		$this->assertTrue($this->matchCondition->match('[treeLevel = 999,998,2]'));
	}

	/**
	 * Tests whether treeLevel comparison matches.
	 *
	 * @test
	 */
	public function treeLevelConditionDoesNotMatchFaultyValue() {
		$this->matchCondition->setRootline($this->rootline);
		$this->assertFalse($this->matchCondition->match('[treeLevel = 999]'));
	}

	/**
	 * Tests whether treeLevel comparison matches when creating new pages.
	 *
	 * @test
	 */
	public function treeLevelConditionMatchesCurrentPageIdWhileEditingNewPage() {
		$GLOBALS['SOBE'] = $this->getMock('TYPO3\\CMS\\Backend\\Controller\\EditDocumentController', array());
		$GLOBALS['SOBE']->elementsData = array(
			array(
				'table' => 'pages',
				'uid' => 'NEW4adc6021e37e7',
				'pid' => 121,
				'cmd' => 'new',
				'deleteAccess' => 0
			)
		);
		$GLOBALS['SOBE']->data = array();
		$this->matchCondition->setRootline($this->rootline);
		$this->matchCondition->setPageId(121);
		$this->assertTrue($this->matchCondition->match('[treeLevel = 3]'));
	}

	/**
	 * Tests whether treeLevel comparison matches when creating new pages.
	 *
	 * @test
	 */
	public function treeLevelConditionMatchesCurrentPageIdWhileSavingNewPage() {
		$GLOBALS['SOBE'] = $this->getMock('TYPO3\\CMS\\Backend\\Controller\\EditDocumentController', array());
		$GLOBALS['SOBE']->elementsData = array(
			array(
				'table' => 'pages',
				/// 999 is the uid of the page that was just created
				'uid' => 999,
				'pid' => 121,
				'cmd' => 'edit',
				'deleteAccess' => 1
			)
		);
		$GLOBALS['SOBE']->data = array(
			'pages' => array(
				'NEW4adc6021e37e7' => array(
					'pid' => 121
				)
			)
		);
		$this->matchCondition->setRootline($this->rootline);
		$this->matchCondition->setPageId(121);
		$this->assertTrue($this->matchCondition->match('[treeLevel = 3]'));
	}

	/**
	 * Tests whether a page Id is found in the previous rootline entries.
	 *
	 * @test
	 */
	public function PIDupinRootlineConditionMatchesSinglePageIdInRootline() {
		$this->matchCondition->setRootline($this->rootline);
		$this->matchCondition->setPageId(121);
		$this->assertTrue($this->matchCondition->match('[PIDupinRootline = 111]'));
	}

	/**
	 * Tests whether a page Id is found in the previous rootline entries.
	 *
	 * @test
	 */
	public function PIDupinRootlineConditionMatchesMultiplePageIdsInRootline() {
		$this->matchCondition->setRootline($this->rootline);
		$this->matchCondition->setPageId(121);
		$this->assertTrue($this->matchCondition->match('[PIDupinRootline = 999,111,101]'));
	}

	/**
	 * Tests whether a page Id is found in the previous rootline entries.
	 *
	 * @test
	 */
	public function PIDupinRootlineConditionDoesNotMatchPageIdNotInRootline() {
		$this->matchCondition->setRootline($this->rootline);
		$this->matchCondition->setPageId(121);
		$this->assertFalse($this->matchCondition->match('[PIDupinRootline = 999]'));
	}

	/**
	 * Tests whether a page Id is found in the previous rootline entries.
	 *
	 * @test
	 */
	public function PIDupinRootlineConditionDoesNotMatchLastPageIdInRootline() {
		$this->matchCondition->setRootline($this->rootline);
		$this->matchCondition->setPageId(121);
		$this->assertFalse($this->matchCondition->match('[PIDupinRootline = 121]'));
	}

	/**
	 * Tests whether a page Id is found in the previous rootline entries.
	 *
	 * @test
	 */
	public function PIDupinRootlineConditionMatchesCurrentPageIdWhileEditingNewPage() {
		$GLOBALS['SOBE'] = $this->getMock('TYPO3\\CMS\\Backend\\Controller\\EditDocumentController', array());
		$GLOBALS['SOBE']->elementsData = array(
			array(
				'table' => 'pages',
				'uid' => 'NEW4adc6021e37e7',
				'pid' => 121,
				'cmd' => 'new',
				'deleteAccess' => 0
			)
		);
		$GLOBALS['SOBE']->data = array();
		$this->matchCondition->setRootline($this->rootline);
		$this->matchCondition->setPageId(121);
		$this->assertTrue($this->matchCondition->match('[PIDupinRootline = 121]'));
	}

	/**
	 * Tests whether a page Id is found in the previous rootline entries.
	 *
	 * @test
	 */
	public function PIDupinRootlineConditionMatchesCurrentPageIdWhileSavingNewPage() {
		$GLOBALS['SOBE'] = $this->getMock('TYPO3\\CMS\\Backend\\Controller\\EditDocumentController', array());
		$GLOBALS['SOBE']->elementsData = array(
			array(
				'table' => 'pages',
				/// 999 is the uid of the page that was just created
				'uid' => 999,
				'pid' => 121,
				'cmd' => 'edit',
				'deleteAccess' => 1
			)
		);
		$GLOBALS['SOBE']->data = array(
			'pages' => array(
				'NEW4adc6021e37e7' => array(
					'pid' => 121
				)
			)
		);
		$this->matchCondition->setRootline($this->rootline);
		$this->matchCondition->setPageId(121);
		$this->assertTrue($this->matchCondition->match('[PIDupinRootline = 121]'));
	}

	/**
	 * Tests whether a page Id is found in all rootline entries.
	 *
	 * @test
	 */
	public function PIDinRootlineConditionMatchesSinglePageIdInRootline() {
		$this->matchCondition->setRootline($this->rootline);
		$this->matchCondition->setPageId(121);
		$this->assertTrue($this->matchCondition->match('[PIDinRootline = 111]'));
	}

	/**
	 * Tests whether a page Id is found in all rootline entries.
	 *
	 * @test
	 */
	public function PIDinRootlineConditionMatchesMultiplePageIdsInRootline() {
		$this->matchCondition->setRootline($this->rootline);
		$this->matchCondition->setPageId(121);
		$this->assertTrue($this->matchCondition->match('[PIDinRootline = 999,111,101]'));
	}

	/**
	 * Tests whether a page Id is found in all rootline entries.
	 *
	 * @test
	 */
	public function PIDinRootlineConditionMatchesLastPageIdInRootline() {
		$this->matchCondition->setRootline($this->rootline);
		$this->matchCondition->setPageId(121);
		$this->assertTrue($this->matchCondition->match('[PIDinRootline = 121]'));
	}

	/**
	 * Tests whether a page Id is found in all rootline entries.
	 *
	 * @test
	 */
	public function PIDinRootlineConditionDoesNotMatchPageIdNotInRootline() {
		$this->matchCondition->setRootline($this->rootline);
		$this->matchCondition->setPageId(121);
		$this->assertFalse($this->matchCondition->match('[PIDinRootline = 999]'));
	}

	/**
	 * Tests whether the compatibility version can be evaluated.
	 * (e.g. 4.9 is compatible to 4.0 but not to 5.0)
	 *
	 * @test
	 */
	public function compatVersionConditionMatchesOlderRelease() {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['compat_version'] = '4.9';
		$this->assertTrue($this->matchCondition->match('[compatVersion = 4.0]'));
	}

	/**
	 * Tests whether the compatibility version can be evaluated.
	 * (e.g. 4.9 is compatible to 4.0 but not to 5.0)
	 *
	 * @test
	 */
	public function compatVersionConditionMatchesSameRelease() {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['compat_version'] = '4.9';
		$this->assertTrue($this->matchCondition->match('[compatVersion = 4.9]'));
	}

	/**
	 * Tests whether the compatibility version can be evaluated.
	 * (e.g. 4.9 is compatible to 4.0 but not to 5.0)
	 *
	 * @test
	 */
	public function compatVersionConditionDoesNotMatchNewerRelease() {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['compat_version'] = '4.9';
		$this->assertFalse($this->matchCondition->match('[compatVersion = 5.0]'));
	}

	/**
	 * Tests whether the generic fetching of variables works with the namespace 'GP'.
	 *
	 * @test
	 */
	public function genericGetVariablesSucceedsWithNamespaceGP() {
		$_GET = array('testGet' => 'getTest');
		$_POST = array('testPost' => 'postTest');
		$this->assertTrue($this->matchCondition->match('[globalString = GP:testGet = getTest]'));
		$this->assertTrue($this->matchCondition->match('[globalString = GP:testPost = postTest]'));
	}

	/**
	 * Tests whether the generic fetching of variables does not work with the namespace 'TSFE',
	 * since we are in the backend context here.
	 *
	 * @test
	 */
	public function genericGetVariablesFailsWithNamespaceTSFE() {
		$GLOBALS['TSFE'] = new \stdClass();
		$GLOBALS['TSFE']->id = 1234567;
		$this->assertFalse($this->matchCondition->match('[globalString = TSFE:id = 1234567]'));
	}

	/**
	 * Tests whether the generic fetching of variables works with the namespace 'ENV'.
	 *
	 * @test
	 */
	public function genericGetVariablesSucceedsWithNamespaceENV() {
		$testKey = uniqid('test');
		putenv($testKey . '=testValue');
		$this->assertTrue($this->matchCondition->match('[globalString = ENV:' . $testKey . ' = testValue]'));
	}

	/**
	 * Tests whether the generic fetching of variables works with the namespace 'IENV'.
	 *
	 * @test
	 */
	public function genericGetVariablesSucceedsWithNamespaceIENV() {
		$_SERVER['HTTP_HOST'] = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY') . ':1234567';
		$this->assertTrue($this->matchCondition->match('[globalString = IENV:TYPO3_PORT = 1234567]'));
	}

	/**
	 * Tests whether the generic fetching of variables works with any global namespace.
	 *
	 * @test
	 */
	public function genericGetVariablesSucceedsWithAnyGlobalNamespace() {
		$GLOBALS[$this->testGlobalNamespace] = array(
			'first' => 'testFirst',
			'second' => array('third' => 'testThird')
		);
		$this->assertTrue($this->matchCondition->match('[globalString = ' . $this->testGlobalNamespace . '|first = testFirst]'));
		$this->assertTrue($this->matchCondition->match('[globalString = ' . $this->testGlobalNamespace . '|second|third = testThird]'));
	}

	/**
	 * Tests whether determining a pageId works.
	 *
	 * @test
	 */
	public function pageIdCanBeDeterminedWhileCallingModuleWithPageTree() {
		$_GET['id'] = 999;
		$this->matchCondition->match('[globalVar = LIT:10 = 10]');
		$this->assertEquals(999, $this->matchCondition->getPageId());
	}

	/**
	 * Tests whether determining a pageId works.
	 *
	 * @test
	 */
	public function pageIdCanBeDeterminedWhileEditingAPageRecord() {
		$_GET['edit']['pages'][999] = 'edit';
		$this->matchCondition->match('[globalVar = LIT:10 = 10]');
		$this->assertEquals(999, $this->matchCondition->getPageId());
	}

	/**
	 * Tests whether determining a pageId works.
	 *
	 * @test
	 */
	public function pageIdCanBeDeterminedWhileEditingARegularRecord() {
		$this->setUpDatabaseMockForDeterminePageId();
		$_GET['edit'][$this->testTableName][13] = 'edit';
		$this->matchCondition->match('[globalVar = LIT:10 = 10]');
		$this->assertEquals(999, $this->matchCondition->getPageId());
	}

	/**
	 * Tests whether determining a pageId works.
	 *
	 * @test
	 */
	public function pageIdCanBeDeterminedWhileCreatingARecord() {
		$_GET['edit']['pages'][999] = 'new';
		$this->matchCondition->match('[globalVar = LIT:10 = 10]');
		$this->assertEquals(999, $this->matchCondition->getPageId());
	}

	/**
	 * Tests whether determining a pageId works.
	 *
	 * @test
	 */
	public function pageIdCanBeDeterminedWhileCreatingARecordAfterAnExistingRecord() {
		$this->setUpDatabaseMockForDeterminePageId();
		$_GET['edit'][$this->testTableName][-13] = 'new';
		$this->matchCondition->match('[globalVar = LIT:10 = 10]');
		$this->assertEquals(999, $this->matchCondition->getPageId());
	}

	/**
	 * Tests whether determining a pageId works.
	 *
	 * @test
	 */
	public function pageIdCanBeDeterminedWhileDeletingAPageRecord() {
		$_GET['cmd']['pages'][999]['delete'] = 1;
		$this->matchCondition->match('[globalVar = LIT:10 = 10]');
		$this->assertEquals(999, $this->matchCondition->getPageId());
	}

	/**
	 * Tests whether determining a pageId works.
	 *
	 * @test
	 */
	public function pageIdCanBeDeterminedWhileCopyingARecordToAnotherPage() {
		$_GET['cmd']['pages'][121]['copy'] = 999;
		$this->matchCondition->match('[globalVar = LIT:10 = 10]');
		$this->assertEquals(999, $this->matchCondition->getPageId());
	}

	/**
	 * Tests whether determining a pageId works.
	 *
	 * @test
	 */
	public function pageIdCanBeDeterminedWhileCopyingARecordAfterAnExistingRecord() {
		$this->setUpDatabaseMockForDeterminePageId();
		$_GET['cmd'][$this->testTableName][121]['copy'] = -13;
		$this->matchCondition->match('[globalVar = LIT:10 = 10]');
		$this->assertEquals(999, $this->matchCondition->getPageId());
	}

	/**
	 * Tests whether determining a pageId works.
	 *
	 * @test
	 */
	public function pageIdCanBeDeterminedWhileMovingARecordToAnotherPage() {
		$_GET['cmd']['pages'][121]['move'] = 999;
		$this->matchCondition->match('[globalVar = LIT:10 = 10]');
		$this->assertEquals(999, $this->matchCondition->getPageId());
	}

	/**
	 * Callback method for pageIdCanBeDetermined test cases.
	 * Simulates TYPO3_DB->exec_SELECTquery().
	 *
	 * @param 	string		$fields
	 * @param 	string		$table
	 * @param 	string		$where
	 * @return 	mixed
	 */
	public function determinePageIdByRecordDatabaseExecuteCallback($fields, $table, $where) {
		if ($table === $this->testTableName) {
			return array(
				'scope' => $this->testTableName,
				'data' => array(
					'pid' => 999
				)
			);
		} else {
			return FALSE;
		}
	}

	/**
	 * Callback method for pageIdCanBeDetermined test cases.
	 * Simulates TYPO3_DB->sql_fetch_assoc().
	 *
	 * @param 	mixed		$resource
	 * @return 	mixed
	 */
	public function determinePageIdByRecordDatabaseFetchCallback($resource) {
		if (is_array($resource) && $resource['scope'] === $this->testTableName) {
			return $resource['data'];
		} else {
			return FALSE;
		}
	}

}

?>