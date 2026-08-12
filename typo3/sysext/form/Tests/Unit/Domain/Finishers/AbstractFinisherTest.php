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
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\CMS\Form\Domain\Model\FormElements\ProcessableValueFormElementInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\StringableFormElementInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Service\FormValueResolver;
use TYPO3\CMS\Form\Service\TranslationService;
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
        $finisherContextStub = self::createStub(FinisherContext::class);
        $formRuntimeStub = self::createStub(FormRuntime::class);
        $formRuntimeStub->method('offsetExists')->willReturn(true);
        $formRuntimeStub->method('offsetGet')->willReturn(null);
        $finisherContextStub->method('getFormRuntime')->willReturn($formRuntimeStub);
        $finisherContextStub->method('getFinisherVariableProvider')->willReturn(new FinisherVariableProvider());

        $subject = new AbstractFinisherFixture();
        $subject->options = [];
        $subject->defaultOptions = [
            'subject' => 'defaultValue',
        ];
        $subject->finisherContext = $finisherContextStub;
        self::assertSame('defaultValue', $subject->parseOption('subject'));
    }

    #[Test]
    public function parseOptionReturnsDefaultOptionValueIfOptionValueIsAFormElementReferenceAndTheFormElementValueIsEmpty(): void
    {
        $finisherContextStub = self::createStub(FinisherContext::class);
        $formRuntimeStub = self::createStub(FormRuntime::class);
        $formRuntimeStub->method('offsetExists')->willReturn(true);
        $formRuntimeStub->method('offsetGet')->willReturn('');
        $formRuntimeStub->method('getFormDefinition')->willReturn(new FormDefinition('form'));
        $finisherContextStub->method('getFormRuntime')->willReturn($formRuntimeStub);

        $subject = new AbstractFinisherFixture();
        $subject->options = [
            'subject' => '{element-identifier-1}',
        ];
        $subject->defaultOptions = [
            'subject' => 'defaultValue',
        ];
        $subject->finisherContext = $finisherContextStub;
        self::assertSame('defaultValue', $subject->parseOption('subject'));
    }

    #[Test]
    public function substituteRuntimeReferencesReturnsArrayIfInputIsArray(): void
    {
        $formRuntimeStub = self::createStub(FormRuntime::class);
        $input = ['bar', 'foobar', ['x', 'y']];
        $expected = ['bar', 'foobar', ['x', 'y']];
        $subject = new AbstractFinisherFixture();
        self::assertSame($expected, $subject->substituteRuntimeReferences($input, $formRuntimeStub));
    }

    #[Test]
    public function substituteRuntimeReferencesReturnsStringIfInputIsString(): void
    {
        $formRuntimeStub = self::createStub(FormRuntime::class);
        $input = 'foobar';
        $expected = 'foobar';
        $subject = new AbstractFinisherFixture();
        self::assertSame($expected, $subject->substituteRuntimeReferences($input, $formRuntimeStub));
    }

    #[Test]
    public function substituteRuntimeReferencesReturnsValueFromFormRuntimeIfInputReferenceAFormElementIdentifierWhoseValueIsAString(): void
    {
        $elementIdentifier = 'element-identifier-1';
        $input = '{' . $elementIdentifier . '}';
        $expected = 'element-value';
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->expects($this->atMost(PHP_INT_MAX))->method('offsetExists')->with($elementIdentifier)->willReturn(true);
        $formRuntimeMock->expects($this->atMost(PHP_INT_MAX))->method('offsetGet')->with($elementIdentifier)->willReturn($expected);
        $formRuntimeMock->method('getFormDefinition')->willReturn(new FormDefinition('form'));
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
        $formRuntimeStub = self::createStub(FormRuntime::class);
        $formRuntimeStub->method('offsetExists')->willReturnMap([
            [$elementIdentifier1, true],
            [$elementIdentifier2, true],
        ]);
        $formRuntimeStub->method('offsetGet')->willReturnMap([
            [$elementIdentifier1, $elementValue1],
            [$elementIdentifier2, $elementValue2],
        ]);
        $formRuntimeStub->method('getFormDefinition')->willReturn(new FormDefinition('form'));
        $subject = new AbstractFinisherFixture();
        self::assertSame($expected, $subject->substituteRuntimeReferences($input, $formRuntimeStub));
    }

    #[Test]
    public function substituteRuntimeReferencesReturnsValueFromFormRuntimeIfInputReferenceAFormElementIdentifierWhoseValueIsAnArray(): void
    {
        $elementIdentifier = 'element-identifier-1';
        $input = '{' . $elementIdentifier . '}';
        $expected = ['bar', 'foobar'];
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->expects($this->atMost(PHP_INT_MAX))->method('offsetExists')->with($elementIdentifier)->willReturn(true);
        $formRuntimeMock->expects($this->atMost(PHP_INT_MAX))->method('offsetGet')->with($elementIdentifier)->willReturn($expected);
        $formRuntimeMock->method('getFormDefinition')->willReturn(new FormDefinition('form'));
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
        $formRuntimeStub = self::createStub(FormRuntime::class);
        $formRuntimeStub->method('offsetExists')->willReturnMap([
            [$elementIdentifier1, true],
            [$elementIdentifier2, true],
        ]);
        $formRuntimeStub->method('offsetGet')->willReturnMap([
            [$elementIdentifier1, $elementValue1],
            [$elementIdentifier2, $elementValue2],
        ]);
        $formRuntimeStub->method('getFormDefinition')->willReturn(new FormDefinition('form'));
        $subject = new AbstractFinisherFixture();
        self::assertSame($expected, $subject->substituteRuntimeReferences($input, $formRuntimeStub));
    }

    #[Test]
    public function substituteRuntimeReferencesReturnsNoReplacedValueIfInputReferenceANonExistingFormElement(): void
    {
        $elementIdentifier = 'element-identifier-1';
        $input = '{' . $elementIdentifier . '}';
        $expected = '{' . $elementIdentifier . '}';
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->expects($this->atMost(PHP_INT_MAX))->method('offsetExists')->with($elementIdentifier)->willReturn(true);
        $formRuntimeMock->expects($this->atMost(PHP_INT_MAX))->method('offsetGet')->with($elementIdentifier)->willReturn($expected);
        $formRuntimeMock->method('getFormDefinition')->willReturn(new FormDefinition('form'));
        $finisherContextStub = self::createStub(FinisherContext::class);
        $finisherContextStub->method('getFinisherVariableProvider')->willReturn(new FinisherVariableProvider());
        $subject = new AbstractFinisherFixture();
        $subject->finisherContext = $finisherContextStub;
        self::assertSame($expected, $subject->substituteRuntimeReferences($input, $formRuntimeMock));
    }

    #[Test]
    public function substituteRuntimeReferencesKeepsPlaceholderWhenNothingResolvesIt(): void
    {
        $elementIdentifier = 'element-identifier-1';
        $input = 'BEFORE {' . $elementIdentifier . '} AFTER';
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->expects($this->atMost(PHP_INT_MAX))->method('offsetExists')->with($elementIdentifier)->willReturn(true);
        $formRuntimeMock->expects($this->atMost(PHP_INT_MAX))->method('offsetGet')->with($elementIdentifier)->willReturn(null);
        $formRuntimeMock->method('getFormDefinition')->willReturn(new FormDefinition('form'));
        $finisherContextStub = self::createStub(FinisherContext::class);
        $finisherContextStub->method('getFinisherVariableProvider')->willReturn(new FinisherVariableProvider());
        $subject = new AbstractFinisherFixture();
        $subject->finisherContext = $finisherContextStub;
        self::assertSame($input, $subject->substituteRuntimeReferences($input, $formRuntimeMock));
    }

    #[Test]
    public function substituteRuntimeReferencesReturnsTimestampIfInputIsATimestampRequestTrigger(): void
    {
        $input = '{__currentTimestamp}';
        $expected = '#^([0-9]{10})$#';
        $formRuntimeStub = self::createStub(FormRuntime::class);
        $subject = new AbstractFinisherFixture();
        self::assertMatchesRegularExpression($expected, (string)$subject->substituteRuntimeReferences($input, $formRuntimeStub));
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
        $formRuntimeStub = self::createStub(FormRuntime::class);
        $formRuntimeStub->method('offsetExists')->willReturnMap([
            [$elementIdentifier1, true],
            [$elementIdentifier2, true],
        ]);
        $formRuntimeStub->method('offsetGet')->willReturnMap([
            [$elementIdentifier1, $elementValue1],
            [$elementIdentifier2, $elementValue2],
        ]);
        $formRuntimeStub->method('getFormDefinition')->willReturn(new FormDefinition('form'));
        $subject = new AbstractFinisherFixture();
        self::assertSame($expected, $subject->substituteRuntimeReferences($input, $formRuntimeStub));
    }

    #[Test]
    public function substituteRuntimeReferencesConvertsObjectsToString(): void
    {
        $date = new \DateTime('@1574415600');
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->expects($this->atMost(PHP_INT_MAX))->method('offsetExists')->with('date-1')->willReturn(true);
        $formRuntimeMock->expects($this->atMost(PHP_INT_MAX))->method('offsetGet')->with('date-1')->willReturn($date);
        $stringableElement = new class ('date-1', 'Date') extends GenericFormElement implements StringableFormElementInterface {
            /**
             * @param \DateTimeInterface $value
             */
            public function valueToString($value): string
            {
                return $value->format('Y-m-d');
            }
        };
        $formDefinition = new FormDefinition('form');
        $stringableElement->setParentRenderable($formDefinition);
        $formRuntimeMock->method('getFormDefinition')->willReturn($formDefinition);
        $subject = new AbstractFinisherFixture();
        self::assertSame('When: 2019-11-22', $subject->substituteRuntimeReferences('When: {date-1}', $formRuntimeMock));
    }

    #[Test]
    public function substituteRuntimeReferencesThrowsExceptionOnObjectWithoutStringableElement(): void
    {
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->expects($this->atMost(PHP_INT_MAX))->method('offsetExists')->with('date-1')->willReturn(true);
        $formRuntimeMock->expects($this->atMost(PHP_INT_MAX))->method('offsetGet')->with('date-1')->willReturn(new \DateTime());
        $formRuntimeMock->method('getFormDefinition')->willReturn(new FormDefinition('form'));
        $this->expectException(FinisherException::class);
        $this->expectExceptionCode(1574362327);
        $subject = new AbstractFinisherFixture();
        $subject->substituteRuntimeReferences('When: {date-1}', $formRuntimeMock);
    }

    #[Test]
    public function substituteRuntimeReferencesThrowsExceptionOnArrayWithNonStringableObject(): void
    {
        $elementIdentifier = 'element-identifier-1';
        $input = 'BEFORE {' . $elementIdentifier . '} AFTER';
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->expects($this->atMost(PHP_INT_MAX))->method('offsetExists')->with($elementIdentifier)->willReturn(true);
        $formRuntimeMock->expects($this->atMost(PHP_INT_MAX))->method('offsetGet')->with($elementIdentifier)->willReturn([new \stdClass()]);
        $formRuntimeMock->method('getFormDefinition')->willReturn(new FormDefinition('form'));
        $this->expectException(FinisherException::class);
        $this->expectExceptionCode(1787754756);
        $subject = new AbstractFinisherFixture();
        $subject->substituteRuntimeReferences($input, $formRuntimeMock);
    }

    #[Test]
    public function substituteRuntimeReferencesImplodesArrayWhenInterpolatedIntoString(): void
    {
        $elementIdentifier = 'element-identifier-1';
        $input = 'BEFORE {' . $elementIdentifier . '} AFTER';
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->expects($this->atMost(PHP_INT_MAX))->method('offsetExists')->with($elementIdentifier)->willReturn(true);
        $formRuntimeMock->expects($this->atMost(PHP_INT_MAX))->method('offsetGet')->with($elementIdentifier)->willReturn(['value-1', 'value-2']);
        $formRuntimeMock->method('getFormDefinition')->willReturn(new FormDefinition('form'));
        $subject = new AbstractFinisherFixture();
        self::assertSame('BEFORE value-1, value-2 AFTER', $subject->substituteRuntimeReferences($input, $formRuntimeMock));
    }

    #[Test]
    public function parseOptionKeepsTheSubmittedValue(): void
    {
        $subject = $this->buildFinisherWithOptionableElement('salutation', 'mr', 'Mister');
        self::assertSame('mr', $subject->parseOption('subject'));
    }

    #[Test]
    public function parseOptionAsDisplayValueResolvesTheRepresentationOfTheElement(): void
    {
        $subject = $this->buildFinisherWithOptionableElement('salutation', 'mr', 'Mister');
        self::assertSame('Mister', $subject->parseOptionAsDisplayValue('subject'));
    }

    #[Test]
    public function parseOptionKeepsTheSubmittedValueAfterADisplayValueHasBeenParsed(): void
    {
        $subject = $this->buildFinisherWithOptionableElement('salutation', 'mr', 'Mister');
        $subject->parseOptionAsDisplayValue('subject');
        self::assertSame('mr', $subject->parseOption('subject'));
    }

    private function buildFinisherWithOptionableElement(
        string $elementIdentifier,
        string $submittedValue,
        string $displayValue
    ): AbstractFinisherFixture {
        $element = new class ($elementIdentifier, '', $displayValue) extends GenericFormElement implements ProcessableValueFormElementInterface {
            public function __construct(string $identifier, string $type, private readonly string $displayValue)
            {
                parent::__construct($identifier, $type);
            }

            public function processElementValue(mixed $value, FormRuntime $formRuntime): mixed
            {
                return $this->displayValue;
            }
        };
        $formDefinition = new FormDefinition('form');
        $element->setParentRenderable($formDefinition);

        $formRuntimeStub = self::createStub(FormRuntime::class);
        $formRuntimeStub->method('offsetExists')->willReturn(true);
        $formRuntimeStub->method('offsetGet')->willReturn($submittedValue);
        $formRuntimeStub->method('getFormDefinition')->willReturn($formDefinition);

        $finisherContextStub = self::createStub(FinisherContext::class);
        $finisherContextStub->method('getFormRuntime')->willReturn($formRuntimeStub);
        $finisherContextStub->method('getFinisherVariableProvider')->willReturn(new FinisherVariableProvider());

        $subject = new AbstractFinisherFixture();
        $subject->finisherContext = $finisherContextStub;
        $subject->injectFormValueResolver(new FormValueResolver(self::createStub(TranslationService::class)));
        $subject->options = ['subject' => '{' . $elementIdentifier . '}'];
        return $subject;
    }
}
