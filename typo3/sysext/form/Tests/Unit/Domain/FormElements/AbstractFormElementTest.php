<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Tests\Unit\Domain\FormElements;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Exception\IdentifierNotValidException;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AbstractFormElementTest extends UnitTestCase
{
    /**
     * @test
     */
    public function newInstanceHasNoProperties(): void
    {
        /** @var AbstractFormElement $subject */
        $subject = $this->getMockForAbstractClass(AbstractFormElement::class, ['an_id', 'a_type']);
        $this->assertNotNull($subject);
        $this->assertCount(0, $subject->getProperties());
    }

    /**
     * @test
     */
    public function setSimpleProperties(): void
    {
        /** @var AbstractFormElement $subject */
        $subject = $this->getMockForAbstractClass(AbstractFormElement::class, ['an_id', 'a_type']);

        $subject->setProperty('foo', 'bar');
        $subject->setProperty('buz', 'qax');
        $properties = $subject->getProperties();

        $this->assertCount(2, $properties);
        $this->assertTrue(array_key_exists('foo', $properties));
        $this->assertEquals('bar', $properties['foo']);
        $this->assertTrue(array_key_exists('buz', $properties));
        $this->assertEquals('qax', $properties['buz']);
    }

    /**
     * @test
     */
    public function overrideProperties(): void
    {
        /** @var AbstractFormElement $subject */
        $subject = $this->getMockForAbstractClass(AbstractFormElement::class, ['an_id', 'a_type']);

        $subject->setProperty('foo', 'bar');
        $subject->setProperty('foo', 'buz');

        $properties = $subject->getProperties();
        $this->assertEquals(1, \count($properties));
        $this->assertTrue(array_key_exists('foo', $properties));
        $this->assertEquals('buz', $properties['foo']);
    }

    /**
     * @test
     */
    public function setArrayProperties(): void
    {
        /** @var AbstractFormElement $subject */
        $subject = $this->getMockForAbstractClass(AbstractFormElement::class, ['an_id', 'a_type']);

        $subject->setProperty('foo', ['bar' => 'baz', 'bla' => 'blubb']);
        $properties = $subject->getProperties();

        $this->assertCount(1, $properties);
        $this->assertTrue(array_key_exists('foo', $properties));

        //check arrays details
        $this->assertTrue(\is_array($properties['foo']));
        $this->assertCount(2, $properties['foo']);
        $this->assertTrue(array_key_exists('bar', $properties['foo']));
        $this->assertEquals('baz', $properties['foo']['bar']);
    }

    /**
     * @test
     */
    public function setPropertyUnsetIfValueIsNull(): void
    {
        $subject = $this->getMockForAbstractClass(AbstractFormElement::class, ['an_id', 'a_type']);

        $expected = ['foo-1' => ['bar-1' => 'foo-2']];
        $subject->setProperty('foo-1', ['bar-1' => 'foo-2']);
        $subject->setProperty('foo-2', ['bar-2' => 'foo-3']);
        $subject->setProperty('foo-2', null);

        $this->assertSame($expected, $subject->getProperties());
    }

    /**
     * @test
     */
    public function setPropertyUnsetIfValueIsArrayWithSomeNullVales(): void
    {
        $subject = $this->getMockForAbstractClass(AbstractFormElement::class, ['an_id', 'a_type']);

        $expected = [
            'foo-1' => [
                'bar-1' => 'foo-2'
            ],
            'foo-2' => [
                'bar-2' => 'foo-3'
            ]
        ];
        $subject->setProperty('foo-1', ['bar-1' => 'foo-2']);
        $subject->setProperty('foo-2', ['bar-2' => 'foo-3', 'bar-3' => 'foo-4']);
        $subject->setProperty('foo-2', ['bar-3' => null]);

        $this->assertSame($expected, $subject->getProperties());
    }

    /**
     * @test
     */
    public function constructThrowsExceptionWhenIdentifierIsEmpty(): void
    {
        $this->expectException(IdentifierNotValidException::class);
        $this->expectExceptionCode(1477082502);

        // GenericFormElement inherits from AbstractFormElement and serves as concrete implementation
        new GenericFormElement('', 'a_type');
    }

    /**
     * @test
     */
    public function constructMustNotThrowExceptionWhenIdentifierIsNonEmptyString(): void
    {
        $mock = $this->getAccessibleMockForAbstractClass(
            AbstractFormElement::class,
            ['is_in', 'a_type'],
            '',
            true,
            true,
            true,
            []
        );
        self::assertInstanceOf(AbstractFormElement::class, $mock);
    }

    /**
     * @test
     */
    public function initializeFormElementExpectedCallInitializeFormObjectHooks(): void
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|AbstractFormElement $abstractFormElementMock */
        $abstractFormElementMock = $this->getAccessibleMockForAbstractClass(
            AbstractFormElement::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|AbstractFormElement $secondMock */
        $secondMock = $this->getAccessibleMockForAbstractClass(
            AbstractFormElement::class,
            [],
            '',
            false,
            false,
            true,
            [
                'initializeFormElement'
            ]
        );

        $secondMock->
        expects($this->once())
            ->method('initializeFormElement')
            ->with($abstractFormElementMock);

        GeneralUtility::addInstance(\get_class($secondMock), $secondMock);

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['initializeFormElement'] = [
            \get_class($secondMock)
        ];

        $abstractFormElementMock->initializeFormElement();
    }

    /**
     * @test
     */
    public function getUniqueIdentifierExpectedUnique(): void
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|AbstractFormElement $abstractFormElementMock1 */
        $abstractFormElementMock1 = $this->getAccessibleMockForAbstractClass(
            AbstractFormElement::class,
            [],
            '',
            false,
            false,
            true,
            [
                'getRootForm'
            ]
        );

        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|AbstractFormElement $abstractFormElementMock2 */
        $abstractFormElementMock2 = $this->getAccessibleMockForAbstractClass(
            AbstractFormElement::class,
            [],
            '',
            false,
            false,
            true,
            [
                'getRootForm'
            ]
        );

        $formDefinition1 = $this->createMock(FormDefinition::class);
        $formDefinition1
            ->method('getIdentifier')
            ->willReturn('c');

        $abstractFormElementMock1
            ->method('getRootForm')
            ->willReturn($formDefinition1);

        $formDefinition2 = $this->createMock(FormDefinition::class);
        $formDefinition2
            ->method('getIdentifier')
            ->willReturn('d');

        $abstractFormElementMock2
            ->method('getRootForm')
            ->willReturn($formDefinition2);

        self::assertNotSame(
            $abstractFormElementMock1->getUniqueIdentifier(),
            $abstractFormElementMock2->getUniqueIdentifier()
        );
    }

    /**
     * @test
     */
    public function setDefaultValueSetStringValueIfKeyDoesNotExists(): void
    {
        $formDefinitionMock = $this->getAccessibleMock(FormDefinition::class, [
            'dummy'
        ], [], '', false);

        $abstractFormElementMock = $this->getAccessibleMockForAbstractClass(
            AbstractFormElement::class,
            ['is_in', 'a_type'],
            '',
            true,
            true,
            true,
            ['getRootForm']
        );

        $abstractFormElementMock
            ->method('getRootForm')
            ->willReturn($formDefinitionMock);

        $input = 'foo';
        $expected = 'foo';

        $abstractFormElementMock->setDefaultValue($input);

        $this->assertSame($expected, $abstractFormElementMock->getDefaultValue());
    }

    /**
     * @test
     */
    public function setDefaultValueSetArrayValueIfKeyDoesNotExists(): void
    {
        $formDefinitionMock = $this->getAccessibleMock(FormDefinition::class, [
            'dummy'
        ], [], '', false);

        $abstractFormElementMock = $this->getAccessibleMockForAbstractClass(
            AbstractFormElement::class,
            ['is_in', 'a_type'],
            '',
            true,
            true,
            true,
            ['getRootForm']
        );

        $abstractFormElementMock
            ->method('getRootForm')
            ->willReturn($formDefinitionMock);

        $input = ['foo' => 'bar'];
        $expected = ['foo' => 'bar'];

        $abstractFormElementMock->setDefaultValue($input);

        $this->assertSame($expected, $abstractFormElementMock->getDefaultValue());
    }

    /**
     * @test
     */
    public function setDefaultValueUnsetIfValueIsArrayWithSomeNullVales(): void
    {
        $formDefinitionMock = $this->getAccessibleMock(FormDefinition::class, [
            'dummy'
        ], [], '', false);

        $abstractFormElementMock = $this->getAccessibleMockForAbstractClass(
            AbstractFormElement::class,
            ['is_in', 'a_type'],
            '',
            true,
            true,
            true,
            ['getRootForm']
        );

        $abstractFormElementMock
            ->method('getRootForm')
            ->willReturn($formDefinitionMock);

        $input1 = [
            'foo-1' => [
                'bar-1' => 'foo-2'
            ],
            'foo-2' => [
                'bar-2' => 'foo-3',
                'bar-3' => 'foo-4'
            ]
        ];

        $input2 = [
            'foo-2' => [
                'bar-3' => null
            ]
        ];

        $expected = [
            'foo-1' => [
                'bar-1' => 'foo-2'
            ],
            'foo-2' => [
                'bar-2' => 'foo-3'
            ]
        ];

        $abstractFormElementMock->setDefaultValue($input1);
        $abstractFormElementMock->setDefaultValue($input2);

        $this->assertSame($expected, $abstractFormElementMock->getDefaultValue());
    }

    /**
     * @test
     */
    public function setDefaultValueAddValueIfValueIsArray(): void
    {
        $formDefinitionMock = $this->getAccessibleMock(FormDefinition::class, [
            'dummy'
        ], [], '', false);

        $abstractFormElementMock = $this->getAccessibleMockForAbstractClass(
            AbstractFormElement::class,
            ['is_in', 'a_type'],
            '',
            true,
            true,
            true,
            ['getRootForm']
        );

        $abstractFormElementMock
            ->method('getRootForm')
            ->willReturn($formDefinitionMock);

        $input1 = [
            'foo-1' => [
                'bar-1' => 'foo-2'
            ],
            'foo-2' => [
                'bar-2' => 'foo-3',
            ]
        ];

        $input2 = [
            'foo-2' => [
                'bar-3' => 'foo-4'
            ]
        ];

        $expected = [
            'foo-1' => [
                'bar-1' => 'foo-2'
            ],
            'foo-2' => [
                'bar-2' => 'foo-3',
                'bar-3' => 'foo-4'
            ]
        ];

        $abstractFormElementMock->setDefaultValue($input1);
        $abstractFormElementMock->setDefaultValue($input2);

        $this->assertSame($expected, $abstractFormElementMock->getDefaultValue());
    }
}
