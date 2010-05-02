<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
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


require_once(dirname(__FILE__) . '/ViewHelperBaseTestcase.php');

/**
 * Testcase for CycleViewHelper
 *
 * @version $Id: GroupedForViewHelperTest.php 3350 2009-10-27 12:01:08Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_ViewHelpers_GroupedForViewHelperTest extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * var Tx_Fluid_ViewHelpers_GroupedForViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		parent::setUp();
		$this->viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_GroupedForViewHelper', array('renderChildren'));
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsEmptyStringIfObjectIsNull() {
		$this->assertEquals('', $this->viewHelper->render(NULL, 'foo', 'bar'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsEmptyStringIfObjectIsEmptyArray() {
		$this->assertEquals('', $this->viewHelper->render(array(), 'foo', 'bar'));
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderThrowsExceptionWhenPassingObjectsToEachThatAreNotTraversable() {
		$object = new stdClass();

		$this->viewHelper->render($object, 'innerVariable', 'someKey');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderGroupsMultidimensionalArrayObjectAndPreservesKeys() {
		$photoshop = new ArrayObject(array('name' => 'Adobe Photoshop', 'license' => 'commercial'));
		$typo3 = new ArrayObject(array('name' => 'TYPO3', 'license' => 'GPL'));
		$office = new ArrayObject(array('name' => 'Microsoft Office', 'license' => 'commercial'));
		$drupal = new ArrayObject(array('name' => 'Drupal', 'license' => 'GPL'));
		$wordpress = new ArrayObject(array('name' => 'Wordpress', 'license' => 'GPL'));

		$products = new ArrayObject(array('photoshop' => $photoshop, 'typo3' => $typo3, 'office' => $office, 'drupal' => $drupal, 'wordpress' => $wordpress));

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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderGroupsArrayOfObjectsAndPreservesKeys() {
		$photoshop = new stdClass();
		$photoshop->name = 'Adobe Photoshop';
		$photoshop->license = 'commercial';
		$typo3 = new stdClass();
		$typo3->name = 'TYPO3';
		$typo3->license = 'GPL';
		$office = new stdClass();
		$office->name = 'Microsoft Office';
		$office->license = 'commercial';
		$drupal = new stdClass();
		$drupal->name = 'Drupal';
		$drupal->license = 'GPL';
		$wordpress = new stdClass();
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
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderThrowsExceptionWhenPassingOneDimensionalArraysToEach() {
		$values = array('some', 'simple', 'array');

		$this->viewHelper->render($values, 'innerVariable', 'someKey');
	}
}

?>
