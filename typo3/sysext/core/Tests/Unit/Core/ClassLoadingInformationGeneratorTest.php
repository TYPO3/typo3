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
use TYPO3\CMS\Core\Core\ClassLoadingInformationGenerator;
use TYPO3\CMS\Core\Tests\UnitTestCase;


/**
 * Testcase for the ClassLoadingInformationGenerator class
 */
class ClassLoadingInformationGeneratorTest extends UnitTestCase {

	/**
	 * Data provider with different class names.
	 *
	 * @return array
	 */
	public function isIgnoredClassNameIgnoresTestClassesDataProvider() {
		return array(
			array('FoTest', TRUE),
			array('FoLowercasetest', TRUE),
			array('DifferentClassTes', FALSE),
			array('Test', TRUE),
			array('FoFixture', TRUE),
			array('FoLowercasefixture', TRUE),
			array('DifferentClassFixtur', FALSE),
			array('Fixture', TRUE),
		);
	}

	/**
	 * @test
	 * @dataProvider isIgnoredClassNameIgnoresTestClassesDataProvider
	 */
	public function isIgnoredClassNameIgnoresTestClasses($path, $expectedResult) {
		$generator = $this->getAccessibleMock(ClassLoadingInformationGenerator::class, ['dummy']);

		$this->assertEquals($expectedResult, $generator->_call('isIgnoredClassName', $path));
	}

}
