<?php

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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

use TYPO3\CMS\Fluid\ViewHelpers\BaseViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

class BaseViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @test
     */
    public function renderTakesBaseUriFromControllerContext()
    {
        $baseUri = 'http://typo3.org/';
        $this->request->getBaseUri()->willReturn($baseUri);
        $viewHelper = new BaseViewHelper();
        $this->injectDependenciesIntoViewHelper($viewHelper);
        $expectedResult = '<base href="' . $baseUri . '" />';
        $actualResult = $viewHelper->render();
        self::assertSame($expectedResult, $actualResult);
    }
}
