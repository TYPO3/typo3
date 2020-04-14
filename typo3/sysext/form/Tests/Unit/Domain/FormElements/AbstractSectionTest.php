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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Domain\Exception\IdentifierNotValidException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotFoundException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotValidException;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractSection;
use TYPO3\CMS\Form\Domain\Model\FormElements\Section;
use TYPO3\CMS\Form\Domain\Model\FormElements\UnknownFormElement;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AbstractSectionTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function constructThrowsExceptionWhenIdentifierIsEmpty(): void
    {
        $this->expectException(IdentifierNotValidException::class);
        $this->expectExceptionCode(1477082501);

        // Section inherits from AbstractSection and serves as concrete implementation
        new Section('', 'foobar');
    }

    /**
     * @test
     */
    public function constructMustNotThrowExceptionWhenIdentifierIsNonEmptyString(): void
    {
        $section = new Section('foobar', 'foobar');
        self::assertInstanceOf(AbstractSection::class, $section);
    }

    /**
     * @test
     */
    public function createElementThrowsExceptionIfTypeDefinitionNotFoundAndSkipUnknownElementsIsFalse(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|FormDefinition $rootForm */
        $rootForm = $this->getMockBuilder(FormDefinition::class)
            ->setMethods(['getRenderingOptions', 'getTypeDefinitions'])
            ->disableOriginalConstructor()
            ->getMock();
        $rootForm
            ->method('getRenderingOptions')
            ->willReturn(['skipUnknownElements' => false]);
        $rootForm
            ->method('getTypeDefinitions')
            ->willReturn([]);

        $mockAbstractSection = $this->getAccessibleMockForAbstractClass(
            AbstractSection::class,
            [],
            '',
            false,
            false,
            true,
            [
                'getRootForm'
            ]
        );

        $mockAbstractSection
            ->expects(self::once())
            ->method('getRootForm')
            ->willReturn($rootForm);

        $this->expectException(TypeDefinitionNotFoundException::class);
        $this->expectExceptionCode(1382364019);

        $mockAbstractSection->_call('createElement', '', '');
    }

    /**
     * @test
     */
    public function createElementReturnsUnknownElementsIfTypeDefinitionIsNotFoundAndSkipUnknownElementsIsTrue(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|FormDefinition $rootForm */
        $rootForm = $this->getMockBuilder(FormDefinition::class)
            ->setMethods(['getRenderingOptions', 'getTypeDefinitions'])
            ->disableOriginalConstructor()
            ->getMock();
        $rootForm
            ->method('getRenderingOptions')
            ->willReturn(['skipUnknownElements' => true]);
        $rootForm
            ->method('getTypeDefinitions')
            ->willReturn([]);

        /** @var \PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|AbstractSection $mockAbstractSection */
        $mockAbstractSection = $this->getMockForAbstractClass(
            AbstractSection::class,
            [],
            '',
            false,
            false,
            true,
            [
                'getRootForm'
            ]
        );

        /** @var \PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|AbstractSection $mockAbstractSection */
        $mockAbstractSection
            ->method('getRootForm')
            ->willReturn($rootForm);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->get(UnknownFormElement::class, 'foo', 'bar')->willReturn(new UnknownFormElement('foo', 'bar'));

        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager->reveal());
        $result = $mockAbstractSection->createElement('foo', 'bar');

        self::assertInstanceOf(UnknownFormElement::class, $result);
        self::assertSame('foo', $result->getIdentifier());
        self::assertSame('bar', $result->getType());
    }

    /**
     * @test
     */
    public function createElementThrowsExceptionIfTypeDefinitionIsNotSet(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|FormDefinition $rootForm */
        $rootForm = $this->getMockBuilder(FormDefinition::class)
            ->setMethods(['getRenderingOptions', 'getTypeDefinitions'])
            ->disableOriginalConstructor()
            ->getMock();
        $rootForm
            ->method('getRenderingOptions')
            ->willReturn(['skipUnknownElements' => true]);
        $rootForm
            ->method('getTypeDefinitions')
            ->willReturn(['foobar' => []]);

        /** @var \PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|AbstractSection $mockAbstractSection */
        $mockAbstractSection = $this->getMockForAbstractClass(
            AbstractSection::class,
            [],
            '',
            false,
            false,
            true,
            [
                'getRootForm'
            ]
        );

        $mockAbstractSection
            ->method('getRootForm')
            ->willReturn($rootForm);

        $this->expectException(TypeDefinitionNotFoundException::class);
        $this->expectExceptionCode(1325689855);

        $mockAbstractSection->createElement('id', 'foobar');
    }

    /**
     * @test
     */
    public function createElementThrowsExceptionIfTypeDefinitionNotInstanceOfFormElementInterface(): void
    {
        $this->resetSingletonInstances = true;
        /** @var \PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|AbstractSection $mockAbstractSection */
        $mockAbstractSection = $this->getMockForAbstractClass(
            AbstractSection::class,
            [],
            '',
            false,
            false,
            true,
            [
                'getRootForm'
            ]
        );

        /** @var \PHPUnit\Framework\MockObject\MockObject|FormDefinition $rootForm */
        $rootForm = $this->getMockBuilder(FormDefinition::class)
            ->setMethods(['getRenderingOptions', 'getTypeDefinitions'])
            ->disableOriginalConstructor()
            ->getMock();
        $rootForm
            ->method('getRenderingOptions')
            ->willReturn([]);
        $rootForm
            ->method('getTypeDefinitions')
            ->willReturn(
                [
                    'foobar' => [
                        'implementationClassName' => self::class
                    ]
                ]
            );

        $mockAbstractSection
            ->method('getRootForm')
            ->willReturn($rootForm);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->get(self::class, 'id', 'foobar')->willReturn($this);
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager->reveal());

        $this->expectException(TypeDefinitionNotValidException::class);
        $this->expectExceptionCode(1327318156);
        $mockAbstractSection->createElement('id', 'foobar');
    }

    /**
     * @test
     */
    public function createElementExpectedToAddAndInitializeElement(): void
    {
        $implementationMock = $this->getMockForAbstractClass(
            AbstractFormElement::class,
            [],
            '',
            false,
            false,
            true,
            ['setOptions', 'initializeFormElement']
        );

        $typeDefinition = [
            'foo' => 'bar',
            'implementationClassName' => get_class($implementationMock),
            'fizz' => 'buzz'
        ];

        $typeDefinitionWithoutImplementationClassName = $typeDefinition;
        unset($typeDefinitionWithoutImplementationClassName['implementationClassName']);

        $implementationMock
            ->expects(self::once())
            ->method('initializeFormElement');

        $implementationMock
            ->expects(self::once())
            ->method('setOptions')
            ->with($typeDefinitionWithoutImplementationClassName);

        /** @var \PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|AbstractSection $mockAbstractSection */
        $mockAbstractSection = $this->getMockForAbstractClass(
            AbstractSection::class,
            [],
            '',
            false,
            false,
            true,
            [
                'getRootForm'
            ]
        );

        /** @var \PHPUnit\Framework\MockObject\MockObject|FormDefinition $rootForm */
        $rootForm = $this->getMockBuilder(FormDefinition::class)
            ->setMethods(['getRenderingOptions', 'getTypeDefinitions'])
            ->disableOriginalConstructor()
            ->getMock();
        $rootForm
            ->method('getRenderingOptions')
            ->willReturn([]);
        $rootForm
            ->method('getTypeDefinitions')
            ->willReturn(['foobar' => $typeDefinition]);

        $mockAbstractSection
            ->method('getRootForm')
            ->willReturn($rootForm);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->get(get_class($implementationMock), 'id', 'foobar')->willReturn($implementationMock);

        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager->reveal());

        $mockAbstractSection->createElement('id', 'foobar');
    }
}
