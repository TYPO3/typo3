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

namespace TYPO3\CMS\Form\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Event\BeforeRenderableIsRenderedEvent;
use TYPO3\CMS\Form\EventListener\TranslateDefaultValueBeforeRendering;
use TYPO3\CMS\Form\Service\TranslationService;

final class TranslateDefaultValueBeforeRenderingTest extends TestCase
{
    #[Test]
    public function nonFormElementRenderableIsIgnored(): void
    {
        $translationService = $this->createMock(TranslationService::class);
        $translationService->expects($this->never())->method('translateFormElementValue');

        $renderable = self::createStub(RootRenderableInterface::class);
        $formRuntime = self::createStub(FormRuntime::class);
        $event = new BeforeRenderableIsRenderedEvent($renderable, $formRuntime);

        (new TranslateDefaultValueBeforeRendering($translationService))($event);
    }

    #[Test]
    public function nullDefaultValueIsIgnored(): void
    {
        $translationService = $this->createMock(TranslationService::class);
        $translationService->expects($this->never())->method('translateFormElementValue');

        $renderable = $this->createMock(FormElementInterface::class);
        $renderable->method('getDefaultValue')->willReturn(null);
        $formRuntime = self::createStub(FormRuntime::class);
        $event = new BeforeRenderableIsRenderedEvent($renderable, $formRuntime);

        (new TranslateDefaultValueBeforeRendering($translationService))($event);
    }

    #[Test]
    public function arrayDefaultValueIsIgnored(): void
    {
        // Array defaultValues (e.g. MultiCheckbox pre-selections) hold option keys, not
        // translatable labels. Translating them would break the value-to-option mapping.
        $translationService = $this->createMock(TranslationService::class);
        $translationService->expects($this->never())->method('translateFormElementValue');

        $renderable = $this->createMock(FormElementInterface::class);
        $renderable->method('getDefaultValue')->willReturn(['foo' => 'bar']);
        $formRuntime = self::createStub(FormRuntime::class);
        $event = new BeforeRenderableIsRenderedEvent($renderable, $formRuntime);

        (new TranslateDefaultValueBeforeRendering($translationService))($event);
    }

    #[Test]
    public function translationExceptionIsSilentlySwallowed(): void
    {
        $translationService = $this->createMock(TranslationService::class);
        $translationService->method('translateFormElementValue')->willThrowException(new \RuntimeException('No site found'));

        $renderable = $this->createMock(FormElementInterface::class);
        $renderable->method('getDefaultValue')->willReturn('original');
        $renderable->expects($this->never())->method('setDefaultValue');
        $formRuntime = self::createStub(FormRuntime::class);
        $event = new BeforeRenderableIsRenderedEvent($renderable, $formRuntime);

        // Must not throw
        (new TranslateDefaultValueBeforeRendering($translationService))($event);
    }

    #[Test]
    public function identicalTranslationDoesNotCallSetDefaultValue(): void
    {
        $translationService = $this->createMock(TranslationService::class);
        $translationService->method('translateFormElementValue')->willReturn('original');

        $renderable = $this->createMock(FormElementInterface::class);
        $renderable->method('getDefaultValue')->willReturn('original');
        $renderable->expects($this->never())->method('setDefaultValue');
        $formRuntime = self::createStub(FormRuntime::class);
        $event = new BeforeRenderableIsRenderedEvent($renderable, $formRuntime);

        (new TranslateDefaultValueBeforeRendering($translationService))($event);
    }

    #[Test]
    public function nullTranslationResultDoesNotCallSetDefaultValue(): void
    {
        $translationService = $this->createMock(TranslationService::class);
        $translationService->method('translateFormElementValue')->willReturn(null);

        $renderable = $this->createMock(FormElementInterface::class);
        $renderable->method('getDefaultValue')->willReturn('original');
        $renderable->expects($this->never())->method('setDefaultValue');
        $formRuntime = self::createStub(FormRuntime::class);
        $event = new BeforeRenderableIsRenderedEvent($renderable, $formRuntime);

        (new TranslateDefaultValueBeforeRendering($translationService))($event);
    }

    #[Test]
    public function differentTranslationCallsSetDefaultValue(): void
    {
        $translationService = $this->createMock(TranslationService::class);
        $translationService->method('translateFormElementValue')->willReturn('translated');

        $capturedValue = null;
        $renderable = $this->createMock(FormElementInterface::class);
        $renderable->method('getDefaultValue')->willReturn('original');
        $renderable->method('setDefaultValue')->willReturnCallback(
            static function (mixed $value) use (&$capturedValue): void {
                $capturedValue = $value;
            }
        );
        $formRuntime = self::createStub(FormRuntime::class);
        $event = new BeforeRenderableIsRenderedEvent($renderable, $formRuntime);

        (new TranslateDefaultValueBeforeRendering($translationService))($event);

        self::assertSame('translated', $capturedValue);
    }
}
