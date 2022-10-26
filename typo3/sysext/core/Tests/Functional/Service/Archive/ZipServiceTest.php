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

namespace TYPO3\CMS\Core\Tests\Functional\Service\Archive;

use TYPO3\CMS\Core\Exception\Archive\ExtractException;
use TYPO3\CMS\Core\Service\Archive\ZipService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ZipServiceTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/ext/malicious', true);
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/ext/my_extension', true);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function filesCanNotGetExtractedOutsideTargetDirectory(): void
    {
        $extensionDirectory = $this->instancePath . '/typo3conf/ext/malicious';
        GeneralUtility::mkdir($extensionDirectory);
        (new ZipService())->extract(
            __DIR__ . '/Fixtures/malicious.zip',
            $extensionDirectory
        );
        self::assertFileDoesNotExist($extensionDirectory . '/../tool.php');
        self::assertFileExists($extensionDirectory . '/tool.php');
        // This is a smoke test to verify PHP's zip library is broken regarding symlinks
        self::assertFileExists($extensionDirectory . '/passwd');
        self::assertFalse(is_link($extensionDirectory . '/passwd'));
    }

    /**
     * @test
     */
    public function fileContentIsExtractedAsExpected(): void
    {
        $extensionDirectory = $this->instancePath . '/typo3conf/ext/my_extension';
        GeneralUtility::mkdir($extensionDirectory);
        (new ZipService())->extract(
            __DIR__ . '/Fixtures/my_extension.zip',
            $extensionDirectory
        );
        self::assertDirectoryExists($extensionDirectory . '/Classes');
        self::assertFileExists($extensionDirectory . '/Resources/Public/Css/empty.css');
        self::assertFileExists($extensionDirectory . '/ext_emconf.php');
    }

    /**
     * @test
     */
    public function fileContentIsExtractedAsExpectedAndSetsPermissions(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'] = '0777';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'] = '0772';
        $extensionDirectory = $this->instancePath . '/typo3conf/ext/my_extension';
        GeneralUtility::mkdir($extensionDirectory);
        (new ZipService())->extract(
            __DIR__ . '/Fixtures/my_extension.zip',
            $extensionDirectory
        );
        self::assertDirectoryExists($extensionDirectory . '/Classes');
        self::assertFileExists($extensionDirectory . '/Resources/Public/Css/empty.css');
        self::assertFileExists($extensionDirectory . '/ext_emconf.php');
        $filePerms = fileperms($extensionDirectory . '/Resources/Public/Css/empty.css');
        $folderPerms = fileperms($extensionDirectory . '/Classes');
        self::assertEquals($GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'], substr(sprintf('%o', $filePerms), -4));
        self::assertEquals($GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'], substr(sprintf('%o', $folderPerms), -4));
    }

    /**
     * @test
     */
    public function nonExistentFileThrowsException(): void
    {
        $this->expectException(ExtractException::class);
        $this->expectExceptionCode(1565709712);
        $extensionDirectory = $this->instancePath . '/typo3conf/ext/my_extension';
        GeneralUtility::mkdir($extensionDirectory);
        (new ZipService())->extract(
            'foobar.zip',
            $this->instancePath . '/typo3conf/ext/my_extension'
        );
    }

    /**
     * @test
     */
    public function nonExistentDirectoryThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1565773005);
        (new ZipService())->extract(
            __DIR__ . '/Fixtures/my_extension.zip',
            $this->instancePath . '/typo3conf/foo/my_extension'
        );
    }

    /**
     * @test
     */
    public function verifyDetectsValidArchive(): void
    {
        self::assertTrue(
            (new ZipService())->verify(__DIR__ . '/Fixtures/my_extension.zip')
        );
    }

    /**
     * @test
     */
    public function verifyDetectsSuspiciousSequences(): void
    {
        $this->expectException(ExtractException::class);
        $this->expectExceptionCode(1565709714);
        (new ZipService())->verify(__DIR__ . '/Fixtures/malicious.zip');
    }
}
