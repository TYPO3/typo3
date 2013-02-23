<?php
namespace TYPO3\CMS\Core\Tests\Unit\Locking\Locker;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Daniel Hürtgen <huertgen@rheinschafe.de>
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
 * Abstract locker tests
 *
 * @author Daniel Hürtgen <huertgen@rheinschafe.de>
 */
class AbstractLockerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Enable backup of global and system variables.
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Mock fixture.
	 *
	 * @var \TYPO3\CMS\Core\Locking\Locker\AbstractLocker|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $fixture;

	/**
	 * Accessible mock fixture.
	 *
	 * @var \TYPO3\CMS\Core\Locking\Locker\AbstractLocker|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $accessibleFixture;

	/**
	 * Contains accessable class name.
	 *
	 * @var string
	 */
	protected $accessibleClassName;

	/**
	 * Holds an array auf default constructor args.
	 *  First arg -> context: dummy
	 *  Second arg -> id: dummy
	 *
	 * @var array
	 */
	protected $dummyConstructorArgs = array('dummy', 'dummy');

	/**
	 * Holds array of default options.
	 *
	 * @var array
	 */
	protected $defaultOptions = array(
		'logging' => TRUE,
		'retries' => 150,
		'retryInterval' => 200,
		'respectExecutionTime' => TRUE,
		'autoReleaseOnPHPShutdown' => TRUE,
		'maxLockAge' => 120,
	);

	/**
	 * Constructs test class.
	 *
	 * @param null   $name
	 * @param array  $data
	 * @param string $dataName
	 */
	public function __construct($name = NULL, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);

		// build accessable proxy class and save classname
		$this->accessibleClassName = $this->buildAccessibleProxy('TYPO3\\CMS\\Core\\Locking\\Locker\\AbstractLocker');
	}

	/**
	 * Inits/setUp test class.
	 *
	 * @return void
	 */
	public function setUp() {
		$this->createFixture();
		$this->createAccessableFixture();
	}

	/**
	 * Create new basic mock for abstract locker.
	 *
	 * @param array $constructorArgs
	 * @param array $mockMethods
	 * @return void
	 */
	protected function createFixture(array $constructorArgs = array(), array $mockMethods = array()) {
		$this->fixture = $this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Locking\\Locker\\AbstractLocker', $constructorArgs, '', (count($constructorArgs) >= 2 ? TRUE : FALSE), TRUE, TRUE, $mockMethods);
	}

	/**
	 * Create new accessable mock for abstract locker.
	 *
	 * @param array $constructorArgs
	 * @param array $mockMethods
	 * @return void
	 */
	protected function createAccessableFixture(array $constructorArgs = array(), array $mockMethods = array()) {
		$this->accessibleFixture = $this->getMockForAbstractClass($this->accessibleClassName, $constructorArgs, '', (count($constructorArgs) >= 2 ? TRUE : FALSE), TRUE, TRUE, $mockMethods);
	}

	/**
	 * ShutDowns test class.
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->fixture);
		unset($this->accessibleFixture);
	}

	/**
	 * Validates thats default options not changed, or also changed in test class.
	 *  Test should indicates, that it might causes problems if you change options.
	 *
	 * @return void
	 * @test
	 */
	public function failIfDefaultOptionsWereChanged() {
		$this->createAccessableFixture($this->dummyConstructorArgs, array('log', 'setOptions'));
		$this->assertSame($this->defaultOptions, $this->accessibleFixture->_get('options'));
	}

	/**
	 * Validates thats property isAcquired will FALSE by default.
	 *  Test required, because this a condition for all other tests.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatPropertyIsAcquiredWillBeFalseByDefault() {
		$this->assertFalse($this->accessibleFixture->_get('isAcquired'));
	}

	/**
	 * Simple dataprovider for testing isAcquired property states TRUE & FALSE.
	 *  Provides several test payloads.
	 *
	 * @return array
	 */
	public function boolTrueFalseDataProvider() {
		return array(
			array(TRUE),
			array(FALSE),
		);
	}

	/**
	 * Test validate that property isAcquired will be checkable by isAcquired() method.
	 *  Stupid test, but usefull.
	 *
	 * @param boolean $state
	 * @return void
	 * @dataProvider boolTrueFalseDataProvider
	 * @test
	 */
	public function validateThatChangePropertyIsAcquiredWillBeGettableByIsAcquiredMethod($state) {
		$this->accessibleFixture->_set('isAcquired', $state);
		$this->assertSame($state, $this->accessibleFixture->isAcquired());
	}

	/**
	 * Test logging enalbing will enable logging.
	 *
	 * @param boolean $state
	 * @return void
	 * @dataProvider boolTrueFalseDataProvider
	 * @test
	 */
	public function validateThatSetLoggingSetsLogging($state) {
		$this->fixture->setLogging($state);
		$this->assertSame($state, $this->fixture->getLogging());
	}

	/**
	 * Test if logging is enabled messsage won't be send to logger.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatSendALogMessageWillBeOnlyLoggedIfLoggingIsEnabled() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('doLog', 'isLoggingEnabled'));

		$this->accessibleFixture
				->expects($this->any())
				->method('isLoggingEnabled')
				->will($this->returnValue(FALSE));
		$this->accessibleFixture
				->expects($this->never())
				->method('doLog');

		$this->accessibleFixture->_call('log', 'Dummy test message');
	}

	/**
	 * Validate that sending a log message will be always prefixed with logger type.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatSendALogMessageWillAlwaysPrefixedWithLoggerTypeContextAndId() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('doLog', 'isLoggingEnabled', 'getType'));

		$this->accessibleFixture
				->expects($this->any())
				->method('isLoggingEnabled')
				->will($this->returnValue(TRUE));
		$this->accessibleFixture
				->expects($this->any())
				->method('getType')
				->will($this->returnValue('Dummy'));
		$this->accessibleFixture
				->expects($this->once())
				->method('doLog')
				->with($this->stringStartsWith('[DummyLocker][C:dummy|ID:dummy]'));

		$this->accessibleFixture->_call('log', 'Dummy test message');
	}

	/**
	 * Test if logging is enabled messsage won't be send to logger.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatSendADevLogMessageWillBeOnlyLoggedIfLoggingIsEnabled() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('doDevLog', 'isLoggingEnabled'));

		$this->accessibleFixture
				->expects($this->any())
				->method('isLoggingEnabled')
				->will($this->returnValue(FALSE));
		$this->accessibleFixture
				->expects($this->never())
				->method('doDevLog');

		$this->accessibleFixture->_call('devLog', 'Dummy test message');
	}

	/**
	 * Validate that sending a devlog message will be always prefixed with logger type.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatSendADevLogMessageWillAlwaysPrefixedWithLoggerTypeContextAndId() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('doDevLog', 'isLoggingEnabled', 'getType'));

		$this->accessibleFixture
				->expects($this->any())
				->method('isLoggingEnabled')
				->will($this->returnValue(TRUE));
		$this->accessibleFixture
				->expects($this->any())
				->method('getType')
				->will($this->returnValue('Dummy'));
		$this->accessibleFixture
				->expects($this->once())
				->method('doDevLog')
				->with($this->stringStartsWith('[DummyLocker][C:dummy|ID:dummy]'));

		$this->accessibleFixture->_call('devlog', 'Dummy test message');
	}

	/**
	 * Tests if constructor will set context given as expected.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatConstructorWillSetContextAsExpected() {
		$this->createFixture(array('foo', 'bar'));
		$this->assertSame('foo', $this->fixture->getContext());
	}

	/**
	 * Tests if constructor will set id given as expected.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatConstructorWillSetIdAsExpected() {
		$this->createFixture(array('foo', 'bar'));
		$this->assertSame('bar', $this->fixture->getId());
	}

	/**
	 * Tests if constructor will set retries as expected.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatConstructorWillSetRetriesAsExpected() {
		$this->createFixture(array('foo', 'bar', array('retries' => 345)));
		$this->assertSame(345, $this->fixture->getRetries());
	}

	/**
	 * Tests if constructor will set retryIntervall as expected.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatConstructorWillSetRetryIntervalAsExpected() {
		$this->createFixture(array('foo', 'bar', array('retryInterval' => 654)));
		$this->assertSame(654, $this->fixture->getRetryInterval());
	}

	/**
	 * Tests if constructor will set retryIntervall as expected.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatConstructorWillSetMaxLogAgeAsExpected() {
		$this->createFixture(array('foo', 'bar', array('maxLockAge' => 654)));
		$this->assertSame(654, $this->fixture->getMaxLockAge());
	}

	/**
	 * Returns array of default locker options.
	 *  Provides several test payloads.
	 *
	 * @return array
	 */
	public function defaultOptionsDataProvider() {
		return array(
			'option_retries' => array('retries'),
			'option_retryInterval' => array('retryInterval'),
			'option_logging' => array('logging'),
			'opion_maxLockAge' => array('maxLockAge'),
		);
	}

	/**
	 * Test validate that set an option will use setter if available.
	 *
	 * @param string $option
	 * @return void
	 * @dataProvider defaultOptionsDataProvider
	 * @test
	 */
	public function validateThatMethodSetOptionWillUseSetterIfAvailable($option) {
		$setterMethod = 'set' . ucfirst($option);
		$this->createFixture(array('dummy', 'dummy'), array($setterMethod));

		$this->fixture
				->expects($this->once())
				->method($setterMethod);

		$this->fixture->$setterMethod('dummy');
	}

	/**
	 * Test validate that setting an invalid option by method setOption will throw an invalid option exception.
	 *
	 * @return void
	 * @expectedException \TYPO3\CMS\Core\Locking\Exception\InvalidOptionException
	 * @test
	 */
	public function validateThatMethodSetOptionForAnInvalidOptionWillThrowAnInvalidOptionException() {
		$option = uniqid('set_option');
		$this->fixture->setOption($option, FALSE);
	}

	public function validateThatMethodSetOptionWillSetOptionsAsExpectedDataProvider() {
		return array(
			'non_existing_option' => array(
				uniqid('invalid_option'),
				array(),
				array(),
				'\TYPO3\CMS\Core\Locking\Exception\InvalidOptionException',
			),
			'non_string_option_name_array' => array(
				array(),
				array(),
				array(),
				'\TYPO3\CMS\Core\Locking\Exception\InvalidOptionException',
			),
			'non_string_option_name_object' => array(
				new \Exception(),
				'',
				'',
				'\TYPO3\CMS\Core\Locking\Exception\InvalidOptionException',
			),
		);
	}

	/**
	 * @param      $option
	 * @param      $optionValue
	 * @param      $expectedValue
	 * @param null $expectException
	 * @return void
	 * @dataProvider validateThatMethodSetOptionWillSetOptionsAsExpectedDataProvider
	 * @TODO
	 */
	public function validateThatMethodSetOptionWillSetOptionsAsExpected($option, $optionValue, $expectedValue, $expectException = NULL) {
		$this->createAccessableFixture($this->dummyConstructorArgs, array('log'));

		if ($expectException !== NULL) {
			$this->setExpectedException($expectException);
		}

		$this->accessibleFixture->setOption($option, $optionValue);
		$options = $this->accessibleFixture->_get('options');
		$this->assertSame($expectedValue, $options[$option]);
	}

	/**
	 * Test validate that get an option will use getter if available.
	 *
	 * @param string $option
	 * @return void
	 * @dataProvider defaultOptionsDataProvider
	 * @test
	 */
	public function validateThatMethodGetOptionWillUseGetterIfAvailable($option) {
		$getterMethod = 'get' . ucfirst($option);
		$this->createFixture(array('dummy', 'dummy'), array($getterMethod));

		$this->fixture
				->expects($this->once())
				->method($getterMethod);

		$this->fixture->$getterMethod('dummy');
	}

	/**
	 * Test validate that getting an invalid option by method getOption will throw an invalid option exception.
	 *
	 * @return void
	 * @expectedException \TYPO3\CMS\Core\Locking\Exception\InvalidOptionException
	 * @test
	 */
	public function validateThatMethodGetOptionForAnInvalidOptionWillThrowAnInvalidOptionException() {
		$option = uniqid('get_option');
		$this->fixture->getOption($option);
	}

	/**
	 * Test validate that get all options will use getter for each option if available.
	 *
	 * @param string $option
	 * @return void
	 * @dataProvider defaultOptionsDataProvider
	 * @test
	 */
	public function validateThatMethodGetOptionsWillUseGettersIfAvailable($option) {
		$optionGetters = array();
		array_walk($this->defaultOptionsDataProvider(), function ($val) use (&$optionGetters) {
			$optionGetters[] = 'get' . ucfirst($val[0]);
		});
		$this->createFixture(array('dummy', 'dummy'), $optionGetters);

		$getterMethod = 'get' . ucfirst($option);
		$this->fixture
				->expects($this->once())
				->method($getterMethod);

		$this->fixture->getOptions();
	}

	/**
	 * Test validate that set all options will use setter for each option if available.
	 *
	 * @param string $option
	 * @return void
	 * @dataProvider defaultOptionsDataProvider
	 * @test
	 */
	public function validateThatMethodSetOptionsWillUseSettersIfAvailable($option) {
		$optionGetters = array();
		array_walk($this->defaultOptionsDataProvider(), function ($val) use (&$optionGetters) {
			$optionGetters[] = 'set' . ucfirst($val[0]);
		});
		$this->createFixture(array('dummy', 'dummy'), $optionGetters);

		$setterMethod = 'set' . ucfirst($option);
		$this->fixture
				->expects($this->once())
				->method($setterMethod);

		$this->fixture->setOption($option, NULL);
	}

	/**
	 * Dataprovider for testing that retries must have a valid integer range.
	 *  Provides several test payloads.
	 *
	 * @return array
	 */
	public function validateThatMethodSetRetriesMustHaveAValidRangeDataProvider() {
		return array(
			array(
				-11, // retries
				0, // expected value
			),
			array(
				0, // retries
				0, // expected value
			),
			array(
				NULL, // retries
				0, // expected value
			),
			array(
				FALSE, // retries
				0, // expected value
			),
			array(
				array(), // retries
				0, // expected value
			),
			array(
				55, // retries
				55, // expected value
			),
			array(
				99999, // retries
				1000, // expected value
			),
			array(
				121.222, // retries
				121 // expected value
			),
			array(
				'28.222', // retries
				28, // expected value
			),
		);
	}

	/**
	 * Test validate that set retries must have a valid integer range.
	 *
	 * @param integer $retries
	 * @param integer $expectedValue
	 * @return void
	 * @dataProvider validateThatMethodSetRetriesMustHaveAValidRangeDataProvider
	 * @test
	 */
	public function validateThatMethodSetRetriesMustHaveAValidIntegerRange($retries, $expectedValue) {
		$this->fixture->setRetries($retries);
		$this->assertSame($expectedValue, $this->fixture->getRetries());
	}

	/**
	 * Dataprovider for testing that retryInterval must have a valid integer range.
	 *  Provides several test payloads.
	 *
	 * @return array
	 */
	public function validateThatMethodSetRetryIntervalMustHaveAValidIntegerRangeDataProvider() {
		return array(
			array(
				-11, // retryInterval
				1, // expected value
			),
			array(
				0, // retryInterval
				1, // expected value
			),
			array(
				NULL, // retryInterval
				1, // expected value
			),
			array(
				FALSE, // retryInterval
				1, // expected value
			),
			array(
				array(), // retryInterval
				1, // expected value
			),
			array(
				55, // retryInterval
				55, // expected value
			),
			array(
				12345678, // retryInterval
				99999, // expected value
			),
			array(
				121.222, // retryInterval
				121 // expected value
			),
			array(
				'28282.222', // retryInterval
				28282, // expected value
			),
		);
	}

	/**
	 * Test validate that set retryInterval must have a valid integer range.
	 *
	 * @param integer  $retryInterval
	 * @param integer  $expectedValue
	 * @return void
	 * @dataProvider validateThatMethodSetRetryIntervalMustHaveAValidIntegerRangeDataProvider
	 * @test
	 */
	public function validateThatMethodSetRetryIntervalMustHaveAValidIntegerRange($retryInterval, $expectedValue) {
		$this->fixture->setRetryInterval($retryInterval);
		$this->assertSame($expectedValue, $this->fixture->getRetryInterval());
	}

	/**
	 * Dataprovider for testing that maxLockAge must have a valid integer range.
	 *  Provides several test payloads.
	 *
	 * @return array
	 */
	public function validateThatMethodSetMaxLockAgeMustHaveAValidIntegerRangeDataProvider() {
		return array(
			array(
				-11, // maxLockAge
				1, // expected value
			),
			array(
				0, // maxLockAge
				1, // expected value
			),
			array(
				NULL, // maxLockAge
				1, // expected value
			),
			array(
				FALSE, // maxLockAge
				1, // expected value
			),
			array(
				array(), // maxLockAge
				1, // expected value
			),
			array(
				55, // maxLockAge
				55, // expected value
			),
			array(
				12345678, // maxLockAge
				12345678, // expected value
			),
			array(
				99000000000, // maxLockAge
				2000000000, // expected value
			),
			array(
				121.222, // maxLockAge
				121 // expected value
			),
			array(
				'28282.222', // maxLockAge
				28282, // expected value
			),
		);
	}

	/**
	 * Test validate that set maxLockAge must have a valid integer range.
	 *
	 * @param integer  $maxLockAge
	 * @param integer  $expectedValue
	 * @return void
	 * @dataProvider validateThatMethodSetMaxLockAgeMustHaveAValidIntegerRangeDataProvider
	 * @test
	 */
	public function validateThatMethodSetMaxLockAgeMustHaveAValidIntegerRange($maxLockAge, $expectedValue) {
		$this->fixture->setMaxLockAge($maxLockAge);
		$this->assertSame($expectedValue, $this->fixture->getMaxLockAge());
	}

	/**
	 * Dataprovider for sha1 hash generation testing.
	 *  Provides several test payloads.
	 *
	 * @return array
	 */
	public function validateThatMethodGetIdHashWillGenerateValidSha1HashDataProvider() {
		return array(
			array( // expected value results from: sha1('PHPUnitTest:dummy_1')
				'PHPUnitTest', // context
				'dummy_1', // id
				'252261774826f3d20bb03086861768b22dfe32c9', // expected value
			),
			array( // expected value results from: sha1('PHPUnitTest:dummy_2')
				'PHPUnitTest', // context
				'dummy_2', // id
				'431a7bd8ffaffbf8b8f7426b1ea58fd711f40466', // expected value
			),
		);
	}

	/**
	 * Test sha1 hash generation of context & id.
	 *
	 * @param string $context
	 * @param string $id
	 * @param string $expectedValue
	 * @return void
	 * @dataProvider validateThatMethodGetIdHashWillGenerateValidSha1HashDataProvider
	 * @test
	 */
	public function validateThatMethodGetIdHashWillGenerateValidSha1Hash($context, $id, $expectedValue) {
		$this->createFixture(array($context, $id));
		$this->assertSame($expectedValue, $this->fixture->getIdHash()); // expected value results from: sha1('foo:bar')
	}

	/**
	 * Dateprovider validation calculated retries used for acquiring will work as expected.
	 *  Provides several test payloads.
	 *
	 * @return array
	 */
	public function validateThatMethodCalcualteMaxRetriesUsedForAcquiringWillWorkAsExpectedDataProvider() {
		return array(
			'retries_higher_than_posible_time_1' => array(
				150, // retries
				200, // retryIntervall (milliseconds)
				30, // maxExecutionTime
				1360440703, // globalExecTime
				1360440703, // currentTime
				140 // expected value
			),
			'retries_higher_than_posible_time_2' => array(
				300, // retries
				400, // retryIntervall (milliseconds)
				55, // maxExecutionTime
				1360440703, // globalExecTime
				1360440703, // currentTime
				130 // expected value
			),
			'retries_smaller_than_they_are_possible_in_max_exec_time_1' => array(
				10, // retries
				200, // retryIntervall (milliseconds)
				30, // maxExecutionTime
				1360440703, // globalExecTime
				1360440703, // currentTime
				10 // expected value
			),
			'retries_smaller_than_they_are_possible_in_max_exec_time_2' => array(
				30, // retries
				200, // retryIntervall (milliseconds)
				60, // maxExecutionTime
				1360440703, // globalExecTime
				1360440703, // currentTime
				30 // expected value
			),
			'retries_will_be_reduced_if_time_was_already_consumed_from_other_scripts_1' => array(
				150, // retries
				200, // retryIntervall (milliseconds)
				30, // maxExecutionTime
				1000000000, // globalExecTime
				1000000010, // currentTime (10 seconds later)
				95 // expected value
			),
			'retries_will_be_reduced_if_time_was_already_consumed_from_other_scripts_2' => array(
				150, // retries
				200, // retryIntervall (milliseconds)
				40, // maxExecutionTime
				1000000000, // globalExecTime
				1000000030, // currentTime (30 seconds later)
				45 // expected value
			),
		);
	}

	/**
	 * Test calculated retries used for acquiring will work as expected.
	 *
	 * @param integer $retries
	 * @param integer $retryInterval
	 * @param integer $maxExecutionTime
	 * @param integer $globalExecTime
	 * @param integer $currentTime
	 * @param integer $expectedValue
	 * @return void
	 * @dataProvider validateThatMethodCalcualteMaxRetriesUsedForAcquiringWillWorkAsExpectedDataProvider
	 * @test
	 */
	public function validateThatMethodCalcualteMaxRetriesUsedForAcquiringWillWorkAsExpected($retries, $retryInterval, $maxExecutionTime, $globalExecTime, $currentTime, $expectedValue) {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('log', 'getMaxExecutionTime', 'getGlobalExecTime', 'getCurrentTime'));

		$this->accessibleFixture
				->expects($this->any())
				->method('getMaxExecutionTime')
				->will($this->returnValue($maxExecutionTime));
		$this->accessibleFixture
				->expects($this->any())
				->method('getGlobalExecTime')
				->will($this->returnValue($globalExecTime));
		$this->accessibleFixture
				->expects($this->any())
				->method('getCurrentTime')
				->will($this->returnValue($currentTime));

		$this->accessibleFixture->setRetries($retries);
		$this->accessibleFixture->setRetryInterval($retryInterval);
		$this->assertSame($expectedValue, $this->accessibleFixture->_call('calculateMaxRetriesForAcquireLoop'));
	}

	/**
	 * Test validate that, if option respectExecutionTime is off, method calculateMaxRetriesForAcquireLoop will return retries option value.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatMethodCalcualteMaxRetriesUsedForAcquiringWillReturnRetriesIfOptionRespectExecutionTimeIsDisabled() {
		$this->createAccessableFixture(
			array('dummy', 'dummy', array('retries' => 1000, 'retryInterval' => 99999, 'respectExecutionTime' => FALSE)),
			array('log', 'getMaxExecutionTime', 'getGlobalExecTime', 'getCurrentTime')
		);

		$this->accessibleFixture
				->expects($this->any())
				->method('getMaxExecutionTime')
				->will($this->returnValue(1));

		$this->assertSame(1000, $this->accessibleFixture->_call('calculateMaxRetriesForAcquireLoop'));
	}

	/**
	 * Test validate that, if max_execution_time will return null, method calculateMaxRetriesForAcquireLoop will return retries option value.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatMethodCalcualteMaxRetriesUsedForAcquiringWillReturnRetriesIfMaxExecutionTimeWillBeNull() {
		$this->createAccessableFixture(
			array('dummy', 'dummy', array('retries' => 1000, 'retryInterval' => 99999, 'respectExecutionTime' => TRUE)),
			array('log', 'getMaxExecutionTime', 'getGlobalExecTime', 'getCurrentTime')
		);

		$this->accessibleFixture
				->expects($this->any())
				->method('getMaxExecutionTime')
				->will($this->returnValue(0));
		$this->accessibleFixture
				->expects($this->never())
				->method('getGlobalExecTime');
		$this->accessibleFixture
				->expects($this->never())
				->method('getCurrentTime');

		$this->assertSame(1000, $this->accessibleFixture->_call('calculateMaxRetriesForAcquireLoop'));
	}

	/**
	 * Tests validate that method aquire won't be run twice or more, if lock was already acquired.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatMethodAquireWillDoNothingIfLockWasAlreadyAquired() {
		$this->createAccessableFixture($this->dummyConstructorArgs, array('doGarbageCollection', 'log', 'doAcquire'));

		$this->accessibleFixture->_set('isAcquired', TRUE);

		$this->accessibleFixture
				->expects($this->never())
				->method('doGarbageCollection');
		$this->accessibleFixture
				->expects($this->never())
				->method('doAquire');

		$this->assertTrue($this->accessibleFixture->acquire());
	}

	/**
	 * Test validate that cleanup stale locks will be called before real locking will be done.
	 *  Serves that stale locks will be removed.
	 *
	 *
	 * @return void
	 * @test
	 * @expectedException        LogicException
	 * @expectedExceptionMessage Thrown from "doCleanStaleLock" method.
	 * @expectedExceptionCode    0
	 */
	public function validateThatMethodAcquireWillRunGarbageCollectionBeforeDoAcquireMethod() {
		$this->createAccessableFixture($this->dummyConstructorArgs, array('doCleanStaleLock', 'log', 'doAcquire', 'waitForRetry'));

		$this->accessibleFixture->_set('isAcquired', FALSE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doCleanStaleLock')
				->will($this->throwException(new \LogicException('Thrown from "doCleanStaleLock" method.', 0)));
		$this->accessibleFixture
				->expects($this->any())
				->method('doAcquire')
				->will($this->throwException(new \LogicException('Thrown from "doAcquire" method.', 1)));

		$this->accessibleFixture->acquire();
	}

	/**
	 * Test validate that global garbage collection will be called if local stale lock was found.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatIfStaleLockWasFoundGlobalGarbageCollectionWillBeTriggered() {
		$this->createAccessableFixture($this->dummyConstructorArgs, array('log', 'doCleanStaleLock', 'doGarbageCollection', 'waitForRetry'));

		$this->accessibleFixture->_set('isAcquired', FALSE);

		$this->accessibleFixture
				->expects($this->atLeastOnce())
				->method('doCleanStaleLock')
				->will($this->returnValue(TRUE));
		$this->accessibleFixture
				->expects($this->atLeastOnce())
				->method('doGarbageCollection');

		// catch all exceptions, not tested yet
		try {
			$this->accessibleFixture->acquire();
		} catch (\Exception $e) {}
	}

	/**
	 * Dateprovider validation calculated retries used for acquiring will work as expected.
	 *  Provides several test payloads.
	 *
	 * @return array
	 */
	public function validateThatMethodCalculateMaxRetriesWillBeTriggeredForEachLoopDuringAquiringDataProvider() {
		return array(
			array(
				array(100, 75, 50, 25, 5), // consecutiveCallsOfCalculateMaxRetries
				6, // willExpectNCallsToCalucateMaxRetriesMethod
				5, // andWillExpectNCallsToDoAcquireMethod
			),
			array(
				array(1), // consecutiveCallsOfCalculateMaxRetries
				2, // willExpectNCallsToCalucateMaxRetriesMethod
				1, // andWillExpectNCallsToDoAcquireMethod
			),
			array(
				array(150, 100, 75, 50, 6, 6), // consecutiveCallsOfCalculateMaxRetries
				7, // willExpectNCallsToCalucateMaxRetriesMethod
				6, // andWillExpectNCallsToDoAcquireMethod
			),
		);
	}

	/**
	 * Test validates that calculateMaxRetriesForAcquireLoop method is called for each loop during acquiring.
	 *
	 * @param array   $consecutiveCallsOfCalculateMaxRetries
	 * @param integer $willExpectNCallsToCalucateMaxRetriesMethod
	 * @param integer $andWillExpectNCallsToDoAcquireMethod
	 * @return void
	 * @dataProvider validateThatMethodCalculateMaxRetriesWillBeTriggeredForEachLoopDuringAquiringDataProvider
	 * @test
	 */
	public function validateThatMethodCalculateMaxRetriesWillBeTriggeredForEachLoopDuringAquiring(array $consecutiveCallsOfCalculateMaxRetries, $willExpectNCallsToCalucateMaxRetriesMethod, $andWillExpectNCallsToDoAcquireMethod) {
		$this->createAccessableFixture($this->dummyConstructorArgs, array('log', 'doCleanStaleLock', 'calculateMaxRetriesForAcquireLoop', 'doAcquire', 'waitForRetry'));

		$this->accessibleFixture->_set('isAcquired', FALSE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doCleanStaleLock')
				->will($this->returnValue(FALSE));
		$this->accessibleFixture
				->expects($this->exactly($willExpectNCallsToCalucateMaxRetriesMethod))
				->method('calculateMaxRetriesForAcquireLoop')
				->will(call_user_func_array(array($this, 'onConsecutiveCalls'), $consecutiveCallsOfCalculateMaxRetries));
		$this->accessibleFixture
				->expects($this->exactly($andWillExpectNCallsToDoAcquireMethod))
				->method('doAcquire')
				->will($this->returnValue(FALSE));

		// catch all exceptions, not tested yet
		try {
			$this->accessibleFixture->acquire();
		} catch (\Exception $e) {}
	}

	/**
	 * Test validate that, if doAcquire will throw an lock block exception, looping will be triggered.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatIfMethodDoAcquireThrowALockBlockedExceptionLoopingWillBeTriggered() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('doCleanStaleLock', 'log', 'doAcquire', 'waitForRetry', 'calculateMaxRetriesForAcquireLoop'));

		$this->accessibleFixture->_set('isAcquired', FALSE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doCleanStaleLock')
				->will($this->returnValue(FALSE));
		$this->accessibleFixture
				->expects($this->any())
				->method('calculateMaxRetriesForAcquireLoop')
				->will($this->returnValue(5)); // one loop will be enough for this test
		$this->accessibleFixture
				->expects($this->once())
				->method('waitForRetry'); // do not sleep
		$this->accessibleFixture
				->expects($this->exactly(2))
				->method('doAcquire')
				->will($this->onConsecutiveCalls($this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockBlockedException(1)), TRUE));

		// catch all exceptions, not tested yet
		try {
			$this->accessibleFixture->acquire();
		} catch (\Exception $e) {}

	}

	/**
	 * Test is similar to 'validateThatIfMethodDoAcquireThrowALockBlockedExceptionLoopingWillBeTriggered', but doAcquire will not return anything
	 * and looping should be also trigger by a lock-blocked-exception.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatIfMethodDoAcquireWillReturnNothingAndWillNotThrowALockBlockedExceptionStillFallingIntoLooping() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('doCleanStaleLock', 'log', 'doAcquire', 'waitForRetry', 'calculateMaxRetriesForAcquireLoop'));

		$this->accessibleFixture->_set('isAcquired', FALSE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doCleanStaleLock')
				->will($this->returnValue(FALSE));
		$this->accessibleFixture
				->expects($this->any())
				->method('calculateMaxRetriesForAcquireLoop')
				->will($this->returnValue(2));
		$this->accessibleFixture
				->expects($this->exactly(2))
				->method('doAcquire')
				->will($this->returnValue(NULL));

		// catch all exceptions, not tested yet
		try {
			$this->accessibleFixture->acquire();
		} catch (\Exception $e) {}
	}

	/**
	 * Test validate that if doAcquire will not throw an exception and return nothing, notice to devlog is sent.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatIfMethodDoAcquireWillReturnNothingNoticeToDevLogIsSent() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('doCleanStaleLock', 'log', 'devLog', 'doAcquire', 'waitForRetry', 'calculateMaxRetriesForAcquireLoop'));

		$this->accessibleFixture->_set('isAcquired', FALSE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doCleanStaleLock')
				->will($this->returnValue(FALSE));
		$this->accessibleFixture
				->expects($this->any())
				->method('calculateMaxRetriesForAcquireLoop')
				->will($this->returnValue(1)); // one loop will be enough for this test
		$this->accessibleFixture
				->expects($this->once())
				->method('doAcquire')
				->will($this->returnValue(NULL));
		$this->accessibleFixture
				->expects($this->once())
				->method('devLog')
				->with($this->anything(), $this->equalTo(1));

		// catch all exceptions, not tested yet
		try {
			$this->accessibleFixture->acquire();
		} catch (\Exception $e) {}
	}

	/**
	 * Test validates that if doAcquire will return TRUE after first try, looping will be stopped.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatIfMethodDoAcquireWillSucceedOnFirstTryLoopingWillBeStopped() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('doCleanStaleLock', 'log', 'doAcquire', 'waitForRetry', 'calculateMaxRetriesForAcquireLoop'));

		$this->accessibleFixture->_set('isAcquired', FALSE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doCleanStaleLock')
				->will($this->returnValue(FALSE));
		$this->accessibleFixture
				->expects($this->any())
				->method('calculateMaxRetriesForAcquireLoop')
				->will($this->returnValue(5)); // looping 5 times
		$this->accessibleFixture
				->expects($this->exactly(1))
				->method('doAcquire')
				->will($this->returnValue(TRUE));


		$this->accessibleFixture->acquire();
	}

	/**
	 * Test validates that if doAcquire will return TRUE after x tries, looping will be stopped and a lock-delayed-exception is thrown.
	 *
	 * @return void
	 * @expectedException \TYPO3\CMS\Core\Locking\Exception\LockDelayedException
	 * @test
	 */
	public function validateThatIfMethodDoAcquireWillSucceedAfterXTriesLoopingWillBeStoppedAndLockDelayedExceptionWillBeThrown() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('doCleanStaleLock', 'log', 'doAcquire', 'waitForRetry', 'calculateMaxRetriesForAcquireLoop'));

		$this->accessibleFixture->_set('isAcquired', FALSE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doCleanStaleLock')
				->will($this->returnValue(FALSE));
		$this->accessibleFixture
				->expects($this->any())
				->method('calculateMaxRetriesForAcquireLoop')
				->will($this->returnValue(5)); // looping 5 times
		$this->accessibleFixture
				->expects($this->exactly(3))
				->method('doAcquire')
				->will(
					$this->onConsecutiveCalls(
						$this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockBlockedException(1)),
						$this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockBlockedException(2)),
						TRUE, // after third try, it should stop
						$this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockBlockedException(4)),
						$this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockBlockedException(5))
					)
				);

		$this->accessibleFixture->acquire();
	}

	/**
	 * Test validates that if acquire was successfull, locked property will be set to true.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatIfMethodAcquireWillSucceedPropertyWillSetToTrue() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('doCleanStaleLock', 'log', 'doAcquire', 'waitForRetry', 'calculateMaxRetriesForAcquireLoop'));

		$this->accessibleFixture->_set('isAcquired', FALSE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doCleanStaleLock')
				->will($this->returnValue(FALSE));
		$this->accessibleFixture
				->expects($this->any())
				->method('calculateMaxRetriesForAcquireLoop')
				->will($this->returnValue(5));
		$this->accessibleFixture
				->expects($this->any())
				->method('doAcquire')
				->will(
					$this->onConsecutiveCalls(
						$this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockBlockedException(1)),
						$this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockBlockedException(2)),
						$this->returnValue(TRUE)
					)
				);

		// catch all exceptions, not tested yet
		try {
			$this->accessibleFixture->acquire();
		} catch (\Exception $e) {}

		$this->assertTrue($this->accessibleFixture->_get('isAcquired'));
	}

	/**
	 * Test validates that if acquire was successfully on first try, method will return TRUE.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatIfMethodAcquireWillSucceedOnFirstTryAcquireMethodWillReturnTrue() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('doCleanStaleLock', 'log', 'doAcquire', 'waitForRetry', 'calculateMaxRetriesForAcquireLoop'));

		$this->accessibleFixture->_set('isAcquired', FALSE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doCleanStaleLock')
				->will($this->returnValue(FALSE));
		$this->accessibleFixture
				->expects($this->any())
				->method('calculateMaxRetriesForAcquireLoop')
				->will($this->returnValue(5));
		$this->accessibleFixture
				->expects($this->exactly(1))
				->method('doAcquire')
				->will($this->returnValue(TRUE));

		$this->assertTrue($this->accessibleFixture->acquire());
	}

	/**
	 * Test validates that if acquire fails, locked property will not be set to true.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatIfMethodAcquireFailsPropertyWillNotSetToTrue() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('doCleanStaleLock', 'log', 'doAcquire', 'waitForRetry', 'calculateMaxRetriesForAcquireLoop'));

		$this->accessibleFixture->_set('isAcquired', FALSE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doCleanStaleLock')
				->will($this->returnValue(FALSE));
		$this->accessibleFixture
				->expects($this->any())
				->method('calculateMaxRetriesForAcquireLoop')
				->will($this->returnValue(5));
		$this->accessibleFixture
				->expects($this->exactly(1))
				->method('doAcquire')
				->will($this->throwException(new \RuntimeException()));

		// catch all exceptions, not tested yet
		try {
			$this->accessibleFixture->acquire();
		} catch (\Exception $e) {}

		$this->assertFalse($this->accessibleFixture->_get('isAcquired'));
	}

	/**
	 * Test validate that if doAcquired throws an other exception than lock-could-not-be-acquired-exception, this exception will wrapped into this one.
	 *
	 * @return void
	 * @expectedException \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeAcquiredException
	 * @test
	 */
	public function validateThatIfMethodDoAcquireFailsWithAnOtherExceptionThanLockCouldNotBeAcquiredExceptionTheExceptionWillBeStillWrappedIntoAnLockCouldNotBeAcquiredException() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('doCleanStaleLock', 'log', 'doAcquire', 'waitForRetry', 'calculateMaxRetriesForAcquireLoop'));

		$this->accessibleFixture->_set('isAcquired', FALSE);

		$testException = new \LogicException();

		$this->accessibleFixture
				->expects($this->any())
				->method('doCleanStaleLock')
				->will($this->returnValue(FALSE));
		$this->accessibleFixture
				->expects($this->any())
				->method('calculateMaxRetriesForAcquireLoop')
				->will($this->returnValue(5));
		$this->accessibleFixture
				->expects($this->exactly(1))
				->method('doAcquire')
				->will($this->throwException($testException));

		// catch all exceptions, not tested yet
		try {
			$this->accessibleFixture->acquire();
		} catch (\Exception $e) {
			$this->assertSame($testException, $e->getPrevious());
			throw $e;
		}
	}

	/**
	 * Test validate that if acquire failed, a lock-could-not-be-acquired-exception is thrown.
	 *
	 * @return void
	 * @expectedException \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeAcquiredException
	 * @test
	 */
	public function validateThatIfMethodAcquireFailsLockCouldNotBeAcquiredExceptionWillBeThrown() {
		$this->createAccessableFixture($this->dummyConstructorArgs, array('doCleanStaleLock', 'log', 'doAcquire', 'waitForRetry', 'calculateMaxRetriesForAcquireLoop'));

		$this->accessibleFixture->_set('isAcquired', FALSE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doCleanStaleLock')
				->will($this->returnValue(FALSE));
		$this->accessibleFixture
				->expects($this->any())
				->method('calculateMaxRetriesForAcquireLoop')
				->will($this->returnValue(5));
		$this->accessibleFixture
				->expects($this->any())
				->method('doAcquire')
				->will($this->returnValue(FALSE));

		$this->accessibleFixture->acquire();
	}

	/**
	 * Test validate, that if doAcquire throws a lock-delayed-exception, acquire lock will be still interpreted as succeed
	 *
	 * @return void
	 * @test
	 */
	public function validateThatIfMethodDoAcquireMethodWillThrowALockDelayedExceptionAcquiringWillInterpretedAsSuccessfully() {
		$this->createAccessableFixture($this->dummyConstructorArgs, array('doCleanStaleLock', 'log', 'doAcquire', 'waitForRetry', 'calculateMaxRetriesForAcquireLoop'));

		$this->accessibleFixture->_set('isAcquired', FALSE);

		$this->accessibleFixture
				->expects($this->once())
				->method('doCleanStaleLock')
				->will($this->returnValue(FALSE));
		$this->accessibleFixture
				->expects($this->any())
				->method('calculateMaxRetriesForAcquireLoop')
				->will($this->returnValue(5));
		$this->accessibleFixture
				->expects($this->exactly(1))
				->method('doAcquire')
				->will($this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockDelayedException(1)));

		// catch all exceptions, not tested yet
		try {
			$this->accessibleFixture->acquire();
		} catch (\TYPO3\CMS\Core\Locking\Exception\LockDelayedException $e) {}

		$this->assertTrue($this->accessibleFixture->_get('isAcquired'));
	}

	/**
	 * Test validate, that if doAcquire throws a locked-delayed-exception, acquire method will pass exception through api.
	 *
	 * @return void
	 * @expectedException \TYPO3\CMS\Core\Locking\Exception\LockDelayedException
	 * @test
	 */
	public function validateThatIfMethodDoAcquireMethodWillThrowALockDelayedExceptionAcquiringWillBePassedThrough() {
		$this->createAccessableFixture($this->dummyConstructorArgs, array('doCleanStaleLock', 'log', 'doAcquire', 'waitForRetry', 'calculateMaxRetriesForAcquireLoop'));

		$this->accessibleFixture->_set('isAcquired', FALSE);

		$this->accessibleFixture
				->expects($this->once())
				->method('doCleanStaleLock')
				->will($this->returnValue(FALSE));
		$this->accessibleFixture
				->expects($this->any())
				->method('calculateMaxRetriesForAcquireLoop')
				->will($this->returnValue(5));
		$this->accessibleFixture
				->expects($this->exactly(1))
				->method('doAcquire')
				->will($this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockDelayedException(1)));

		$this->accessibleFixture->acquire();
	}

	/**
	 *
	 *
	 * @return array
	 */
	public function validateThatMethodCalculateMaxRetriesWillTakeAwareOfExecutionTimeOfDoAcquireInLoopCalculationDataProvider() {
		return array(
			array(
				30, // maxExecutionTime
				1361739000, // globalExecTime
				array(1361739010, 1361739012, 1361739014, 1361739016, 1361739018, 1361739020, 1361739022, 1361739024, 1361739026, 1361739028, 1361739030), // currentTime
				300, // retries
				200, // retryInterval
				9, // expected doAcquire calls
			),
			array(
				20, // maxExecutionTime
				1361739000, // globalExecTime
				array(1361739010, 1361739012, 1361739014, 1361739016, 1361739018, 1361739020, 1361739022, 1361739024, 1361739026, 1361739028, 1361739030), // currentTime
				300, // retries
				200, // retryInterval
				5, // expected doAcquire calls
			),
			array(
				32, // maxExecutionTime
				1361739000, // globalExecTime
				array(1361739010, 1361739012, 1361739014, 1361739016, 1361739018, 1361739020, 1361739022, 1361739024, 1361739026, 1361739028, 1361739030, 1361739032), // currentTime
				300, // retries
				200, // retryInterval
				10, // expected doAcquire calls
			),
		);
	}

	/**
	 *
	 *
	 * @param integer   $maxExecutionTime
	 * @param integer   $globalExecTime
	 * @param array     $currentTimeConsecutiveCalls
	 * @param integer   $retries
	 * @param integer   $retryInterval
	 * @param integer   $expectedDoAcquireCalls
	 * @return void
	 * @dataProvider validateThatMethodCalculateMaxRetriesWillTakeAwareOfExecutionTimeOfDoAcquireInLoopCalculationDataProvider
     * @test
	 */
	public function validateThatMethodCalculateMaxRetriesWillTakeAwareOfExecutionTimeOfDoAcquireInLoopCalculation(
		$maxExecutionTime,
		$globalExecTime,
		array $currentTimeConsecutiveCalls,
		$retries,
		$retryInterval,
		$expectedDoAcquireCalls
	) {
		$this->createAccessableFixture(
			$this->dummyConstructorArgs,
			array('log', 'doCleanStaleLock', 'doAcquire', 'waitForRetry', 'getMaxExecutionTime', 'getGlobalExecTime', 'getCurrentTime', 'getRetries')
		);

		$this->accessibleFixture->_set('isAcquired', FALSE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doCleanStaleLock')
				->will($this->returnValue(FALSE));
		$this->accessibleFixture
				->expects($this->any())
				->method('getRetries')
				->will($this->returnValue($retries));
		$this->accessibleFixture
				->expects($this->any())
				->method('getRetryInterval')
				->will($this->returnValue($retryInterval));
		$this->accessibleFixture
				->expects($this->any())
				->method('getMaxExecutionTime')
				->will($this->returnValue($maxExecutionTime));
		$this->accessibleFixture
				->expects($this->any())
				->method('getGlobalExecTime')
				->will($this->returnValue($globalExecTime));

		$currentTimeConsecutiveCallStub = call_user_func_array(array($this, 'onConsecutiveCalls'), $currentTimeConsecutiveCalls);
		$this->accessibleFixture
				->expects($this->any())
				->method('getCurrentTime')
				->will($currentTimeConsecutiveCallStub);

		$this->accessibleFixture
				->expects($this->exactly($expectedDoAcquireCalls))
				->method('doAcquire')
				->will($this->returnValue(FALSE));

		// catch all exceptions, not tested yet
		try {
			$this->accessibleFixture->acquire();
		} catch (\Exception $e) {}
	}

	/**
	 * Test validates that if locking could not be acquired on time and lock-could-not-be-acquired-on-time-exception will be thrown.
	 *
	 * @return void
	 * @expectedException \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeAcquiredOnTimeException
	 * @test
	 */
	public function validateThatIfMethodCalculatedMaxRetriesWillBeLowerThanProposedRetriesAndLockingCouldNotBeAcquiredWillThrowALockCouldNotBeAcquiredOnTimeException() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('doCleanStaleLock', 'log', 'doAcquire', 'calculateMaxRetriesForAcquireLoop', 'getRetries', 'waitForRetry'));

		$this->accessibleFixture->_set('isAcquired', FALSE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doCleanStaleLock')
				->will($this->returnValue(FALSE));
		$this->accessibleFixture
				->expects($this->any())
				->method('getRetries')
				->will($this->returnValue(20));
		$this->accessibleFixture
				->expects($this->any())
				->method('calculateMaxRetriesForAcquireLoop')
				->will($this->returnValue(5));
		$this->accessibleFixture
				->expects($this->any())
				->method('doAcquire')
				->will(
					$this->onConsecutiveCalls(
						$this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockBlockedException(1)),
						$this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockBlockedException(2)),
						$this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockBlockedException(3)),
						$this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockBlockedException(4)),
						$this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockBlockedException(5))
					)
				);

		$this->accessibleFixture->acquire();
	}

	/**
	 * Test validates that if locking could not be acquired wihtin proposed retries and lock-could-not-be-acquired-withing-proposed-retries-exception will be thrown.
	 *
	 * @return void
	 * @expectedException \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeAcquiredWithinProposedRetriesException
	 * @test
	 */
	public function validateThatIfProposedRetriesWasPassedAndLockingCouldNotBeAcquiredWillThrowALockCouldNotBeAcquiredWithinPoroposedRetriesException() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('doCleanStaleLock', 'log', 'doAcquire', 'calculateMaxRetriesForAcquireLoop', 'getRetries', 'waitForRetry'));

		$this->accessibleFixture->_set('isAcquired', FALSE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doCleanStaleLock')
				->will($this->returnValue(FALSE));
		$this->accessibleFixture
				->expects($this->any())
				->method('getRetries')
				->will($this->returnValue(3));
		$this->accessibleFixture
				->expects($this->any())
				->method('calculateMaxRetriesForAcquireLoop')
				->will($this->returnValue(3));
		$this->accessibleFixture
				->expects($this->any())
				->method('doAcquire')
				->will(
			$this->onConsecutiveCalls(
				$this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockBlockedException(1)),
				$this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockBlockedException(2)),
				$this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockBlockedException(3))
			)
		);

		$this->accessibleFixture->acquire();
	}

	/**
	 * Tests validate that a method release won't be run twice or more, if lock wasn't acquired yet.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatMethodReleaseWillDoNothingIfLockWasNotAcquired() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('log', 'doRelease'));

		$this->accessibleFixture->_set('isAcquired', FALSE);

		$this->accessibleFixture
				->expects($this->never())
				->method('doRelease');

		$this->accessibleFixture->release();
	}

	/**
	 * Test validate that method release will succeed property isAcquired will set to FALSE.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatIfMethodReleaseWillSucceedPropertyWasSetToFalse() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('log', 'doRelease'));

		$this->accessibleFixture->_set('isAcquired', TRUE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doRelease')
				->will($this->returnValue(TRUE));

		$this->accessibleFixture->release();

		$this->assertFalse($this->accessibleFixture->_get('isAcquired'));
	}

	/**
	 * Test validate that method release will succeed method will return TRUE.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatIfMethodReleaseWillSucceedReleaseMethodWillReturnTrue() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('log', 'doRelease'));

		$this->accessibleFixture->_set('isAcquired', TRUE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doRelease')
				->will($this->returnValue(TRUE));

		$this->assertTrue($this->accessibleFixture->release());
	}

	/**
	 * Test validate that if method release will fails property isAcquired will left TRUE.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatIfMethodReleaseWillFailsPropertyWasLeftTrue() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('log', 'doRelease'));

		$this->accessibleFixture->_set('isAcquired', TRUE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doRelease')
				->will($this->returnValue(FALSE));

		// catch all exceptions, not tested yet
		try {
			$this->accessibleFixture->release();
		} catch (\Exception $e) {}

		$this->assertTrue($this->accessibleFixture->_get('isAcquired'));
	}

	/**
	 *
	 *
	 * @return void
	 * @expectedException \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeReleasedException
	 * @test
	 */
	public function validateThatIfMethodReleaseFailsLockCouldNotBeReleasedExceptionWillBeThrown() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('log', 'doRelease'));

		$this->accessibleFixture->_set('isAcquired', TRUE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doRelease')
				->will($this->returnValue(FALSE));

		$this->accessibleFixture->release();
	}

	/**
	 *
	 *
	 * @return void
	 * @expectedException \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeReleasedException
	 * @test
	 */
	public function validateThatIfMethodDoReleaseThrowALockCouldNotBeReleasedExceptionTheExceptionWillBePassedThroughApi() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('log', 'doRelease'));

		$this->accessibleFixture->_set('isAcquired', TRUE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doRelease')
				->will($this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeReleasedException()));

		$this->accessibleFixture->release();
	}

	/**
	 *
	 *
	 * @return void
	 * @expectedException \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeReleasedException
	 * @test
	 */
	public function validateThatIfMethodDoReleaseFailsWithAnOtherExceptionThanLockCouldNotBeReleasedExceptionTheExceptionWillBeStillWrappedIntoAnLockCouldNotBeReleasedException() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('log', 'doRelease'));

		$this->accessibleFixture->_set('isAcquired', TRUE);

		$testException = new \RuntimeException();

		$this->accessibleFixture
				->expects($this->any())
				->method('doRelease')
				->will($this->throwException($testException));

		try {
			$this->accessibleFixture->release();
		} catch (\TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeReleasedException $e) {
			$this->assertSame($testException, $e->getPrevious());
			throw $e;
		}
	}

	/**
	 *
	 *
	 * @return void
	 * @test
	 */
	public function validateThatIfMethodDoReleaseThrowALockHasBeenAlreadyReleasedExceptionReleaseWillBeInterpretedAsSuccessfully() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('log', 'doRelease'));

		$this->accessibleFixture->_set('isAcquired', TRUE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doRelease')
				->will($this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockHasBeenAlreadyReleasedException()));

		// catch all exceptions, not tested yet
		try {
			$this->accessibleFixture->release();
		} catch (\Exception $e) {}

		$this->assertFalse($this->accessibleFixture->_get('isAcquired'));
	}

	/**
	 *
	 *
	 * @return void
	 * @expectedException \TYPO3\CMS\Core\Locking\Exception\LockHasBeenAlreadyReleasedException()
	 * @test
	 */
	public function validateThatIfMethodDoReleaseThrowALockHasBeenAlreadyReleasedExceptionTheExceptionWillBePassedThrough() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('log', 'doRelease'));

		$this->accessibleFixture->_set('isAcquired', TRUE);

		$this->accessibleFixture
				->expects($this->any())
				->method('doRelease')
				->will($this->throwException(new \TYPO3\CMS\Core\Locking\Exception\LockHasBeenAlreadyReleasedException()));

		$this->accessibleFixture->release();
	}

	/**
	 * Validate that shutdown method will not throw any exception, due it will causes php fatal errors.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatMethodShutdownWillCatchAllExceptions() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('log', 'doAcquire', 'preShutdown', 'postShutdown', 'getOption'));

		$this->accessibleFixture->_set('isAcquired', TRUE);
		$this->accessibleFixture
				->expects($this->any())
				->method('getOption')
				->with($this->equalTo('autoReleaseOnPHPShutdown'))
				->will($this->returnValue(TRUE));

		$this->accessibleFixture
				->expects($this->any())
				->method('doAcquire')
				->will($this->throwException(new \RuntimeException()));
		$this->accessibleFixture
				->expects($this->any())
				->method('preShutdown')
				->will($this->throwException(new \InvalidArgumentException()));
		$this->accessibleFixture
				->expects($this->any())
				->method('postShutdown')
				->will($this->throwException(new \LogicException()));

		$this->accessibleFixture->shutdown();
	}

	public function validateThatMethodShutdownWillOnlyPerformActionsIfOptionAutoReleaseOnPHPShutdownIsEnabledDataProvider() {
		return array(
			'disabled' => array(
				FALSE, // state
				0, // expectedDoReleaseCalls
			),
			'enabled' => array(
				TRUE, // state
				1, // expectedDoReleaseCalls
			),
		);
	}

	/**
	 *
	 *
	 * @param boolean $state
	 * @param integer $expectedDoReleaseCalls
	 * @return void
	 * @dataProvider validateThatMethodShutdownWillOnlyPerformActionsIfOptionAutoReleaseOnPHPShutdownIsEnabledDataProvider
	 * @test
	 */
	public function validateThatMethodShutdownWillOnlyPerformActionsIfOptionAutoReleaseOnPHPShutdownIsEnabled($state, $expectedDoReleaseCalls) {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('log', 'doRelease'));

		$this->accessibleFixture->_set('isAcquired', TRUE);
		$this->accessibleFixture->setOption('autoReleaseOnPHPShutdown', $state);

		$this->accessibleFixture
				->expects($this->exactly($expectedDoReleaseCalls))
				->method('doRelease');

		$this->accessibleFixture->shutdown();
	}

	public function validateThatMethodShutdownWillOnlyRunIfLockWasAcquiredDataProvider() {
		return array(
			'lock_not_acquired' => array(
				FALSE, // state
				0, // expectedDoReleaseCalls
			),
			'lock_acquired' => array(
				TRUE, // state
				1, // expectedDoReleaseCalls
			),
		);
	}

	/**
	 *
	 *
	 * @param boolean $state
	 * @param integer $expectedDoReleaseCalls
	 * @return void
	 * @dataProvider validateThatMethodShutdownWillOnlyRunIfLockWasAcquiredDataProvider
	 * @test
	 */
	public function validateThatMethodShutdownWillOnlyRunIfLockWasAcquired($state, $expectedDoReleaseCalls) {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('log', 'doRelease'));

		$this->accessibleFixture->_set('isAcquired', $state);
		$this->accessibleFixture->setOption('autoReleaseOnPHPShutdown', TRUE);

		$this->accessibleFixture
				->expects($this->exactly($expectedDoReleaseCalls))
				->method('doRelease');

		$this->accessibleFixture->shutdown();
	}

	/**
	 * Test validate that shutdown method will execute pre shutdown method (if exists) before doRelease() method.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatMethodShutdownWillExecutePreShutdownMethodBeforeDoReleaseMethodIfPreMethodAvailable() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('log', 'doRelease', 'getOption', 'preShutdown'));

		$this->accessibleFixture->_set('isAcquired', TRUE);
		$this->accessibleFixture
				->expects($this->any())
				->method('getOption')
				->with($this->equalTo('autoReleaseOnPHPShutdown'))
				->will($this->returnValue(TRUE));


		$this->accessibleFixture
				->expects($this->once())
				->method('preShutdown')
				->will($this->throwException(new \RuntimeException('Dummy exception to stop proceeding.')));
		$this->accessibleFixture
				->expects($this->never())
				->method('doRelease');

		$this->accessibleFixture->shutdown();
	}

	/**
	 * Test validate that shutdown method will execute post shutdown method (if exists) after doRelease() method.
	 *
	 * @return void
	 * @test
	 */
	public function validateThatMethodShutdownWillExecutePostShutdownMethodAfterDoReleaseMethodIfPostMethodAvailable() {
		$this->createAccessableFixture(array('dummy', 'dummy'), array('log', 'doRelease', 'getOption', 'preShutdown', 'postShutdown'));

		$this->accessibleFixture->_set('isAcquired', TRUE);
		$this->accessibleFixture
				->expects($this->any())
				->method('getOption')
				->with($this->equalTo('autoReleaseOnPHPShutdown'))
				->will($this->returnValue(TRUE));

		$this->accessibleFixture
				->expects($this->once())
				->method('preShutdown');
		$this->accessibleFixture
				->expects($this->once())
				->method('doRelease');
		$this->accessibleFixture
				->expects($this->once())
				->method('postShutdown')
				->will($this->throwException(new \RuntimeException('Dummy exception to stop proceeding.')));

		$this->accessibleFixture->shutdown();
	}

}

?>