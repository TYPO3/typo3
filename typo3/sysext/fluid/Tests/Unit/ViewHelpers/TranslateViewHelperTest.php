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

use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\TranslateViewHelperFixtureForEmptyString;
use TYPO3\CMS\Fluid\ViewHelpers\TranslateViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * Test class for TranslateViewHelper
 */
class TranslateViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var TranslateViewHelper
     */
    protected $viewHelper;

    /**
     * @test
     */
    public function renderThrowsExceptionIfNoKeyOrIdParameterIsGiven()
    {
        $this->viewHelper = new TranslateViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(1351584844);

        $this->setArgumentsUnderTest(
            $this->viewHelper,
            []
        );
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function renderReturnsStringForGivenKey()
    {
        $this->viewHelper = new TranslateViewHelperFixtureForEmptyString();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return '<p>hello world</p>';
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'key' => 'foo'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('<p>hello world</p>', $actualResult);
    }

    /**
     * @test
     */
    public function renderReturnsStringForGivenId()
    {
        $this->viewHelper = new TranslateViewHelperFixtureForEmptyString();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return '<p>hello world</p>';
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'key' => null,
                'id' => 'bar'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('<p>hello world</p>', $actualResult);
    }

    /**
     * @test
     */
    public function renderReturnsDefaultIfNoTranslationIsFound()
    {
        $this->viewHelper = new TranslateViewHelperFixtureForEmptyString();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 'default';
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'key' => null,
                'id' => 'bar',
                'default' => 'default'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('default', $actualResult);
    }
}
