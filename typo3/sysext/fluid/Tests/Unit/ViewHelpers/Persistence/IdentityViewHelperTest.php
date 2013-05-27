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
 * Testcase for IdentityViewHelper
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_ViewHelpers_Persistence_IdentityViewHelperTest extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function renderGetsIdentityForObjectFromPersistenceManager() {
		$mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');

		$viewHelper = $this->getAccessibleMock('Tx_Fluid_ViewHelpers_Persistence_IdentityViewHelper', array('dummy'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->injectPersistenceManager($mockPersistenceManager);

		$object = new stdClass();

		$mockPersistenceManager->expects($this->atLeastOnce())->method('getIdentifierByObject')->with($object)->will($this->returnValue('6f487e40-4483-11de-8a39-0800200c9a66'));

		$output = $viewHelper->render($object);

		$this->assertEquals('6f487e40-4483-11de-8a39-0800200c9a66', $output, 'Identity is rendered as is');
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function renderOutputsEmptyStringForNullIdentity() {
		$mockPersistenceManager = $this->getMock('Tx_Extbase_Persistence_ManagerInterface');

		$viewHelper = $this->getAccessibleMock('Tx_Fluid_ViewHelpers_Persistence_IdentityViewHelper', array('dummy'), array(), '', FALSE);
		$this->injectDependenciesIntoViewHelper($viewHelper);
		$viewHelper->injectPersistenceManager($mockPersistenceManager);

		$object = new stdClass();

		$mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnValue(NULL));

		$output = $viewHelper->render($object);

		$this->assertEquals('', $output, 'NULL Identity is rendered as empty string');
	}
}

?>