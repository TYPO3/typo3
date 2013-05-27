<?php

/*                                                                        *
 * This script belongs to the Extbase framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the MVC Controller Argument
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @covers Tx_Extbase_MVC_Controller_Argument
 */
class Tx_Extbase_Tests_Unit_MVC_Controller_ArgumentTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Extbase_MVC_Controller_Argument
	 */
	protected $simpleValueArgument;

	/**
	 * @var Tx_Extbase_MVC_Controller_Argument
	 */
	protected $objectArgument;

	protected $mockPropertyMapper;
	protected $mockConfigurationBuilder;
	protected $mockConfiguration;

	/**
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUp() {
		$this->simpleValueArgument = new Tx_Extbase_MVC_Controller_Argument('someName', 'string');
		$this->objectArgument = new Tx_Extbase_MVC_Controller_Argument('someName', 'DateTime');

		$this->mockPropertyMapper = $this->getMock('Tx_Extbase_Property_PropertyMapper');
		$this->simpleValueArgument->injectPropertyMapper($this->mockPropertyMapper);
		$this->objectArgument->injectPropertyMapper($this->mockPropertyMapper);

		$this->mockConfigurationBuilder = $this->getMock('Tx_Extbase_Property_PropertyMappingConfigurationBuilder');
		$this->mockConfiguration = $this->getMock('Tx_Extbase_Property_PropertyMappingConfigurationInterface');
		$this->mockConfigurationBuilder->expects($this->any())->method('build')->with('Tx_Extbase_MVC_Controller_MvcPropertyMappingConfiguration')->will($this->returnValue($this->mockConfiguration));

		$this->simpleValueArgument->injectPropertyMappingConfigurationBuilder($this->mockConfigurationBuilder);
		$this->objectArgument->injectPropertyMappingConfigurationBuilder($this->mockConfigurationBuilder);

		$this->simpleValueArgument->initializeObject();
		$this->objectArgument->initializeObject();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException InvalidArgumentException
	 */
	public function constructingArgumentWithoutNameThrowsException() {
		new Tx_Extbase_MVC_Controller_Argument('', 'Text');
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructingArgumentWithInvalidNameThrowsException() {
		new Tx_Extbase_MVC_Controller_Argument(new ArrayObject(), 'Text');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function passingDataTypeToConstructorReallySetsTheDataType() {
		$this->assertEquals('string', $this->simpleValueArgument->getDataType(), 'The specified data type has not been set correctly.');
		$this->assertEquals('someName', $this->simpleValueArgument->getName(), 'The specified name has not been set correctly.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setShortNameProvidesFluentInterface() {
		$returnedArgument = $this->simpleValueArgument->setShortName('x');
		$this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
	}

	public function invalidShortNames() {
		return array(
			array(''),
			array('as'),
			array(5)
		);
	}
	/**
	 * @test
	 * @dataProvider invalidShortNames
	 * @expectedException InvalidArgumentException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function shortNameShouldThrowExceptionIfInvalid($invalidShortName) {
		$this->simpleValueArgument->setShortName($invalidShortName);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function shortNameCanBeRetrievedAgain() {
		$this->simpleValueArgument->setShortName('x');
		$this->assertEquals('x', $this->simpleValueArgument->getShortName());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setRequiredShouldProvideFluentInterfaceAndReallySetRequiredState() {
		$returnedArgument = $this->simpleValueArgument->setRequired(TRUE);
		$this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
		$this->assertTrue($this->simpleValueArgument->isRequired());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setDefaultValueShouldProvideFluentInterfaceAndReallySetDefaultValue() {
		$returnedArgument = $this->simpleValueArgument->setDefaultValue('default');
		$this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
		$this->assertSame('default', $this->simpleValueArgument->getDefaultValue());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setValidatorShouldProvideFluentInterfaceAndReallySetValidator() {
		$mockValidator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface');
		$returnedArgument = $this->simpleValueArgument->setValidator($mockValidator);
		$this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
		$this->assertSame($mockValidator, $this->simpleValueArgument->getValidator());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setValueProvidesFluentInterface() {
		$this->enableRewrittenPropertyMapperInArgument($this->simpleValueArgument);
		$returnedArgument = $this->simpleValueArgument->setValue(NULL);
		$this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
	}


	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setValueUsesNullAsIs() {
		$this->simpleValueArgument = new Tx_Extbase_MVC_Controller_Argument('dummy', 'string');
		$this->enableRewrittenPropertyMapperInArgument($this->simpleValueArgument);
		$this->simpleValueArgument->setValue(NULL);
		$this->assertNull($this->simpleValueArgument->getValue());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setValueUsesMatchingInstanceAsIs() {
		$this->enableRewrittenPropertyMapperInArgument($this->objectArgument);
		$this->mockPropertyMapper->expects($this->never())->method('convert');
		$this->objectArgument->setValue(new DateTime());
	}

	protected function setupPropertyMapperAndSetValue() {
		$this->mockPropertyMapper->expects($this->once())->method('convert')->with('someRawValue', 'string', $this->mockConfiguration)->will($this->returnValue('convertedValue'));
		$this->mockPropertyMapper->expects($this->once())->method('getMessages')->will($this->returnValue(new Tx_Extbase_Error_Result()));
		return $this->simpleValueArgument->setValue('someRawValue');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setValueShouldCallPropertyMapperCorrectlyAndStoreResultInValue() {
		$this->enableRewrittenPropertyMapperInArgument($this->simpleValueArgument);
		$this->setupPropertyMapperAndSetValue();
		$this->assertSame('convertedValue', $this->simpleValueArgument->getValue());
		$this->assertTrue($this->simpleValueArgument->isValid());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setValueShouldBeFluentInterface() {
		$this->enableRewrittenPropertyMapperInArgument($this->simpleValueArgument);
		$this->assertSame($this->simpleValueArgument, $this->setupPropertyMapperAndSetValue());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setValueShouldSetValidationErrorsIfValidatorIsSetAndValidationFailed() {
		$this->enableRewrittenPropertyMapperInArgument($this->simpleValueArgument);
		$error = new Tx_Extbase_Error_Error('Some Error', 1234);

		$mockValidator = $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface', array('validate'));
		$validationMessages = new Tx_Extbase_Error_Result();
		$validationMessages->addError($error);
		$mockValidator->expects($this->once())->method('validate')->with('convertedValue')->will($this->returnValue($validationMessages));

		$this->simpleValueArgument->setValidator($mockValidator);
		$this->setupPropertyMapperAndSetValue();
		$this->assertFalse($this->simpleValueArgument->isValid());
		$this->assertEquals(array($error), $this->simpleValueArgument->getValidationResults()->getErrors());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function defaultPropertyMappingConfigurationShouldBeFetchable() {
		$this->assertSame($this->mockConfiguration, $this->simpleValueArgument->getPropertyMappingConfiguration());
	}

	/**
	 * Helper which enables the new property mapper in the Argument class.
	 *
	 * @param Tx_Extbase_MVC_Controller_Argument $argument
	 */
	protected function enableRewrittenPropertyMapperInArgument(Tx_Extbase_MVC_Controller_Argument $argument) {
		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->any())->method('isFeatureEnabled')->with('rewrittenPropertyMapper')->will($this->returnValue(TRUE));
		$argument->injectConfigurationManager($mockConfigurationManager);
	}
}
?>