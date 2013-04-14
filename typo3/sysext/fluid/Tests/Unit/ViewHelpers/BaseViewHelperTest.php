<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(__DIR__ . '/ViewHelperBaseTestcase.php');
/**
 */
class BaseViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase {

	/**
	 * @test
	 */
	public function renderTakesBaseUriFromControllerContext() {
		$baseUri = 'http://typo3.org/';
		$this->request->expects($this->any())->method('getBaseUri')->will($this->returnValue($baseUri));
		$viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\BaseViewHelper();
		$this->injectDependenciesIntoViewHelper($viewHelper);
		$expectedResult = '<base href="' . $baseUri . '" />';
		$actualResult = $viewHelper->render();
		$this->assertSame($expectedResult, $actualResult);
	}
}

?>