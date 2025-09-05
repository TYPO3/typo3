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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\FormElements;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Exception\IdentifierNotValidException;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\CMS\Form\Tests\Unit\Domain\FormElements\Fixtures\TestingFormElement;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AbstractFormElementTest extends UnitTestCase
{
    #[Test]
    public function newInstanceHasNoProperties(): void
    {
        $subject = new TestingFormElement();
        self::assertNotNull($subject);
        self::assertCount(0, $subject->getProperties());
    }

    #[Test]
    public function setSimpleProperties(): void
    {
        $subject = new TestingFormElement();

        $subject->setProperty('foo', 'bar');
        $subject->setProperty('buz', 'qax');
        $properties = $subject->getProperties();

        self::assertCount(2, $properties);
        self::assertArrayHasKey('foo', $properties);
        self::assertEquals('bar', $properties['foo']);
        self::assertArrayHasKey('buz', $properties);
        self::assertEquals('qax', $properties['buz']);
    }

    #[Test]
    public function overrideProperties(): void
    {
        $subject = new TestingFormElement();

        $subject->setProperty('foo', 'bar');
        $subject->setProperty('foo', 'buz');

        $properties = $subject->getProperties();
        self::assertCount(1, $properties);
        self::assertArrayHasKey('foo', $properties);
        self::assertEquals('buz', $properties['foo']);
    }

    #[Test]
    public function setArrayProperties(): void
    {
        $subject = new TestingFormElement();

        $subject->setProperty('foo', ['bar' => 'baz', 'bla' => 'blubb']);
        $properties = $subject->getProperties();

        self::assertCount(1, $properties);
        self::assertArrayHasKey('foo', $properties);

        //check arrays details
        self::assertIsArray($properties['foo']);
        self::assertCount(2, $properties['foo']);
        self::assertArrayHasKey('bar', $properties['foo']);
        self::assertEquals('baz', $properties['foo']['bar']);
    }

    #[Test]
    public function setPropertyUnsetIfValueIsNull(): void
    {
        $subject = new TestingFormElement();

        $expected = ['foo-1' => ['bar-1' => 'foo-2']];
        $subject->setProperty('foo-1', ['bar-1' => 'foo-2']);
        $subject->setProperty('foo-2', ['bar-2' => 'foo-3']);
        $subject->setProperty('foo-2', null);

        self::assertSame($expected, $subject->getProperties());
    }

    #[Test]
    public function setPropertyUnsetIfValueIsArrayWithSomeNullVales(): void
    {
        $subject = new TestingFormElement();

        $expected = [
            'foo-1' => [
                'bar-1' => 'foo-2',
            ],
            'foo-2' => [
                'bar-2' => 'foo-3',
            ],
        ];
        $subject->setProperty('foo-1', ['bar-1' => 'foo-2']);
        $subject->setProperty('foo-2', ['bar-2' => 'foo-3', 'bar-3' => 'foo-4']);
        $subject->setProperty('foo-2', ['bar-3' => null]);

        self::assertSame($expected, $subject->getProperties());
    }

    #[Test]
    public function constructThrowsExceptionWhenIdentifierIsEmpty(): void
    {
        $this->expectException(IdentifierNotValidException::class);
        $this->expectExceptionCode(1477082502);

        // GenericFormElement inherits from AbstractFormElement and serves as concrete implementation
        new GenericFormElement('', 'a_type');
    }

    #[Test]
    public function constructMustNotThrowExceptionWhenIdentifierIsNonEmptyString(): void
    {
        $formElement = new TestingFormElement();

        self::assertInstanceOf(TestingFormElement::class, $formElement);
    }

    #[Test]
    public function initializeFormElementExpectedCallInitializeFormObjectHooks(): void
    {
        $formElement = new TestingFormElement();
        $secondFormElementMock = $this->createMock(TestingFormElement::class);

        $secondFormElementMock->
        expects($this->once())
            ->method('initializeFormElement')
            ->with($formElement);

        $secondFormElementMockClassName = \get_class($secondFormElementMock);
        GeneralUtility::addInstance($secondFormElementMockClassName, $secondFormElementMock);

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['initializeFormElement'] = [
            $secondFormElementMockClassName,
        ];

        $formElement->initializeFormElement();
    }

    #[Test]
    public function getUniqueIdentifierExpectedUnique(): void
    {
        $formDefinition1 = $this->createMock(FormDefinition::class);
        $formDefinition1
            ->method('getIdentifier')
            ->willReturn('c');

        $formElement1 = new TestingFormElement();
        $formElement1->setParentRenderable($formDefinition1);

        $formDefinition2 = $this->createMock(FormDefinition::class);
        $formDefinition2
            ->method('getIdentifier')
            ->willReturn('d');

        $formElement2 = new TestingFormElement();
        $formElement2->setParentRenderable($formDefinition2);

        self::assertNotSame(
            $formElement1->getUniqueIdentifier(),
            $formElement2->getUniqueIdentifier()
        );
    }

    #[Test]
    public function setDefaultValueSetStringValueIfKeyDoesNotExists(): void
    {
        $formDefinitionMock = $this->getAccessibleMock(FormDefinition::class, null, [], '', false);
        $formElement = new TestingFormElement();
        $formElement->setParentRenderable($formDefinitionMock);

        $input = 'foo';
        $expected = 'foo';

        $formElement->setDefaultValue($input);

        self::assertSame($expected, $formElement->getDefaultValue());
    }

    #[Test]
    public function setDefaultValueSetArrayValueIfKeyDoesNotExists(): void
    {
        $formDefinitionMock = $this->getAccessibleMock(FormDefinition::class, null, [], '', false);
        $formElement = new TestingFormElement();
        $formElement->setParentRenderable($formDefinitionMock);

        $input = ['foo' => 'bar'];
        $expected = ['foo' => 'bar'];

        $formElement->setDefaultValue($input);

        self::assertSame($expected, $formElement->getDefaultValue());
    }

    #[Test]
    public function setDefaultValueUnsetIfValueIsArrayWithSomeNullVales(): void
    {
        $formDefinitionMock = $this->getAccessibleMock(FormDefinition::class, null, [], '', false);
        $formElement = new TestingFormElement();
        $formElement->setParentRenderable($formDefinitionMock);

        $input1 = [
            'foo-1' => [
                'bar-1' => 'foo-2',
            ],
            'foo-2' => [
                'bar-2' => 'foo-3',
                'bar-3' => 'foo-4',
            ],
        ];

        $input2 = [
            'foo-2' => [
                'bar-3' => null,
            ],
        ];

        $expected = [
            'foo-1' => [
                'bar-1' => 'foo-2',
            ],
            'foo-2' => [
                'bar-2' => 'foo-3',
            ],
        ];

        $formElement->setDefaultValue($input1);
        $formElement->setDefaultValue($input2);

        self::assertSame($expected, $formElement->getDefaultValue());
    }

    #[Test]
    public function setDefaultValueAddValueIfValueIsArray(): void
    {
        $formDefinitionMock = $this->getAccessibleMock(FormDefinition::class, null, [], '', false);
        $formElement = new TestingFormElement();
        $formElement->setParentRenderable($formDefinitionMock);

        $input1 = [
            'foo-1' => [
                'bar-1' => 'foo-2',
            ],
            'foo-2' => [
                'bar-2' => 'foo-3',
            ],
        ];

        $input2 = [
            'foo-2' => [
                'bar-3' => 'foo-4',
            ],
        ];

        $expected = [
            'foo-1' => [
                'bar-1' => 'foo-2',
            ],
            'foo-2' => [
                'bar-2' => 'foo-3',
                'bar-3' => 'foo-4',
            ],
        ];

        $formElement->setDefaultValue($input1);
        $formElement->setDefaultValue($input2);

        self::assertSame($expected, $formElement->getDefaultValue());
    }
}
