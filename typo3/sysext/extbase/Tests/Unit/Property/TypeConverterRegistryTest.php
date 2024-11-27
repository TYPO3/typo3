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

namespace TYPO3\CMS\Extbase\Tests\Unit\Property;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Property\Exception\DuplicateTypeConverterException;
use TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException;
use TYPO3\CMS\Extbase\Property\Exception\TypeConverterException;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\CMS\Extbase\Property\TypeConverter\BooleanConverter;
use TYPO3\CMS\Extbase\Property\TypeConverter\FileReferenceConverter;
use TYPO3\CMS\Extbase\Property\TypeConverterRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TypeConverterRegistryTest extends UnitTestCase
{
    private TypeConverterRegistry $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new TypeConverterRegistry();
        $this->subject->add(new BooleanConverter(), 10, ['boolean', 'integer'], 'boolean');
    }

    #[Test]
    public function addThrowsDuplicateTypeConverterException(): void
    {
        $extendedBooleanConverter = new class () extends BooleanConverter {};
        $extendedBooleanConverterClassName = get_class($extendedBooleanConverter);

        $this->expectException(DuplicateTypeConverterException::class);
        $this->expectExceptionCode(1297951378);
        $this->expectExceptionMessage(
            sprintf(
                'There exist at least two type converters which handle the conversion from "boolean" to "boolean" with priority "10": %s and %s',
                ltrim(BooleanConverter::class),
                ltrim($extendedBooleanConverterClassName),
            )
        );

        $this->subject->add($extendedBooleanConverter, 10, ['boolean', 'integer'], 'boolean');
    }

    #[Test]
    public function findConverterFindsConverterForSimpleTypes(): void
    {
        $converter = $this->subject->findTypeConverter('boolean', 'boolean');
        self::assertInstanceOf(BooleanConverter::class, $converter);
    }

    #[Test]
    public function findConverterFindsConverterForSimpleTargetTypesWithHighestPriority(): void
    {
        $extendedBooleanConverter = new class () extends BooleanConverter {};
        $extendedBooleanConverterClassName = get_class($extendedBooleanConverter);
        $this->subject->add($extendedBooleanConverter, 20, ['boolean', 'integer'], 'boolean');

        $converter = $this->subject->findTypeConverter('boolean', 'boolean');
        self::assertInstanceOf($extendedBooleanConverterClassName, $converter);
    }

    #[Test]
    public function findConverterThrowsTypeConverterExceptionWhenConverterForSimpleTypeTargetCannotBeFound(): void
    {
        $this->expectException(TypeConverterException::class);
        $this->expectExceptionCode(1476044883);
        $this->expectExceptionMessage('No converter found which can be used to convert from "array" to "boolean".');

        $this->subject->findTypeConverter('array', 'boolean');
    }

    #[Test]
    public function findConverterThrowsInvalidTargetException(): void
    {
        $this->expectException(InvalidTargetException::class);
        $this->expectExceptionCode(1297948764);
        $this->expectExceptionMessage('Could not find a suitable type converter for "NonExistingClass" because no such class or interface exists.');

        $this->subject->findTypeConverter('integer', 'NonExistingClass');
    }

    #[Test]
    public function findConverterThrowsTypeConverterExceptionWhenThereIsNoConverterRegisteredForGivenSourceTypeAndObjectTargetType(): void
    {
        $this->expectException(TypeConverterException::class);
        $this->expectExceptionCode(1476044883);
        $this->expectExceptionMessage('No converter found which can be used to convert from "array" to "stdClass".');

        $this->subject->findTypeConverter('array', \stdClass::class);
    }

    #[Test]
    public function findConverterFindsTypeConverterForClassOrInterfaceTargetTypes(): void
    {
        $this->subject->add(new FileReferenceConverter(), 10, ['integer'], FileReference::class);

        $converter = $this->subject->findTypeConverter('integer', FileReference::class);
        self::assertInstanceOf(FileReferenceConverter::class, $converter);
    }

    #[Test]
    public function findConverterFindsTypeConverterForClassOrInterfaceParentClassOfTargetType(): void
    {
        $this->subject->add(new FileReferenceConverter(), 10, ['integer'], FileReference::class);

        $extendedFileReference = new class () extends FileReference {};
        $extendedFileReferenceClassName = get_class($extendedFileReference);

        $converter = $this->subject->findTypeConverter('integer', $extendedFileReferenceClassName);
        self::assertInstanceOf(FileReferenceConverter::class, $converter);
    }

    #[Test]
    public function findConverterThrowsDuplicateTypeConverterException(): void
    {
        /*
         * This test is of course complete nonsense. Nobody would try to convert an object to an ArrayAccess and rely on
         * converters that handle \Countable and \ArrayAccess as target types but those objects and interfaces allow to
         * test the underlying functionality without having to create fake classes.
         */

        $this->expectException(DuplicateTypeConverterException::class);
        $this->expectExceptionCode(1297951338);
        $this->expectExceptionMessageMatches(
            '/^There exist at least two converters which handle the conversion to an interface with priority "10"/'
        );

        $countableConverter = new class () extends AbstractTypeConverter {
            public function convertFrom($source, string $targetType, array $convertedChildProperties = [], ?PropertyMappingConfigurationInterface $configuration = null)
            {
                return null;
            }
        };

        $arrayAccessConverter = new class () extends AbstractTypeConverter {
            public function convertFrom($source, string $targetType, array $convertedChildProperties = [], ?PropertyMappingConfigurationInterface $configuration = null)
            {
                return null;
            }
        };

        $this->subject->add($countableConverter, 10, ['object'], \Countable::class);
        $this->subject->add($arrayAccessConverter, 10, ['object'], \ArrayAccess::class);

        $this->subject->findTypeConverter('object', \ArrayObject::class);
    }
}
