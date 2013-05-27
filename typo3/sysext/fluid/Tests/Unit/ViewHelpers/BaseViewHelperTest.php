<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(dirname(__FILE__) . '/ViewHelperBaseTestcase.php');
/**
 */
class Tx_Fluid_Tests_Unit_ViewHelpers_BaseViewHelperTest extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {
	/**
	 * @test
	 */
	public function renderTakesBaseUriFromControllerContext() {
		$baseUri = 'http://typo3.org/';

		$this->request->expects($this->any())->method('getBaseUri')->will($this->returnValue($baseUri));

		$viewHelper = new Tx_Fluid_ViewHelpers_BaseViewHelper();
		$this->injectDependenciesIntoViewHelper($viewHelper);

		$expectedResult = '<base href="' . $baseUri . '" />';
		$actualResult = $viewHelper->render();
		$this->assertSame($expectedResult, $actualResult);
	}
}
?>