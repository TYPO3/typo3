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

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Exception\IdentifierNotValidException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotFoundException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotValidException;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\UnknownFormElement;
use TYPO3\CMS\Form\Tests\Unit\Domain\FormElements\Fixtures\AbstractSectionFixture;
use TYPO3\CMS\Form\Tests\Unit\Domain\FormElements\Fixtures\TestingFormElement;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AbstractSectionTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function constructThrowsExceptionWhenIdentifierIsEmpty(): void
    {
        $this->expectException(IdentifierNotValidException::class);
        $this->expectExceptionCode(1477082501);
        new AbstractSectionFixture('', 'foobar');
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function constructMustNotThrowExceptionWhenIdentifierIsNonEmptyString(): void
    {
        new AbstractSectionFixture('foobar', '');
    }

    #[Test]
    public function createElementThrowsExceptionIfTypeDefinitionNotFoundAndSkipUnknownElementsIsFalse(): void
    {
        $rootForm = $this->createMock(FormDefinition::class);
        $this->expectException(TypeDefinitionNotFoundException::class);
        $this->expectExceptionCode(1382364019);
        $subject = new AbstractSectionFixture('identifier', '');
        $subject->setParentRenderable($rootForm);
        $subject->createElement('', '');
    }

    #[Test]
    public function createElementReturnsUnknownElementsIfTypeDefinitionIsNotFoundAndSkipUnknownElementsIsTrue(): void
    {
        $rootForm = $this->createMock(FormDefinition::class);
        $rootForm->method('getRenderingOptions')->willReturn(['skipUnknownElements' => true]);
        GeneralUtility::addInstance(UnknownFormElement::class, new UnknownFormElement('foo', 'bar'));
        $subject = new AbstractSectionFixture('testing', '');
        $subject->setParentRenderable($rootForm);
        $result = $subject->createElement('foo', 'bar');
        self::assertInstanceOf(UnknownFormElement::class, $result);
        self::assertSame('foo', $result->getIdentifier());
        self::assertSame('bar', $result->getType());
    }

    #[Test]
    public function createElementThrowsExceptionIfTypeDefinitionIsNotSet(): void
    {
        $this->expectException(TypeDefinitionNotFoundException::class);
        $this->expectExceptionCode(1325689855);
        $rootForm = $this->createMock(FormDefinition::class);
        $rootForm->method('getTypeDefinitions')->willReturn(['foobar' => []]);
        $subject = new AbstractSectionFixture('testing', '');
        $subject->setParentRenderable($rootForm);
        $subject->createElement('id', 'foobar');
    }

    #[Test]
    public function createElementThrowsExceptionIfTypeDefinitionNotInstanceOfFormElementInterface(): void
    {
        $this->expectException(TypeDefinitionNotValidException::class);
        $this->expectExceptionCode(1327318156);
        $rootForm = $this->createMock(FormDefinition::class);
        $rootForm->method('getTypeDefinitions')->willReturn([
            'foobar' => [
                'implementationClassName' => self::class,
            ],
        ]);
        $subject = new AbstractSectionFixture('testing', '');
        $subject->setParentRenderable($rootForm);
        GeneralUtility::addInstance(self::class, $this);
        $subject->createElement('id', 'foobar');
    }

    #[Test]
    public function createElementExpectedToAddAndInitializeElement(): void
    {
        $implementationMock = $this->createMock(TestingFormElement::class);
        $typeDefinition = [
            'foo' => 'bar',
            'implementationClassName' => get_class($implementationMock),
            'fizz' => 'buzz',
        ];
        $typeDefinitionWithoutImplementationClassName = $typeDefinition;
        unset($typeDefinitionWithoutImplementationClassName['implementationClassName']);
        $implementationMock->expects($this->once())->method('initializeFormElement');
        $implementationMock->expects($this->once())->method('setOptions')->with($typeDefinitionWithoutImplementationClassName);
        $rootForm = $this->createMock(FormDefinition::class);
        $rootForm->method('getTypeDefinitions')->willReturn(['foobar' => $typeDefinition]);
        $subject = new AbstractSectionFixture('testing', '');
        $subject->setParentRenderable($rootForm);
        GeneralUtility::addInstance(get_class($implementationMock), $implementationMock);
        $subject->createElement('id', 'foobar');
    }
}
