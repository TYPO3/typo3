<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Uri;

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
use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3\CMS\Fluid\ViewHelpers\Uri\ExternalViewHelper;

/**
 * Testcase for the external uri view helper
 */
class ExternalViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Uri\ExternalViewHelper
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = new ExternalViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderReturnsSpecifiedUri()
    {
        $this->viewHelper->initialize();
        $actualResult = $this->viewHelper->render('http://www.some-domain.tld');

        $this->assertEquals('http://www.some-domain.tld', $actualResult);
    }

    /**
     * @test
     */
    public function renderAddsHttpPrefixIfSpecifiedUriDoesNotContainScheme()
    {
        $this->viewHelper->initialize();
        $actualResult = $this->viewHelper->render('www.some-domain.tld');

        $this->assertEquals('http://www.some-domain.tld', $actualResult);
    }

    /**
     * @test
     */
    public function renderAddsSpecifiedSchemeIfUriDoesNotContainScheme()
    {
        $this->viewHelper->initialize();
        $actualResult = $this->viewHelper->render('some-domain.tld', 'ftp');

        $this->assertEquals('ftp://some-domain.tld', $actualResult);
    }

    /**
     * @test
     */
    public function renderDoesNotAddEmptyScheme()
    {
        $this->viewHelper->initialize();
        $actualResult = $this->viewHelper->render('some-domain.tld', '');

        $this->assertEquals('some-domain.tld', $actualResult);
    }
}
