<?php
namespace TYPO3\CMS\Core\Tests\Unit\Messaging;

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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\Renderer\BootstrapRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class BootstrapRendererTest extends UnitTestCase
{
    /**
     * @test
     */
    public function renderCreatesCorrectOutputForFlashMessage()
    {
        $rendererClass = GeneralUtility::makeInstance(BootstrapRenderer::class);
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            'messageBody',
            'messageTitle',
            FlashMessage::NOTICE
        );
        $output = $rendererClass->render([$flashMessage]);
        $this->assertContains('<div class="typo3-messages">', $output);
        $this->assertContains('<div class="alert alert-notice">', $output);
        $this->assertContains('<div class="media-body">', $output);
        $this->assertContains('<h4 class="alert-title">messageTitle</h4>', $output);
        $this->assertContains('<p class="alert-message">messageBody</p>', $output);
    }

    /**
     * @test
     */
    public function renderCreatesCorrectOutputForFlashMessageWithoutTitle()
    {
        $rendererClass = GeneralUtility::makeInstance(BootstrapRenderer::class);
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            'messageBody',
            '',
            FlashMessage::NOTICE
        );
        $output = $rendererClass->render([$flashMessage]);
        $this->assertContains('<div class="typo3-messages">', $output);
        $this->assertContains('<div class="alert alert-notice">', $output);
        $this->assertContains('<div class="media-body">', $output);
        $this->assertContains('<p class="alert-message">messageBody</p>', $output);
        $this->assertNotContains('<h4 class="alert-title">messageTitle</h4>', $output);
    }
}
