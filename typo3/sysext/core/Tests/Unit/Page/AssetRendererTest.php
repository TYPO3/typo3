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

namespace TYPO3\CMS\Core\Tests\Unit\Page;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\AssetRenderer;
use TYPO3\CMS\Core\Page\Event\BeforeJavaScriptsRenderingEvent;
use TYPO3\CMS\Core\Page\Event\BeforeStylesheetsRenderingEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AssetRendererTest extends UnitTestCase
{
    /**
     * cross-product of all combinations of AssetRenderer::render*() methods and priorities
     * @return array[] [render method name, isInline, isPriority, event class]
     */
    public static function renderMethodsAndEventsDataProvider(): array
    {
        return [
            ['renderInlineJavaScript', true, true, BeforeJavaScriptsRenderingEvent::class],
            ['renderInlineJavaScript', true, false, BeforeJavaScriptsRenderingEvent::class],
            ['renderJavaScript', false, true, BeforeJavaScriptsRenderingEvent::class],
            ['renderJavaScript', false, false, BeforeJavaScriptsRenderingEvent::class],
            ['renderInlineStylesheets', true, true, BeforeStylesheetsRenderingEvent::class],
            ['renderInlineStylesheets', true, false, BeforeStylesheetsRenderingEvent::class],
            ['renderStylesheets', false, true, BeforeStylesheetsRenderingEvent::class],
            ['renderStylesheets', false, false, BeforeStylesheetsRenderingEvent::class],
        ];
    }

    #[DataProvider('renderMethodsAndEventsDataProvider')]
    #[Test]
    public function beforeRenderingEvent(
        string $renderMethodName,
        bool $isInline,
        bool $priority,
        string $eventClassName
    ): void {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnArgument(0);
        $assetCollector = new AssetCollector();
        $assetRenderer = new AssetRenderer($assetCollector, $eventDispatcher);

        $event = new $eventClassName(
            $assetCollector,
            $isInline,
            $priority
        );

        $eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with($event);

        $assetRenderer->$renderMethodName($priority);
    }
}
