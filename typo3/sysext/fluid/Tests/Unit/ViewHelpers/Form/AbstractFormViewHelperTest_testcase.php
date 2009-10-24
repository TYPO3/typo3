<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

require_once(dirname(__FILE__) . '/../ViewHelperBaseTestcase.php');

/**
 * Test for the Abstract Form view helper
 *
 * @version $Id: AbstractFormViewHelperTest.php 3337 2009-10-21 17:12:10Z bwaidelich $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelperTest_testcase extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function renderHiddenIdentityFieldReturnsAHiddenInputFieldContainingTheObjectsUID() { $this->markTestIncomplete("Works differently in v4. Thus, this test does not work");
		$className = 'Object' . uniqid();
		$fullClassName = 'F3\\Fluid\\ViewHelpers\\Form\\' . $className;
		eval('namespace F3\\Fluid\\ViewHelpers\\Form; class ' . $className . ' implements \\F3\\FLOW3\\Persistence\\Aspect\\DirtyMonitoringInterface {
			public function FLOW3_Persistence_isNew() { return FALSE; }
			public function FLOW3_Persistence_isClone() { return FALSE; }
			public function FLOW3_Persistence_isDirty($propertyName) {}
			public function FLOW3_Persistence_memorizeCleanState($propertyName = NULL) {}
			public function FLOW3_AOP_Proxy_getProperty($name) {}
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() {}
			public function __clone() {}
		}');
		$object = $this->getMock($fullClassName);

		$mockBackend = $this->getMock('Tx_Extbase_Persistence_BackendInterface');
		$mockBackend->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue('123'));

		$mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('getBackend')->will($this->returnValue($mockBackend));

		$expectedResult = chr(10) . '<input type="hidden" name="prefix[theName][__identity]" value="123" />' . chr(10);

		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('prefixFieldName', 'registerFieldNameForFormTokenGeneration'), array(), '', FALSE);
		$viewHelper->expects($this->any())->method('prefixFieldName')->with('theName')->will($this->returnValue('prefix[theName]'));
		$viewHelper->_set('persistenceManager', $mockPersistenceManager);

		$actualResult = $viewHelper->_call('renderHiddenIdentityField', $object, 'theName');
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function renderHiddenIdentityFieldReturnsAHiddenInputFieldIfObjectIsNewButAClone() { $this->markTestIncomplete("Works differently in v4. Thus, this test does not work");
		$className = 'Object' . uniqid();
		$fullClassName = 'F3\\Fluid\\ViewHelpers\\Form\\' . $className;
		eval('namespace F3\\Fluid\\ViewHelpers\\Form; class ' . $className . ' implements \\F3\\FLOW3\\Persistence\\Aspect\\DirtyMonitoringInterface {
			public function FLOW3_Persistence_isNew() { return TRUE; }
			public function FLOW3_Persistence_isClone() { return TRUE; }
			public function FLOW3_Persistence_isDirty($propertyName) {}
			public function FLOW3_Persistence_memorizeCleanState($propertyName = NULL) {}
			public function FLOW3_AOP_Proxy_getProperty($name) {}
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() {}
			public function __clone() {}
		}');
		$object = $this->getMock($fullClassName);

		$mockBackend = $this->getMock('Tx_Extbase_Persistence_BackendInterface');
		$mockBackend->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue('123'));

		$mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('getBackend')->will($this->returnValue($mockBackend));

		$expectedResult = chr(10) . '<input type="hidden" name="prefix[theName][__identity]" value="123" />' . chr(10);

		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('prefixFieldName', 'registerFieldNameForFormTokenGeneration'), array(), '', FALSE);
		$viewHelper->expects($this->any())->method('prefixFieldName')->with('theName')->will($this->returnValue('prefix[theName]'));
		$viewHelper->_set('persistenceManager', $mockPersistenceManager);

		$actualResult = $viewHelper->_call('renderHiddenIdentityField', $object, 'theName');
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderHiddenIdentityFieldReturnsACommentIfTheObjectIsWithoutIdentity() { $this->markTestIncomplete("Works differently in v4. Thus, this test does not work");
		$className = 'Object' . uniqid();
		$fullClassName = 'F3\\Fluid\\ViewHelpers\\Form\\' . $className;
		eval('namespace F3\\Fluid\\ViewHelpers\\Form; class ' . $className . ' implements \\F3\\FLOW3\\Persistence\\Aspect\\DirtyMonitoringInterface {
			public function FLOW3_Persistence_isNew() { return FALSE; }
			public function FLOW3_Persistence_isClone() { return FALSE; }
			public function FLOW3_Persistence_isDirty($propertyName) {}
			public function FLOW3_Persistence_memorizeCleanState($propertyName = NULL) {}
			public function FLOW3_AOP_Proxy_getProperty($name) {}
			public function FLOW3_AOP_Proxy_getProxyTargetClassName() {}
			public function __clone() {}
		}');
		$object = $this->getMock($fullClassName);

		$mockBackend = $this->getMock('Tx_Extbase_Persistence_BackendInterface');
		$mockBackend->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue(NULL));

		$mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('getBackend')->will($this->returnValue($mockBackend));

		$expectedResult = chr(10) . '<!-- Object of type ' . get_class($object) . ' is without identity -->' . chr(10);

		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_FormViewHelper'), array('prefixFieldName', 'registerFieldNameForFormTokenGeneration'), array(), '', FALSE);
		$viewHelper->_set('persistenceManager', $mockPersistenceManager);

		$actualResult = $viewHelper->_call('renderHiddenIdentityField', $object, 'theName');
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function prefixFieldNameReturnsEmptyStringIfGivenFieldNameIsNULL() {
		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper'), array('dummy'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$this->assertSame('', $viewHelper->_call('prefixFieldName', NULL));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function prefixFieldNameReturnsEmptyStringIfGivenFieldNameIsEmpty() {
		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper'), array('dummy'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$this->assertSame('', $viewHelper->_call('prefixFieldName', ''));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function prefixFieldNameReturnsGivenFieldNameIfFieldNamePrefixIsEmpty() {
		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper'), array('dummy'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);
		$this->viewHelperVariableContainer->expects($this->any())->method('exists')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix')->will($this->returnValue(TRUE));
		$this->viewHelperVariableContainer->expects($this->once())->method('get')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix')->will($this->returnValue(''));

		$this->assertSame('someFieldName', $viewHelper->_call('prefixFieldName', 'someFieldName'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function prefixFieldNamePrefixesGivenFieldNameWithFieldNamePrefix() {
		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper'), array('dummy'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);
		$this->viewHelperVariableContainer->expects($this->any())->method('exists')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix')->will($this->returnValue(TRUE));
		$this->viewHelperVariableContainer->expects($this->once())->method('get')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix')->will($this->returnValue('somePrefix'));

		$this->assertSame('somePrefix[someFieldName]', $viewHelper->_call('prefixFieldName', 'someFieldName'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function prefixFieldNamePreservesSquareBracketsOfFieldName() {
		$viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper'), array('dummy'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);
		$this->viewHelperVariableContainer->expects($this->any())->method('exists')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix')->will($this->returnValue(TRUE));
		$this->viewHelperVariableContainer->expects($this->once())->method('get')->with('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix')->will($this->returnValue('somePrefix[foo]'));

		$this->assertSame('somePrefix[foo][someFieldName][bar]', $viewHelper->_call('prefixFieldName', 'someFieldName[bar]'));
	}
}

?>