<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form;

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

/**
 * Test for the Abstract Form view helper
 */
class AbstractFormViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @test
     */
    public function renderHiddenIdentityFieldReturnsAHiddenInputFieldContainingTheObjectsUID()
    {
        $object = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\ExtendsAbstractEntity::class, array('dummy'));
        $object->_set('uid', 123);
        $expectedResult = chr(10) . '<input type="hidden" name="prefix[theName][__identity]" value="123" />' . chr(10);
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, array('prefixFieldName', 'registerFieldNameForFormTokenGeneration'), array(), '', false);
        $viewHelper->expects($this->any())->method('prefixFieldName')->with('theName')->will($this->returnValue('prefix[theName]'));
        $actualResult = $viewHelper->_call('renderHiddenIdentityField', $object, 'theName');
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderHiddenIdentityFieldReturnsAHiddenInputFieldIfObjectIsNewButAClone()
    {
        $object = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\ExtendsAbstractEntity::class, array('dummy'));
        $object->_set('uid', 123);
        $object = clone $object;
        $expectedResult = chr(10) . '<input type="hidden" name="prefix[theName][__identity]" value="123" />' . chr(10);
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, array('prefixFieldName', 'registerFieldNameForFormTokenGeneration'), array(), '', false);
        $viewHelper->expects($this->any())->method('prefixFieldName')->with('theName')->will($this->returnValue('prefix[theName]'));
        $actualResult = $viewHelper->_call('renderHiddenIdentityField', $object, 'theName');
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function prefixFieldNameReturnsEmptyStringIfGivenFieldNameIsNULL()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormViewHelper::class, array('dummy'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->assertSame('', $viewHelper->_call('prefixFieldName', null));
    }

    /**
     * @test
     */
    public function prefixFieldNameReturnsEmptyStringIfGivenFieldNameIsEmpty()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormViewHelper::class, array('dummy'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->assertSame('', $viewHelper->_call('prefixFieldName', ''));
    }

    /**
     * @test
     */
    public function prefixFieldNameReturnsGivenFieldNameIfFieldNamePrefixIsEmpty()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormViewHelper::class, array('dummy'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->viewHelperVariableContainer->expects($this->any())->method('exists')->with(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix')->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->once())->method('get')->with(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix')->will($this->returnValue(''));
        $this->assertSame('someFieldName', $viewHelper->_call('prefixFieldName', 'someFieldName'));
    }

    /**
     * @test
     */
    public function prefixFieldNamePrefixesGivenFieldNameWithFieldNamePrefix()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormViewHelper::class, array('dummy'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->viewHelperVariableContainer->expects($this->any())->method('exists')->with(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix')->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->once())->method('get')->with(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix')->will($this->returnValue('somePrefix'));
        $this->assertSame('somePrefix[someFieldName]', $viewHelper->_call('prefixFieldName', 'someFieldName'));
    }

    /**
     * @test
     */
    public function prefixFieldNamePreservesSquareBracketsOfFieldName()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormViewHelper::class, array('dummy'), array(), '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->viewHelperVariableContainer->expects($this->any())->method('exists')->with(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix')->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->once())->method('get')->with(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix')->will($this->returnValue('somePrefix[foo]'));
        $this->assertSame('somePrefix[foo][someFieldName][bar]', $viewHelper->_call('prefixFieldName', 'someFieldName[bar]'));
    }
}
