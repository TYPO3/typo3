<?php

class webservice_Dispatcher_Test extends tx_phpunit_testcase {
	
	const REQUEST_STRING = 'acme.org/api/';


	protected $fixture = NULL;
	
	public function setUp() {
		$this->fixture = t3lib_div::makeInstance('t3lib_webservice_dispatcher');
	}

	public function tearDown() {
		unset($this->fixture);
	}
	
	/**
	 * @test
	 */
	public function dispatchMethodReturnsStandardOutputOnStandardInputTest() {	
		$this->assertTrue(true);
		//somethin' like: assert($this->dispatcherObject->output() "contains" 'some foobar output');
	}

	
}