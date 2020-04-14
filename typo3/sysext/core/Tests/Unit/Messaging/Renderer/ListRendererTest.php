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

namespace TYPO3\CMS\Core\Tests\Unit\Messaging\Renderer;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\Renderer\ListRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ListRendererTest extends UnitTestCase
{
    /**
     * @test
     */
    public function renderCreatesCorrectOutputForFlashMessage()
    {
        $rendererClass = GeneralUtility::makeInstance(ListRenderer::class);
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            'messageBody',
            'messageTitle',
            FlashMessage::NOTICE
        );
        self::assertSame('<ul class="typo3-messages"><li class="alert alert-notice"><h4 class="alert-title">messageTitle</h4><p class="alert-message">messageBody</p></li></ul>', $rendererClass->render([$flashMessage]));
    }

    /**
     * @test
     */
    public function renderCreatesCorrectOutputForFlashMessageWithoutTitle()
    {
        $rendererClass = GeneralUtility::makeInstance(ListRenderer::class);
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            'messageBody',
            '',
            FlashMessage::NOTICE
        );
        self::assertSame('<ul class="typo3-messages"><li class="alert alert-notice"><p class="alert-message">messageBody</p></li></ul>', $rendererClass->render([$flashMessage]));
    }
}
