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
			'FoTest' => array('FoTest', TRUE),
			'FoLowercasetest' => array('FoLowercasetest', FALSE),
			'DifferentClassTes' => array('DifferentClassTes', FALSE),
			'Test' => array('Test', TRUE),
			'FoFixture' => array('FoFixture', TRUE),
			'FoLowercasefixture' => array('FoLowercasefixture', FALSE),
			'DifferentClassFixtur' => array('DifferentClassFixtur', FALSE),
			'Fixture' => array('Fixture', TRUE),
			'Latest' => array('Latest', FALSE),
			'LaTest' => array('LaTest', TRUE),
			'Tx_RedirectTest_Domain_Model_Test' => array('Tx_RedirectTest_Domain_Model_Test', FALSE),
		);
	}

	/**
	 * @test
	 * @dataProvider isIgnoredClassNameIgnoresTestClassesDataProvider
	 *
	 * @param string $className
	 * @param bool $expectedResult
	 */
	public function isIgnoredClassNameIgnoresTestClasses($className, $expectedResult) {
		$generator = $this->getAccessibleMock(ClassLoadingInformationGenerator::class, ['dummy']);

		$this->assertEquals($expectedResult, $generator->_call('isIgnoredClassName', $className));
	}

}
