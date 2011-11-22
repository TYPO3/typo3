<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(dirname(__FILE__) . '/Fixtures/EmptySyntaxTreeNode.php');
require_once(dirname(__FILE__) . '/Fixtures/Fixture_UserDomainClass.php');
require_once(dirname(__FILE__) . '/FormFieldViewHelperBaseTestcase.php');

/**
 * Test for the "Select" Form view helper
 *
 */
class Tx_Fluid_Tests_Unit_ViewHelpers_Form_SelectViewHelperTest extends Tx_Fluid_Tests_Unit_ViewHelpers_Form_FormFieldViewHelperBaseTestcase {

	/**
	 * var Tx_Fluid_ViewHelpers_Form_SelectViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		parent::setUp();
		$this->arguments['name'] = '';
		$this->arguments['sortByOptionLabel'] = FALSE;
		$this->viewHelper = $this->getAccessibleMock('Tx_Fluid_ViewHelpers_Form_SelectViewHelper', array('setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration'));
	}

	/**
	 * @test
	 */
	public function selectCorrectlySetsTagName() {
		$this->tagBuilder->expects($this->once())->method('setTagName')->with('select');

		$this->arguments['options'] = array();
		$this->injectDependenciesIntoViewHelper($this->viewHelper);

		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}

	/**
	 * @test
	 */
	public function selectCreatesExpectedOptions() {
		$this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
		$this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
		$this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value1">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10));
		$this->tagBuilder->expects($this->once())->method('render');

		$this->arguments['options'] = array(
			'value1' => 'label1',
			'value2' => 'label2'
		);
		$this->arguments['value'] = 'value2';
		$this->arguments['name'] = 'myName';

		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}

	/**
	 * @test
	 */
	public function anEmptyOptionTagIsRenderedIfOptionsArrayIsEmptyToAssureXhtmlCompatibility() {
		$this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
		$this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
		$this->tagBuilder->expects($this->once())->method('setContent')->with('<option value=""></option>' . chr(10));
		$this->tagBuilder->expects($this->once())->method('render');

		$this->arguments['options'] = array();
		$this->arguments['value'] = 'value2';
		$this->arguments['name'] = 'myName';
		$this->injectDependenciesIntoViewHelper($this->viewHelper);

		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}

	/**
	 * @test
	 */
	public function OrderOfOptionsIsNotAlteredByDefault() {
		$this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
		$this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
		$this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value3">label3</option>' . chr(10) . '<option value="value1">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10));
		$this->tagBuilder->expects($this->once())->method('render');

		$this->arguments['options'] = array(
			'value3' => 'label3',
			'value1' => 'label1',
			'value2' => 'label2'
		);

		$this->arguments['value'] = 'value2';
		$this->arguments['name'] = 'myName';

		$this->injectDependenciesIntoViewHelper($this->viewHelper);

		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}

	/**
	 * @test
	 */
	public function optionsAreSortedByLabelIfSortByOptionLabelIsSet() {
		$this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
		$this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
		$this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value1">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10) . '<option value="value3">label3</option>' . chr(10));
		$this->tagBuilder->expects($this->once())->method('render');

		$this->arguments['options'] = array(
			'value3' => 'label3',
			'value1' => 'label1',
			'value2' => 'label2'
		);

		$this->arguments['value'] = 'value2';
		$this->arguments['name'] = 'myName';
		$this->arguments['sortByOptionLabel'] = TRUE;

		$this->injectDependenciesIntoViewHelper($this->viewHelper);

		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}

	/**
	 * @test
	 */
	public function multipleSelectCreatesExpectedOptions() {
		$this->tagBuilder = new Tx_Fluid_Core_ViewHelper_TagBuilder();

		$this->arguments['options'] = array(
			'value1' => 'label1',
			'value2' => 'label2',
			'value3' => 'label3'
		);

		$this->arguments['value'] = array('value3', 'value1');
		$this->arguments['name'] = 'myName';
		$this->arguments['multiple'] = 'multiple';

		$this->injectDependenciesIntoViewHelper($this->viewHelper);

		$this->viewHelper->initializeArguments();
		$this->viewHelper->initialize();
		$result = $this->viewHelper->render();
		$expected = '<input type="hidden" name="myName" value="" /><select multiple="multiple" name="myName[]"><option value="value1" selected="selected">label1</option>' . chr(10) .
			'<option value="value2">label2</option>' . chr(10) .
			'<option value="value3" selected="selected">label3</option>' . chr(10) .
			'</select>';
		$this->assertSame($expected, $result);
	}

	/**
	 * @test
	 */
	public function selectOnDomainObjectsCreatesExpectedOptions() { $this->markTestIncomplete("TODO - fix test in backporter");
		$mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue(NULL));
		$this->viewHelper->injectPersistenceManager($mockPersistenceManager);

		$this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
		$this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
		$this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="1">Ingmar</option>' . chr(10) . '<option value="2" selected="selected">Sebastian</option>' . chr(10) . '<option value="3">Robert</option>' . chr(10));
		$this->tagBuilder->expects($this->once())->method('render');

		$user_is = new Tx_Fluid_ViewHelpers_Fixtures_UserDomainClass(1, 'Ingmar', 'Schlecht');
		$user_sk = new Tx_Fluid_ViewHelpers_Fixtures_UserDomainClass(2, 'Sebastian', 'Kurfuerst');
		$user_rl = new Tx_Fluid_ViewHelpers_Fixtures_UserDomainClass(3, 'Robert', 'Lemke');

		$this->arguments['options'] = array(
			$user_is,
			$user_sk,
			$user_rl
		);

		$this->arguments['value'] = $user_sk;
		$this->arguments['optionValueField'] = 'id';
		$this->arguments['optionLabelField'] = 'firstName';
		$this->arguments['name'] = 'myName';
		$this->injectDependenciesIntoViewHelper($this->viewHelper);

		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}

	/**
	 * @test
	 */
	public function multipleSelectOnDomainObjectsCreatesExpectedOptions() {
		$this->tagBuilder = new Tx_Fluid_Core_ViewHelper_TagBuilder();
		$this->viewHelper->expects($this->exactly(3))->method('registerFieldNameForFormTokenGeneration')->with('myName[]');

		$user_is = new Tx_Fluid_ViewHelpers_Fixtures_UserDomainClass(1, 'Ingmar', 'Schlecht');
		$user_sk = new Tx_Fluid_ViewHelpers_Fixtures_UserDomainClass(2, 'Sebastian', 'Kurfuerst');
		$user_rl = new Tx_Fluid_ViewHelpers_Fixtures_UserDomainClass(3, 'Robert', 'Lemke');

		$this->arguments['options'] = array(
			$user_is,
			$user_sk,
			$user_rl
		);
		$this->arguments['value'] = array($user_rl, $user_is);
		$this->arguments['optionValueField'] = 'id';
		$this->arguments['optionLabelField'] = 'lastName';
		$this->arguments['name'] = 'myName';
		$this->arguments['multiple'] = 'multiple';

		$this->injectDependenciesIntoViewHelper($this->viewHelper);

		$this->viewHelper->initializeArguments();
		$this->viewHelper->initialize();
		$actual = $this->viewHelper->render();

		$expected = '<input type="hidden" name="myName" value="" /><select multiple="multiple" name="myName[]"><option value="1" selected="selected">Schlecht</option>' . chr(10) .
			'<option value="2">Kurfuerst</option>' . chr(10) .
			'<option value="3" selected="selected">Lemke</option>' . chr(10) .
			'</select>';
		$this->assertSame($expected, $actual);
	}

	/**
	 * @test
	 */
	public function selectWithoutFurtherConfigurationOnDomainObjectsUsesUuidForValueAndLabel() { $this->markTestIncomplete("TODO - fix test in backporter");
		$mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue('fakeUID'));
		$this->viewHelper->injectPersistenceManager($mockPersistenceManager);

		$this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
		$this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
		$this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="fakeUID">fakeUID</option>' . chr(10));
		$this->tagBuilder->expects($this->once())->method('render');

		$user = new Tx_Fluid_ViewHelpers_Fixtures_UserDomainClass(1, 'Ingmar', 'Schlecht');

		$this->arguments['options'] = array(
			$user
		);
		$this->arguments['name'] = 'myName';
		$this->injectDependenciesIntoViewHelper($this->viewHelper);

		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}

	/**
	 * @test
	 */
	public function selectWithoutFurtherConfigurationOnDomainObjectsUsesToStringForLabelIfAvailable() { $this->markTestIncomplete("TODO - fix test in backporter");
		$mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue('fakeUID'));
		$this->viewHelper->injectPersistenceManager($mockPersistenceManager);

		$this->tagBuilder->expects($this->once())->method('addAttribute')->with('name', 'myName');
		$this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('myName');
		$this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="fakeUID">toStringResult</option>' . chr(10));
		$this->tagBuilder->expects($this->once())->method('render');

		$user = $this->getMock('Tx_Fluid_ViewHelpers_Fixtures_UserDomainClass', array('__toString'), array(1, 'Ingmar', 'Schlecht'));
		$user->expects($this->atLeastOnce())->method('__toString')->will($this->returnValue('toStringResult'));

		$this->arguments['options'] = array(
			$user
		);
		$this->arguments['name'] = 'myName';
		$this->injectDependenciesIntoViewHelper($this->viewHelper);

		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception
	 */
	public function selectOnDomainObjectsThrowsExceptionIfNoValueCanBeFound() { $this->markTestIncomplete("TODO - fix test in backporter");
		$mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue(NULL));
		$this->viewHelper->injectPersistenceManager($mockPersistenceManager);

		$user = new Tx_Fluid_ViewHelpers_Fixtures_UserDomainClass(1, 'Ingmar', 'Schlecht');

		$this->arguments['options'] = array(
			$user
		);
		$this->arguments['name'] = 'myName';
		$this->injectDependenciesIntoViewHelper($this->viewHelper);

		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}

	/**
	 * @test
	 */
	public function renderCallsSetErrorClassAttribute() {
		$this->arguments['options'] = array();

		$this->injectDependenciesIntoViewHelper($this->viewHelper);

		$this->viewHelper->expects($this->once())->method('setErrorClassAttribute');
		$this->viewHelper->render();
	}

	/**
	 * @test
	 */
	public function allOptionsAreSelectedIfSelectAllIsTrue() {
		$this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value1" selected="selected">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10) . '<option value="value3" selected="selected">label3</option>' . chr(10));

		$this->arguments['options'] = array(
			'value1' => 'label1',
			'value2' => 'label2',
			'value3' => 'label3'
		);
		$this->arguments['name'] = 'myName';
		$this->arguments['multiple'] = 'multiple';
		$this->arguments['selectAllByDefault'] = TRUE;

		$this->injectDependenciesIntoViewHelper($this->viewHelper);

		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}

	/**
	 * @test
	 */
	public function selectAllHasNoEffectIfValueIsSet() {
		$this->tagBuilder->expects($this->once())->method('setContent')->with('<option value="value1" selected="selected">label1</option>' . chr(10) . '<option value="value2" selected="selected">label2</option>' . chr(10) . '<option value="value3">label3</option>' . chr(10));

		$this->arguments['options'] = array(
			'value1' => 'label1',
			'value2' => 'label2',
			'value3' => 'label3'
		);
		$this->arguments['value'] = array('value2', 'value1');
		$this->arguments['name'] = 'myName';
		$this->arguments['multiple'] = 'multiple';
		$this->arguments['selectAllByDefault'] = TRUE;

		$this->injectDependenciesIntoViewHelper($this->viewHelper);

		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}
}
?>