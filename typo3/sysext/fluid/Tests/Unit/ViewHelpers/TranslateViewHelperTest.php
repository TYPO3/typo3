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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\TranslateViewHelperFixtureForEmptyString;
use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\TranslateViewHelperFixtureForTranslatedString;
use TYPO3\CMS\Fluid\ViewHelpers\TranslateViewHelper;

/**
 * Test class for TranslateViewHelper
 */
class TranslateViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var TranslateViewHelper
     */
    protected $subject;

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException
     * @expectedExceptionCode 1351584844
     */
    public function renderThrowsExceptionIfNoKeyOrIdParameterIsGiven()
    {
        $this->subject = GeneralUtility::makeInstance(TranslateViewHelper::class);
        $this->injectDependenciesIntoViewHelper($this->subject);
        $this->subject->render();
    }

    /**
     * @test
     */
    public function renderReturnsStringForGivenKey()
    {
        $this->subject = GeneralUtility::makeInstance(TranslateViewHelperFixtureForTranslatedString::class);
        $this->injectDependenciesIntoViewHelper($this->subject);
        $this->assertEquals('<p>hello world</p>', $this->subject->render('foo'));
    }

    /**
     * @test
     */
    public function renderReturnsStringForGivenId()
    {
        $this->subject = GeneralUtility::makeInstance(TranslateViewHelperFixtureForTranslatedString::class);
        $this->injectDependenciesIntoViewHelper($this->subject);
        $this->assertEquals('<p>hello world</p>', $this->subject->render(null, 'bar'));
    }

    /**
     * @test
     */
    public function renderReturnsDefaultIfNoTranslationIsFound()
    {
        $this->subject = GeneralUtility::makeInstance(TranslateViewHelperFixtureForEmptyString::class);
        $this->injectDependenciesIntoViewHelper($this->subject);
        $this->assertEquals('default', $this->subject->render(null, 'bar', 'default'));
    }

    /**
     * @test
     */
    public function resultIsNotHtmlEscapedIfSoRequested()
    {
        $this->subject = GeneralUtility::makeInstance(TranslateViewHelperFixtureForTranslatedString::class);
        $this->injectDependenciesIntoViewHelper($this->subject);
        $this->assertEquals('&lt;p&gt;hello world&lt;/p&gt;', $this->subject->render('foo', null, null, true));
    }
}
