<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Test for the Abstract Form view helper
 */
class AbstractFormFieldViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\FormFieldViewHelperBaseTestcase
{
    /**
     * @test
     */
    public function getRespectSubmittedDataValueInitiallyReturnsFalse()
    {
        $formViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $this->assertFalse($formViewHelper->_call('getRespectSubmittedDataValue'));
    }

    /**
     * @test
     */
    public function setRespectSubmittedDataValueToTrue()
    {
        $formViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->_set('respectSubmittedDataValue', true);
        $this->assertTrue($formViewHelper->_call('getRespectSubmittedDataValue'));
    }

    /**
     * @test
     */
    public function getValueAttributeBuildsValueFromPropertyAndFormObjectIfInObjectAccessorModeAndRespectSubmittedDataValueSetFalse()
    {
        $formViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class,
            ['isObjectAccessorMode', 'addAdditionalIdentityPropertiesIfNeeded'], [], '', false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->_set('respectSubmittedDataValue', false);

        $mockObject = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\ClassWithTwoGetters();

        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('get')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject'
        )->will($this->returnValue($mockObject));
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('exists')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject'
        )->will($this->returnValue(true));

        $arguments = ['name' => null, 'value' => null, 'property' => 'value.something'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'MyString';
        $actual = $formViewHelper->_call('getValueAttribute');
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function ifAnAttributeValueIsAnObjectMaintainedByThePersistenceManagerItIsConvertedToAUID()
    {
        $mockPersistenceManager = $this->getMock(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will(
            $this->returnValue('6f487e40-4483-11de-8a39-0800200c9a66')
        );

        $object = $this->getMock(\TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\EmptyClass::class);
        $object->expects($this->any())->method('FLOW3_Persistence_isNew')->will($this->returnValue(false));

        $formViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['dummy'], [], '', false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->_set('persistenceManager', $mockPersistenceManager);

        $arguments = ['name' => 'foo', 'value' => $object, 'property' => null];
        $formViewHelper->_set('arguments', $arguments);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));

        $this->assertSame('foo[__identity]', $formViewHelper->_call('getName'));
        $this->assertSame('6f487e40-4483-11de-8a39-0800200c9a66', $formViewHelper->_call('getValue'));
    }

    /**
     * @test
     */
    public function getNameBuildsNameFromFieldNamePrefixFormObjectNameAndPropertyIfInObjectAccessorMode()
    {
        $formViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->at(0))->method('get')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObjectName'
        )->will($this->returnValue('myObjectName'));
        $this->viewHelperVariableContainer->expects($this->at(1))->method('exists')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix'
        )->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->at(2))->method('get')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix'
        )->will($this->returnValue('formPrefix'));

        $arguments = ['name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'formPrefix[myObjectName][bla]';
        $actual = $formViewHelper->_call('getName');
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getNameBuildsNameFromFieldNamePrefixFormObjectNameAndHierarchicalPropertyIfInObjectAccessorMode()
    {
        $formViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->at(0))->method('get')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObjectName'
        )->will($this->returnValue('myObjectName'));
        $this->viewHelperVariableContainer->expects($this->at(1))->method('exists')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix'
        )->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->at(2))->method('get')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix'
        )->will($this->returnValue('formPrefix'));

        $arguments = ['name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla.blubb'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'formPrefix[myObjectName][bla][blubb]';
        $actual = $formViewHelper->_call('getName');
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getNameBuildsNameFromFieldNamePrefixAndPropertyIfInObjectAccessorModeAndNoFormObjectNameIsSpecified(
    ) {
        $formViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->at(0))->method('get')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObjectName'
        )->will($this->returnValue(null));
        $this->viewHelperVariableContainer->expects($this->at(1))->method('exists')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix'
        )->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->at(2))->method('get')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix'
        )->will($this->returnValue('formPrefix'));

        $arguments = ['name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'formPrefix[bla]';
        $actual = $formViewHelper->_call('getName');
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getNameBuildsNameFromFieldNamePrefixAndFieldNameIfNotInObjectAccessorMode()
    {
        $formViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));
        $this->viewHelperVariableContainer->expects($this->at(0))->method('exists')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix'
        )->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->at(1))->method('get')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix'
        )->will($this->returnValue('formPrefix'));

        $arguments = ['name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'formPrefix[fieldName]';
        $actual = $formViewHelper->_call('getName');
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getValueBuildsValueFromPropertyAndFormObjectIfInObjectAccessorMode()
    {
        $formViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class,
            ['isObjectAccessorMode', 'addAdditionalIdentityPropertiesIfNeeded'], [], '', false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $mockObject = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\ClassWithTwoGetters();

        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $formViewHelper->expects($this->once())->method('addAdditionalIdentityPropertiesIfNeeded');
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('get')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject'
        )->will($this->returnValue($mockObject));
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('exists')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject'
        )->will($this->returnValue(true));

        $arguments = ['name' => null, 'value' => null, 'property' => 'value.something'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'MyString';
        $actual = $formViewHelper->_call('getValue');
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getValueReturnsNullIfNotInObjectAccessorModeAndValueArgumentIsNoSet()
    {
        $formViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));

        $mockArguments = [];
        $formViewHelper->_set('arguments', $mockArguments);

        $this->assertNull($formViewHelper->_call('getValue'));
    }

    /**
     * @test
     */
    public function getValueReturnsValueArgumentIfSpecified()
    {
        $formViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '',
            false
        );
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $mockArguments = ['value' => 'someValue'];
        $formViewHelper->_set('arguments', $mockArguments);

        $this->assertEquals('someValue', $formViewHelper->_call('getValue'));
    }

    /**
     * @test
     */
    public function getValueConvertsObjectsToIdentifiersByDefault()
    {
        $mockObject = $this->getMock('stdClass');

        $mockPersistenceManager = $this->getMock(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->atLeastOnce())->method('getIdentifierByObject')->with(
            $mockObject
        )->will($this->returnValue('6f487e40-4483-11de-8a39-0800200c9a66'));

        $formViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '',
            false
        );
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->_set('persistenceManager', $mockPersistenceManager);

        $mockArguments = ['value' => $mockObject];
        $formViewHelper->_set('arguments', $mockArguments);

        $this->assertSame('6f487e40-4483-11de-8a39-0800200c9a66', $formViewHelper->_call('getValue'));
    }

    /**
     * @test
     */
    public function getValueDoesNotConvertObjectsIfConvertObjectsIsFalse()
    {
        $mockObject = $this->getMock('stdClass');

        $mockPersistenceManager = $this->getMock(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will(
            $this->returnValue('6f487e40-4483-11de-8a39-0800200c9a66')
        );

        $formViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['isObjectAccessorMode'], [], '',
            false
        );
        $formViewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(false));
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->_set('persistenceManager', $mockPersistenceManager);

        $mockArguments = ['value' => $mockObject];
        $formViewHelper->_set('arguments', $mockArguments);

        $this->assertSame($mockObject, $formViewHelper->_call('getValue', false));
    }

    /**
     * @test
     */
    public function isObjectAccessorModeReturnsTrueIfPropertyIsSetAndFormObjectIsGiven()
    {
        $formViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['dummy'], [], '', false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $this->viewHelperVariableContainer->expects($this->once())->method('exists')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObjectName'
        )->will($this->returnValue(true));

        $formViewHelper->_set('arguments', ['name' => null, 'value' => null, 'property' => 'bla']);
        $this->assertTrue($formViewHelper->_call('isObjectAccessorMode'));

        $formViewHelper->_set('arguments', ['name' => null, 'value' => null, 'property' => null]);
        $this->assertFalse($formViewHelper->_call('isObjectAccessorMode'));
    }

    /**
     * @test
     */
    public function addAdditionalIdentityPropertiesIfNeededDoesNotCreateAnythingIfPropertyIsWithoutDot()
    {
        $formFieldViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['renderHiddenIdentityField'], [], '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $arguments = ['property' => 'simple'];
        $formFieldViewHelper->expects($this->any())->method('renderHiddenIdentityField')->will(
            $this->throwException(new \Exception('Should not be executed!!!'))
        );
        $formFieldViewHelper->_set('arguments', $arguments);
        $formFieldViewHelper->_call('addAdditionalIdentityPropertiesIfNeeded');
    }

    /**
     * @test
     */
    public function addAdditionalIdentityPropertiesIfNeededCallsRenderIdentityFieldWithTheRightParameters()
    {
        $mockFormObject = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\ClassWithTwoGetters();
        $property = 'value.something';
        $objectName = 'myObject';
        $expectedProperty = 'myObject[value]';

        $formFieldViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class,
            ['renderHiddenIdentityField', 'isObjectAccessorMode'], [], '', false
        );
        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $arguments = ['property' => $property];
        $formFieldViewHelper->_set('arguments', $arguments);
        $formFieldViewHelper->expects($this->atLeastOnce())->method('isObjectAccessorMode')->will(
            $this->returnValue(true)
        );
        $this->viewHelperVariableContainer->expects($this->at(0))->method('exists')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject'
        )->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->at(1))->method('get')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject'
        )->will($this->returnValue($mockFormObject));
        $this->viewHelperVariableContainer->expects($this->at(2))->method('get')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObjectName'
        )->will($this->returnValue($objectName));

        $formFieldViewHelper->expects($this->once())->method('renderHiddenIdentityField')->with(
            $mockFormObject, $expectedProperty
        );

        $formFieldViewHelper->_call('addAdditionalIdentityPropertiesIfNeeded');
    }

    /**
     * @test
     */
    public function addAdditionalIdentityPropertiesIfNeededCallsRenderIdentityFieldWithTheRightParametersWithMoreHierarchyLevels(
    ) {
        $mockFormObject = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\ClassWithTwoGetters();
        $property = 'value.value.something';
        $objectName = 'myObject';
        $expectedProperty1 = 'myObject[value]';
        $expectedProperty2 = 'myObject[value][value]';

        $formFieldViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class,
            ['renderHiddenIdentityField', 'isObjectAccessorMode'], [], '', false
        );
        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $arguments = ['property' => $property];
        $formFieldViewHelper->_set('arguments', $arguments);
        $formFieldViewHelper->expects($this->atLeastOnce())->method('isObjectAccessorMode')->will(
            $this->returnValue(true)
        );
        $this->viewHelperVariableContainer->expects($this->at(0))->method('exists')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject'
        )->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->at(1))->method('get')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject'
        )->will($this->returnValue($mockFormObject));
        $this->viewHelperVariableContainer->expects($this->at(2))->method('get')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObjectName'
        )->will($this->returnValue($objectName));

        $formFieldViewHelper->expects($this->at(1))->method('renderHiddenIdentityField')->with(
            $mockFormObject, $expectedProperty1
        );
        $formFieldViewHelper->expects($this->at(2))->method('renderHiddenIdentityField')->with(
            $mockFormObject, $expectedProperty2
        );

        $formFieldViewHelper->_call('addAdditionalIdentityPropertiesIfNeeded');
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueRendersHiddenFieldIfItHasNotBeenRenderedBefore()
    {
        $formViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['getName'], [], '', false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->any())->method('getName')->will($this->returnValue('SomeFieldName'));
        $this->viewHelperVariableContainer->expects($this->at(0))->method('exists')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'renderedHiddenFields'
        )->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->at(1))->method('get')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'renderedHiddenFields'
        )->will($this->returnValue([]));
        $expected = '<input type="hidden" name="SomeFieldName" value="" />';
        $actual = $formViewHelper->_call('renderHiddenFieldForEmptyValue');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueAddsHiddenFieldNameToVariableContainerIfItHasBeenRendered()
    {
        $formViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['getName'], [], '', false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->any())->method('getName')->will($this->returnValue('NewFieldName'));
        $this->viewHelperVariableContainer->expects($this->at(0))->method('exists')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'renderedHiddenFields'
        )->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->at(1))->method('get')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'renderedHiddenFields'
        )->will($this->returnValue(['OldFieldName']));
        $this->viewHelperVariableContainer->expects($this->at(2))->method('addOrUpdate')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'renderedHiddenFields', ['OldFieldName', 'NewFieldName']
        );
        $formViewHelper->_call('renderHiddenFieldForEmptyValue');
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueDoesNotRenderHiddenFieldIfItHasBeenRenderedBefore()
    {
        $formViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['getName'], [], '', false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->any())->method('getName')->will($this->returnValue('SomeFieldName'));
        $this->viewHelperVariableContainer->expects($this->at(0))->method('exists')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'renderedHiddenFields'
        )->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->at(1))->method('get')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'renderedHiddenFields'
        )->will($this->returnValue(['SomeFieldName']));
        $this->viewHelperVariableContainer->expects($this->never())->method('addOrUpdate');
        $expected = '';
        $actual = $formViewHelper->_call('renderHiddenFieldForEmptyValue');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueRemovesEmptySquareBracketsFromHiddenFieldName()
    {
        $formViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['getName'], [], '', false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->any())->method('getName')->will(
            $this->returnValue('SomeFieldName[WithBrackets][]')
        );
        $this->viewHelperVariableContainer->expects($this->at(0))->method('exists')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'renderedHiddenFields'
        )->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->at(1))->method('get')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'renderedHiddenFields'
        )->will($this->returnValue([]));
        $this->viewHelperVariableContainer->expects($this->at(2))->method('addOrUpdate')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'renderedHiddenFields', ['SomeFieldName[WithBrackets]']
        );
        $expected = '<input type="hidden" name="SomeFieldName[WithBrackets]" value="" />';
        $actual = $formViewHelper->_call('renderHiddenFieldForEmptyValue');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueDoesNotRemoveNonEmptySquareBracketsFromHiddenFieldName()
    {
        $formViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class, ['getName'], [], '', false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects($this->any())->method('getName')->will(
            $this->returnValue('SomeFieldName[WithBrackets][foo]')
        );
        $this->viewHelperVariableContainer->expects($this->at(0))->method('exists')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'renderedHiddenFields'
        )->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->at(1))->method('get')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'renderedHiddenFields'
        )->will($this->returnValue([]));
        $this->viewHelperVariableContainer->expects($this->at(2))->method('addOrUpdate')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'renderedHiddenFields',
            ['SomeFieldName[WithBrackets][foo]']
        );
        $expected = '<input type="hidden" name="SomeFieldName[WithBrackets][foo]" value="" />';
        $actual = $formViewHelper->_call('renderHiddenFieldForEmptyValue');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function getPropertyValueReturnsArrayValueByPropertyPath()
    {
        $formFieldViewHelper = $this->getAccessibleMock(
            \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper::class,
            ['renderHiddenIdentityField'],
            [],
            '',
            false
        );

        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $formFieldViewHelper->_set('arguments', ['property' => 'key1.key2']);

        $this->viewHelperVariableContainer->expects($this->at(0))->method('exists')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject'
        )->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->at(1))->method('get')->with(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formObject'
        )->will($this->returnValue(['key1' => ['key2' => 'valueX']]));

        $actual = $formFieldViewHelper->_call('getPropertyValue');
        $this->assertEquals('valueX', $actual);
    }
}
