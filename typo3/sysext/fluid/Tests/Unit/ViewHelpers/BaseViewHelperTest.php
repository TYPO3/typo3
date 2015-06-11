<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 */
class BaseViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @test
     */
    public function renderTakesBaseUriFromControllerContext()
    {
        $baseUri = 'http://typo3.org/';
        $this->request->expects($this->any())->method('getBaseUri')->will($this->returnValue($baseUri));
        $viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\BaseViewHelper();
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $expectedResult = '<base href="' . $baseUri . '" />';
        $actualResult = $viewHelper->render();
        $this->assertSame($expectedResult, $actualResult);
    }
}
