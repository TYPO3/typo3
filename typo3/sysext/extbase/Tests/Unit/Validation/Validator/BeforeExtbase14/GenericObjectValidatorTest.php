<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\BeforeExtbase14;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Test case
 *
 * This testcase checks the expected behavior for Extbase < 1.4.0, to make sure
 * we do not break backwards compatibility.
 */
class GenericObjectValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function isValidReturnsFalseIfTheValueIsNoObject() {
		$configurationManager = $this->getMock('TYPO3\CMS\Extbase\Configuration\ConfigurationManager', array('isFeatureEnabled'), array(), '', FALSE);
		$configurationManager->expects($this->any())->method('isFeatureEnabled')->with('rewrittenPropertyMapper')->will($this->returnValue(FALSE));
		$validator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\GenericObjectValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		$validator->injectConfigurationManager($configurationManager);
		$this->assertFalse($validator->isValid('foo'));
	}

	/**
	 * @test
	 */
	public function isValidChecksAllPropertiesForWhichAPropertyValidatorExists() {
		$configurationManager = $this->getMock('TYPO3\CMS\Extbase\Configuration\ConfigurationManager', array('isFeatureEnabled'), array(), '', FALSE);
		$configurationManager->expects($this->any())->method('isFeatureEnabled')->with('rewrittenPropertyMapper')->will($this->returnValue(FALSE));
		$mockPropertyValidators = array('foo' => 'validator', 'bar' => 'validator');
		$mockObject = new \stdClass();
		$validator = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\GenericObjectValidator', array('addError', 'isPropertyValid'), array(), '', FALSE);
		$validator->injectConfigurationManager($configurationManager);
		$validator->_set('propertyValidators', $mockPropertyValidators);
		$validator->expects($this->at(0))->method('isPropertyValid')->with($mockObject, 'foo')->will($this->returnValue(TRUE));
		$validator->expects($this->at(1))->method('isPropertyValid')->with($mockObject, 'bar')->will($this->returnValue(TRUE));
		$validator->isValid($mockObject);
	}
}
