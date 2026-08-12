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
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ResourceFactoryTest extends FunctionalTestCase
{
    private array $filesCreated = [];

    protected function tearDown(): void
    {
        foreach ($this->filesCreated as $file) {
            unlink($file);
        }
        parent::tearDown();
    }

    /***********************************
     *  File Handling
     ***********************************/
    #[Test]
    public function retrieveFileOrFolderObjectCallsGetFolderObjectFromCombinedIdentifierWithRelativePath(): void
    {
        $subject = $this->getMockBuilder(ResourceFactory::class)
            ->onlyMethods(['getFolderObjectFromCombinedIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $subject
            ->expects($this->once())
            ->method('getFolderObjectFromCombinedIdentifier')
            ->with('typo3');
        $subject->retrieveFileOrFolderObject('typo3');
    }

    #[Test]
    public function retrieveFileOrFolderObjectCallsGetFolderObjectFromCombinedIdentifierWithAbsolutePath(): void
    {
        $subject = $this->getMockBuilder(ResourceFactory::class)
            ->onlyMethods(['getFolderObjectFromCombinedIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $subject
            ->expects($this->once())
            ->method('getFolderObjectFromCombinedIdentifier')
            ->with('typo3');
        $subject->retrieveFileOrFolderObject(Environment::getPublicPath() . '/typo3');
    }

    #[Test]
    public function retrieveFileOrFolderObjectReturnsFileIfPathIsGiven(): void
    {
        $subject = $this->getMockBuilder(ResourceFactory::class)
            ->onlyMethods(['getFileObjectFromCombinedIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $filename = 'typo3temp/var/tests/4711.txt';
        $subject->expects($this->once())
            ->method('getFileObjectFromCombinedIdentifier')
            ->with($filename);
        // Create and prepare test file
        GeneralUtility::writeFileToTypo3tempDir(Environment::getPublicPath() . '/' . $filename, '42');
        $this->filesCreated[] = Environment::getPublicPath() . '/' . $filename;
        $subject->retrieveFileOrFolderObject($filename);
    }

    #[Test]
    public function retrieveFileOrFolderObjectReturnsFileFromPublicFolderWhenProjectRootIsNotPublic(): void
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            true,
            Environment::getProjectPath(),
            Environment::getPublicPath() . '/typo3temp/public',
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );

        GeneralUtility::mkdir_deep(Environment::getPublicPath() . '/typo3temp');

        $subject = $this->getMockBuilder(ResourceFactory::class)
            ->onlyMethods(['getFileObjectFromCombinedIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $filename = 'typo3temp/var/tests/4711.txt';
        $subject->expects($this->once())
            ->method('getFileObjectFromCombinedIdentifier')
            ->with($filename);
        // Create and prepare test file
        GeneralUtility::writeFileToTypo3tempDir(Environment::getPublicPath() . '/' . $filename, '42');
        $this->filesCreated[] = Environment::getPublicPath() . '/' . $filename;
        $subject->retrieveFileOrFolderObject($filename);
    }

    #[Test]
    public function retrieveFileOrFolderObjectReturnsFileFromPublicExtensionResourceWhenExtensionIsNotPublic(): void
    {
        // Emulate public dir and private app dir
        Environment::initialize(
            Environment::getContext(),
            true,
            true,
            Environment::getProjectPath(),
            Environment::getPublicPath() . '/typo3temp/public',
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );

        // Emulate publishing
        $publicFile = '/_assets/d25de869aebcd01495d2fe67ad5b0e25/Icons/Extension.svg';
        GeneralUtility::mkdir_deep(Environment::getPublicPath() . dirname($publicFile));
        copy(GeneralUtility::getFileAbsFileName('EXT:core/Resources/Public/Icons/Extension.svg'), Environment::getPublicPath() . $publicFile);

        $subject = $this->getMockBuilder(ResourceFactory::class)
            ->onlyMethods(['getFileObjectFromCombinedIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $subject->expects($this->once())
            ->method('getFileObjectFromCombinedIdentifier')
            ->with($publicFile);
        // Create and prepare test file
        $subject->retrieveFileOrFolderObject('EXT:core/Resources/Public/Icons/Extension.svg');
    }

    #[Test]
    public function retrieveFileOrFolderObjectThrowsExceptionFromPrivateExtensionResourceWhenExtensionIsNotPublic(): void
    {
        Environment::initialize(
            Environment::getContext(),
            true,
            true,
            Environment::getProjectPath(),
            Environment::getPublicPath() . '/typo3temp/public',
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $subject = $this->getMockBuilder(ResourceFactory::class)
            ->onlyMethods(['getFileObjectFromCombinedIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->expectException(ResourceDoesNotExistException::class);
        $subject->retrieveFileOrFolderObject('EXT:core/Resources/Private/Templates/PageRenderer.html');
    }
}
