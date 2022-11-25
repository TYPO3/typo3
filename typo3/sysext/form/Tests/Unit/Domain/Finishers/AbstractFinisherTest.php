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

use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Finishers\FinisherVariableProvider;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\StringableFormElementInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AbstractFinisherTest extends UnitTestCase
{
    /**
     * @test
     */
    public function parseOptionReturnsNullIfOptionNameIsTranslation(): void
    {
        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        self::assertNull($mockAbstractFinisher->_call('parseOption', 'translation'));
    }

    /**
     * @test
     */
    public function parseOptionReturnsNullIfOptionNameNotExistsWithinOptions(): void
    {
        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $mockAbstractFinisher->_set('options', []);

        self::assertNull($mockAbstractFinisher->_call('parseOption', 'foo'));
    }

    /**
     * @test
     */
    public function parseOptionReturnsNullIfOptionNameNotExistsWithinDefaultOptions(): void
    {
        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $mockAbstractFinisher->_set('options', []);

        self::assertNull($mockAbstractFinisher->_call('parseOption', 'foo'));
    }

    /**
     * @test
     */
    public function parseOptionReturnsBoolOptionValuesAsBool(): void
    {
        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $mockAbstractFinisher->_set('options', [
            'foo1' => false,
        ]);

        self::assertFalse($mockAbstractFinisher->_call('parseOption', 'foo1'));
    }

    /**
     * @test
     */
    public function parseOptionReturnsDefaultOptionValueIfOptionNameNotExistsWithinOptionsButWithinDefaultOptions(): void
    {
        $expected = 'defaultValue';

        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false,
            false,
            true,
            [
                'translateFinisherOption',
            ]
        );

        $mockAbstractFinisher
            ->method('translateFinisherOption')
            ->willReturnArgument(0);

        $mockAbstractFinisher->_set('options', []);
        $mockAbstractFinisher->_set('defaultOptions', [
            'subject' => $expected,
        ]);

        $finisherContextMock = $this->createMock(FinisherContext::class);

        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->method('offsetExists')->with(self::anything())->willReturn(true);
        $formRuntimeMock->method('offsetGet')->with(self::anything())->willReturn(null);

        $finisherContextMock->method('getFormRuntime')->willReturn($formRuntimeMock);
        $finisherContextMock->method('getFinisherVariableProvider')
            ->willReturn(new FinisherVariableProvider());

        $mockAbstractFinisher->_set('finisherContext', $finisherContextMock);

        self::assertSame($expected, $mockAbstractFinisher->_call('parseOption', 'subject'));
    }

    /**
     * @test
     */
    public function parseOptionReturnsDefaultOptionValueIfOptionValueIsAFormElementReferenceAndTheFormElementValueIsEmpty(): void
    {
        $elementIdentifier = 'element-identifier-1';
        $expected = 'defaultValue';

        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false,
            false,
            true,
            [
                'translateFinisherOption',
            ]
        );

        $mockAbstractFinisher
            ->method('translateFinisherOption')
            ->willReturnArgument(0);

        $mockAbstractFinisher->_set('options', [
            'subject' => '{' . $elementIdentifier . '}',
        ]);
        $mockAbstractFinisher->_set('defaultOptions', [
            'subject' => $expected,
        ]);

        $finisherContextMock = $this->createMock(FinisherContext::class);

        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->method('offsetExists')->with($elementIdentifier)->willReturn(true);
        $formRuntimeMock->method('offsetGet')->with($elementIdentifier)->willReturn('');

        $finisherContextMock->method('getFormRuntime')->willReturn($formRuntimeMock);

        $mockAbstractFinisher->_set('finisherContext', $finisherContextMock);

        self::assertSame($expected, $mockAbstractFinisher->_call('parseOption', 'subject'));
    }

    /**
     * @test
     */
    public function parseOptionResolvesFormElementReferenceFromTranslation(): void
    {
        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false,
            false,
            true,
            [
                'translateFinisherOption',
            ]
        );

        $elementIdentifier = 'element-identifier-1';
        $elementValue = 'element-value-1';
        $elementReferenceName = '{' . $elementIdentifier . '}';

        $translationValue = 'subject: ' . $elementReferenceName;
        $expected = 'subject: ' . $elementValue;

        $mockAbstractFinisher
            ->method('translateFinisherOption')
            ->willReturn($translationValue);

        $mockAbstractFinisher->_set('options', [
            'subject' => '',
        ]);

        $finisherContextMock = $this->createMock(FinisherContext::class);

        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->method('offsetExists')->with($elementIdentifier)->willReturn(true);
        $formRuntimeMock->method('offsetGet')->with($elementIdentifier)->willReturn($elementValue);

        $finisherContextMock->method('getFormRuntime')->willReturn($formRuntimeMock);

        $mockAbstractFinisher->_set('finisherContext', $finisherContextMock);

        self::assertSame($expected, $mockAbstractFinisher->_call('parseOption', 'subject'));
    }

    /**
     * @test
     */
    public function substituteRuntimeReferencesReturnsArrayIfInputIsArray(): void
    {
        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $formRuntimeMock = $this->createMock(FormRuntime::class);

        $input = ['bar', 'foobar', ['x', 'y']];
        $expected = ['bar', 'foobar', ['x', 'y']];

        self::assertSame($expected, $mockAbstractFinisher->_call('substituteRuntimeReferences', $input, $formRuntimeMock));
    }

    /**
     * @test
     */
    public function substituteRuntimeReferencesReturnsStringIfInputIsString(): void
    {
        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $formRuntimeMock = $this->createMock(FormRuntime::class);

        $input = 'foobar';
        $expected = 'foobar';

        self::assertSame($expected, $mockAbstractFinisher->_call('substituteRuntimeReferences', $input, $formRuntimeMock));
    }

    /**
     * @test
     */
    public function substituteRuntimeReferencesReturnsValueFromFormRuntimeIfInputReferenceAFormElementIdentifierWhoseValueIsAString(): void
    {
        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $elementIdentifier = 'element-identifier-1';
        $input = '{' . $elementIdentifier . '}';
        $expected = 'element-value';

        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->method('offsetExists')->with($elementIdentifier)->willReturn(true);
        $formRuntimeMock->method('offsetGet')->with($elementIdentifier)->willReturn($expected);

        self::assertSame($expected, $mockAbstractFinisher->_call('substituteRuntimeReferences', $input, $formRuntimeMock));
    }

    /**
     * @test
     */
    public function substituteRuntimeReferencesReturnsValueFromFormRuntimeIfInputReferenceMultipleFormElementIdentifierWhoseValueIsAString(): void
    {
        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

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

        self::assertSame($expected, $mockAbstractFinisher->_call('substituteRuntimeReferences', $input, $formRuntimeMock));
    }

    /**
     * @test
     */
    public function substituteRuntimeReferencesReturnsValueFromFormRuntimeIfInputReferenceAFormElementIdentifierWhoseValueIsAnArray(): void
    {
        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $elementIdentifier = 'element-identifier-1';
        $input = '{' . $elementIdentifier . '}';
        $expected = ['bar', 'foobar'];

        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->method('offsetExists')->with($elementIdentifier)->willReturn(true);
        $formRuntimeMock->method('offsetGet')->with($elementIdentifier)->willReturn($expected);

        self::assertSame($expected, $mockAbstractFinisher->_call('substituteRuntimeReferences', $input, $formRuntimeMock));
    }

    /**
     * @test
     */
    public function substituteRuntimeReferencesReturnsValueFromFormRuntimeIfInputIsArrayAndSomeItemsReferenceAFormElementIdentifierWhoseValueIsAnArray(): void
    {
        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

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

        self::assertSame($expected, $mockAbstractFinisher->_call('substituteRuntimeReferences', $input, $formRuntimeMock));
    }

    /**
     * @test
     */
    public function substituteRuntimeReferencesReturnsNoReplacedValueIfInputReferenceANonExistingFormElement(): void
    {
        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $elementIdentifier = 'element-identifier-1';
        $input = '{' . $elementIdentifier . '}';
        $expected = '{' . $elementIdentifier . '}';

        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->method('offsetExists')->with($elementIdentifier)->willReturn(true);
        $formRuntimeMock->method('offsetGet')->with($elementIdentifier)->willReturn($expected);

        $finisherContextMock = $this->createMock(FinisherContext::class);
        $finisherContextMock->method('getFinisherVariableProvider')->willReturn(new FinisherVariableProvider());
        $mockAbstractFinisher->_set('finisherContext', $finisherContextMock);

        self::assertSame($expected, $mockAbstractFinisher->_call('substituteRuntimeReferences', $input, $formRuntimeMock));
    }

    /**
     * @test
     */
    public function substituteRuntimeReferencesReturnsTimestampIfInputIsATimestampRequestTrigger(): void
    {
        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $input = '{__currentTimestamp}';
        $expected = '#^([0-9]{10})$#';

        $formRuntimeMock = $this->createMock(FormRuntime::class);

        self::assertMatchesRegularExpression(
            $expected,
            (string)$mockAbstractFinisher->_call('substituteRuntimeReferences', $input, $formRuntimeMock)
        );
    }

    /**
     * @test
     */
    public function substituteRuntimeReferencesReturnsResolvesElementIdentifiersInArrayKeys(): void
    {
        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

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

        self::assertSame($expected, $mockAbstractFinisher->_call('substituteRuntimeReferences', $input, $formRuntimeMock));
    }

    /**
     * @test
     */
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

        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );
        $result = $mockAbstractFinisher->_call(
            'substituteRuntimeReferences',
            'When: {date-1}',
            $formRuntimeMock
        );

        self::assertSame('When: 2019-11-22', $result);
    }

    /**
     * @test
     */
    public function substituteRuntimeReferencesThrowsExceptionOnObjectWithoutStringableElement(): void
    {
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->method('offsetExists')->with('date-1')->willReturn(true);
        $formRuntimeMock->method('offsetGet')->with('date-1')->willReturn(new \DateTime());

        $formDefinitionMock = $this->createMock(FormDefinition::class);
        $formRuntimeMock->method('getFormDefinition')->willReturn($formDefinitionMock);

        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $this->expectException(FinisherException::class);
        $this->expectExceptionCode(1574362327);

        $mockAbstractFinisher->_call(
            'substituteRuntimeReferences',
            'When: {date-1}',
            $formRuntimeMock
        );
    }

    /**
     * @test
     */
    public function substituteRuntimeReferencesThrowsExceptionOnMultipleVariablesResolvedAsArray(): void
    {
        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );

        $elementIdentifier = 'element-identifier-1';
        $input = 'BEFORE {' . $elementIdentifier . '} AFTER';

        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $formRuntimeMock->method('offsetExists')->with($elementIdentifier)->willReturn(true);
        $formRuntimeMock->method('offsetGet')->with($elementIdentifier)->willReturn(['value-1', 'value-2']);

        $finisherContextMock = $this->createMock(FinisherContext::class);
        $finisherContextMock->method('getFinisherVariableProvider')->willReturn(new FinisherVariableProvider());
        $mockAbstractFinisher->_set('finisherContext', $finisherContextMock);

        $this->expectException(FinisherException::class);
        $this->expectExceptionCode(1519239265);

        $mockAbstractFinisher->_call(
            'substituteRuntimeReferences',
            $input,
            $formRuntimeMock
        );
    }
}
