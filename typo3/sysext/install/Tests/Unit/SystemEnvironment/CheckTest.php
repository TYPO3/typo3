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
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Install\SystemEnvironment\Check;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CheckTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        new \ReflectionProperty(GraphicsCanvas::class, 'freeTypeSupport')->setValue(null, null);
        parent::tearDown();
    }

    /**
     * A GD build without FreeType still has imagettftext(), so its existence
     * says nothing about text rendering.
     */
    #[Test]
    public function checkGdLibFreeTypeSupportReportsMissingFreeType(): void
    {
        new \ReflectionProperty(GraphicsCanvas::class, 'freeTypeSupport')->setValue(null, false);

        $messages = $this->checkGdLibFreeTypeSupport();

        self::assertCount(1, $messages);
        self::assertSame('PHP GD library freetype support missing', $messages[0]->getTitle());
        self::assertSame(ContextualFeedbackSeverity::ERROR, $messages[0]->getSeverity());
    }

    #[Test]
    public function checkGdLibFreeTypeSupportReportsFreeType(): void
    {
        new \ReflectionProperty(GraphicsCanvas::class, 'freeTypeSupport')->setValue(null, true);

        $messages = $this->checkGdLibFreeTypeSupport();

        self::assertCount(1, $messages);
        self::assertSame('PHP GD library has freetype font support', $messages[0]->getTitle());
    }

    /**
     * @return FlashMessage[]
     */
    private function checkGdLibFreeTypeSupport(): array
    {
        $subject = new Check();
        new \ReflectionMethod($subject, 'checkGdLibFreeTypeSupport')->invoke($subject);
        return $subject->getMessageQueue()->getAllMessages();
    }
}
