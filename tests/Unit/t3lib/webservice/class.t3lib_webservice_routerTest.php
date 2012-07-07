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
class t3lib_webservice_RouterTest extends Tx_Phpunit_TestCase {

	/**
	 * @var t3lib_webservice_Router
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new t3lib_webservice_Router();
	}

	public function tearDown() {
		$this->fixture->setRoutes(array());
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function setRoutesGetRoutes() {
		$routes = array(
			'!^/(?P<name>\w+)/(?P<zahl>\d+)/$!' => 'FirstMatchingWebservice',
			'~^/(?P<name>\w+)/(?P<zahl>\d+)/$~' => 'SecondMatchingWebservice',
		);
		$this->fixture->setRoutes($routes);
		$this->assertSame($routes, $this->fixture->getRoutes());
	}

	/**
	 * @test
	 */
	public function addRoute() {
		$this->fixture->addRoute('/testroute/', 'TestClass');
		$routes = $this->fixture->getRoutes();
		$this->assertInternalType('array', $routes);
		$this->assertArrayHasKey('/testroute/', $routes);
		$this->assertEquals('TestClass', $routes['/testroute/']);
	}

	/**
	 * @test
	 */
	public function removeRoute() {
		$routes = array(
			'!^/(?P<name>\w+)/(?P<zahl>\d+)/$!' => 'FirstMatchingWebservice',
			'~^/(?P<name>\w+)/(?P<zahl>\d+)/$~' => 'SecondMatchingWebservice',
		);
		$this->fixture->setRoutes($routes);
		$this->fixture->removeRoute('~^/(?P<name>\w+)/(?P<zahl>\d+)/$~');
		$this->assertArrayNotHasKey('~^/(?P<name>\w+)/(?P<zahl>\d+)/$~', $this->fixture->getRoutes());
	}

	/**
	 * @test
	 */
	public function resolveRouteWithArguments() {
		$routes = array(
			'~^/(?P<name>\w+)/(?P<zahl>\d+)/$~' => 'SomeWebservice',
		);
		$resolvedRoute = $this->fixture->setRoutes($routes)->resolve('/test/1234/', $routes);
		$this->assertInternalType('array', $resolvedRoute);
		$this->assertArrayHasKey('webserviceClassName', $resolvedRoute);
		$this->assertEquals('SomeWebservice', $resolvedRoute['webserviceClassName']);
		$this->assertArrayHasKey('resolvedArguments', $resolvedRoute);
		$this->assertTrue(is_array($resolvedRoute['resolvedArguments']));
		$this->assertArrayHasKey('name', $resolvedRoute['resolvedArguments']);
		$this->assertEquals('test', $resolvedRoute['resolvedArguments']['name']);
		$this->assertArrayHasKey('zahl', $resolvedRoute['resolvedArguments']);
		$this->assertEquals('1234', $resolvedRoute['resolvedArguments']['zahl']);
	}

	/**
	 * @test
	 */
	public function returnFirstMatchingRoute() {
		$routes = array(
			'!^/(?P<name>\w+)/(?P<zahl>\d+)/$!' => 'FirstMatchingWebservice',
			'~^/(?P<name>\w+)/(?P<zahl>\d+)/$~' => 'SecondMatchingWebservice',
		);
		$resolvedRoute = $this->fixture->setRoutes($routes)->resolve('/test/1234/', $routes);
		$this->assertInternalType('array', $resolvedRoute);
		$this->assertArrayHasKey('webserviceClassName', $resolvedRoute);
		$this->assertEquals('FirstMatchingWebservice', $resolvedRoute['webserviceClassName']);
	}

	/**
	 * @test
	 */
	public function returnMatchingRoute() {
		$routes = array(
			'!^/this should never match/$!' => 'FirstMatchingWebservice',
			'~^/(?P<name>\w+)/(?P<zahl>\d+)/$~' => 'SecondMatchingWebservice',
			'!^/.*/$!' => 'ThirdMatchingWebservice',
		);
		$resolvedRoute = $this->fixture->setRoutes($routes)->resolve('/test/1234/', $routes);
		$this->assertInternalType('array', $resolvedRoute);
		$this->assertArrayHasKey('webserviceClassName', $resolvedRoute);
		$this->assertEquals('SecondMatchingWebservice', $resolvedRoute['webserviceClassName']);
	}

	/**
	 * @test
	 */
	public function returnNoMatchingRoute() {
		$routes = array(
			'!^/this should never match/$!' => 'FirstMatchingWebservice',
			'~^/(?P<name>\w+)/(?P<zahl>\d+)/$~' => 'SecondMatchingWebservice',
			'!^/typo3/$!' => 'ThirdMatchingWebservice',
		);
		$resolvedRoute = $this->fixture->setRoutes($routes)->resolve('/example/failing/route/', $routes);
		$this->assertEquals(NULL, $resolvedRoute);
	}

}

?>