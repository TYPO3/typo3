<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2001 Thomas Maroschik <tmaroschik@dfau.de>
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
 * Enter descriptions here
 *
 * @author Thomas Maroschik<tmaroschik@dfau.de>
 */
class t3lib_webservice_DispatcherTest extends Tx_Phpunit_TestCase {

	/**
	 * @var t3lib_webservice_Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @var t3lib_webservice_Router
	 */
	protected $router;

	public function setUp() {
		$this->router = t3lib_div::makeInstance('t3lib_webservice_Router');
		$this->dispatcher = t3lib_div::makeInstance('t3lib_webservice_Dispatcher');
	}

	public function tearDown() {
		$this->router->setRoutes(array());
		unset($this->router, $this->dispatcher);
	}

	/**
	 * @test
	 */
	public function addARouteViaStaticMethod() {
		t3lib_webservice_Dispatcher::addRoute('~someRoute~', 'SomeWebservice');
		/** @var t3lib_webservice_Router $router */
		$router = t3lib_div::makeInstance('t3lib_webservice_Router');
		$routes = $router->getRoutes();
		$this->assertInternalType('array', $routes);
		$this->assertArrayHasKey('~someRoute~', $routes);
		$this->assertEquals('SomeWebservice', $routes['~someRoute~']);
	}

	/**
	 * @test
	 */
	public function removeARouteViaStaticMethod() {
		/** @var t3lib_webservice_Router $router */
		$router = t3lib_div::makeInstance('t3lib_webservice_Router');
		$router->setRoutes(array(
			'!^/(?P<name>\w+)/(?P<zahl>\d+)/$!' => 'FirstMatchingWebservice',
			'~^/(?P<name>\w+)/(?P<zahl>\d+)/$~' => 'SecondMatchingWebservice',
		));
		t3lib_webservice_Dispatcher::removeRoute('!^/(?P<name>\w+)/(?P<zahl>\d+)/$!');
		$returnedRoutes = $router->getRoutes();
		$this->assertInternalType('array', $returnedRoutes);
		$this->assertArrayNotHasKey('!^/(?P<name>\w+)/(?P<zahl>\d+)/$!', $returnedRoutes);
		$this->assertNotEquals('SomeWebservice', $returnedRoutes['!^/(?P<name>\w+)/(?P<zahl>\d+)/$!']);
	}

	/**
	 * @test
	 */
	public function dispatchSimpleWebservice() {
		require_once(__DIR__ . '/fixtures/class.t3lib_webservice_simplewebserviceFixture.php');
		t3lib_webservice_Dispatcher::addRoute('!^/(?P<name>\w+)/(?P<zahl>\d+)/$!', 't3lib_webservice_simpleWebserviceFixture');
		$_SERVER['REQUEST_URI'] = '/foo/312/';
		ob_start();
		$this->dispatcher->dispatch($_SERVER['REQUEST_URI']);
		$this->assertEquals('foo,312', ob_get_contents());
		ob_end_clean();
	}

}
?>