<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Tests\Unit\Messaging\Renderer;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\Renderer\BootstrapRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
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
    public function renderCreatesCorrectOutputForFlashMessage(): void
    {
        $rendererClass = GeneralUtility::makeInstance(BootstrapRenderer::class);
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            'messageBody',
            'messageTitle',
            ContextualFeedbackSeverity::NOTICE
        );
        $output = $rendererClass->render([$flashMessage]);
        self::assertStringContainsString('<div class="typo3-messages">', $output);
        self::assertStringContainsString('<div class="alert alert-notice">', $output);
        self::assertStringContainsString('<div class="media-body">', $output);
        self::assertStringContainsString('<h4 class="alert-title">messageTitle</h4>', $output);
        self::assertStringContainsString('<p class="alert-message">messageBody</p>', $output);
    }

    /**
     * @test
     */
    public function renderCreatesCorrectOutputForFlashMessageWithoutTitle(): void
    {
        $rendererClass = GeneralUtility::makeInstance(BootstrapRenderer::class);
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            'messageBody',
            '',
            ContextualFeedbackSeverity::NOTICE
        );
        $output = $rendererClass->render([$flashMessage]);
        self::assertStringContainsString('<div class="typo3-messages">', $output);
        self::assertStringContainsString('<div class="alert alert-notice">', $output);
        self::assertStringContainsString('<div class="media-body">', $output);
        self::assertStringContainsString('<p class="alert-message">messageBody</p>', $output);
        self::assertStringNotContainsString('<h4 class="alert-title">messageTitle</h4>', $output);
    }
}
