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

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use TYPO3\CMS\Core\Exception\Archive\ExtractException;
use TYPO3\CMS\Core\Service\Archive\ZipService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ZipServiceTest extends FunctionalTestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $vfs;

    /**
     * @var string
     */
    private $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $structure = [
            'typo3conf' => [
                'ext' => [],
            ],
        ];
        $this->vfs = vfsStream::setup('root', null, $structure);
        $this->directory = vfsStream::url('root/typo3conf/ext');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->vfs, $this->directory);
    }

    /**
     * @test
     */
    public function filesCanNotGetExtractedOutsideTargetDirectory(): void
    {
        $extensionDirectory = $this->directory . '/malicious';
        GeneralUtility::mkdir($extensionDirectory);

        (new ZipService())->extract(
            __DIR__ . '/Fixtures/malicious.zip',
            $extensionDirectory
        );
        self::assertFileNotExists($extensionDirectory . '/../tool.php');
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
        $extensionDirectory = $this->directory . '/my_extension';
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
        $extensionDirectory = $this->directory . '/my_extension';
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

        (new ZipService())->extract(
            'foobar.zip',
            vfsStream::url('root')
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
            vfsStream::url('root/non-existent-directory')
        );
    }

    /**
     * @test
     */
    public function nonWritableDirectoryThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1565773006);

        $extensionDirectory = $this->directory . '/my_extension';
        GeneralUtility::mkdir($extensionDirectory);
        chmod($extensionDirectory, 0000);

        (new ZipService())->extract(
            __DIR__ . '/Fixtures/my_extension.zip',
            $extensionDirectory
        );
        self::assertFileExists($extensionDirectory . '/Resources/Public/Css/empty.css');
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
