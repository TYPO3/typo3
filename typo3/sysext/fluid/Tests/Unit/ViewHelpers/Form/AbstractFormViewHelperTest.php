<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form;

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
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
require_once __DIR__ . '/../ViewHelperBaseTestcase.php';

/**
 * Test for the Abstract Form view helper
 */
class AbstractFormViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase {

	/**
	 * @test
	 */
	public function renderHiddenIdentityFieldReturnsAHiddenInputFieldContainingTheObjectsUID() {
		$className = 'Object' . uniqid();
		$fullClassName = '\\TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\' . $className;
		eval('namespace TYPO3\\CMS\\Fluid\\ViewHelpers\\Form; class ' . $className . ' extends \\TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity {
		}');
		$object = $this->getAccessibleMock($fullClassName, array('dummy'));
		$object->_set('uid', 123);
		$expectedResult = chr(10) . '<input type="hidden" name="prefix[theName][__identity]" value="123" />' . chr(10);
		$viewHelper = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', array('prefixFieldName', 'registerFieldNameForFormTokenGeneration'), array(), '', FALSE);
		$viewHelper->expects($this->any())->method('prefixFieldName')->with('theName')->will($this->returnValue('prefix[theName]'));
		$actualResult = $viewHelper->_call('renderHiddenIdentityField', $object, 'theName');
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderHiddenIdentityFieldReturnsAHiddenInputFieldIfObjectIsNewButAClone() {
		$className = 'Object' . uniqid();
		eval('class ' . $className . ' extends TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity {
		}');
		$object = $this->getAccessibleMock($className, array('dummy'));
		$object->_set('uid', 123);
		$object = clone $object;
		$expectedResult = chr(10) . '<input type="hidden" name="prefix[theName][__identity]" value="123" />' . chr(10);
		$viewHelper = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', array('prefixFieldName', 'registerFieldNameForFormTokenGeneration'), array(), '', FALSE);
		$viewHelper->expects($this->any())->method('prefixFieldName')->with('theName')->will($this->returnValue('prefix[theName]'));
		$actualResult = $viewHelper->_call('renderHiddenIdentityField', $object, 'theName');
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function prefixFieldNameReturnsEmptyStringIfGivenFieldNameIsNULL() {
		$viewHelper = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\AbstractFormViewHelper', array('dummy'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);
		$this->assertSame('', $viewHelper->_call('prefixFieldName', NULL));
	}

	/**
	 * @test
	 */
	public function prefixFieldNameReturnsEmptyStringIfGivenFieldNameIsEmpty() {
		$viewHelper = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\AbstractFormViewHelper', array('dummy'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);
		$this->assertSame('', $viewHelper->_call('prefixFieldName', ''));
	}

	/**
	 * @test
	 */
	public function prefixFieldNameReturnsGivenFieldNameIfFieldNamePrefixIsEmpty() {
		$viewHelper = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\AbstractFormViewHelper', array('dummy'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);
		$this->viewHelperVariableContainer->expects($this->any())->method('exists')->with('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'fieldNamePrefix')->will($this->returnValue(TRUE));
		$this->viewHelperVariableContainer->expects($this->once())->method('get')->with('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'fieldNamePrefix')->will($this->returnValue(''));
		$this->assertSame('someFieldName', $viewHelper->_call('prefixFieldName', 'someFieldName'));
	}

	/**
	 * @test
	 */
	public function prefixFieldNamePrefixesGivenFieldNameWithFieldNamePrefix() {
		$viewHelper = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\AbstractFormViewHelper', array('dummy'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);
		$this->viewHelperVariableContainer->expects($this->any())->method('exists')->with('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'fieldNamePrefix')->will($this->returnValue(TRUE));
		$this->viewHelperVariableContainer->expects($this->once())->method('get')->with('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'fieldNamePrefix')->will($this->returnValue('somePrefix'));
		$this->assertSame('somePrefix[someFieldName]', $viewHelper->_call('prefixFieldName', 'someFieldName'));
	}

	/**
	 * @test
	 */
	public function prefixFieldNamePreservesSquareBracketsOfFieldName() {
		$viewHelper = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Form\\AbstractFormViewHelper', array('dummy'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);
		$this->viewHelperVariableContainer->expects($this->any())->method('exists')->with('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'fieldNamePrefix')->will($this->returnValue(TRUE));
		$this->viewHelperVariableContainer->expects($this->once())->method('get')->with('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'fieldNamePrefix')->will($this->returnValue('somePrefix[foo]'));
		$this->assertSame('somePrefix[foo][someFieldName][bar]', $viewHelper->_call('prefixFieldName', 'someFieldName[bar]'));
	}
}

?>