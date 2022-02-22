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

use TYPO3\CMS\Beuser\Domain\Model\BackendUser;
use TYPO3\CMS\Extbase\Property\Exception;
use TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PersistentObjectConverterTest extends FunctionalTestCase
{
    // @todo: Switch to a simple test extension that contains a test model, instead.
    protected array $coreExtensionsToLoad = ['beuser'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/be_users.csv');
    }

    /**
     * @test
     */
    public function converterReturnsNullWithEmptyStringsOrIntegers(): void
    {
        $propertyMapper = $this->get(PropertyMapper::class);

        self::assertNull($propertyMapper->convert(0, BackendUser::class));
        self::assertNull($propertyMapper->convert('', BackendUser::class));
    }

    /**
     * @test
     */
    public function fetchObjectFromPersistenceThrowsInvalidSourceExceptionIfSourceIANonNumericString(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('Exception while property mapping at property path "": The identity property "foo" is no UID.');

        $this->get(PropertyMapper::class)->convert('foo', BackendUser::class);
    }

    /**
     * @test
     */
    public function fetchObjectFromPersistenceThrowsTargetNotFoundExceptionIfObjectIsNotToBeFoundInThePersistence(): void
    {
        $this->expectException(TargetNotFoundException::class);
        $this->expectExceptionCode(1297933823);
        $this->expectExceptionMessage('Object of type TYPO3\CMS\Beuser\Domain\Model\BackendUser with identity "2" not found.');

        $this->get(PropertyMapper::class)->convert(2, BackendUser::class);
    }

    /**
     * @test
     */
    public function converterFetchesObjectFromPersistenceIfSourceIsAnInteger(): void
    {
        $propertyMapper = $this->get(PropertyMapper::class);

        $backendUser = $propertyMapper->convert(1, BackendUser::class);

        self::assertInstanceOf(BackendUser::class, $backendUser);
        self::assertSame(1, $backendUser->getUid());
    }

    /**
     * @test
     */
    public function converterFetchesObjectFromPersistenceIfSourceIsANumericString(): void
    {
        $propertyMapper = $this->get(PropertyMapper::class);

        $backendUser = $propertyMapper->convert('1', BackendUser::class);

        self::assertInstanceOf(BackendUser::class, $backendUser);
        self::assertSame(1, $backendUser->getUid());
    }

    /**
     * @test
     */
    public function converterBuildsEmptyObjectIfSourceIsAnEmptyArray(): void
    {
        $propertyMapper = $this->get(PropertyMapper::class);

        $backendUser = $propertyMapper->convert([], BackendUser::class);

        self::assertInstanceOf(BackendUser::class, $backendUser);
        self::assertNull($backendUser->getUid());
    }

    /**
     * @test
     */
    public function converterFetchesObjectFromPersistenceIfSourceIsAnArrayWithIdentityKey(): void
    {
        $propertyMapper = $this->get(PropertyMapper::class);

        $backendUser = $propertyMapper->convert(['__identity' => 1], BackendUser::class);

        self::assertInstanceOf(BackendUser::class, $backendUser);
        self::assertSame(1, $backendUser->getUid());
    }

    /**
     * @test
     */
    public function handleArrayDataThrowsInvalidPropertyMappingConfigurationExceptionIfCreationOfObjectsIsNotAllowed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('Exception while property mapping at property path "": Creation of objects not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_CREATION_ALLOWED" to TRUE');

        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->setTypeConverterOption(
            PersistentObjectConverter::class,
            PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
            false
        );

        $this->get(PropertyMapper::class)->convert(
            [],
            BackendUser::class,
            $propertyMapperConfiguration
        );
    }

    /**
     * @test
     */
    public function converterRespectsAndSetsProperties(): void
    {
        $propertyMapper = $this->get(PropertyMapper::class);

        $backendUser = $propertyMapper->convert(['userName' => 'johndoe'], BackendUser::class);

        self::assertInstanceOf(BackendUser::class, $backendUser);
        self::assertNull($backendUser->getUid());
        self::assertSame('johndoe', $backendUser->getUserName());
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyThrowsInvalidTargetExceptionIfPropertyIsNonExistant(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('Exception while property mapping at property path "": Property "nonExistant" was not found in target object of type "TYPO3\CMS\Beuser\Domain\Model\BackendUser".');

        $this->get(PropertyMapper::class)->convert(['nonExistant' => 'johndoe'], BackendUser::class);
    }

    /**
     * @test
     */
    public function convertFromThrowsInvalidTargetExceptionIfSourceContainsANonSettableProperty(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('Exception while property mapping at property path "": Property "uid" having a value of type "int" could not be set in target object of type "TYPO3\CMS\Beuser\Domain\Model\BackendUser"');

        $this->get(PropertyMapper::class)->convert(['uid' => 7], BackendUser::class);
    }
}
