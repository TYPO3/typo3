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

use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for the Abstract Form view helper
 */
class AbstractFormViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @test
     */
    public function renderHiddenIdentityFieldReturnsAHiddenInputFieldContainingTheObjectsUID()
    {
        $object = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\ExtendsAbstractEntity::class, ['dummy']);
        $object->_set('uid', 123);
        $expectedResult = chr(10) . '<input type="hidden" name="prefix[theName][__identity]" value="123" />' . chr(10);
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, ['prefixFieldName', 'registerFieldNameForFormTokenGeneration'], [], '', false);
        $viewHelper->expects(self::any())->method('prefixFieldName')->with('theName')->will(self::returnValue('prefix[theName]'));
        $actualResult = $viewHelper->_call('renderHiddenIdentityField', $object, 'theName');
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderHiddenIdentityFieldReturnsAHiddenInputFieldIfObjectIsNewButAClone()
    {
        $object = $this->getAccessibleMock(\TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures\ExtendsAbstractEntity::class, ['dummy']);
        $object->_set('uid', 123);
        $object = clone $object;
        $expectedResult = chr(10) . '<input type="hidden" name="prefix[theName][__identity]" value="123" />' . chr(10);
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, ['prefixFieldName', 'registerFieldNameForFormTokenGeneration'], [], '', false);
        $viewHelper->expects(self::any())->method('prefixFieldName')->with('theName')->will(self::returnValue('prefix[theName]'));
        $actualResult = $viewHelper->_call('renderHiddenIdentityField', $object, 'theName');
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function prefixFieldNameReturnsEmptyStringIfGivenFieldNameIsNULL()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormViewHelper::class, ['dummy'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        self::assertSame('', $viewHelper->_call('prefixFieldName', null));
    }

    /**
     * @test
     */
    public function prefixFieldNameReturnsEmptyStringIfGivenFieldNameIsEmpty()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormViewHelper::class, ['dummy'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        self::assertSame('', $viewHelper->_call('prefixFieldName', ''));
    }

    /**
     * @test
     */
    public function prefixFieldNameReturnsGivenFieldNameIfFieldNamePrefixIsEmpty()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormViewHelper::class, ['dummy'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->viewHelperVariableContainer->exists(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix')->willReturn(true);
        $this->viewHelperVariableContainer->get(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix')->willReturn('');
        self::assertSame('someFieldName', $viewHelper->_call('prefixFieldName', 'someFieldName'));
    }

    /**
     * @test
     */
    public function prefixFieldNamePrefixesGivenFieldNameWithFieldNamePrefix()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormViewHelper::class, ['dummy'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->viewHelperVariableContainer->exists(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix')->willReturn(true);
        $this->viewHelperVariableContainer->get(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix')->willReturn('somePrefix');
        self::assertSame('somePrefix[someFieldName]', $viewHelper->_call('prefixFieldName', 'someFieldName'));
    }

    /**
     * @test
     */
    public function prefixFieldNamePreservesSquareBracketsOfFieldName()
    {
        $viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormViewHelper::class, ['dummy'], [], '', false);
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $this->viewHelperVariableContainer->exists(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix')->willReturn(true);
        $this->viewHelperVariableContainer->get(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'fieldNamePrefix')->willReturn('somePrefix[foo]');
        self::assertSame('somePrefix[foo][someFieldName][bar]', $viewHelper->_call('prefixFieldName', 'someFieldName[bar]'));
    }
}
