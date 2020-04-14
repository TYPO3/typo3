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
use TYPO3\CMS\Fluid\ViewHelpers\Form\RadioViewHelper;

/**
 * Test for the "Radio" Form view helper
 */
class RadioViewHelperTest extends FormFieldViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Form\RadioViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new RadioViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderSetsTagNameAndDefaultAttributes()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'name' => 'foo',
            ]
        );
        $expectedResult = '<input type="radio" name="foo" value="" />';
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
        $expectedResult = '<input type="radio" name="foo" value="" checked="checked" />';
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderIgnoresBoundPropertyIfCheckedIsSet()
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

        self::assertStringContainsString(
            '<input type="radio" name="fieldPrefix[objectName][someProperty]" value="foo" checked="checked" />',
            $result
        );
    }

    /**
     * @test
     */
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeBoolean()
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

        self::assertStringContainsString(
            '<input type="radio" name="fieldPrefix[objectName][someProperty]" value="foo" checked="checked" />',
            $result
        );
    }

    /**
     * @test
     */
    public function renderDoesNotAppendSquareBracketsToNameAttributeIfBoundToAPropertyOfTypeArray()
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
            '<input type="radio" name="fieldPrefix[objectName][someProperty]" value="foo" />',
            $result
        );
    }

    /**
     * @test
     */
    public function renderSetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeString()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'value' => 'foo',
                'property' => 'someProperty',
            ]
        );

        $formObject = new \stdClass();
        $formObject->someProperty = '';

        $this->stubVariableContainer($formObject);
        $this->stubRequestWithoutMappingErrors();

        $result = $this->viewHelper->initializeArgumentsAndRender();

        self::assertStringContainsString(
            '<input type="radio" name="fieldPrefix[objectName][someProperty]" value="foo" />',
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
            '<input type="radio" name="fieldPrefix[objectName][someProperty]" value="foo" />',
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
            '<input type="radio" name="fieldPrefix[objectName][someProperty]" value="2" />',
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
            '<input type="radio" name="fieldPrefix[objectName][someProperty]" value="foo" class="error" />',
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
            '<input type="radio" name="fieldPrefix[objectName][someProperty]" value="foo" class="f3-form-error" />',
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
            '<input class="css_class f3-form-error" type="radio" name="fieldPrefix[objectName][someProperty]" value="foo" />',
            $result
        );
    }
}
