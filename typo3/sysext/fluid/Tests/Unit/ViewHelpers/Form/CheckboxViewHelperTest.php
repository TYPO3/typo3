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

use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Fluid\ViewHelpers\Form\CheckboxViewHelper;

/**
 * Test for the "Checkbox" Form view helper
 */
class CheckboxViewHelperTest extends FormFieldViewHelperBaseTestcase
{

    /**
     * @var CheckboxViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new CheckboxViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndDefaultAttributes()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'name' => 'foo',
            ]
        );
        $expectedResult = '<input type="hidden" name="foo" value="" /><input type="checkbox" name="foo" value="" />';
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderSetsCheckedAttributeIfSpecified()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'name' => 'foo',
                'checked' => true,
            ]
        );
        $expectedResult = '<input type="hidden" name="foo" value="" /><input type="checkbox" name="foo" value="" checked="checked" />';
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * Object property = false, argument "checked" set
     *
     * @test
     */
    public function renderIgnoresValueOfBoundPropertyIfCheckedIsSet()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => 'foo',
                'property' => 'someProperty',
                'checked' => true
            ]
        );

        $formObject = new \stdClass();
        $formObject->someProperty = false;

        $this->stubVariableContainer($formObject);
        $this->stubRequestWithoutMappingErrors();

        $result = $this->viewHelper->initializeArgumentsAndRender();

        self::assertStringContainsString('<input type="hidden" name="fieldPrefix[objectName][someProperty]" value="" />', $result);
        self::assertStringContainsString('<input type="checkbox" name="fieldPrefix[objectName][someProperty]" value="foo" checked="checked" />', $result);
    }

    /**
     * Object property = true, argument "checked" not set
     *
     * @test
     */
    public function renderSetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeBoolean()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => 'foo',
                'property' => 'someProperty',
            ]
        );

        $formObject = new \stdClass();
        $formObject->someProperty = true;

        $this->stubVariableContainer($formObject);
        $this->stubRequestWithoutMappingErrors();

        $result = $this->viewHelper->initializeArgumentsAndRender();

        self::assertStringContainsString('<input type="hidden" name="fieldPrefix[objectName][someProperty]" value="" />', $result);
        self::assertStringContainsString(
            '<input type="checkbox" name="fieldPrefix[objectName][someProperty]" value="foo" checked="checked" />',
            $result
        );
    }

    /**
     * @test
     */
    public function renderAppendsSquareBracketsToNameAttributeIfBoundToAPropertyOfTypeArray()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => 'foo',
                'property' => 'someProperty',
            ]
        );

        $formObject = new \stdClass();
        $formObject->someProperty = [];

        $this->stubVariableContainer($formObject);
        $this->stubRequestWithoutMappingErrors();

        $result = $this->viewHelper->initializeArgumentsAndRender();

        self::assertStringContainsString(
            '<input type="checkbox" name="fieldPrefix[objectName][someProperty][]" value="foo" />',
            $result
        );
    }

    /**
     * @test
     */
    public function renderSetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeArray()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => 'foo',
                'property' => 'someProperty',
                'checked' => true
            ]
        );

        $formObject = new \stdClass();
        $formObject->someProperty = ['foo'];

        $this->stubVariableContainer($formObject);
        $this->stubRequestWithoutMappingErrors();

        $result = $this->viewHelper->initializeArgumentsAndRender();

        self::assertStringContainsString(
            '<input type="checkbox" name="fieldPrefix[objectName][someProperty]" value="foo" checked="checked" />',
            $result
        );
    }

    /**
     * @test
     */
    public function renderSetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeArrayObject()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => 'bar',
                'property' => 'someProperty',
                'checked' => true
            ]
        );

        $formObject = new \ArrayObject(['foo', 'bar', 'baz']);

        $this->stubVariableContainer($formObject);
        $this->stubRequestWithoutMappingErrors();

        $result = $this->viewHelper->initializeArgumentsAndRender();

        self::assertStringContainsString(
            '<input type="checkbox" name="fieldPrefix[objectName][someProperty]" value="bar" checked="checked" />',
            $result
        );
    }

    /**
     * @test
     */
    public function renderSetsCheckedAttributeIfBoundPropertyIsNotNull()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => 'foo',
                'property' => 'someProperty',
            ]
        );

        $formObject = new \stdClass();
        $formObject->someProperty = 'bar';

        $this->stubVariableContainer($formObject);
        $this->stubRequestWithoutMappingErrors();

        $result = $this->viewHelper->initializeArgumentsAndRender();

        self::assertStringContainsString(
            '<input type="checkbox" name="fieldPrefix[objectName][someProperty]" value="foo" checked="checked" />',
            $result
        );
    }

    /**
     * @test
     */
    public function renderDoesNotSetsCheckedAttributeIfBoundPropertyIsNull()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => 'foo',
                'property' => 'someProperty',
            ]
        );

        $formObject = new \stdClass();
        $formObject->someProperty = null;

        $this->stubVariableContainer($formObject);
        $this->stubRequestWithoutMappingErrors();

        $result = $this->viewHelper->initializeArgumentsAndRender();

        self::assertStringContainsString(
            '<input type="checkbox" name="fieldPrefix[objectName][someProperty]" value="foo" />',
            $result
        );
    }

    /**
     * @test
     */
    public function renderSetsCheckedAttributeForListOfObjects()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => 2,
                'property' => 'someProperty',
            ]
        );

        $property1 = new \stdClass();
        $property2 = new \stdClass();
        $property3 = new \stdClass();

        $persistenceManager = $this->prophesize(PersistenceManager::class);
        $persistenceManager->getIdentifierByObject($property1)->willReturn(1);
        $persistenceManager->getIdentifierByObject($property2)->willReturn(2);
        $persistenceManager->getIdentifierByObject($property3)->willReturn(3);
        $this->viewHelper->injectPersistenceManager($persistenceManager->reveal());

        $formObject = new \stdClass();
        $formObject->someProperty = [$property1, $property2, $property3];

        $this->stubVariableContainer($formObject);
        $this->stubRequestWithoutMappingErrors();

        $result = $this->viewHelper->initializeArgumentsAndRender();

        self::assertStringContainsString(
            '<input type="checkbox" name="fieldPrefix[objectName][someProperty][]" value="2" />',
            $result
        );
    }

    /**
     * @test
     */
    public function renderCallSetsErrorClassAttribute()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => 'foo',
                'property' => 'someProperty',
                'errorClass' => 'error',
            ]
        );

        $formObject = new \stdClass();
        $formObject->someProperty = null;

        $this->stubVariableContainer($formObject);
        $this->stubRequestWithMappingErrors();

        $result = $this->viewHelper->initializeArgumentsAndRender();

        self::assertStringContainsString(
            '<input type="checkbox" name="fieldPrefix[objectName][someProperty]" value="foo" class="error" />',
            $result
        );
    }

    /**
     * @test
     */
    public function renderCallSetsStandardErrorClassAttributeIfNonIsSpecified()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => 'foo',
                'property' => 'someProperty',
            ]
        );

        $formObject = new \stdClass();
        $formObject->someProperty = null;

        $this->stubVariableContainer($formObject);
        $this->stubRequestWithMappingErrors();

        $result = $this->viewHelper->initializeArgumentsAndRender();

        self::assertStringContainsString(
            '<input type="checkbox" name="fieldPrefix[objectName][someProperty]" value="foo" class="f3-form-error" />',
            $result
        );
    }

    /**
     * @test
     */
    public function renderCallExtendsClassAttributeWithErrorClass()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => 'foo',
                'property' => 'someProperty',
                'class' => 'css_class'
            ]
        );

        $formObject = new \stdClass();
        $formObject->someProperty = null;

        $this->stubVariableContainer($formObject);
        $this->stubRequestWithMappingErrors();

        $result = $this->viewHelper->initializeArgumentsAndRender();

        self::assertStringContainsString(
            '<input class="css_class f3-form-error" type="checkbox" name="fieldPrefix[objectName][someProperty]" value="foo" />',
            $result
        );
    }
}
