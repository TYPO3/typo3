<?php
namespace TYPO3\CMS\Core\Tests\Unit\Core;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Core\Core\ApplicationContext;

/**
 * Testcase for the ApplicationContext class
 */
class ApplicationContextTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Data provider with allowed contexts.
	 *
	 * @return array
	 */
	public function allowedContexts() {
		return array(
			array('Production'),
			array('Testing'),
			array('Development'),

			array('Development/MyLocalComputer'),
			array('Development/MyLocalComputer/Foo'),
			array('Production/SpecialDeployment/LiveSystem'),
		);
	}

	/**
	 * @test
	 * @dataProvider allowedContexts
	 */
	public function contextStringCanBeSetInConstructorAndReadByCallingToString($allowedContext) {
		$context = new ApplicationContext($allowedContext);
		$this->assertSame($allowedContext, (string)$context);
	}

	/**
	 * Data provider with forbidden contexts.
	 *
	 * @return array
	 */
	public function forbiddenContexts() {
		return array(
			array('MySpecialContexz'),
			array('Testing123'),
			array('DevelopmentStuff'),
			array('DevelopmentStuff/FooBar'),
		);
	}

	/**
	 * @test
	 * @dataProvider forbiddenContexts
	 * @expectedException \TYPO3\CMS\Core\Exception
	 */
	public function constructorThrowsExceptionIfMainContextIsForbidden($forbiddenContext) {
		new ApplicationContext($forbiddenContext);
	}

	/**
	 * Data provider with expected is*() values for various contexts.
	 *
	 * @return array
	 */
	public function isMethods() {
		return array(
			'Development' => array(
				'contextName' => 'Development',
				'isDevelopment' => TRUE,
				'isProduction' => FALSE,
				'isTesting' => FALSE,
				'parentContext' => NULL
			),
			'Development/YourSpecialContext' => array(
				'contextName' => 'Development/YourSpecialContext',
				'isDevelopment' => TRUE,
				'isProduction' => FALSE,
				'isTesting' => FALSE,
				'parentContext' => 'Development'
			),

			'Production' => array(
				'contextName' => 'Production',
				'isDevelopment' => FALSE,
				'isProduction' => TRUE,
				'isTesting' => FALSE,
				'parentContext' => NULL
			),
			'Production/MySpecialContext' => array(
				'contextName' => 'Production/MySpecialContext',
				'isDevelopment' => FALSE,
				'isProduction' => TRUE,
				'isTesting' => FALSE,
				'parentContext' => 'Production'
			),

			'Testing' => array(
				'contextName' => 'Testing',
				'isDevelopment' => FALSE,
				'isProduction' => FALSE,
				'isTesting' => TRUE,
				'parentContext' => NULL
			),
			'Testing/MySpecialContext' => array(
				'contextName' => 'Testing/MySpecialContext',
				'isDevelopment' => FALSE,
				'isProduction' => FALSE,
				'isTesting' => TRUE,
				'parentContext' => 'Testing'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider isMethods
	 */
	public function contextMethodsReturnTheCorrectValues($contextName, $isDevelopment, $isProduction, $isTesting, $parentContext) {
		$context = new ApplicationContext($contextName);
		$this->assertSame($isDevelopment, $context->isDevelopment());
		$this->assertSame($isProduction, $context->isProduction());
		$this->assertSame($isTesting, $context->isTesting());
		$this->assertSame((string)$parentContext, (string)$context->getParent());
	}

	/**
	 * @test
	 */
	public function parentContextIsConnectedRecursively() {
		$context = new ApplicationContext('Production/Foo/Bar');
		$parentContext = $context->getParent();
		$this->assertSame('Production/Foo', (string) $parentContext);

		$rootContext = $parentContext->getParent();
		$this->assertSame('Production', (string) $rootContext);
	}
}
