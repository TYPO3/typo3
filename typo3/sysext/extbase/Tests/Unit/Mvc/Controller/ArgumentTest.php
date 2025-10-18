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

namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Extbase\Mvc\Controller\Argument;
use TYPO3\CMS\Extbase\Mvc\Controller\FileHandlingServiceConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ArgumentTest extends UnitTestCase
{
    protected Argument $simpleValueArgument;
    protected Argument $objectArgument;

    protected function setUp(): void
    {
        parent::setUp();
        $this->simpleValueArgument = new Argument('someName', 'string');
        $this->objectArgument = new Argument('someName', 'DateTime');
    }

    #[Test]
    public function constructingArgumentWithoutNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1232551853);
        new Argument('', 'Text');
    }

    #[Test]
    public function passingDataTypeToConstructorReallySetsTheDataType(): void
    {
        self::assertEquals('string', $this->simpleValueArgument->getDataType(), 'The specified data type has not been set correctly.');
        self::assertEquals('someName', $this->simpleValueArgument->getName(), 'The specified name has not been set correctly.');
    }

    #[Test]
    public function setShortNameProvidesFluentInterface(): void
    {
        $returnedArgument = $this->simpleValueArgument->setShortName('x');
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
    }

    public static function invalidShortNames(): array
    {
        return [
            [''],
            ['as'],
        ];
    }

    #[DataProvider('invalidShortNames')]
    #[Test]
    public function shortNameShouldThrowExceptionIfInvalid(string $invalidShortName): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1195824959);
        $this->simpleValueArgument->setShortName($invalidShortName);
    }

    #[Test]
    public function shortNameCanBeRetrievedAgain(): void
    {
        $this->simpleValueArgument->setShortName('x');
        self::assertEquals('x', $this->simpleValueArgument->getShortName());
    }

    #[Test]
    public function setRequiredShouldProvideFluentInterfaceAndReallySetRequiredState(): void
    {
        $returnedArgument = $this->simpleValueArgument->setRequired(true);
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        self::assertTrue($this->simpleValueArgument->isRequired());
    }

    #[Test]
    public function setDefaultValueShouldProvideFluentInterfaceAndReallySetDefaultValue(): void
    {
        $returnedArgument = $this->simpleValueArgument->setDefaultValue('default');
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        self::assertSame('default', $this->simpleValueArgument->getDefaultValue());
    }

    #[Test]
    public function setValidatorShouldProvideFluentInterfaceAndReallySetValidator(): void
    {
        $mockValidator = $this->createMock(ValidatorInterface::class);
        $returnedArgument = $this->simpleValueArgument->setValidator($mockValidator);
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        self::assertSame($mockValidator, $this->simpleValueArgument->getValidator());
    }

    #[Test]
    public function setValueProvidesFluentInterface(): void
    {
        $returnedArgument = $this->simpleValueArgument->setValue(null);
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
    }

    #[Test]
    public function setValueUsesNullAsIs(): void
    {
        $this->simpleValueArgument = new Argument('dummy', 'string');
        $this->simpleValueArgument->setValue(null);
        self::assertNull($this->simpleValueArgument->getValue());
    }

    #[Test]
    public function defaultPropertyMappingConfigurationDoesNotAllowCreationOrModificationOfObjects(): void
    {
        self::assertNull($this->simpleValueArgument->getPropertyMappingConfiguration()->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        self::assertNull($this->simpleValueArgument->getPropertyMappingConfiguration()->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
    }

    #[Test]
    public function fileHandlingServiceConfigurationInitializedForNewArgument(): void
    {
        self::assertInstanceOf(FileHandlingServiceConfiguration::class, $this->simpleValueArgument->getFileHandlingServiceConfiguration());
    }

    #[Test]
    public function uploadedFilesContainsInitialValue(): void
    {
        self::assertEmpty($this->simpleValueArgument->getUploadedFiles());
    }

    #[Test]
    public function uploadedFilesCanBeSet(): void
    {
        $uploadedFile = new UploadedFile('/path/to/file', 0, UPLOAD_ERR_OK);
        $this->simpleValueArgument->setUploadedFiles(['someProperty' => $uploadedFile]);

        self::assertNotEmpty($this->simpleValueArgument->getUploadedFiles());
    }

    #[Test]
    public function getUploadedFilesForPropertyReturnsUploadedFileIfAvailable(): void
    {
        $uploadedFile = new UploadedFile('/path/to/file', 0, UPLOAD_ERR_OK);
        $this->simpleValueArgument->setUploadedFiles(['someProperty' => $uploadedFile]);

        self::assertEquals([$uploadedFile], $this->simpleValueArgument->getUploadedFilesForProperty('someProperty'));
    }

    #[Test]
    public function getUploadedFilesForPropertyReturnsEmptyArrayIfNoUploadedFileForProperty(): void
    {
        $uploadedFile = new UploadedFile('/path/to/file', 0, UPLOAD_ERR_OK);
        $this->simpleValueArgument->setUploadedFiles(['someProperty' => $uploadedFile]);

        self::assertEmpty($this->simpleValueArgument->getUploadedFilesForProperty('otherProperty'));
    }
}
