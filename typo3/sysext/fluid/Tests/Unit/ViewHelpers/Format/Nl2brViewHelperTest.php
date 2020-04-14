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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Format;

use TYPO3\CMS\Fluid\ViewHelpers\Format\Nl2brViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test case
 */
class Nl2brViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var Nl2brViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new Nl2brViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function viewHelperDoesNotModifyTextWithoutLineBreaks()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return '<p class="bodytext">Some Text without line breaks</p>';
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('<p class="bodytext">Some Text without line breaks</p>', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperConvertsLineBreaksToBRTags()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 'Line 1' . chr(10) . 'Line 2';
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('Line 1<br />' . chr(10) . 'Line 2', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperConvertsWindowsLineBreaksToBRTags()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 'Line 1' . chr(13) . chr(10) . 'Line 2';
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('Line 1<br />' . chr(13) . chr(10) . 'Line 2', $actualResult);
    }
}
