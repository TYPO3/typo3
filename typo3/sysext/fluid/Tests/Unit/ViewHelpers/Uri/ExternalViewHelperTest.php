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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Uri;

use TYPO3\CMS\Fluid\ViewHelpers\Uri\ExternalViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Testcase for the external uri view helper
 */
class ExternalViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Uri\ExternalViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = new ExternalViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 'http://www.some-domain.tld';
            }
        );
    }

    /**
     * @test
     */
    public function renderReturnsSpecifiedUri()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'uri' => 'http://www.some-domain.tld'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('http://www.some-domain.tld', $actualResult);
    }

    /**
     * @test
     */
    public function renderAddsHttpPrefixIfSpecifiedUriDoesNotContainScheme()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'uri' => 'www.some-domain.tld',
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('http://www.some-domain.tld', $actualResult);
    }

    /**
     * @test
     */
    public function renderAddsSpecifiedSchemeIfUriDoesNotContainScheme()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'uri' => 'some-domain.tld',
                'defaultScheme' => 'ftp'
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('ftp://some-domain.tld', $actualResult);
    }

    /**
     * @test
     */
    public function renderDoesNotAddEmptyScheme()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'uri' => 'some-domain.tld',
                'defaultScheme' => ''
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        self::assertEquals('some-domain.tld', $actualResult);
    }
}
