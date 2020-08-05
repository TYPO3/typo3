<?php

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ResourceFactoryTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var ResourceFactory
     */
    protected $subject;

    /**
     * @var array
     */
    protected $filesCreated = [];

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
    public function createFolderCreatesObjectWithCorrectArguments()
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
    public function retrieveFileOrFolderObjectCallsGetFolderObjectFromCombinedIdentifierWithRelativePath()
    {
        /** @var $subject \PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|ResourceFactory */
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
    public function retrieveFileOrFolderObjectCallsGetFolderObjectFromCombinedIdentifierWithAbsolutePath()
    {
        /** @var $subject \PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|ResourceFactory */
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
    public function retrieveFileOrFolderObjectReturnsFileIfPathIsGiven()
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
}
