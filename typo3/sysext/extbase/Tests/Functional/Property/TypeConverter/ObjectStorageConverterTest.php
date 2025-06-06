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

namespace TYPO3\CMS\Extbase\Tests\Functional\Property\TypeConverter;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TypeConverterTest\Domain\Model\Cat;
use TYPO3Tests\TypeConverterTest\Domain\Model\Home;
use TYPO3Tests\TypeConverterTest\Domain\Model\Horse;

final class ObjectStorageConverterTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function convertHomeWithCatsViaObjectConverterWillResultInTwoCatsInObjectStorage(): void
    {
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->allowProperties('cats');
        $propertyMapperConfiguration->forProperty('cats.*')->allowProperties('color');

        $propertyMapper = $this->get(PropertyMapper::class);

        $home = $propertyMapper->convert(
            [
                'cats' => [
                    0 => ['color' => 'black'],
                    1 => ['color' => 'white'],
                ],
            ],
            Home::class,
            $propertyMapperConfiguration
        );

        self::assertInstanceOf(Home::class, $home);
        self::assertCount(2, $home->getCats());

        $cat = $home->getCats()->current();
        self::assertInstanceOf(Cat::class, $cat);
        self::assertSame('black', $cat->getColor());
    }

    #[Test]
    public function convertHomeWithHorsesViaPersistenceObjectConverterWillResultInTwoHorsesInObjectStorage(): void
    {
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->allowProperties('horses');
        $propertyMapperConfiguration
            ->forProperty('horses.*')
            ->allowProperties('color')
            ->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, true);

        $propertyMapper = $this->get(PropertyMapper::class);

        $home = $propertyMapper->convert(
            [
                'horses' => [
                    0 => ['color' => 'black'],
                    1 => ['color' => 'white'],
                ],
            ],
            Home::class,
            $propertyMapperConfiguration
        );

        self::assertInstanceOf(Home::class, $home);
        self::assertCount(2, $home->getHorses());

        $cat = $home->getHorses()->current();
        self::assertInstanceOf(Horse::class, $cat);
        self::assertSame('black', $cat->getColor());
    }

    #[Test]
    public function convertHomeWithOptionalHorsesViaPersistenceObjectConverterWillResultInOneHorseInObjectStorage(): void
    {
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->allowProperties('horses');
        $propertyMapperConfiguration
            ->forProperty('horses.*')
            ->allowProperties('color')
            ->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, true);

        $propertyMapper = $this->get(PropertyMapper::class);

        $home = $propertyMapper->convert(
            [
                'horses' => [
                    0 => ['color' => 'black'],
                    1 => '',
                ],
            ],
            Home::class,
            $propertyMapperConfiguration
        );

        self::assertInstanceOf(Home::class, $home);
        self::assertCount(1, $home->getHorses());

        $cat = $home->getHorses()->current();
        self::assertInstanceOf(Horse::class, $cat);
        self::assertSame('black', $cat->getColor());
    }

    #[Test]
    public function convertPlainCatsWillReturnCatsInObjectStorage(): void
    {
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->allowAllProperties();
        $propertyMapperConfiguration->forProperty('*')->allowAllProperties();

        $propertyMapper = $this->get(PropertyMapper::class);

        $objectStorage = $propertyMapper->convert(
            [
                0 => ['color' => 'black'],
                1 => ['color' => 'white'],
            ],
            ObjectStorage::class . '<' . Cat::class . '>',
            $propertyMapperConfiguration
        );

        self::assertInstanceOf(ObjectStorage::class, $objectStorage);
        self::assertCount(2, $objectStorage);

        $objectStorage->rewind();
        $cat = $objectStorage->current();
        self::assertInstanceOf(Cat::class, $cat);
        self::assertSame('black', $cat->getColor());
    }

    #[Test]
    public function getSourceChildPropertiesToBeConvertedReturnsEmptyArrayIfSourceIsAString(): void
    {
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->forProperty('foo')->allowAllProperties();

        $propertyMapper = $this->get(PropertyMapper::class);

        $objectStorage = $propertyMapper->convert(
            'foo',
            ObjectStorage::class . '<' . Cat::class . '>',
            $propertyMapperConfiguration
        );

        self::assertInstanceOf(ObjectStorage::class, $objectStorage);
        self::assertCount(0, $objectStorage);
    }
}
