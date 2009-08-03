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

require_once(dirname(__FILE__) . '/../ViewHelperBaseTestcase.php');

/**
 * Testcase for the resource uri view helper
 *
 * @version $Id: AliasViewHelper.php 2614 2009-06-15 18:13:18Z bwaidelich $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_ViewHelpers_Uri_ResourceViewHelperTest_testcase extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * var Tx_Fluid_ViewHelpers_Uri_ResourceViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		parent::setUp();
		$this->viewHelper = $this->getMock($this->buildAccessibleProxy('Tx_Fluid_ViewHelpers_Uri_ResourceViewHelper'), array('renderChildren'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function renderUsesCurrentControllerPackageKeyToBuildTheResourceURI() {
		$this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('foo'));

		$mockRequest = $this->getMock('Tx_Fluid_MVC_RequestInterface');
		$mockRequest->expects($this->atLeastOnce())->method('getControllerPackageKey')->will($this->returnValue('PackageKey'));
		$this->controllerContext->expects($this->atLeastOnce())->method('getRequest')->will($this->returnValue($mockRequest));

		$resourceUri = $this->viewHelper->render();
		$this->assertEquals('Resources/Packages/PackageKey/foo', $resourceUri);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderUsesCustomPackageKeyIfSpecified() {
		$this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('foo'));

		$resourceUri = $this->viewHelper->render('SomePackage');
		$this->assertEquals('Resources/Packages/SomePackage/foo', $resourceUri);
	}
}

?>