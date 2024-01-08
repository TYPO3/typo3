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

namespace TYPO3\CMS\Core\Tests\Functional\Resource;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileReferenceTest extends FunctionalTestCase
{
    /**
     * @var array<string, non-empty-string>
     */
    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Resource/Fixtures/ProcessedFileTest.jpg' => 'fileadmin/ProcessedFileTest.jpg',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileReference/FileReference.csv');
    }

    #[Test]
    public function fileReferenceCanBeSoftDeleted(): void
    {
        $fileReference = new FileReference(['uid' => 1, 'uid_local' => 1, 'tablenames' => 'tt_content', 'uid_foreign' => 1]);
        self::assertTrue($fileReference->delete());

        // Ensure file reference is soft deleted and reference index is clear
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FileReference/FileReferenceSoftDelete.csv');

        // Ensure file of file relation is not deleted
        self::assertFileExists(Environment::getPublicPath() . '/fileadmin/ProcessedFileTest.jpg');
    }

    #[Test]
    public function fileReferenceCanBeDeleted(): void
    {
        $fileReference = new FileReference(['uid' => 1, 'uid_local' => 1, 'tablenames' => 'tt_content', 'uid_foreign' => 1]);
        unset($GLOBALS['TCA']['sys_file_reference']['ctrl']['delete']);
        self::assertTrue($fileReference->delete());

        // Ensure file reference is soft deleted and reference index is clear
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FileReference/FileReferenceHardDelete.csv');

        // Ensure file of file relation is not deleted
        self::assertFileExists(Environment::getPublicPath() . '/fileadmin/ProcessedFileTest.jpg');
    }
}
