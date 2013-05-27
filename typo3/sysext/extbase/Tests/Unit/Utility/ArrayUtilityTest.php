<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) Extbase Team
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Testcase for the \TYPO3\CMS\Extbase\Utility\ArrayUtility class.
 *
 * @author Tymoteusz Motylewski <t.motylewski@gmail.com>
 */
class ArrayUtilityTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 */
	public function containsMultipleTypesReturnsFalseOnEmptyArray() {
		$this->assertFalse(\TYPO3\CMS\Extbase\Utility\ArrayUtility::containsMultipleTypes(array()));
	}

	/**
	 * @test
	 */
	public function containsMultipleTypesReturnsFalseOnArrayWithIntegers() {
		$this->assertFalse(\TYPO3\CMS\Extbase\Utility\ArrayUtility::containsMultipleTypes(array(1, 2, 3)));
	}

	/**
	 * @test
	 */
	public function containsMultipleTypesReturnsFalseOnArrayWithObjects() {
		$this->assertFalse(\TYPO3\CMS\Extbase\Utility\ArrayUtility::containsMultipleTypes(array(new \stdClass(), new \stdClass(), new \stdClass())));
	}

	/**
	 * @test
	 */
	public function containsMultipleTypesReturnsTrueOnMixedArray() {
		$this->assertTrue(\TYPO3\CMS\Extbase\Utility\ArrayUtility::containsMultipleTypes(array(1, 'string', 1.25, new \stdClass())));
	}

	/**
	 * @test
	 */
	public function getValueByPathReturnsTheValueOfANestedArrayByFollowingTheGivenSimplePath() {
		$array = array('Foo' => 'the value');
		$this->assertSame('the value', \TYPO3\CMS\Extbase\Utility\ArrayUtility::getValueByPath($array, array('Foo')));
	}

	/**
	 * @test
	 */
	public function getValueByPathReturnsTheValueOfANestedArrayByFollowingTheGivenPath() {
		$array = array('Foo' => array('Bar' => array('Baz' => array(2 => 'the value'))));
		$this->assertSame('the value', \TYPO3\CMS\Extbase\Utility\ArrayUtility::getValueByPath($array, array('Foo', 'Bar', 'Baz', 2)));
	}

	/**
	 * @test
	 */
	public function getValueByPathReturnsTheValueOfANestedArrayByFollowingTheGivenPathIfPathIsString() {
		$path = 'Foo.Bar.Baz.2';
		$array = array('Foo' => array('Bar' => array('Baz' => array(2 => 'the value'))));
		$expectedResult = 'the value';
		$actualResult = \TYPO3\CMS\Extbase\Utility\ArrayUtility::getValueByPath($array, $path);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function getValueByPathThrowsExceptionIfPathIsNoArrayOrString() {
		$array = array('Foo' => array('Bar' => array('Baz' => array(2 => 'the value'))));
		\TYPO3\CMS\Extbase\Utility\ArrayUtility::getValueByPath($array, NULL);
	}

	/**
	 * @test
	 */
	public function getValueByPathReturnsNullIfTheSegementsOfThePathDontExist() {
		$array = array('Foo' => array('Bar' => array('Baz' => array(2 => 'the value'))));
		$this->assertNull(\TYPO3\CMS\Extbase\Utility\ArrayUtility::getValueByPath($array, array('Foo', 'Bar', 'Bax', 2)));
	}

	/**
	 * @test
	 */
	public function getValueByPathReturnsNullIfThePathHasMoreSegmentsThanTheGivenArray() {
		$array = array('Foo' => array('Bar' => array('Baz' => 'the value')));
		$this->assertNull(\TYPO3\CMS\Extbase\Utility\ArrayUtility::getValueByPath($array, array('Foo', 'Bar', 'Baz', 'Bux')));
	}

	/**
	 * @test
	 */
	public function convertObjectToArrayConvertsNestedObjectsToArray() {
		$object = new \stdClass();
		$object->a = 'v';
		$object->b = new \stdClass();
		$object->b->c = 'w';
		$object->d = array('i' => 'foo', 'j' => 12, 'k' => TRUE, 'l' => new \stdClass());
		$array = \TYPO3\CMS\Extbase\Utility\ArrayUtility::convertObjectToArray($object);
		$expected = array(
			'a' => 'v',
			'b' => array(
				'c' => 'w'
			),
			'd' => array(
				'i' => 'foo',
				'j' => 12,
				'k' => TRUE,
				'l' => array()
			)
		);
		$this->assertSame($expected, $array);
	}

	/**
	 * @test
	 */
	public function setValueByPathSetsValueRecursivelyIfPathIsArray() {
		$array = array();
		$path = array('foo', 'bar', 'baz');
		$expectedValue = array('foo' => array('bar' => array('baz' => 'The Value')));
		$actualValue = \TYPO3\CMS\Extbase\Utility\ArrayUtility::setValueByPath($array, $path, 'The Value');
		$this->assertSame($expectedValue, $actualValue);
	}

	/**
	 * @test
	 */
	public function setValueByPathSetsValueRecursivelyIfPathIsString() {
		$array = array();
		$path = 'foo.bar.baz';
		$expectedValue = array('foo' => array('bar' => array('baz' => 'The Value')));
		$actualValue = \TYPO3\CMS\Extbase\Utility\ArrayUtility::setValueByPath($array, $path, 'The Value');
		$this->assertSame($expectedValue, $actualValue);
	}

	/**
	 * @test
	 */
	public function setValueByPathRecursivelyMergesAnArray() {
		$array = array('foo' => array('bar' => 'should be overriden'), 'bar' => 'Baz');
		$path = array('foo', 'bar', 'baz');
		$expectedValue = array('foo' => array('bar' => array('baz' => 'The Value')), 'bar' => 'Baz');
		$actualValue = \TYPO3\CMS\Extbase\Utility\ArrayUtility::setValueByPath($array, $path, 'The Value');
		$this->assertSame($expectedValue, $actualValue);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setValueByPathThrowsExceptionIfPathIsNoArrayOrString() {
		$array = array('Foo' => array('Bar' => array('Baz' => array(2 => 'the value'))));
		\TYPO3\CMS\Extbase\Utility\ArrayUtility::setValueByPath($array, NULL, 'Some Value');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setValueByPathThrowsExceptionIfSubjectIsNoArray() {
		$subject = 'foobar';
		\TYPO3\CMS\Extbase\Utility\ArrayUtility::setValueByPath($subject, 'foo', 'bar');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setValueByPathThrowsExceptionIfSubjectIsNoArrayAccess() {
		$subject = new \stdClass();
		\TYPO3\CMS\Extbase\Utility\ArrayUtility::setValueByPath($subject, 'foo', 'bar');
	}

	/**
	 * @test
	 */
	public function setValueByLeavesInputArrayUnchanged() {
		$subject = ($subjectBackup = array('foo' => 'bar'));
		\TYPO3\CMS\Extbase\Utility\ArrayUtility::setValueByPath($subject, 'foo', 'baz');
		$this->assertSame($subject, $subjectBackup);
	}

	/**
	 * @test
	 */
	public function unsetValueByPathDoesNotModifyAnArrayIfThePathWasNotFound() {
		$array = array('foo' => array('bar' => array('baz' => 'Some Value')), 'bar' => 'Baz');
		$path = array('foo', 'bar', 'nonExistingKey');
		$expectedValue = $array;
		$actualValue = \TYPO3\CMS\Extbase\Utility\ArrayUtility::unsetValueByPath($array, $path);
		$this->assertSame($expectedValue, $actualValue);
	}

	/**
	 * @test
	 */
	public function unsetValueByPathRemovesSpecifiedKey() {
		$array = array('foo' => array('bar' => array('baz' => 'Some Value')), 'bar' => 'Baz');
		$path = array('foo', 'bar', 'baz');
		$expectedValue = array('foo' => array('bar' => array()), 'bar' => 'Baz');
		$actualValue = \TYPO3\CMS\Extbase\Utility\ArrayUtility::unsetValueByPath($array, $path);
		$this->assertSame($expectedValue, $actualValue);
	}

	/**
	 * @test
	 */
	public function unsetValueByPathRemovesSpecifiedKeyIfPathIsString() {
		$array = array('foo' => array('bar' => array('baz' => 'Some Value')), 'bar' => 'Baz');
		$path = 'foo.bar.baz';
		$expectedValue = array('foo' => array('bar' => array()), 'bar' => 'Baz');
		$actualValue = \TYPO3\CMS\Extbase\Utility\ArrayUtility::unsetValueByPath($array, $path);
		$this->assertSame($expectedValue, $actualValue);
	}

	/**
	 * @test
	 */
	public function unsetValueByPathRemovesSpecifiedBranch() {
		$array = array('foo' => array('bar' => array('baz' => 'Some Value')), 'bar' => 'Baz');
		$path = array('foo');
		$expectedValue = array('bar' => 'Baz');
		$actualValue = \TYPO3\CMS\Extbase\Utility\ArrayUtility::unsetValueByPath($array, $path);
		$this->assertSame($expectedValue, $actualValue);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function unsetValueByPathThrowsExceptionIfPathIsNoArrayOrString() {
		$array = array('Foo' => array('Bar' => array('Baz' => array(2 => 'the value'))));
		\TYPO3\CMS\Extbase\Utility\ArrayUtility::unsetValueByPath($array, NULL);
	}

	/**
	 * @test
	 */
	public function removeEmptyElementsRecursivelyRemovesNullValues() {
		$array = array('EmptyElement' => NULL, 'Foo' => array('Bar' => array('Baz' => array('NotNull' => '', 'AnotherEmptyElement' => NULL))));
		$expectedResult = array('Foo' => array('Bar' => array('Baz' => array('NotNull' => ''))));
		$actualResult = \TYPO3\CMS\Extbase\Utility\ArrayUtility::removeEmptyElementsRecursively($array);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function removeEmptyElementsRecursivelyRemovesEmptySubArrays() {
		$array = array('EmptyElement' => array(), 'Foo' => array('Bar' => array('Baz' => array('AnotherEmptyElement' => NULL))), 'NotNull' => 123);
		$expectedResult = array('NotNull' => 123);
		$actualResult = \TYPO3\CMS\Extbase\Utility\ArrayUtility::removeEmptyElementsRecursively($array);
		$this->assertSame($expectedResult, $actualResult);
	}

	public function arrayMergeRecursiveOverruleData() {
		return array(
			'simple usage' => array(
				'inputArray1' => array(
					'k1' => 'v1',
					'k2' => 'v2'
				),
				'inputArray2' => array(
					'k2' => 'v2a',
					'k3' => 'v3'
				),
				'dontAddNewKeys' => FALSE,
				// default
				'emptyValuesOverride' => TRUE,
				// default
				'expected' => array(
					'k1' => 'v1',
					'k2' => 'v2a',
					'k3' => 'v3'
				)
			),
			'simple usage with recursion' => array(
				'inputArray1' => array(
					'k1' => 'v1',
					'k2' => array(
						'k2.1' => 'v2.1',
						'k2.2' => 'v2.2'
					)
				),
				'inputArray2' => array(
					'k2' => array(
						'k2.2' => 'v2.2a',
						'k2.3' => 'v2.3'
					),
					'k3' => 'v3'
				),
				'dontAddNewKeys' => FALSE,
				// default
				'emptyValuesOverride' => TRUE,
				// default
				'expected' => array(
					'k1' => 'v1',
					'k2' => array(
						'k2.1' => 'v2.1',
						'k2.2' => 'v2.2a',
						'k2.3' => 'v2.3'
					),
					'k3' => 'v3'
				)
			),
			'simple type should override array (k2)' => array(
				'inputArray1' => array(
					'k1' => 'v1',
					'k2' => array(
						'k2.1' => 'v2.1'
					)
				),
				'inputArray2' => array(
					'k2' => 'v2a',
					'k3' => 'v3'
				),
				'dontAddNewKeys' => FALSE,
				// default
				'emptyValuesOverride' => TRUE,
				// default
				'expected' => array(
					'k1' => 'v1',
					'k2' => 'v2a',
					'k3' => 'v3'
				)
			),
			'null should override array (k2)' => array(
				'inputArray1' => array(
					'k1' => 'v1',
					'k2' => array(
						'k2.1' => 'v2.1'
					)
				),
				'inputArray2' => array(
					'k2' => NULL,
					'k3' => 'v3'
				),
				'dontAddNewKeys' => FALSE,
				// default
				'emptyValuesOverride' => TRUE,
				// default
				'expected' => array(
					'k1' => 'v1',
					'k2' => NULL,
					'k3' => 'v3'
				)
			)
		);
	}

	/**
	 * @test
	 * @param array $inputArray1
	 * @param array $inputArray2
	 * @param boolean $dontAddNewKeys
	 * @param boolean $emptyValuesOverride
	 * @param array $expected
	 * @dataProvider arrayMergeRecursiveOverruleData
	 */
	public function arrayMergeRecursiveOverruleMergesSimpleArrays(array $inputArray1, array $inputArray2, $dontAddNewKeys, $emptyValuesOverride, array $expected) {
		$this->assertSame($expected, \TYPO3\CMS\Extbase\Utility\ArrayUtility::arrayMergeRecursiveOverrule($inputArray1, $inputArray2, $dontAddNewKeys, $emptyValuesOverride));
	}

	/**
	 * @test
	 */
	public function integerExplodeReturnsArrayOfIntegers() {
		$inputString = '1,2,3,4,5,6';
		$expected = array(1, 2, 3, 4, 5, 6);
		$this->assertSame($expected, \TYPO3\CMS\Extbase\Utility\ArrayUtility::integerExplode(',', $inputString));
	}

	/**
	 * @test
	 */
	public function integerExplodeReturnsZeroForStringValues() {
		$inputString = '1,abc,3,,5';
		$expected = array(1, 0, 3, 0, 5);
		$this->assertSame($expected, \TYPO3\CMS\Extbase\Utility\ArrayUtility::integerExplode(',', $inputString));
	}
}

?>