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

namespace TYPO3\CMS\Form\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\ProcessableValueFormElementInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\StringableFormElementInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Service\FormValueResolver;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FormValueResolverTest extends UnitTestCase
{
    #[Test]
    public function resolveDisplayValueReturnsProcessedValueForProcessableElement(): void
    {
        $formRuntime = self::createStub(FormRuntime::class);
        $element = $this->createMock(ProcessableFormElementFixtureInterface::class);
        $element->expects($this->once())->method('processElementValue')->with('key1', $formRuntime)->willReturn('Label 1');

        $subject = new FormValueResolver(self::createStub(TranslationService::class));
        self::assertSame('Label 1', $subject->resolveDisplayValue($element, 'key1', $formRuntime));
    }

    #[Test]
    public function resolveDisplayValueReturnsArrayResultFromProcessableElementAsIs(): void
    {
        $formRuntime = self::createStub(FormRuntime::class);
        $element = $this->createMock(ProcessableFormElementFixtureInterface::class);
        $element->expects($this->once())->method('processElementValue')->with(['key1', 'key2'], $formRuntime)->willReturn(['Label 1', 'Label 2']);

        $subject = new FormValueResolver(self::createStub(TranslationService::class));
        self::assertSame(['Label 1', 'Label 2'], $subject->resolveDisplayValue($element, ['key1', 'key2'], $formRuntime));
    }

    #[Test]
    public function resolveDisplayValueMapsOptionsForGenericElements(): void
    {
        $formRuntime = self::createStub(FormRuntime::class);
        $element = self::createStub(FormElementInterface::class);
        $element->method('getProperties')->willReturn(['options' => ['a' => 'A', 'b' => 'B']]);
        $translationService = $this->createMock(TranslationService::class);
        $translationService
            ->expects($this->once())
            ->method('translateFormElementValue')
            ->with($element, ['options'], $formRuntime)
            ->willReturn(['a' => 'X', 'b' => 'Y']);

        $subject = new FormValueResolver($translationService);
        self::assertSame(['X', 'Y'], $subject->resolveDisplayValue($element, ['a', 'b'], $formRuntime));
    }

    #[Test]
    public function resolveDisplayValueMapsSingleOptionForGenericElements(): void
    {
        $formRuntime = self::createStub(FormRuntime::class);
        $element = self::createStub(FormElementInterface::class);
        $element->method('getProperties')->willReturn(['options' => ['a' => 'A']]);
        $translationService = $this->createMock(TranslationService::class);
        $translationService
            ->expects($this->once())
            ->method('translateFormElementValue')
            ->with($element, ['options'], $formRuntime)
            ->willReturn(['a' => 'Translated A']);

        $subject = new FormValueResolver($translationService);
        self::assertSame('Translated A', $subject->resolveDisplayValue($element, 'a', $formRuntime));
    }

    #[Test]
    public function resolveDisplayValueKeepsValueWhenOptionIsUnknown(): void
    {
        $formRuntime = self::createStub(FormRuntime::class);
        $element = self::createStub(FormElementInterface::class);
        $element->method('getProperties')->willReturn(['options' => ['a' => 'A']]);
        $translationService = $this->createMock(TranslationService::class);
        $translationService
            ->expects($this->once())
            ->method('translateFormElementValue')
            ->with($element, ['options'], $formRuntime)
            ->willReturn(['a' => 'Translated A']);

        $subject = new FormValueResolver($translationService);
        self::assertSame('unknown', $subject->resolveDisplayValue($element, 'unknown', $formRuntime));
    }

    #[Test]
    public function resolveDisplayValueUsesValueToStringForObjectValues(): void
    {
        $formRuntime = self::createStub(FormRuntime::class);
        $dateTime = new \DateTime('2025-01-15');
        $element = $this->createMock(StringableFormElementFixtureInterface::class);
        $element->method('getProperties')->willReturn([]);
        $element->expects($this->once())->method('valueToString')->with($dateTime)->willReturn('2025-01-15');

        $subject = new FormValueResolver(self::createStub(TranslationService::class));
        self::assertSame('2025-01-15', $subject->resolveDisplayValue($element, $dateTime, $formRuntime));
    }

    #[Test]
    public function resolveDisplayValueFallsBackToDateTimeFormatting(): void
    {
        $formRuntime = self::createStub(FormRuntime::class);
        $dateTime = new \DateTime('@1574415600');
        $element = self::createStub(FormElementInterface::class);
        $element->method('getProperties')->willReturn([]);

        $subject = new FormValueResolver(self::createStub(TranslationService::class));
        self::assertSame(
            $dateTime->format(\DateTimeInterface::W3C),
            $subject->resolveDisplayValue($element, $dateTime, $formRuntime)
        );
    }

    #[Test]
    public function resolveDisplayValueReturnsScalarValueAsIs(): void
    {
        $formRuntime = self::createStub(FormRuntime::class);
        $element = self::createStub(FormElementInterface::class);
        $element->method('getProperties')->willReturn([]);

        $subject = new FormValueResolver(self::createStub(TranslationService::class));
        self::assertSame('plain text', $subject->resolveDisplayValue($element, 'plain text', $formRuntime));
    }
}

interface ProcessableFormElementFixtureInterface extends FormElementInterface, ProcessableValueFormElementInterface {}

interface StringableFormElementFixtureInterface extends FormElementInterface, StringableFormElementInterface {}
