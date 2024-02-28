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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\Finishers;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Finishers\FinisherVariableProvider;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\StringableFormElementInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Tests\Unit\Domain\Finishers\Fixtures\AbstractFinisherFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AbstractFinisherTest extends UnitTestCase
{
    #[Test]
    public function parseOptionReturnsNullIfOptionNameIsTranslation(): void
    {
        $subject = new AbstractFinisherFixture();
        self::assertNull($subject->parseOption('translation'));
    }

    #[Test]
    public function parseOptionReturnsNullIfOptionNameNotExistsWithinOptions(): void
    {
        $subject = new AbstractFinisherFixture();
        $subject->options = [];
        self::assertNull($subject->parseOption('foo'));
    }

    #[Test]
    public function parseOptionReturnsDefaultOptionValueIfOptionNameNotExistsWithinOptionsButWithinDefaultOptions(): void
    {
        $finisherContextMock = $this->createMock(FinisherContext::class);
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->method('offsetExists')->with(self::anything())->willReturn(true);
        $formRuntimeMock->method('offsetGet')->with(self::anything())->willReturn(null);
        $finisherContextMock->method('getFormRuntime')->willReturn($formRuntimeMock);
        $finisherContextMock->method('getFinisherVariableProvider')->willReturn(new FinisherVariableProvider());

        $subject = new AbstractFinisherFixture();
        $subject->options = [];
        $subject->defaultOptions = [
            'subject' => 'defaultValue',
        ];
        $subject->finisherContext = $finisherContextMock;
        self::assertSame('defaultValue', $subject->parseOption('subject'));
    }

    #[Test]
    public function parseOptionReturnsDefaultOptionValueIfOptionValueIsAFormElementReferenceAndTheFormElementValueIsEmpty(): void
    {
        $finisherContextMock = $this->createMock(FinisherContext::class);
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->method('offsetExists')->with(self::anything())->willReturn(true);
        $formRuntimeMock->method('offsetGet')->with(self::anything())->willReturn('');
        $finisherContextMock->method('getFormRuntime')->willReturn($formRuntimeMock);

        $subject = new AbstractFinisherFixture();
        $subject->options = [
            'subject' => '{element-identifier-1}',
        ];
        $subject->defaultOptions = [
            'subject' => 'defaultValue',
        ];
        $subject->finisherContext = $finisherContextMock;
        self::assertSame('defaultValue', $subject->parseOption('subject'));
    }

    #[Test]
    public function substituteRuntimeReferencesReturnsArrayIfInputIsArray(): void
    {
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $input = ['bar', 'foobar', ['x', 'y']];
        $expected = ['bar', 'foobar', ['x', 'y']];
        $subject = new AbstractFinisherFixture();
        self::assertSame($expected, $subject->substituteRuntimeReferences($input, $formRuntimeMock));
    }

    #[Test]
    public function substituteRuntimeReferencesReturnsStringIfInputIsString(): void
    {
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $input = 'foobar';
        $expected = 'foobar';
        $subject = new AbstractFinisherFixture();
        self::assertSame($expected, $subject->substituteRuntimeReferences($input, $formRuntimeMock));
    }

    #[Test]
    public function substituteRuntimeReferencesReturnsValueFromFormRuntimeIfInputReferenceAFormElementIdentifierWhoseValueIsAString(): void
    {
        $elementIdentifier = 'element-identifier-1';
        $input = '{' . $elementIdentifier . '}';
        $expected = 'element-value';
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->method('offsetExists')->with($elementIdentifier)->willReturn(true);
        $formRuntimeMock->method('offsetGet')->with($elementIdentifier)->willReturn($expected);
        $subject = new AbstractFinisherFixture();
        self::assertSame($expected, $subject->substituteRuntimeReferences($input, $formRuntimeMock));
    }

    #[Test]
    public function substituteRuntimeReferencesReturnsValueFromFormRuntimeIfInputReferenceMultipleFormElementIdentifierWhoseValueIsAString(): void
    {
        $elementIdentifier1 = 'element-identifier-1';
        $elementValue1 = 'element-value-1';
        $elementIdentifier2 = 'element-identifier-2';
        $elementValue2 = 'element-value-2';
        $input = '{' . $elementIdentifier1 . '},{' . $elementIdentifier2 . '}';
        $expected = $elementValue1 . ',' . $elementValue2;
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->method('offsetExists')->willReturnMap([
            [$elementIdentifier1, true],
            [$elementIdentifier2, true],
        ]);
        $formRuntimeMock->method('offsetGet')->willReturnMap([
            [$elementIdentifier1, $elementValue1],
            [$elementIdentifier2, $elementValue2],
        ]);
        $subject = new AbstractFinisherFixture();
        self::assertSame($expected, $subject->substituteRuntimeReferences($input, $formRuntimeMock));
    }

    #[Test]
    public function substituteRuntimeReferencesReturnsValueFromFormRuntimeIfInputReferenceAFormElementIdentifierWhoseValueIsAnArray(): void
    {
        $elementIdentifier = 'element-identifier-1';
        $input = '{' . $elementIdentifier . '}';
        $expected = ['bar', 'foobar'];
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->method('offsetExists')->with($elementIdentifier)->willReturn(true);
        $formRuntimeMock->method('offsetGet')->with($elementIdentifier)->willReturn($expected);
        $subject = new AbstractFinisherFixture();
        self::assertSame($expected, $subject->substituteRuntimeReferences($input, $formRuntimeMock));
    }

    #[Test]
    public function substituteRuntimeReferencesReturnsValueFromFormRuntimeIfInputIsArrayAndSomeItemsReferenceAFormElementIdentifierWhoseValueIsAnArray(): void
    {
        $elementIdentifier1 = 'element-identifier-1';
        $elementValue1 = ['klaus', 'fritz'];
        $elementIdentifier2 = 'element-identifier-2';
        $elementValue2 = ['stan', 'steve'];
        $input = [
            '{' . $elementIdentifier1 . '}',
            'static value',
            'norbert' => [
                'lisa',
                '{' . $elementIdentifier1 . '}',
                '{' . $elementIdentifier2 . '}',
            ],
        ];
        $expected = [
            ['klaus', 'fritz'],
            'static value',
            'norbert' => [
                'lisa',
                ['klaus', 'fritz'],
                ['stan', 'steve'],
            ],
        ];
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->method('offsetExists')->willReturnMap([
            [$elementIdentifier1, true],
            [$elementIdentifier2, true],
        ]);
        $formRuntimeMock->method('offsetGet')->willReturnMap([
            [$elementIdentifier1, $elementValue1],
            [$elementIdentifier2, $elementValue2],
        ]);
        $subject = new AbstractFinisherFixture();
        self::assertSame($expected, $subject->substituteRuntimeReferences($input, $formRuntimeMock));
    }

    #[Test]
    public function substituteRuntimeReferencesReturnsNoReplacedValueIfInputReferenceANonExistingFormElement(): void
    {
        $elementIdentifier = 'element-identifier-1';
        $input = '{' . $elementIdentifier . '}';
        $expected = '{' . $elementIdentifier . '}';
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->method('offsetExists')->with($elementIdentifier)->willReturn(true);
        $formRuntimeMock->method('offsetGet')->with($elementIdentifier)->willReturn($expected);
        $finisherContextMock = $this->createMock(FinisherContext::class);
        $finisherContextMock->method('getFinisherVariableProvider')->willReturn(new FinisherVariableProvider());
        $subject = new AbstractFinisherFixture();
        $subject->finisherContext = $finisherContextMock;
        self::assertSame($expected, $subject->substituteRuntimeReferences($input, $formRuntimeMock));
    }

    #[Test]
    public function substituteRuntimeReferencesReturnsTimestampIfInputIsATimestampRequestTrigger(): void
    {
        $input = '{__currentTimestamp}';
        $expected = '#^([0-9]{10})$#';
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $subject = new AbstractFinisherFixture();
        self::assertMatchesRegularExpression($expected, (string)$subject->substituteRuntimeReferences($input, $formRuntimeMock));
    }

    #[Test]
    public function substituteRuntimeReferencesReturnsResolvesElementIdentifiersInArrayKeys(): void
    {
        $elementIdentifier1 = 'element-identifier-1';
        $elementValue1 = 'norbert';
        $elementIdentifier2 = 'element-identifier-2';
        $elementValue2 = ['stan', 'steve'];
        $input = [
            '{' . $elementIdentifier1 . '}' => [
                'lisa',
                '{' . $elementIdentifier2 . '}',
            ],
        ];
        $expected = [
            'norbert' => [
                'lisa',
                ['stan', 'steve'],
            ],
        ];
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->method('offsetExists')->willReturnMap([
            [$elementIdentifier1, true],
            [$elementIdentifier2, true],
        ]);
        $formRuntimeMock->method('offsetGet')->willReturnMap([
            [$elementIdentifier1, $elementValue1],
            [$elementIdentifier2, $elementValue2],
        ]);
        $subject = new AbstractFinisherFixture();
        self::assertSame($expected, $subject->substituteRuntimeReferences($input, $formRuntimeMock));
    }

    #[Test]
    public function substituteRuntimeReferencesConvertsObjectsToString(): void
    {
        $date = new \DateTime('@1574415600');
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->method('offsetExists')->with('date-1')->willReturn(true);
        $formRuntimeMock->method('offsetGet')->with('date-1')->willReturn($date);
        $stringableElement = new class () implements StringableFormElementInterface {
            /**
             * @param \DateTimeInterface $value
             */
            public function valueToString($value): string
            {
                return $value->format('Y-m-d');
            }
        };
        $formDefinitionMock = $this->createMock(FormDefinition::class);
        $formDefinitionMock->method('getElementByIdentifier')->with('date-1')->willReturn($stringableElement);
        $formRuntimeMock->method('getFormDefinition')->willReturn($formDefinitionMock);
        $subject = new AbstractFinisherFixture();
        self::assertSame('When: 2019-11-22', $subject->substituteRuntimeReferences('When: {date-1}', $formRuntimeMock));
    }

    #[Test]
    public function substituteRuntimeReferencesThrowsExceptionOnObjectWithoutStringableElement(): void
    {
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->method('offsetExists')->with('date-1')->willReturn(true);
        $formRuntimeMock->method('offsetGet')->with('date-1')->willReturn(new \DateTime());
        $formDefinitionMock = $this->createMock(FormDefinition::class);
        $formRuntimeMock->method('getFormDefinition')->willReturn($formDefinitionMock);
        $this->expectException(FinisherException::class);
        $this->expectExceptionCode(1574362327);
        $subject = new AbstractFinisherFixture();
        $subject->substituteRuntimeReferences('When: {date-1}', $formRuntimeMock);
    }

    #[Test]
    public function substituteRuntimeReferencesThrowsExceptionOnMultipleVariablesResolvedAsArray(): void
    {
        $elementIdentifier = 'element-identifier-1';
        $input = 'BEFORE {' . $elementIdentifier . '} AFTER';
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->method('offsetExists')->with($elementIdentifier)->willReturn(true);
        $formRuntimeMock->method('offsetGet')->with($elementIdentifier)->willReturn(['value-1', 'value-2']);
        $this->expectException(FinisherException::class);
        $this->expectExceptionCode(1519239265);
        $subject = new AbstractFinisherFixture();
        $subject->substituteRuntimeReferences($input, $formRuntimeMock);
    }
}
