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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form;

use TYPO3\CMS\Fluid\ViewHelpers\Form\ButtonViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for the "Button" Form view helper
 */
class ButtonViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var ButtonViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new ButtonViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndDefaultAttributes()
    {
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 'Button Content';
            }
        );

        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'type' => 'submit'
            ]
        );

        $expectedResult = '<button type="submit" name="" value="">Button Content</button>';
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals($expectedResult, $actualResult);
    }
}
