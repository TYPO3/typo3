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
use TYPO3\CMS\Extbase\Mvc\Controller\FileUploadDeletionConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileUploadDeletionConfigurationTest extends UnitTestCase
{
    #[Test]
    public function propertyNameIsSetInConstructor(): void
    {
        $fileUploadDeletionConfiguration = new FileUploadDeletionConfiguration('myProperty');
        self::assertEquals('myProperty', $fileUploadDeletionConfiguration->getPropertyName());
    }

    #[Test]
    public function fileReferenceUidsAreSetInConstructor(): void
    {
        $fileUploadDeletionConfiguration = new FileUploadDeletionConfiguration('myProperty', [1, 2, 3]);
        self::assertEquals([1, 2, 3], $fileUploadDeletionConfiguration->getFileReferenceUids());
    }

    #[Test]
    public function addFileReferenceUidAddsFileReferenceUid(): void
    {
        $fileUploadDeletionConfiguration = new FileUploadDeletionConfiguration('myProperty', [1]);
        $fileUploadDeletionConfiguration->addFileReferenceUid(2);
        self::assertEquals([1, 2], $fileUploadDeletionConfiguration->getFileReferenceUids());
    }
}
