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
use TYPO3\CMS\Core\Messaging\Renderer\PlaintextRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class PlaintextRendererTest extends UnitTestCase
{
    /**
     * @test
     */
    public function renderCreatesCorrectOutputForFlashMessage(): void
    {
        $rendererClass = GeneralUtility::makeInstance(PlaintextRenderer::class);
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            'messageBody',
            'messageTitle',
            ContextualFeedbackSeverity::NOTICE
        );
        self::assertSame('[NOTICE] messageTitle: messageBody', $rendererClass->render([$flashMessage]));
    }

    /**
     * @test
     */
    public function renderCreatesCorrectOutputForFlashMessageWithoutTitle(): void
    {
        $rendererClass = GeneralUtility::makeInstance(PlaintextRenderer::class);
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            'messageBody',
            '',
            ContextualFeedbackSeverity::NOTICE
        );
        self::assertSame('[NOTICE] messageBody', $rendererClass->render([$flashMessage]));
    }
}
