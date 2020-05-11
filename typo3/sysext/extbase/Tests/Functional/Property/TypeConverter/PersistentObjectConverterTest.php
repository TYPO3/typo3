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

use TYPO3\CMS\Extbase\Domain\Model\BackendUser;
use TYPO3\CMS\Extbase\Property\Exception;
use TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PersistentObjectConverterTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/../Fixtures/DatabaseImports/be_users.xml');
    }

    /**
     * @test
     */
    public function converterReturnsNullWithEmptyStringsOrIntegers()
    {
        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        self::assertNull($propertyMapper->convert(0, BackendUser::class));
        self::assertNull($propertyMapper->convert('', BackendUser::class));
    }

    /**
     * @test
     */
    public function fetchObjectFromPersistenceThrowsInvalidSourceExceptionIfSourceIANonNumericString()
    {
        static::expectException(Exception::class);
        static::expectExceptionCode(1297759968);
        static::expectExceptionMessage('Exception while property mapping at property path "": The identity property "foo" is no UID.');

        $this->getContainer()->get(PropertyMapper::class)->convert('foo', BackendUser::class);
    }

    /**
     * @test
     */
    public function fetchObjectFromPersistenceThrowsTargetNotFoundExceptionIfObjectIsNotToBeFoundInThePersistence()
    {
        static::expectException(TargetNotFoundException::class);
        static::expectExceptionCode(1297933823);
        static::expectExceptionMessage('Object of type TYPO3\CMS\Extbase\Domain\Model\BackendUser with identity "2" not found.');

        $this->getContainer()->get(PropertyMapper::class)->convert(2, BackendUser::class);
    }

    /**
     * @test
     */
    public function converterFetchesObjectFromPersistenceIfSourceIsAnInteger()
    {
        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $backendUser = $propertyMapper->convert(1, BackendUser::class);

        self::assertInstanceOf(BackendUser::class, $backendUser);
        self::assertSame(1, $backendUser->getUid());
    }

    /**
     * @test
     */
    public function converterFetchesObjectFromPersistenceIfSourceIsANumericString()
    {
        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $backendUser = $propertyMapper->convert('1', BackendUser::class);

        self::assertInstanceOf(BackendUser::class, $backendUser);
        self::assertSame(1, $backendUser->getUid());
    }

    /**
     * @test
     */
    public function converterBuildsEmptyObjectIfSourceIsAnEmptyArray()
    {
        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $backendUser = $propertyMapper->convert([], BackendUser::class);

        self::assertInstanceOf(BackendUser::class, $backendUser);
        self::assertNull($backendUser->getUid());
    }

    /**
     * @test
     */
    public function converterFetchesObjectFromPersistenceIfSourceIsAnArrayWithIdentityKey()
    {
        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $backendUser = $propertyMapper->convert(['__identity' => 1], BackendUser::class);

        self::assertInstanceOf(BackendUser::class, $backendUser);
        self::assertSame(1, $backendUser->getUid());
    }

    /**
     * @test
     */
    public function handleArrayDataThrowsInvalidPropertyMappingConfigurationExceptionIfCreationOfObjectsIsNotAllowed()
    {
        static::expectException(Exception::class);
        static::expectExceptionCode(1297759968);
        static::expectExceptionMessage('Exception while property mapping at property path "": Creation of objects not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_CREATION_ALLOWED" to TRUE');

        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->setTypeConverterOption(
            PersistentObjectConverter::class,
            PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
            false
        );

        $this->getContainer()->get(PropertyMapper::class)->convert(
            [],
            BackendUser::class,
            $propertyMapperConfiguration
        );
    }

    /**
     * @test
     */
    public function converterRespectsAndSetsProperties()
    {
        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        $backendUser = $propertyMapper->convert(['userName' => 'johndoe'], BackendUser::class);

        self::assertInstanceOf(BackendUser::class, $backendUser);
        self::assertNull($backendUser->getUid());
        self::assertSame('johndoe', $backendUser->getUserName());
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyThrowsInvalidTargetExceptionIfPropertyIsNonExistant()
    {
        static::expectException(Exception::class);
        static::expectExceptionCode(1297759968);
        static::expectExceptionMessage('Exception while property mapping at property path "": Property "nonExistant" was not found in target object of type "TYPO3\CMS\Extbase\Domain\Model\BackendUser".');

        $this->getContainer()->get(PropertyMapper::class)->convert(['nonExistant' => 'johndoe'], BackendUser::class);
    }

    /**
     * @test
     */
    public function convertFromThrowsInvalidTargetExceptionIfSourceContainsANonSettableProperty()
    {
        static::expectException(Exception::class);
        static::expectExceptionCode(1297759968);
        static::expectExceptionMessage('Exception while property mapping at property path "": Property "uid" having a value of type "integer" could not be set in target object of type "TYPO3\CMS\Extbase\Domain\Model\BackendUser"');

        $this->getContainer()->get(PropertyMapper::class)->convert(['uid' => 7], BackendUser::class);
    }
}
