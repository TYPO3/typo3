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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Link;

use TYPO3\CMS\Fluid\ViewHelpers\Link\ExternalViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Test for \TYPO3\CMS\Fluid\ViewHelpers\Link\ExternalViewHelper
 */
class ExternalViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Link\ExternalViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(ExternalViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndAttributesAndContent()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)
            ->setMethods(['setTagName', 'addAttribute', 'setContent'])
            ->getMock();
        $mockTagBuilder->expects(self::atLeastOnce())->method('setTagName')->with('a');
        $mockTagBuilder->expects(self::once())->method('addAttribute')->with('href', 'http://www.some-domain.tld');
        $mockTagBuilder->expects(self::once())->method('setContent')->with('some content');
        $this->viewHelper->setTagBuilder($mockTagBuilder);

        $this->viewHelper->expects(self::any())->method('renderChildren')->willReturn('some content');

        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'uri' => 'http://www.some-domain.tld',
            ]
        );
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function renderAddsHttpPrefixIfSpecifiedUriDoesNotContainScheme()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)
            ->setMethods(['setTagName', 'addAttribute', 'setContent'])
            ->getMock();
        $mockTagBuilder->expects(self::atLeastOnce())->method('setTagName')->with('a');
        $mockTagBuilder->expects(self::once())->method('addAttribute')->with('href', 'http://www.some-domain.tld');
        $mockTagBuilder->expects(self::once())->method('setContent')->with('some content');
        $this->viewHelper->setTagBuilder($mockTagBuilder);

        $this->viewHelper->expects(self::any())->method('renderChildren')->willReturn('some content');

        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'uri' => 'www.some-domain.tld',
            ]
        );
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function renderAddsSpecifiedSchemeIfUriDoesNotContainScheme()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)
            ->setMethods(['setTagName', 'addAttribute', 'setContent'])
            ->getMock();
        $mockTagBuilder->expects(self::atLeastOnce())->method('setTagName')->with('a');
        $mockTagBuilder->expects(self::once())->method('addAttribute')->with('href', 'ftp://some-domain.tld');
        $mockTagBuilder->expects(self::once())->method('setContent')->with('some content');
        $this->viewHelper->setTagBuilder($mockTagBuilder);

        $this->viewHelper->expects(self::any())->method('renderChildren')->willReturn('some content');

        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'uri' => 'some-domain.tld',
                'defaultScheme' => 'ftp',
            ]
        );
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function renderDoesNotAddEmptyScheme()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)
            ->setMethods(['setTagName', 'addAttribute', 'setContent'])
            ->getMock();
        $mockTagBuilder->expects(self::atLeastOnce())->method('setTagName')->with('a');
        $mockTagBuilder->expects(self::once())->method('addAttribute')->with('href', 'some-domain.tld');
        $mockTagBuilder->expects(self::once())->method('setContent')->with('some content');
        $this->viewHelper->setTagBuilder($mockTagBuilder);

        $this->viewHelper->expects(self::any())->method('renderChildren')->willReturn('some content');

        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'uri' => 'some-domain.tld',
                'defaultScheme' => '',
            ]
        );
        $this->viewHelper->initializeArgumentsAndRender();
    }
}
