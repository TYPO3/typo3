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

namespace TYPO3\CMS\Core\Tests\Functional\Resource\Security;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\Security\FileMetadataPermissionsAspect;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileMetadataPermissionsAspectTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileMetadataPermissionsAspect/sys_file.csv');
    }

    #[Test]
    public function updateOfMetadataOfFileInRemovedFallbackStorageIsDenied(): void
    {
        $subject = $this->get(FileMetadataPermissionsAspect::class);
        $dataHandler = $this->get(DataHandler::class);

        self::assertSame(0, $subject->checkRecordUpdateAccess('sys_file_metadata', 1, ['title' => 'changed'], null, $dataHandler));
    }

    #[Test]
    public function updateOfMetadataOfNonExistingFileIsDenied(): void
    {
        $subject = $this->get(FileMetadataPermissionsAspect::class);
        $dataHandler = $this->get(DataHandler::class);

        self::assertSame(0, $subject->checkRecordUpdateAccess('sys_file_metadata', 2, ['title' => 'changed'], null, $dataHandler));
    }
}
