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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Controller;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extensionmanager\Controller\ActionController;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ActionControllerTest extends UnitTestCase
{
    /**
     * @var array List of created fake extensions to be deleted in tearDown() again
     */
    protected $fakedExtensions = [];

    /**
     * Creates a fake extension inside typo3temp/. No configuration is created,
     * just the folder
     *
     * @return array
     */
    protected function createFakeExtension()
    {
        $extKey = strtolower(StringUtility::getUniqueId('testing'));
        $absExtPath = Environment::getVarPath() . '/tests/ext-' . $extKey . '/';
        GeneralUtility::mkdir($absExtPath);
        $this->testFilesToDelete[] = Environment::getVarPath() . '/tests/ext-' . $extKey;
        return [
            'extensionKey' => $extKey,
            'version' => '0.0.0',
            'packagePath' => $absExtPath
        ];
    }

    /**
     * Warning: This test asserts multiple things at once to keep the setup short.
     *
     * @test
     */
    public function createZipFileFromExtensionGeneratesCorrectArchive()
    {
        // 42 second of first day in 1970 - used to have achieve stable file names
        $GLOBALS['EXEC_TIME'] = 42;

        // Create extension for testing:
        $fakeExtension = $this->createFakeExtension();
        $extKey = $fakeExtension['extensionKey'];
        $extensionRoot = $fakeExtension['packagePath'];
        $installUtility = $this->prophesize(InstallUtility::class);
        $installUtility->enrichExtensionWithDetails($extKey)->willReturn($fakeExtension);
        // Build mocked fileHandlingUtility:
        $subject = $this->getAccessibleMock(
            ActionController::class,
            ['dummy']
        );
        $subject->injectInstallUtility($installUtility->reveal());

        // Add files and directories to extension:
        touch($extensionRoot . 'emptyFile.txt');
        file_put_contents($extensionRoot . 'notEmptyFile.txt', 'content');
        touch($extensionRoot . '.hiddenFile');
        mkdir($extensionRoot . 'emptyDir');
        mkdir($extensionRoot . 'notEmptyDir');
        touch($extensionRoot . 'notEmptyDir/file.txt');

        // Create zip-file from extension
        $filename = $subject->_call('createZipFileFromExtension', $extKey);

        $expectedFilename = Environment::getVarPath() . '/transient/' . $extKey . '_0.0.0_' . date('YmdHi', 42) . '.zip';
        $this->testFilesToDelete[] = $filename;
        self::assertEquals($expectedFilename, $filename, 'Archive file name differs from expectation');

        // File was created
        self::assertTrue(file_exists($filename), 'Zip file not created');

        // Read archive and check its contents
        $archive = new \ZipArchive();
        self::assertTrue($archive->open($filename), 'Unable to open archive');
        self::assertEquals($archive->statName('emptyFile.txt')['size'], 0, 'Empty file not in archive');
        self::assertEquals($archive->getFromName('notEmptyFile.txt'), 'content', 'Expected content not found');
        self::assertFalse($archive->statName('.hiddenFile'), 'Hidden file not in archive');
        self::assertTrue(is_array($archive->statName('emptyDir/')), 'Empty directory not in archive');
        self::assertTrue(is_array($archive->statName('notEmptyDir/')), 'Not empty directory not in archive');
        self::assertTrue(is_array($archive->statName('notEmptyDir/file.txt')), 'File within directory not in archive');

        // Check that the archive has no additional content
        self::assertEquals($archive->numFiles, 5, 'Too many or too less files in archive');
    }
}
