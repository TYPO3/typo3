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
 * Testcase for GroupedForViewHelper.
 */
class GroupedForViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase {

	/**
	 * @var \TYPO3\CMS\Fluid\ViewHelpers\GroupedForViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		parent::setUp();
		$this->viewHelper = $this->getMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\GroupedForViewHelper', array('renderChildren'));
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	/**
	 * @test
	 */
	public function renderReturnsEmptyStringIfObjectIsNull() {
		$this->assertEquals('', $this->viewHelper->render(NULL, 'foo', 'bar'));
	}

	/**
	 * @test
	 */
	public function renderReturnsEmptyStringIfObjectIsEmptyArray() {
		$this->assertEquals('', $this->viewHelper->render(array(), 'foo', 'bar'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
	 */
	public function renderThrowsExceptionWhenPassingObjectsToEachThatAreNotTraversable() {
		$object = new \stdClass();

		$this->viewHelper->render($object, 'innerVariable', 'someKey');
	}

	/**
	 * @test
	 */
	public function renderGroupsMultidimensionalArrayAndPreservesKeys() {
		$photoshop = array('name' => 'Adobe Photoshop', 'license' => 'commercial');
		$typo3 = array('name' => 'TYPO3', 'license' => 'GPL');
		$office = array('name' => 'Microsoft Office', 'license' => 'commercial');
		$drupal = array('name' => 'Drupal', 'license' => 'GPL');
		$wordpress = array('name' => 'Wordpress', 'license' => 'GPL');

		$products = array('photoshop' => $photoshop, 'typo3' => $typo3, 'office' => $office, 'drupal' => $drupal, 'wordpress' => $wordpress);

		$this->templateVariableContainer->expects($this->at(0))->method('add')->with('myGroupKey', 'commercial');
		$this->templateVariableContainer->expects($this->at(1))->method('add')->with('products', array('photoshop' => $photoshop, 'office' => $office));
		$this->templateVariableContainer->expects($this->at(2))->method('remove')->with('myGroupKey');
		$this->templateVariableContainer->expects($this->at(3))->method('remove')->with('products');
		$this->templateVariableContainer->expects($this->at(4))->method('add')->with('myGroupKey', 'GPL');
		$this->templateVariableContainer->expects($this->at(5))->method('add')->with('products', array('typo3' => $typo3, 'drupal' => $drupal, 'wordpress' => $wordpress));
		$this->templateVariableContainer->expects($this->at(6))->method('remove')->with('myGroupKey');
		$this->templateVariableContainer->expects($this->at(7))->method('remove')->with('products');

		$this->viewHelper->render($products, 'products', 'license', 'myGroupKey');
	}

	/**
	 * @test
	 */
	public function renderGroupsMultidimensionalArrayObjectAndPreservesKeys() {
		$photoshop = new \ArrayObject(array('name' => 'Adobe Photoshop', 'license' => 'commercial'));
		$typo3 = new \ArrayObject(array('name' => 'TYPO3', 'license' => 'GPL'));
		$office = new \ArrayObject(array('name' => 'Microsoft Office', 'license' => 'commercial'));
		$drupal = new \ArrayObject(array('name' => 'Drupal', 'license' => 'GPL'));
		$wordpress = new \ArrayObject(array('name' => 'Wordpress', 'license' => 'GPL'));

		$products = new \ArrayObject(array('photoshop' => $photoshop, 'typo3' => $typo3, 'office' => $office, 'drupal' => $drupal, 'wordpress' => $wordpress));

		$this->templateVariableContainer->expects($this->at(0))->method('add')->with('myGroupKey', 'commercial');
		$this->templateVariableContainer->expects($this->at(1))->method('add')->with('products', array('photoshop' => $photoshop, 'office' => $office));
		$this->templateVariableContainer->expects($this->at(2))->method('remove')->with('myGroupKey');
		$this->templateVariableContainer->expects($this->at(3))->method('remove')->with('products');
		$this->templateVariableContainer->expects($this->at(4))->method('add')->with('myGroupKey', 'GPL');
		$this->templateVariableContainer->expects($this->at(5))->method('add')->with('products', array('typo3' => $typo3, 'drupal' => $drupal, 'wordpress' => $wordpress));
		$this->templateVariableContainer->expects($this->at(6))->method('remove')->with('myGroupKey');
		$this->templateVariableContainer->expects($this->at(7))->method('remove')->with('products');

		$this->viewHelper->render($products, 'products', 'license', 'myGroupKey');
	}

	/**
	 * @test
	 */
	public function renderGroupsArrayOfObjectsAndPreservesKeys() {
		$photoshop = new \stdClass();
		$photoshop->name = 'Adobe Photoshop';
		$photoshop->license = 'commercial';
		$typo3 = new \stdClass();
		$typo3->name = 'TYPO3';
		$typo3->license = 'GPL';
		$office = new \stdClass();
		$office->name = 'Microsoft Office';
		$office->license = 'commercial';
		$drupal = new \stdClass();
		$drupal->name = 'Drupal';
		$drupal->license = 'GPL';
		$wordpress = new \stdClass();
		$wordpress->name = 'Wordpress';
		$wordpress->license = 'GPL';

		$products = array('photoshop' => $photoshop, 'typo3' => $typo3, 'office' => $office, 'drupal' => $drupal, 'wordpress' => $wordpress);

		$this->templateVariableContainer->expects($this->at(0))->method('add')->with('myGroupKey', 'commercial');
		$this->templateVariableContainer->expects($this->at(1))->method('add')->with('products', array('photoshop' => $photoshop, 'office' => $office));
		$this->templateVariableContainer->expects($this->at(2))->method('remove')->with('myGroupKey');
		$this->templateVariableContainer->expects($this->at(3))->method('remove')->with('products');
		$this->templateVariableContainer->expects($this->at(4))->method('add')->with('myGroupKey', 'GPL');
		$this->templateVariableContainer->expects($this->at(5))->method('add')->with('products', array('typo3' => $typo3, 'drupal' => $drupal, 'wordpress' => $wordpress));
		$this->templateVariableContainer->expects($this->at(6))->method('remove')->with('myGroupKey');
		$this->templateVariableContainer->expects($this->at(7))->method('remove')->with('products');

		$this->viewHelper->render($products, 'products', 'license', 'myGroupKey');
	}

	/**
	 * @test
	 */
	public function renderGroupsMultidimensionalArrayByObjectKey() {
		$customer1 = new \stdClass();
		$customer1->name = 'Anton Abel';

		$customer2 = new \stdClass();
		$customer2->name = 'Balthasar Bux';

		$invoice1 = array('date' => new \DateTime('1980-12-13'), 'customer' => $customer1);
		$invoice2 = array('date' => new \DateTime('2010-07-01'), 'customer' => $customer1);
		$invoice3 = array('date' => new \DateTime('2010-07-04'), 'customer' => $customer2);

		$invoices = array('invoice1' => $invoice1, 'invoice2' => $invoice2, 'invoice3' => $invoice3);

		$this->templateVariableContainer->expects($this->at(0))->method('add')->with('myGroupKey', $customer1);
		$this->templateVariableContainer->expects($this->at(1))->method('add')->with('invoices', array('invoice1' => $invoice1, 'invoice2' => $invoice2));
		$this->templateVariableContainer->expects($this->at(2))->method('remove')->with('myGroupKey');
		$this->templateVariableContainer->expects($this->at(3))->method('remove')->with('invoices');
		$this->templateVariableContainer->expects($this->at(4))->method('add')->with('myGroupKey', $customer2);
		$this->templateVariableContainer->expects($this->at(5))->method('add')->with('invoices', array('invoice3' => $invoice3));
		$this->templateVariableContainer->expects($this->at(6))->method('remove')->with('myGroupKey');
		$this->templateVariableContainer->expects($this->at(7))->method('remove')->with('invoices');

		$this->viewHelper->render($invoices, 'invoices', 'customer', 'myGroupKey');
	}

	/**
	 * @test
	 */
	public function renderGroupsMultidimensionalArrayByPropertyPath() {
		$customer1 = new \stdClass();
		$customer1->name = 'Anton Abel';

		$customer2 = new \stdClass();
		$customer2->name = 'Balthasar Bux';

		$invoice1 = new \stdClass();
		$invoice1->customer = $customer1;

		$invoice2 = new \stdClass();
		$invoice2->customer = $customer1;

		$invoice3 = new \stdClass();
		$invoice3->customer = $customer2;

		$invoices = array('invoice1' => $invoice1, 'invoice2' => $invoice2, 'invoice3' => $invoice3);
		$groupBy = 'customer.name';
		/** @var \TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\TYPO3\CMS\Fluid\ViewHelpers\GroupedForViewHelper $accessibleMock */
		$accessibleMock = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\GroupedForViewHelper', array('dummy'));
		$expectedResult = array(
			'keys' => array(
				'Anton Abel' => 'Anton Abel',
				'Balthasar Bux' => 'Balthasar Bux'
			),
			'values' => array(
				'Anton Abel' => array(
					'invoice1' => $invoice1,
					'invoice2' => $invoice2
				),
				'Balthasar Bux' => array(
					'invoice3' => $invoice3
				)
			)
		);
		$this->assertSame($expectedResult, $accessibleMock->_callRef('groupElements', $invoices, $groupBy));
	}

	/**
	 * @test
	 */
	public function renderGroupsMultidimensionalObjectByObjectKey() {
		$customer1 = new \stdClass();
		$customer1->name = 'Anton Abel';

		$customer2 = new \stdClass();
		$customer2->name = 'Balthasar Bux';

		$invoice1 = new \stdClass();
		$invoice1->date = new \DateTime('1980-12-13');
		$invoice1->customer = $customer1;

		$invoice2 = new \stdClass();
		$invoice2->date = new \DateTime('2010-07-01');
		$invoice2->customer = $customer1;

		$invoice3 = new \stdClass();
		$invoice3->date = new \DateTime('2010-07-04');
		$invoice3->customer = $customer2;

		$invoices = array('invoice1' => $invoice1, 'invoice2' => $invoice2, 'invoice3' => $invoice3);

		$this->templateVariableContainer->expects($this->at(0))->method('add')->with('myGroupKey', $customer1);
		$this->templateVariableContainer->expects($this->at(1))->method('add')->with('invoices', array('invoice1' => $invoice1, 'invoice2' => $invoice2));
		$this->templateVariableContainer->expects($this->at(2))->method('remove')->with('myGroupKey');
		$this->templateVariableContainer->expects($this->at(3))->method('remove')->with('invoices');
		$this->templateVariableContainer->expects($this->at(4))->method('add')->with('myGroupKey', $customer2);
		$this->templateVariableContainer->expects($this->at(5))->method('add')->with('invoices', array('invoice3' => $invoice3));
		$this->templateVariableContainer->expects($this->at(6))->method('remove')->with('myGroupKey');
		$this->templateVariableContainer->expects($this->at(7))->method('remove')->with('invoices');

		$this->viewHelper->render($invoices, 'invoices', 'customer', 'myGroupKey');
	}

	/**
	 * @test
	 */
	public function renderGroupsMultidimensionalObjectByDateTimeObject() {

		$date1 = new \DateTime('2010-07-01');
		$date2 = new \DateTime('2010-07-04');

		$invoice1 = new \stdClass();
		$invoice1->date = $date1;
		$invoice1->id = 12340;

		$invoice2 = new \stdClass();
		$invoice2->date = $date1;
		$invoice2->id = 12341;

		$invoice3 = new \stdClass();
		$invoice3->date = $date2;
		$invoice3->id = 12342;

		$invoices = array('invoice1' => $invoice1, 'invoice2' => $invoice2, 'invoice3' => $invoice3);

		$this->templateVariableContainer->expects($this->at(0))->method('add')->with('myGroupKey', $date1);
		$this->templateVariableContainer->expects($this->at(1))->method('add')->with('invoices', array('invoice1' => $invoice1, 'invoice2' => $invoice2));
		$this->templateVariableContainer->expects($this->at(2))->method('remove')->with('myGroupKey');
		$this->templateVariableContainer->expects($this->at(3))->method('remove')->with('invoices');
		$this->templateVariableContainer->expects($this->at(4))->method('add')->with('myGroupKey', $date2);
		$this->templateVariableContainer->expects($this->at(5))->method('add')->with('invoices', array('invoice3' => $invoice3));
		$this->templateVariableContainer->expects($this->at(6))->method('remove')->with('myGroupKey');
		$this->templateVariableContainer->expects($this->at(7))->method('remove')->with('invoices');

		$this->viewHelper->render($invoices, 'invoices', 'date', 'myGroupKey');
	}

	/**
	 * @test
	 */
	public function groupingByAKeyThatDoesNotExistCreatesASingleGroup() {
		$photoshop = array('name' => 'Adobe Photoshop', 'license' => 'commercial');
		$typo3 = array('name' => 'TYPO3', 'license' => 'GPL');
		$office = array('name' => 'Microsoft Office', 'license' => 'commercial');

		$products = array('photoshop' => $photoshop, 'typo3' => $typo3, 'office' => $office);

		$this->templateVariableContainer->expects($this->at(0))->method('add')->with('groupKey', NULL);
		$this->templateVariableContainer->expects($this->at(1))->method('add')->with('innerKey', $products);
		$this->templateVariableContainer->expects($this->at(2))->method('remove')->with('groupKey');
		$this->templateVariableContainer->expects($this->at(3))->method('remove')->with('innerKey');

		$this->viewHelper->render($products, 'innerKey', 'NonExistingKey');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
	 */
	public function renderThrowsExceptionWhenPassingOneDimensionalArraysToEach() {
		$values = array('some', 'simple', 'array');

		$this->viewHelper->render($values, 'innerVariable', 'someKey');
	}
}

?>