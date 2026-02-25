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
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Event\BeforeRenderableIsRenderedEvent;
use TYPO3\CMS\Form\EventListener\FormatDateRenderableBeforeRendering;

final class FormatDateRenderableBeforeRenderingTest extends TestCase
{
    #[Test]
    public function todayIsResolvedToCurrentDate(): void
    {
        $result = $this->invokeListener(['min' => 'today']);
        $expected = (new \DateTime('today'))->format('Y-m-d');

        self::assertNotNull($result, 'setProperty must be called for relative expression');
        self::assertSame($expected, $result['min']);
    }

    #[Test]
    public function relativeMinusYearsIsResolved(): void
    {
        $result = $this->invokeListener(['max' => '-18 years']);
        $expected = (new \DateTime('-18 years'))->format('Y-m-d');

        self::assertNotNull($result, 'setProperty must be called for relative expression');
        self::assertSame($expected, $result['max']);
    }

    #[Test]
    public function relativePlusMonthIsResolved(): void
    {
        $result = $this->invokeListener(['min' => '+1 month']);
        $expected = (new \DateTime('+1 month'))->format('Y-m-d');

        self::assertNotNull($result, 'setProperty must be called for relative expression');
        self::assertSame($expected, $result['min']);
    }

    #[Test]
    public function absoluteDateIsNotModified(): void
    {
        $result = $this->invokeListener(['min' => '2025-01-01', 'max' => '2025-12-31']);

        self::assertNull($result, 'setProperty must not be called for absolute dates');
    }

    #[Test]
    public function emptyAttributesAreNotModified(): void
    {
        $result = $this->invokeListener(['min' => '', 'max' => '']);

        self::assertNull($result, 'setProperty must not be called for empty attributes');
    }

    #[Test]
    public function invalidExpressionIsNotResolved(): void
    {
        $result = $this->invokeListener(['min' => 'foobar']);

        self::assertNull($result, 'setProperty must not be called for invalid expressions');
    }

    #[Test]
    public function nonDateElementIsIgnored(): void
    {
        $renderable = $this->createMock(FormElementInterface::class);
        $renderable->method('getType')->willReturn('Text');
        $renderable->expects($this->never())->method('getProperties');

        $formRuntime = $this->createMock(FormRuntime::class);
        $event = new BeforeRenderableIsRenderedEvent($renderable, $formRuntime);

        (new FormatDateRenderableBeforeRendering())($event);
    }

    #[Test]
    public function mixedAbsoluteAndRelativeResolvesOnlyRelative(): void
    {
        $result = $this->invokeListener(['min' => '2025-01-01', 'max' => '-18 years']);
        $expected = (new \DateTime('-18 years'))->format('Y-m-d');

        self::assertNotNull($result, 'setProperty must be called when at least one attribute is relative');
        self::assertSame('2025-01-01', $result['min'], 'Absolute min must be preserved');
        self::assertSame($expected, $result['max'], 'Relative max must be resolved');
    }

    #[Test]
    public function relativeDefaultValueIsResolvedToAbsoluteDate(): void
    {
        $renderable = $this->createMock(FormElementInterface::class);
        $renderable->method('getType')->willReturn('Date');
        $renderable->method('getIdentifier')->willReturn('date-1');
        $renderable->method('getProperties')->willReturn([]);

        $formRuntime = $this->createMock(FormRuntime::class);
        $formRuntime->method('offsetGet')->with('date-1')->willReturn('today');

        $capturedIdentifier = null;
        $capturedValue = null;
        $formRuntime->method('offsetSet')->willReturnCallback(
            static function (string $identifier, mixed $value) use (&$capturedIdentifier, &$capturedValue): void {
                $capturedIdentifier = $identifier;
                $capturedValue = $value;
            }
        );

        $event = new BeforeRenderableIsRenderedEvent($renderable, $formRuntime);
        (new FormatDateRenderableBeforeRendering())($event);

        $expected = (new \DateTime('today'))->format('Y-m-d');
        self::assertSame('date-1', $capturedIdentifier);
        self::assertSame($expected, $capturedValue);
    }

    #[Test]
    public function absoluteDefaultValueIsNotModified(): void
    {
        $renderable = $this->createMock(FormElementInterface::class);
        $renderable->method('getType')->willReturn('Date');
        $renderable->method('getIdentifier')->willReturn('date-1');
        $renderable->method('getProperties')->willReturn([]);

        $formRuntime = $this->createMock(FormRuntime::class);
        $formRuntime->method('offsetGet')->with('date-1')->willReturn('2025-06-15');
        $formRuntime->expects($this->never())->method('offsetSet');

        $event = new BeforeRenderableIsRenderedEvent($renderable, $formRuntime);
        (new FormatDateRenderableBeforeRendering())($event);
    }

    private function invokeListener(array $fluidAttributes): ?array
    {
        $properties = ['fluidAdditionalAttributes' => $fluidAttributes];

        $renderable = $this->createMock(FormElementInterface::class);
        $renderable->method('getType')->willReturn('Date');
        $renderable->method('getIdentifier')->willReturn('date-1');
        $renderable->method('getProperties')->willReturn($properties);

        $capturedValue = null;
        $renderable->method('setProperty')->willReturnCallback(
            static function (string $key, mixed $value) use (&$capturedValue): void {
                $capturedValue = $value;
            }
        );

        $formRuntime = $this->createMock(FormRuntime::class);
        $formRuntime->method('offsetGet')->willReturn(null);

        $event = new BeforeRenderableIsRenderedEvent($renderable, $formRuntime);
        (new FormatDateRenderableBeforeRendering())($event);

        return $capturedValue;
    }
}
