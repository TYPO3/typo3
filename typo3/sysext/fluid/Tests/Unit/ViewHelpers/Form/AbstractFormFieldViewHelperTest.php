<?php

/*
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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form;

use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\ClassWithTwoGetters;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;
use TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper;

/**
 * Test for the Abstract Form view helper
 */
class AbstractFormFieldViewHelperTest extends FormFieldViewHelperBaseTestcase
{
    /**
     * @test
     */
    public function getRespectSubmittedDataValueInitiallyReturnsFalse()
    {
        $formViewHelper = $this->getAccessibleMock(
            AbstractFormFieldViewHelper::class,
            ['isObjectAccessorMode'],
            [],
            '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        self::assertFalse($formViewHelper->_call('getRespectSubmittedDataValue'));
    }

    /*
     * @test
     */
    public function setRespectSubmittedDataValueToTrue()
    {
        $formViewHelper = $this->getAccessibleMock(
            AbstractFormFieldViewHelper::class,
            ['isObjectAccessorMode'],
            [],
            '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->_set('respectSubmittedDataValue', true);
        self::assertTrue($formViewHelper->_call('getRespectSubmittedDataValue'));
    }

    /**
     * @test
     */
    public function getValueAttributeBuildsValueFromPropertyAndFormObjectIfInObjectAccessorModeAndRespectSubmittedDataValueSetFalse(
    ) {
        $formViewHelper = $this->getAccessibleMock(
            AbstractFormFieldViewHelper::class,
            ['isObjectAccessorMode', 'addAdditionalIdentityPropertiesIfNeeded'],
            [],
            '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->_set('respectSubmittedDataValue', false);

        $mockObject = new ClassWithTwoGetters('testing', 1476108385);

        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(true);
        $this->viewHelperVariableContainer->get(FormViewHelper::class, 'formObject')
            ->willReturn($mockObject);
        $this->viewHelperVariableContainer->exists(
            FormViewHelper::class,
            'formObject'
        )->willReturn(true);

        $arguments = ['name' => null, 'value' => null, 'property' => 'value.something'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'MyString';
        $actual = $formViewHelper->_call('getValueAttribute');
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getNameBuildsNameFromFieldNamePrefixFormObjectNameAndPropertyIfInObjectAccessorMode()
    {
        $formViewHelper = $this->getAccessibleMock(
            AbstractFormFieldViewHelper::class,
            ['isObjectAccessorMode'],
            [],
            '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(true);
        $this->viewHelperVariableContainer->get(
            FormViewHelper::class,
            'formObjectName'
        )->willReturn('myObjectName');
        $this->viewHelperVariableContainer->exists(
            FormViewHelper::class,
            'fieldNamePrefix'
        )->willReturn(true);
        $this->viewHelperVariableContainer->get(FormViewHelper::class, 'fieldNamePrefix')
            ->willReturn('formPrefix');

        $arguments = ['name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'formPrefix[myObjectName][bla]';
        $actual = $formViewHelper->_call('getName');
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getNameBuildsNameFromFieldNamePrefixFormObjectNameAndHierarchicalPropertyIfInObjectAccessorMode()
    {
        $formViewHelper = $this->getAccessibleMock(
            AbstractFormFieldViewHelper::class,
            ['isObjectAccessorMode'],
            [],
            '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(true);
        $this->viewHelperVariableContainer->get(FormViewHelper::class, 'formObjectName')
            ->willReturn('myObjectName');
        $this->viewHelperVariableContainer->exists(
            FormViewHelper::class,
            'fieldNamePrefix'
        )->willReturn(true);
        $this->viewHelperVariableContainer->get(FormViewHelper::class, 'fieldNamePrefix')
            ->willReturn('formPrefix');

        $arguments = ['name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla.blubb'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'formPrefix[myObjectName][bla][blubb]';
        $actual = $formViewHelper->_call('getName');
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getNameBuildsNameFromFieldNamePrefixAndPropertyIfInObjectAccessorModeAndNoFormObjectNameIsSpecified(
    ) {
        $formViewHelper = $this->getAccessibleMock(
            AbstractFormFieldViewHelper::class,
            ['isObjectAccessorMode'],
            [],
            '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(true);
        $this->viewHelperVariableContainer->get(FormViewHelper::class, 'formObjectName')
            ->willReturn(null);
        $this->viewHelperVariableContainer->exists(
            FormViewHelper::class,
            'fieldNamePrefix'
        )->willReturn(true);
        $this->viewHelperVariableContainer->get(FormViewHelper::class, 'fieldNamePrefix')
            ->willReturn('formPrefix');

        $arguments = ['name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'formPrefix[bla]';
        $actual = $formViewHelper->_call('getName');
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getNameBuildsNameFromFieldNamePrefixAndFieldNameIfNotInObjectAccessorMode()
    {
        $formViewHelper = $this->getAccessibleMock(
            AbstractFormFieldViewHelper::class,
            ['isObjectAccessorMode'],
            [],
            '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $formViewHelper->expects(self::any())->method('isObjectAccessorMode')->willReturn(false);
        $this->viewHelperVariableContainer->exists(
            FormViewHelper::class,
            'fieldNamePrefix'
        )->willReturn(true);
        $this->viewHelperVariableContainer->get(FormViewHelper::class, 'fieldNamePrefix')
            ->willReturn('formPrefix');

        $arguments = ['name' => 'fieldName', 'value' => 'fieldValue', 'property' => 'bla'];
        $formViewHelper->_set('arguments', $arguments);
        $expected = 'formPrefix[fieldName]';
        $actual = $formViewHelper->_call('getName');
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function isObjectAccessorModeReturnsTrueIfPropertyIsSetAndFormObjectIsGiven()
    {
        $formViewHelper = $this->getAccessibleMock(
            AbstractFormFieldViewHelper::class,
            ['dummy'],
            [],
            '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);

        $this->viewHelperVariableContainer->exists(FormViewHelper::class, 'formObjectName')
            ->willReturn(true);

        $formViewHelper->_set('arguments', ['name' => null, 'value' => null, 'property' => 'bla']);
        self::assertTrue($formViewHelper->_call('isObjectAccessorMode'));

        $formViewHelper->_set('arguments', ['name' => null, 'value' => null, 'property' => null]);
        self::assertFalse($formViewHelper->_call('isObjectAccessorMode'));
    }

    /**
     * @test
     */
    public function addAdditionalIdentityPropertiesIfNeededDoesNotCreateAnythingIfPropertyIsWithoutDot()
    {
        $formFieldViewHelper = $this->getAccessibleMock(
            AbstractFormFieldViewHelper::class,
            ['renderHiddenIdentityField'],
            [],
            '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $arguments = ['property' => 'simple'];
        $formFieldViewHelper->expects(self::any())->method('renderHiddenIdentityField')->will(
            self::throwException(new \Exception('Should not be executed!!!', 1476108413))
        );
        $formFieldViewHelper->_set('arguments', $arguments);
        $formFieldViewHelper->_call('addAdditionalIdentityPropertiesIfNeeded');
    }

    /**
     * @test
     */
    public function addAdditionalIdentityPropertiesIfNeededCallsRenderIdentityFieldWithTheRightParameters()
    {
        $mockFormObject = new ClassWithTwoGetters();

        $property = 'value.something';
        $objectName = 'myObject';
        $expectedProperty = 'myObject[value]';

        $formFieldViewHelper = $this->getAccessibleMock(
            AbstractFormFieldViewHelper::class,
            ['renderHiddenIdentityField', 'isObjectAccessorMode'],
            [],
            '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $arguments = ['property' => $property];
        $formFieldViewHelper->_set('arguments', $arguments);
        $formFieldViewHelper->expects(self::atLeastOnce())->method('isObjectAccessorMode')->willReturn(
            true
        );
        $this->viewHelperVariableContainer->exists(FormViewHelper::class, 'formObject')
            ->willReturn(true);
        $this->viewHelperVariableContainer->get(FormViewHelper::class, 'formObject')
            ->willReturn($mockFormObject);
        $this->viewHelperVariableContainer->get(FormViewHelper::class, 'formObjectName')
            ->willReturn($objectName);
        $this->viewHelperVariableContainer->get(
            FormViewHelper::class,
            'additionalIdentityProperties'
        )->willReturn([]);
        $this->viewHelperVariableContainer->addOrUpdate(
            FormViewHelper::class,
            'additionalIdentityProperties',
            [$expectedProperty => null]
        )->willReturn();

        $formFieldViewHelper->expects(self::once())->method('renderHiddenIdentityField')->with(
            $mockFormObject,
            $expectedProperty
        );

        $formFieldViewHelper->_call('addAdditionalIdentityPropertiesIfNeeded');
    }

    /**
     * @test
     */
    public function addAdditionalIdentityPropertiesIfNeededCallsRenderIdentityFieldWithTheRightParametersWithMoreHierarchyLevels(
    ) {
        $mockFormObject = new ClassWithTwoGetters();
        $property = 'value.value.something';
        $objectName = 'myObject';
        $expectedProperty1 = 'myObject[value]';
        $expectedProperty2 = 'myObject[value][value]';

        $formFieldViewHelper = $this->getAccessibleMock(
            AbstractFormFieldViewHelper::class,
            ['renderHiddenIdentityField', 'isObjectAccessorMode'],
            [],
            '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $arguments = ['property' => $property];
        $formFieldViewHelper->_set('arguments', $arguments);
        $formFieldViewHelper->expects(self::atLeastOnce())->method('isObjectAccessorMode')->willReturn(
            true
        );
        $this->viewHelperVariableContainer->exists(FormViewHelper::class, 'formObject')
            ->willReturn(true);
        $this->viewHelperVariableContainer->get(FormViewHelper::class, 'formObject')
            ->willReturn($mockFormObject);
        $this->viewHelperVariableContainer->get(FormViewHelper::class, 'formObjectName')
            ->willReturn($objectName);
        $this->viewHelperVariableContainer->get(
            FormViewHelper::class,
            'additionalIdentityProperties'
        )->willReturn([]);
        $this->viewHelperVariableContainer->addOrUpdate(
            FormViewHelper::class,
            'additionalIdentityProperties',
            [$expectedProperty1 => null]
        )->willReturn();
        $this->viewHelperVariableContainer->addOrUpdate(
            FormViewHelper::class,
            'additionalIdentityProperties',
            [$expectedProperty2 => null]
        )->willReturn();

        $formFieldViewHelper->expects(self::at(1))->method('renderHiddenIdentityField')->with(
            $mockFormObject,
            $expectedProperty1
        );
        $formFieldViewHelper->expects(self::at(2))->method('renderHiddenIdentityField')->with(
            $mockFormObject,
            $expectedProperty2
        );

        $formFieldViewHelper->_call('addAdditionalIdentityPropertiesIfNeeded');
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueRendersHiddenFieldIfItHasNotBeenRenderedBefore()
    {
        $formViewHelper = $this->getAccessibleMock(
            AbstractFormFieldViewHelper::class,
            ['getName'],
            [],
            '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects(self::any())->method('getName')->willReturn('SomeFieldName');
        $this->viewHelperVariableContainer->exists(
            FormViewHelper::class,
            'renderedHiddenFields'
        )->willReturn(true);
        $this->viewHelperVariableContainer->get(
            FormViewHelper::class,
            'renderedHiddenFields'
        )->willReturn([]);
        $this->viewHelperVariableContainer->addOrUpdate(
            FormViewHelper::class,
            'renderedHiddenFields',
            ['SomeFieldName']
        )->willReturn();
        $expected = '<input type="hidden" name="SomeFieldName" value="" />';
        $actual = $formViewHelper->_call('renderHiddenFieldForEmptyValue');
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueAddsHiddenFieldNameToVariableContainerIfItHasBeenRendered()
    {
        $formViewHelper = $this->getAccessibleMock(
            AbstractFormFieldViewHelper::class,
            ['getName'],
            [],
            '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects(self::any())->method('getName')->willReturn('NewFieldName');
        $this->viewHelperVariableContainer->exists(
            FormViewHelper::class,
            'renderedHiddenFields'
        )->willReturn(true);
        $this->viewHelperVariableContainer->get(
            FormViewHelper::class,
            'renderedHiddenFields'
        )->willReturn(['OldFieldName']);
        $this->viewHelperVariableContainer->addOrUpdate(
            FormViewHelper::class,
            'renderedHiddenFields',
            ['OldFieldName', 'NewFieldName']
        )->willReturn();
        $formViewHelper->_call('renderHiddenFieldForEmptyValue');
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueDoesNotRenderHiddenFieldIfItHasBeenRenderedBefore()
    {
        $formViewHelper = $this->getAccessibleMock(
            AbstractFormFieldViewHelper::class,
            ['getName'],
            [],
            '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects(self::any())->method('getName')->willReturn('SomeFieldName');
        $this->viewHelperVariableContainer->exists(
            FormViewHelper::class,
            'renderedHiddenFields'
        )->willReturn(true);
        $this->viewHelperVariableContainer->get(
            FormViewHelper::class,
            'renderedHiddenFields'
        )->willReturn(['SomeFieldName']);
        $expected = '';
        $actual = $formViewHelper->_call('renderHiddenFieldForEmptyValue');
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueRemovesEmptySquareBracketsFromHiddenFieldName()
    {
        $formViewHelper = $this->getAccessibleMock(
            AbstractFormFieldViewHelper::class,
            ['getName'],
            [],
            '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects(self::any())->method('getName')->willReturn(
            'SomeFieldName[WithBrackets][]'
        );
        $this->viewHelperVariableContainer->exists(
            FormViewHelper::class,
            'renderedHiddenFields'
        )->willReturn(true);
        $this->viewHelperVariableContainer->get(
            FormViewHelper::class,
            'renderedHiddenFields'
        )->willReturn([]);
        $this->viewHelperVariableContainer->addOrUpdate(
            FormViewHelper::class,
            'renderedHiddenFields',
            ['SomeFieldName[WithBrackets]']
        )->willReturn();
        $expected = '<input type="hidden" name="SomeFieldName[WithBrackets]" value="" />';
        $actual = $formViewHelper->_call('renderHiddenFieldForEmptyValue');
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function renderHiddenFieldForEmptyValueDoesNotRemoveNonEmptySquareBracketsFromHiddenFieldName()
    {
        $formViewHelper = $this->getAccessibleMock(
            AbstractFormFieldViewHelper::class,
            ['getName'],
            [],
            '',
            false
        );
        $this->injectDependenciesIntoViewHelper($formViewHelper);
        $formViewHelper->expects(self::any())->method('getName')->willReturn(
            'SomeFieldName[WithBrackets][foo]'
        );
        $this->viewHelperVariableContainer->exists(
            FormViewHelper::class,
            'renderedHiddenFields'
        )->willReturn(true);
        $this->viewHelperVariableContainer->get(
            FormViewHelper::class,
            'renderedHiddenFields'
        )->willReturn([]);
        $this->viewHelperVariableContainer->addOrUpdate(
            FormViewHelper::class,
            'renderedHiddenFields',
            ['SomeFieldName[WithBrackets][foo]']
        )->willReturn();
        $expected = '<input type="hidden" name="SomeFieldName[WithBrackets][foo]" value="" />';
        $actual = $formViewHelper->_call('renderHiddenFieldForEmptyValue');
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function getPropertyValueReturnsArrayValueByPropertyPath()
    {
        $formFieldViewHelper = $this->getAccessibleMock(
            AbstractFormFieldViewHelper::class,
            ['renderHiddenIdentityField'],
            [],
            '',
            false
        );

        $this->injectDependenciesIntoViewHelper($formFieldViewHelper);
        $formFieldViewHelper->_set('arguments', ['property' => 'key1.key2']);

        $this->viewHelperVariableContainer->exists(FormViewHelper::class, 'formObject')
            ->willReturn(true);
        $this->viewHelperVariableContainer->get(FormViewHelper::class, 'formObject')
            ->willReturn(['key1' => ['key2' => 'valueX']]);

        $actual = $formFieldViewHelper->_call('getPropertyValue');
        self::assertEquals('valueX', $actual);
    }
}
