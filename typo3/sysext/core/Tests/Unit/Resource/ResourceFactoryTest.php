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

namespace TYPO3\CMS\Core\Tests\Unit\Resource;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ResourceFactoryTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected bool $backupEnvironment = true;

    /**
     * @var ResourceFactory
     */
    protected $subject;

    protected array $filesCreated = [];

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getAccessibleMock(ResourceFactory::class, ['dummy'], [], '', false);
    }

    /**
     * Tear down
     */
    protected function tearDown(): void
    {
        foreach ($this->filesCreated as $file) {
            unlink($file);
        }
        parent::tearDown();
    }

    /**
     * @test
     */
    public function createFolderCreatesObjectWithCorrectArguments(): void
    {
        $mockedMount = $this->createMock(ResourceStorage::class);
        $path = StringUtility::getUniqueId('path_');
        $name = StringUtility::getUniqueId('name_');
        $folderObject = $this->subject->createFolderObject($mockedMount, $path, $name);
        self::assertSame($mockedMount, $folderObject->getStorage());
        self::assertEquals($path, $folderObject->getIdentifier());
        self::assertEquals($name, $folderObject->getName());
    }

    /***********************************
     *  File Handling
     ***********************************/

    /**
     * @test
     */
    public function retrieveFileOrFolderObjectCallsGetFolderObjectFromCombinedIdentifierWithRelativePath(): void
    {
        /** @var MockObject|AccessibleObjectInterface|ResourceFactory $subject */
        $subject = $this->getAccessibleMock(
            ResourceFactory::class,
            ['getFolderObjectFromCombinedIdentifier'],
            [],
            '',
            false
        );
        $subject
            ->expects(self::once())
            ->method('getFolderObjectFromCombinedIdentifier')
            ->with('typo3');
        $subject->retrieveFileOrFolderObject('typo3');
    }

    /**
     * @test
     */
    public function retrieveFileOrFolderObjectCallsGetFolderObjectFromCombinedIdentifierWithAbsolutePath(): void
    {
        /** @var MockObject|AccessibleObjectInterface|ResourceFactory $subject */
        $subject = $this->getAccessibleMock(
            ResourceFactory::class,
            ['getFolderObjectFromCombinedIdentifier'],
            [],
            '',
            false
        );
        $subject
            ->expects(self::once())
            ->method('getFolderObjectFromCombinedIdentifier')
            ->with('typo3');
        $subject->retrieveFileOrFolderObject(Environment::getPublicPath() . '/typo3');
    }

    /**
     * @test
     */
    public function retrieveFileOrFolderObjectReturnsFileIfPathIsGiven(): void
    {
        $this->subject = $this->getAccessibleMock(ResourceFactory::class, ['getFileObjectFromCombinedIdentifier'], [], '', false);
        $filename = 'typo3temp/var/tests/4711.txt';
        $this->subject->expects(self::once())
            ->method('getFileObjectFromCombinedIdentifier')
            ->with($filename);
        // Create and prepare test file
        GeneralUtility::writeFileToTypo3tempDir(Environment::getPublicPath() . '/' . $filename, '42');
        $this->filesCreated[] = Environment::getPublicPath() . '/' . $filename;
        $this->subject->retrieveFileOrFolderObject($filename);
    }

    /**
     * @test
     */
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

        $this->subject = $this->getAccessibleMock(ResourceFactory::class, ['getFileObjectFromCombinedIdentifier'], [], '', false);
        $filename = 'typo3temp/var/tests/4711.txt';
        $this->subject->expects(self::once())
            ->method('getFileObjectFromCombinedIdentifier')
            ->with($filename);
        // Create and prepare test file
        GeneralUtility::writeFileToTypo3tempDir(Environment::getPublicPath() . '/' . $filename, '42');
        $this->filesCreated[] = Environment::getPublicPath() . '/' . $filename;
        $this->subject->retrieveFileOrFolderObject($filename);
    }

    /**
     * @test
     */
    public function retrieveFileOrFolderObjectReturnsFileFromPublicExtensionResourceWhenExtensionIsNotPublic(): void
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
        $this->subject = $this->getAccessibleMock(ResourceFactory::class, ['getFileObjectFromCombinedIdentifier'], [], '', false);
        $this->subject->expects(self::once())
            ->method('getFileObjectFromCombinedIdentifier')
            ->with('_assets/d25de869aebcd01495d2fe67ad5b0e25/Icons/Extension.svg');
        // Create and prepare test file
        $this->subject->retrieveFileOrFolderObject('EXT:core/Resources/Public/Icons/Extension.svg');
    }

    /**
     * @test
     */
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
        $this->subject = $this->getAccessibleMock(ResourceFactory::class, ['getFileObjectFromCombinedIdentifier'], [], '', false);
        $this->expectException(ResourceDoesNotExistException::class);
        $this->subject->retrieveFileOrFolderObject('EXT:core/Resources/Private/Templates/PageRenderer.html');
    }
}
