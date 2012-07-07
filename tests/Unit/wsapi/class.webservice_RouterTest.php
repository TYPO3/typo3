<?php

class webservice_Router_Test extends Tx_Phpunit_TestCase {
	const REQUEST_URI = 'http://acme.com/api/rest/just/some/foo/uri';
	
	protected $fixture = NULL;
	
	public function setUp() {
		$this->fixture = t3lib_div::makeInstance('t3lib_webservice_router');
	}
	
	public function tearDown() {
		unset($this->fixture);
	}
	
	/**
	 * @test
	 */
	public function testIfRouterResolvesRightExtensionNameFromRequestUri() {
		$extName = $this->fixture->resolveExtension(self::REQUEST_URI);
		$this->assertEquals('rest', $extName);
	}
}