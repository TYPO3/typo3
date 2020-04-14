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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Finishers\FinisherVariableProvider;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\StringableFormElementInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
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

        $expected = false;

        self::assertSame($expected, $mockAbstractFinisher->_call('parseOption', 'foo1'));
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
                'translateFinisherOption'
            ]
        );

        $mockAbstractFinisher
            ->expects(self::any())
            ->method('translateFinisherOption')
            ->willReturnArgument(0);

        $mockAbstractFinisher->_set('options', []);
        $mockAbstractFinisher->_set('defaultOptions', [
            'subject' => $expected
        ]);

        $finisherContextProphecy = $this->prophesize(FinisherContext::class);

        $formRuntimeProphecy = $this->prophesize(FormRuntime::class);
        $formRuntimeProphecy->offsetExists(Argument::cetera())->willReturn(true);
        $formRuntimeProphecy->offsetGet(Argument::cetera())->willReturn(null);

        $finisherContextProphecy->getFormRuntime(Argument::cetera())
            ->willReturn($formRuntimeProphecy->reveal());
        $finisherContextProphecy->getFinisherVariableProvider(Argument::cetera())
            ->willReturn(new FinisherVariableProvider());

        $mockAbstractFinisher->_set('finisherContext', $finisherContextProphecy->reveal());

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
                'translateFinisherOption'
            ]
        );

        $mockAbstractFinisher
            ->expects(self::any())
            ->method('translateFinisherOption')
            ->willReturnArgument(0);

        $mockAbstractFinisher->_set('options', [
            'subject' => '{' . $elementIdentifier . '}'
        ]);
        $mockAbstractFinisher->_set('defaultOptions', [
            'subject' => $expected
        ]);

        $finisherContextProphecy = $this->prophesize(FinisherContext::class);

        $formRuntimeProphecy = $this->createFormRuntimeProphecy([
            $elementIdentifier => ''
        ]);

        $finisherContextProphecy->getFormRuntime(Argument::cetera())
            ->willReturn($formRuntimeProphecy->reveal());

        $mockAbstractFinisher->_set('finisherContext', $finisherContextProphecy->reveal());

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
                'translateFinisherOption'
            ]
        );

        $elementIdentifier = 'element-identifier-1';
        $elementValue = 'element-value-1';
        $elementReferenceName = '{' . $elementIdentifier . '}';

        $translationValue = 'subject: ' . $elementReferenceName;
        $expected = 'subject: ' . $elementValue;

        $mockAbstractFinisher
            ->expects(self::any())
            ->method('translateFinisherOption')
            ->willReturn($translationValue);

        $mockAbstractFinisher->_set('options', [
            'subject' => ''
        ]);

        $finisherContextProphecy = $this->prophesize(FinisherContext::class);

        $formRuntimeProphecy = $this->createFormRuntimeProphecy([
            $elementIdentifier => $elementValue
        ]);

        $finisherContextProphecy->getFormRuntime(Argument::cetera())
            ->willReturn($formRuntimeProphecy->reveal());

        $mockAbstractFinisher->_set('finisherContext', $finisherContextProphecy->reveal());

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

        $formRuntimeProphecy = $this->prophesize(FormRuntime::class);

        $input = ['bar', 'foobar', ['x', 'y']];
        $expected = ['bar', 'foobar', ['x', 'y']];

        self::assertSame($expected, $mockAbstractFinisher->_call('substituteRuntimeReferences', $input, $formRuntimeProphecy->reveal()));
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

        $formRuntimeProphecy = $this->prophesize(FormRuntime::class);

        $input = 'foobar';
        $expected = 'foobar';

        self::assertSame($expected, $mockAbstractFinisher->_call('substituteRuntimeReferences', $input, $formRuntimeProphecy->reveal()));
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

        $formRuntimeProphecy = $this->createFormRuntimeProphecy([
            $elementIdentifier => $expected
        ]);

        self::assertSame($expected, $mockAbstractFinisher->_call('substituteRuntimeReferences', $input, $formRuntimeProphecy->reveal()));
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

        $formRuntimeProphecy = $this->createFormRuntimeProphecy([
            $elementIdentifier1 => $elementValue1,
            $elementIdentifier2 => $elementValue2
        ]);

        self::assertSame($expected, $mockAbstractFinisher->_call('substituteRuntimeReferences', $input, $formRuntimeProphecy->reveal()));
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

        $formRuntimeProphecy = $this->createFormRuntimeProphecy([
            $elementIdentifier => $expected
        ]);

        self::assertSame($expected, $mockAbstractFinisher->_call('substituteRuntimeReferences', $input, $formRuntimeProphecy->reveal()));
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
            ]
        ];

        $formRuntimeProphecy = $this->createFormRuntimeProphecy([
            $elementIdentifier1 => $elementValue1,
            $elementIdentifier2 => $elementValue2
        ]);

        self::assertSame($expected, $mockAbstractFinisher->_call('substituteRuntimeReferences', $input, $formRuntimeProphecy->reveal()));
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

        $formRuntimeProphecy = $this->createFormRuntimeProphecy([
            $elementIdentifier => $expected
        ]);

        $finisherContextProphecy = $this->prophesize(FinisherContext::class);
        $finisherContextProphecy->getFinisherVariableProvider(Argument::cetera())->willReturn(new FinisherVariableProvider());
        $mockAbstractFinisher->_set('finisherContext', $finisherContextProphecy->reveal());

        self::assertSame($expected, $mockAbstractFinisher->_call('substituteRuntimeReferences', $input, $formRuntimeProphecy->reveal()));
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

        $formRuntimeProphecy = $this->prophesize(FormRuntime::class);

        self::assertEquals(1, preg_match($expected, (string)$mockAbstractFinisher->_call('substituteRuntimeReferences', $input, $formRuntimeProphecy->reveal())));
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
            ]
        ];

        $formRuntimeProphecy = $this->createFormRuntimeProphecy([
            $elementIdentifier1 => $elementValue1,
            $elementIdentifier2 => $elementValue2
        ]);

        self::assertSame($expected, $mockAbstractFinisher->_call('substituteRuntimeReferences', $input, $formRuntimeProphecy->reveal()));
    }

    /**
     * @test
     */
    public function substituteRuntimeReferencesConvertsObjectsToString(): void
    {
        $date = new \DateTime('@1574415600');
        $formRuntimeProphecy = $this->createFormRuntimeProphecy([
            'date-1' => $date,
        ]);

        $stringableElement = new class() implements StringableFormElementInterface {
            /**
             * @param \DateTimeInterface $value
             */
            public function valueToString($value): string
            {
                return $value->format('Y-m-d');
            }
        };
        $formDefinitionProphecy = $this->prophesize(FormDefinition::class);
        $formDefinitionProphecy->getElementByIdentifier('date-1')->willReturn($stringableElement);
        $formRuntimeProphecy->getFormDefinition()->willReturn($formDefinitionProphecy->reveal());

        $mockAbstractFinisher = $this->getAccessibleMockForAbstractClass(
            AbstractFinisher::class,
            [],
            '',
            false
        );
        $result = $mockAbstractFinisher->_call(
            'substituteRuntimeReferences',
            'When: {date-1}',
            $formRuntimeProphecy->reveal()
        );

        self::assertSame('When: 2019-11-22', $result);
    }

    /**
     * @test
     */
    public function substituteRuntimeReferencesThrowsExceptionOnObjectWithoutStringableElement(): void
    {
        $formRuntimeProphecy = $this->createFormRuntimeProphecy([
            'date-1' => new \DateTime(),
        ]);

        $formDefinitionProphecy = $this->prophesize(FormDefinition::class);
        $formDefinitionProphecy->getElementByIdentifier('date-1')->willReturn($this->prophesize(FormElementInterface::class)->reveal());
        $formRuntimeProphecy->getFormDefinition()->willReturn($formDefinitionProphecy->reveal());

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
            $formRuntimeProphecy->reveal()
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

        $formRuntimeProphecy = $this->createFormRuntimeProphecy([
            $elementIdentifier => ['value-1', 'value-2']
        ]);

        $finisherContextProphecy = $this->prophesize(FinisherContext::class);
        $finisherContextProphecy->getFinisherVariableProvider(Argument::cetera())->willReturn(new FinisherVariableProvider());
        $mockAbstractFinisher->_set('finisherContext', $finisherContextProphecy->reveal());

        $this->expectException(FinisherException::class);
        $this->expectExceptionCode(1519239265);

        $mockAbstractFinisher->_call(
            'substituteRuntimeReferences',
            $input,
            $formRuntimeProphecy->reveal()
        );
    }

    /**
     * @param array $values Key/Value pairs to be retrievable
     * @return ObjectProphecy|FormRuntime
     */
    protected function createFormRuntimeProphecy(array $values)
    {
        /** @var ObjectProphecy|FormRuntime $formRuntimeProphecy */
        $formRuntimeProphecy = $this->prophesize(FormRuntime::class);
        foreach ($values as $key => $value) {
            $formRuntimeProphecy->offsetExists(Argument::exact($key))->willReturn(true);
            $formRuntimeProphecy->offsetGet(Argument::exact($key))->willReturn($value);
        }
        return $formRuntimeProphecy;
    }
}
