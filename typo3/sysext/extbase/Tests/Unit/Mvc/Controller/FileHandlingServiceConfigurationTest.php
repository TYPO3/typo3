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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Mvc\Controller\FileHandlingServiceConfiguration;
use TYPO3\CMS\Extbase\Mvc\Controller\FileUploadConfiguration;
use TYPO3\CMS\Extbase\Mvc\Controller\FileUploadDeletionConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileHandlingServiceConfigurationTest extends UnitTestCase
{
    #[Test]
    public function emptyObjectStoragesCreatedOnInstantiation(): void
    {
        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        self::assertEmpty($fileHandlingServiceConfiguration->getFileUploadConfigurations());
        self::assertEmpty($fileHandlingServiceConfiguration->getFileUploadConfigurations());
    }

    #[Test]
    public function addFileUploadConfigurationAddsConfiguration(): void
    {
        $fileUploadConfiguration = new FileUploadConfiguration('myProperty');
        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfiguration);
        self::assertTrue($fileHandlingServiceConfiguration->hasfileUploadConfigurations());
        self::assertEquals(
            $fileUploadConfiguration,
            $fileHandlingServiceConfiguration->getFileUploadConfigurations()->current()
        );
    }

    #[Test]
    public function getFileUploadConfigurationForPropertyReturnsConfigurationByProperty(): void
    {
        $fileUploadConfigurationProperty1 = new FileUploadConfiguration('myProperty1');
        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $fileHandlingServiceConfiguration->addFileUploadConfiguration($fileUploadConfigurationProperty1);

        self::assertEquals(
            $fileUploadConfigurationProperty1,
            $fileHandlingServiceConfiguration->getFileUploadConfigurationForProperty('myProperty1')
        );
    }

    #[Test]
    public function registerFileDeletionRegistersFileDeletionForPropertyAndFileReferenceUid(): void
    {
        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $fileHandlingServiceConfiguration->registerFileDeletion('myProperty', 1);

        self::assertNotEmpty($fileHandlingServiceConfiguration->getFileUploadDeletionConfigurations());
    }

    #[Test]
    public function registerFileDeletionRegistersFileDeletionsForMultipleFileReferenceUids(): void
    {
        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $fileHandlingServiceConfiguration->registerFileDeletion('myProperty', 1);
        $fileHandlingServiceConfiguration->registerFileDeletion('myProperty', 2);
        $fileHandlingServiceConfiguration->registerFileDeletion('myProperty', 3);

        $fileUploadDeletionConfiguration = $fileHandlingServiceConfiguration->getFileUploadDeletionConfigurationForProperty('myProperty');
        self::assertEquals([1, 2, 3], $fileUploadDeletionConfiguration->getFileReferenceUids());
    }

    #[Test]
    public function getFileUploadDeletionConfigurationForPropertyReturnsConfigurationByProperty(): void
    {
        $fileHandlingServiceConfiguration = new FileHandlingServiceConfiguration();
        $fileHandlingServiceConfiguration->registerFileDeletion('myProperty', 1);

        $fileUploadDeletionConfiguration = $fileHandlingServiceConfiguration->getFileUploadDeletionConfigurationForProperty('myProperty');
        self::assertInstanceOf(FileUploadDeletionConfiguration::class, $fileUploadDeletionConfiguration);
        self::assertEquals('myProperty', $fileUploadDeletionConfiguration->getPropertyName());
    }
}
