<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 * Testcase for CountViewHelper
 */
class CountViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase {

	/**
	 * @var \TYPO3\CMS\Fluid\ViewHelpers\CountViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		parent::setUp();
		$this->viewHelper = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\CountViewHelper', array('renderChildren'));
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	/**
	 * @test
	 */
	public function renderReturnsNumberOfElementsInAnArray() {
		$expectedResult = 3;
		$actualResult = $this->viewHelper->render(array('foo', 'bar', 'Baz'));
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderReturnsNumberOfElementsInAnArrayObject() {
		$expectedResult = 2;
		$actualResult = $this->viewHelper->render(new \ArrayObject(array('foo', 'bar')));
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderReturnsZeroIfGivenArrayIsEmpty() {
		$expectedResult = 0;
		$actualResult = $this->viewHelper->render(array());
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderUsesChildrenAsSubjectIfGivenSubjectIsNull() {
		$this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(array('foo', 'bar', 'baz')));
		$expectedResult = 3;
		$actualResult = $this->viewHelper->render(NULL);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderReturnsZeroIfGivenSubjectIsNullAndRenderChildrenReturnsNull() {
		$this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(NULL));
		$expectedResult = 0;
		$actualResult = $this->viewHelper->render(NULL);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
	 */
	public function renderThrowsExceptionIfGivenSubjectIsNotCountable() {
		$object = new \stdClass();
		$this->viewHelper->render($object);
	}
}

?>