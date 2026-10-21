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

namespace TYPO3\CMS\Install\Tests\Unit\SystemEnvironment;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\GraphicsCanvas;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Install\SystemEnvironment\SetupCheck;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SetupCheckTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        new \ReflectionProperty(GraphicsCanvas::class, 'freeTypeSupport')->setValue(null, null);
        parent::tearDown();
    }

    #[Test]
    public function isTrueTypeFontWorkingReportsMissingFreeType(): void
    {
        new \ReflectionProperty(GraphicsCanvas::class, 'freeTypeSupport')->setValue(null, false);

        $messages = $this->isTrueTypeFontWorking();

        self::assertCount(1, $messages);
        self::assertSame('PHP GD library freetype2 support missing', $messages[0]->getTitle());
        self::assertSame(ContextualFeedbackSeverity::ERROR, $messages[0]->getSeverity());
    }

    #[Test]
    public function isTrueTypeFontWorkingMeasuresTheTestFontWithFreeType(): void
    {
        if (!GraphicsCanvas::canRenderText()) {
            self::markTestSkipped('GD of this PHP cannot render text.');
        }

        $messages = $this->isTrueTypeFontWorking();

        self::assertCount(1, $messages);
        self::assertSame('FreeType True Type Font DPI', $messages[0]->getTitle());
    }

    /**
     * @return FlashMessage[]
     */
    private function isTrueTypeFontWorking(): array
    {
        $messageQueue = new FlashMessageQueue('install');
        $subject = new SetupCheck();
        new \ReflectionProperty($subject, 'messageQueue')->setValue($subject, $messageQueue);
        new \ReflectionMethod($subject, 'isTrueTypeFontWorking')->invoke($subject);
        return $messageQueue->getAllMessages();
    }
}
