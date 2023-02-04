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

use TYPO3\CMS\Core\Authentication\LoginType;
use TYPO3\CMS\Core\Type\TypeInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Property\Exception\DuplicateTypeConverterException;
use TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException;
use TYPO3\CMS\Extbase\Property\Exception\TypeConverterException;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\CMS\Extbase\Property\TypeConverter\ArrayConverter;
use TYPO3\CMS\Extbase\Property\TypeConverter\BooleanConverter;
use TYPO3\CMS\Extbase\Property\TypeConverter\CoreTypeConverter;
use TYPO3\CMS\Extbase\Property\TypeConverter\FileReferenceConverter;
use TYPO3\CMS\Extbase\Property\TypeConverter\ObjectConverter;
use TYPO3\CMS\Extbase\Property\TypeConverterRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TypeConverterRegistryTest extends UnitTestCase
{
    private ?TypeConverterRegistry $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new TypeConverterRegistry();
        $this->subject->add(new BooleanConverter(), 10, ['boolean', 'integer'], 'boolean');
    }

    /**
     * @test
     */
    public function addThrowsDuplicateTypeConverterException(): void
    {
        $extendedBooleanConverter = new class () extends BooleanConverter {
        };
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

    /**
     * @test
     */
    public function findConverterFindsConverterForSimpleTypes(): void
    {
        $converter = $this->subject->findTypeConverter('boolean', 'boolean');
        self::assertInstanceOf(BooleanConverter::class, $converter);
    }

    /**
     * @test
     */
    public function findConverterFindsConverterForSimpleTargetTypesWithHighestPriority(): void
    {
        $extendedBooleanConverter = new class () extends BooleanConverter {
        };
        $extendedBooleanConverterClassName = get_class($extendedBooleanConverter);
        $this->subject->add($extendedBooleanConverter, 20, ['boolean', 'integer'], 'boolean');

        $converter = $this->subject->findTypeConverter('boolean', 'boolean');
        self::assertInstanceOf($extendedBooleanConverterClassName, $converter);
    }

    /**
     * @test
     */
    public function findConverterThrowsTypeConverterExceptionWhenConverterForSimpleTypeTargetCannotBeFound(): void
    {
        $this->expectException(TypeConverterException::class);
        $this->expectExceptionCode(1476044883);
        $this->expectExceptionMessage('No converter found which can be used to convert from "array" to "boolean".');

        $this->subject->findTypeConverter('array', 'boolean');
    }

    /**
     * @test
     */
    public function findConverterThrowsInvalidTargetException(): void
    {
        $this->expectException(InvalidTargetException::class);
        $this->expectExceptionCode(1297948764);
        $this->expectExceptionMessage('Could not find a suitable type converter for "NonExistingClass" because no such class or interface exists.');

        $this->subject->findTypeConverter('integer', 'NonExistingClass');
    }

    /**
     * @test
     */
    public function findConverterThrowsTypeConverterExceptionWhenThereIsNoConverterRegisteredForGivenSourceTypeAndObjectTargetType(): void
    {
        $this->expectException(TypeConverterException::class);
        $this->expectExceptionCode(1476044883);
        $this->expectExceptionMessage('No converter found which can be used to convert from "array" to "stdClass".');

        $this->subject->findTypeConverter('array', \stdClass::class);
    }

    /**
     * @test
     */
    public function findConverterFindsTypeConverterForClassOrInterfaceTargetTypes(): void
    {
        $this->subject->add(new FileReferenceConverter(), 10, ['integer'], FileReference::class);

        $converter = $this->subject->findTypeConverter('integer', FileReference::class);
        self::assertInstanceOf(FileReferenceConverter::class, $converter);
    }

    /**
     * @test
     */
    public function findConverterFindsTypeConverterForClassOrInterfaceParentClassOfTargetType(): void
    {
        $this->subject->add(new FileReferenceConverter(), 10, ['integer'], FileReference::class);

        $extendedFileReference = new class () extends FileReference {
        };
        $extendedFileReferenceClassName = get_class($extendedFileReference);

        $converter = $this->subject->findTypeConverter('integer', $extendedFileReferenceClassName);
        self::assertInstanceOf(FileReferenceConverter::class, $converter);
    }

    /**
     * @test
     */
    public function findConverterFindsTypeConverterForClassInterfaceOfTargetType(): void
    {
        $this->subject->add(new CoreTypeConverter(), 10, ['integer'], TypeInterface::class);

        $converter = $this->subject->findTypeConverter('integer', LoginType::class);
        self::assertInstanceOf(CoreTypeConverter::class, $converter);
    }

    /**
     * @test
     */
    public function findConverterFindsLeastSpecificTypeConverterForClassOrInterfaceWithoutSpecificTypeConverterSet(): void
    {
        /*
         * This test needs a short explanation. When searching a type converter for a specific class, the registry
         * looks for a converter that is specifically made for the target class.
         *
         * Example: FileReference as target type and FileReferenceConverter as registered converter.
         *
         * If such a converter is not registered, the registry tries to find a converter by looking at the interfaces
         * the target class implements.
         *
         * Example: LoginType as target type and CoreTypeConverter as registered converter.
         *
         * If such a converter is not registered, the registry does one last attempt to find a suitable converter by
         * checking the source type.
         *
         * Example: LoginType as target, no suitable converter for that specific target type registered but 1 or n
         *          converters registered that convert the desired source type into the unspecific target type "object".
         */

        $this->subject->add(new ObjectConverter(), 10, ['array'], 'object');

        $converter = $this->subject->findTypeConverter('array', LoginType::class);
        self::assertInstanceOf(ObjectConverter::class, $converter);
    }

    /**
     * @test
     *
     * @see testFindConverterThrowsTypeConverterExceptionWhenThereIsNoConverterRegisteredForGivenSourceTypeAndObjectTargetType
     */
    public function findConverterThrowsExceptionIfNoConverterCanBeFoundToConvertSourceToAnObject(): void
    {
        /*
         * This test is similar to another one above but in this case the exception is thrown later because there is a
         * converter registered for the given source type but the registry ultimately fails to find a converter that
         * not only handles the desired source type but also the desired target type. In the other test further above,
         * the registry could early return as soon as no converter for the desired source type was found.
         */

        $this->subject->add(new ArrayConverter(), 10, ['array'], 'array');

        $this->expectException(TypeConverterException::class);
        $this->expectExceptionCode(1476044883);
        $this->expectExceptionMessage('No converter found which can be used to convert from "array" to "TYPO3\CMS\Core\Authentication\LoginType".');

        $this->subject->findTypeConverter('array', LoginType::class);
    }

    /**
     * @test
     */
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
            public function convertFrom($source, string $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
            {
                return null;
            }
        };

        $arrayAccessConverter = new class () extends AbstractTypeConverter {
            public function convertFrom($source, string $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
            {
                return null;
            }
        };

        $this->subject->add($countableConverter, 10, ['object'], \Countable::class);
        $this->subject->add($arrayAccessConverter, 10, ['object'], \ArrayAccess::class);

        $this->subject->findTypeConverter('object', \ArrayObject::class);
    }
}
