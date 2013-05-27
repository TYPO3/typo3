<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Controller;

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
 * @covers \TYPO3\CMS\Extbase\Mvc\Controller\Argument
 */
class ArgumentTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\Argument
	 */
	protected $simpleValueArgument;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\Argument
	 */
	protected $objectArgument;

	protected $mockPropertyMapper;

	protected $mockConfigurationBuilder;

	protected $mockConfiguration;

	/**
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUp() {
		$this->simpleValueArgument = new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('someName', 'string');
		$this->objectArgument = new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('someName', 'DateTime');
		$this->mockPropertyMapper = $this->getMock('TYPO3\\CMS\\Extbase\\Property\\PropertyMapper');
		$this->simpleValueArgument->injectPropertyMapper($this->mockPropertyMapper);
		$this->objectArgument->injectPropertyMapper($this->mockPropertyMapper);
		$this->mockConfiguration = new \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfiguration();
		$propertyMappingConfiguranion = new \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfiguration();
		$this->simpleValueArgument->injectPropertyMappingConfiguration($propertyMappingConfiguranion);
		$this->objectArgument->injectPropertyMappingConfiguration($propertyMappingConfiguranion);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \InvalidArgumentException
	 */
	public function constructingArgumentWithoutNameThrowsException() {
		new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('', 'Text');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructingArgumentWithInvalidNameThrowsException() {
		new \TYPO3\CMS\Extbase\Mvc\Controller\Argument(new \ArrayObject(), 'Text');
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

	/**
	 * @return array
	 */
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
	 * @expectedException \InvalidArgumentException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @param string $invalidShortName
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
		$mockValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ValidatorInterface');
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
		$this->simpleValueArgument = new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('dummy', 'string');
		$this->enableRewrittenPropertyMapperInArgument($this->simpleValueArgument);
		$this->simpleValueArgument->setValue(NULL);
		$this->assertNull($this->simpleValueArgument->getValue());
	}

	/**
	 * @return array
	 */
	public function validClassNameDataTypes() {
		return array(
			array('Tx_Foo_Bar'),
			array('ExtbaseTeam\\BlogExample\\Foo\\Bar'),
		);
	}

	/**
	 * @test
	 * @dataProvider validClassNameDataTypes
	 */
	public function classSchemaIsBuiltForClassNameDataTypesWhenReflectionServiceIsInjected($dataType) {
		/** @var $argument \TYPO3\CMS\Extbase\Mvc\Controller\Argument|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$argument = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Argument', array('dummy'), array(), '', FALSE);
		$argument->_set('dataType', $dataType);
		$reflectionService = $this->getMock('TYPO3\\CMS\\Extbase\\Reflection\\ReflectionService');
		$reflectionService->expects($this->once())->method('getClassSchema')->with($dataType);
		$argument->injectReflectionService($reflectionService);
	}

	/**
	 * @return array
	 */
	public function primitiveOrBuiltInObjectsDataTypes() {
		return array(
			array('integer'),
			array('array'),
			array('DateTime'),
		);
	}

	/**
	 * @test
	 * @dataProvider primitiveOrBuiltInObjectsDataTypes
	 */
	public function classSchemaIsNotBuiltForPrimitiveDataTypesWhenReflectionServiceIsInjected($dataType) {
		/** @var $argument \TYPO3\CMS\Extbase\Mvc\Controller\Argument|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$argument = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Argument', array('dummy'), array(), '', FALSE);
		$argument->_set('dataType', $dataType);
		$reflectionService = $this->getMock('TYPO3\\CMS\\Extbase\\Reflection\\ReflectionService');
		$reflectionService->expects($this->never())->method('getClassSchema');
		$argument->injectReflectionService($reflectionService);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setValueUsesMatchingInstanceAsIs() {
		$this->enableRewrittenPropertyMapperInArgument($this->objectArgument);
		$this->mockPropertyMapper->expects($this->never())->method('convert');
		$this->objectArgument->setValue(new \DateTime());
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Mvc\Controller\Argument $this
	 */
	protected function setupPropertyMapperAndSetValue() {
		$this->mockPropertyMapper->expects($this->once())->method('convert')->with('someRawValue', 'string', $this->mockConfiguration)->will($this->returnValue('convertedValue'));
		$this->mockPropertyMapper->expects($this->once())->method('getMessages')->will($this->returnValue(new \TYPO3\CMS\Extbase\Error\Result()));
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
		$error = new \TYPO3\CMS\Extbase\Error\Error('Some Error', 1234);
		$mockValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ValidatorInterface', array('validate'));
		$validationMessages = new \TYPO3\CMS\Extbase\Error\Result();
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
	public function defaultPropertyMappingConfigurationDoesNotAllowCreationOrModificationOfObjects() {
		$this->assertNull($this->simpleValueArgument->getPropertyMappingConfiguration()->getConfigurationValue('TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter', \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
		$this->assertNull($this->simpleValueArgument->getPropertyMappingConfiguration()->getConfigurationValue('TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter', \TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
	}

	/**
	 * Helper which enables the new property mapper in the Argument class.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Controller\Argument $argument
	 */
	protected function enableRewrittenPropertyMapperInArgument(\TYPO3\CMS\Extbase\Mvc\Controller\Argument $argument) {
		$mockConfigurationManager = $this->getMock('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->any())->method('isFeatureEnabled')->with('rewrittenPropertyMapper')->will($this->returnValue(TRUE));
		$argument->injectConfigurationManager($mockConfigurationManager);
	}
}

?>