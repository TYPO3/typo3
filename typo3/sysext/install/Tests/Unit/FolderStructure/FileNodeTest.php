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

namespace TYPO3\CMS\Install\Tests\Unit\FolderStructure;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Install\FolderStructure\Exception;
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;
use TYPO3\CMS\Install\FolderStructure\FileNode;
use TYPO3\CMS\Install\FolderStructure\NodeInterface;
use TYPO3\CMS\Install\FolderStructure\RootNodeInterface;
use TYPO3\CMS\Install\Tests\Unit\FolderStructureTestCase;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;

/**
 * Test case
 */
class FileNodeTest extends FolderStructureTestCase
{
    /**
     * @test
     */
    public function constructorThrowsExceptionIfParentIsNull(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366927513);
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['dummy'], [], '', false);
        $node->__construct([], null);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfNameContainsForwardSlash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366222207);
        $parent = $this->createMock(NodeInterface::class);
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['dummy'], [], '', false);
        $structure = [
            'name' => 'foo/bar',
        ];
        $node->__construct($structure, $parent);
    }

    /**
     * @test
     */
    public function constructorSetsParent(): void
    {
        $parent = $this->createMock(NodeInterface::class);
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['dummy'], [], '', false);
        $structure = [
            'name' => 'foo',
        ];
        $node->__construct($structure, $parent);
        self::assertSame($parent, $node->_call('getParent'));
    }

    /**
     * @test
     */
    public function constructorSetsTargetPermission(): void
    {
        $parent = $this->createMock(NodeInterface::class);
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['dummy'], [], '', false);
        $targetPermission = '0660';
        $structure = [
            'name' => 'foo',
            'targetPermission' => $targetPermission,
        ];
        $node->__construct($structure, $parent);
        self::assertSame($targetPermission, $node->_call('getTargetPermission'));
    }

    /**
     * @test
     */
    public function constructorSetsName(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $name = StringUtility::getUniqueId('test_');
        $node->__construct(['name' => $name], $parent);
        self::assertSame($name, $node->getName());
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfBothTargetContentAndTargetContentFileAreSet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380364361);
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $structure = [
            'name' => 'foo',
            'targetContent' => 'foo',
            'targetContentFile' => 'aPath',
        ];
        $node->__construct($structure, $parent);
    }

    /**
     * @test
     */
    public function constructorSetsTargetContent(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $targetContent = StringUtility::getUniqueId('content_');
        $structure = [
            'name' => 'foo',
            'targetContent' => $targetContent,
        ];
        $node->__construct($structure, $parent);
        self::assertSame($targetContent, $node->_get('targetContent'));
    }

    /**
     * @test
     */
    public function constructorSetsTargetContentToContentOfTargetContentFile(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $targetFile = $this->getVirtualTestFilePath('test_');
        $targetContent = StringUtility::getUniqueId('content_');
        file_put_contents($targetFile, $targetContent);
        $structure = [
            'name' => 'foo',
            'targetContentFile' => $targetFile,
        ];
        $node->__construct($structure, $parent);
        self::assertSame($targetContent, $node->_get('targetContent'));
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfTargetContentFileDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380364362);
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $targetFile = $this->getVirtualTestFilePath('test_');
        $structure = [
            'name' => 'foo',
            'targetContentFile' => $targetFile,
        ];
        $node->__construct($structure, $parent);
    }

    /**
     * @test
     */
    public function targetContentIsNullIfNotGiven(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $structure = [
            'name' => 'foo',
        ];
        $node->__construct($structure, $parent);
        self::assertNull($node->_get('targetContent'));
    }

    /**
     * @test
     */
    public function getStatusReturnsArray(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['getAbsolutePath', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'],
            [],
            '',
            false
        );
        // do not use var path here, as file nodes explicitly check for public path
        $path = Environment::getPublicPath() . '/typo3temp/tests/' . StringUtility::getUniqueId('dir_');
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isFile')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
        $node->method('isContentCorrect')->willReturn(true);
        self::assertIsArray($node->getStatus());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithWarningStatusIFileNotExists(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'],
            [],
            '',
            false
        );
        $path = $this->getVirtualTestDir('dir_');
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(false);
        $node->method('isFile')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
        $node->method('isContentCorrect')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::WARNING, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithErrorStatusIfNodeIsNotAFile(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'],
            [],
            '',
            false
        );
        $path = $this->getVirtualTestFilePath('dir_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isFile')->willReturn(false);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
        $node->method('isContentCorrect')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::ERROR, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayNoticeStatusIfFileExistsButIsNotWritable(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'],
            [],
            '',
            false
        );
        $path = $this->getVirtualTestFilePath('dir_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isFile')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(false);
        $node->method('isContentCorrect')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::NOTICE, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithNoticeStatusIfFileExistsButPermissionAreNotCorrect(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'],
            [],
            '',
            false
        );
        $path = $this->getVirtualTestFilePath('dir_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isFile')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(false);
        $node->method('isWritable')->willReturn(true);
        $node->method('isContentCorrect')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::NOTICE, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithNoticeStatusIfFileExistsButContentIsNotCorrect(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'],
            [],
            '',
            false
        );
        $path = $this->getVirtualTestFilePath('dir_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isFile')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
        $node->method('isContentCorrect')->willReturn(false);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::NOTICE, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithOkStatusIfFileExistsAndPermissionAreCorrect(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'],
            [],
            '',
            false
        );
        $path = $this->getVirtualTestFilePath('dir_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isFile')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
        $node->method('isContentCorrect')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::OK, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function fixCallsFixSelfAndReturnsItsResult(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['fixSelf'],
            [],
            '',
            false
        );
        $uniqueReturn = [StringUtility::getUniqueId('foo_')];
        $node->expects(self::once())->method('fixSelf')->willReturn($uniqueReturn);
        self::assertSame($uniqueReturn, $node->fix());
    }

    /**
     * @test
     */
    public function fixSelfCallsCreateFileIfFileDoesNotExistAndReturnsResult(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['exists', 'createFile', 'setContent', 'getAbsolutePath', 'isFile', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $node->method('exists')->willReturn(false);
        $node->method('isFile')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $message = new FlashMessage('foo');
        $node->expects(self::once())->method('createFile')->willReturn($message);
        $actualReturn = $node->_call('fixSelf');
        $actualReturn = $actualReturn[0];
        self::assertSame($message, $actualReturn);
    }

    /**
     * @test
     */
    public function fixSelfCallsSetsContentIfFileCreationWasSuccessfulAndTargetContentIsNotNullAndReturnsResult(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['exists', 'createFile', 'setContent', 'getAbsolutePath', 'isFile', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $node->method('exists')->willReturn(false);
        $node->method('isFile')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $message1 = new FlashMessage('foo');
        $node->method('createFile')->willReturn($message1);
        $node->_set('targetContent', 'foo');
        $message2 = new FlashMessage('foo');
        $node->expects(self::once())->method('setContent')->willReturn($message2);
        $actualReturn = $node->_call('fixSelf');
        $actualReturn = $actualReturn[1];
        self::assertSame($message2, $actualReturn);
    }

    /**
     * @test
     */
    public function fixSelfDoesNotCallSetContentIfFileCreationFailed(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['exists', 'createFile', 'setContent', 'getAbsolutePath', 'isFile', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $node->method('exists')->willReturn(false);
        $node->method('isFile')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $message = new FlashMessage('foo', '', FlashMessage::ERROR);
        $node->method('createFile')->willReturn($message);
        $node->_set('targetContent', 'foo');
        $node->expects(self::never())->method('setContent');
        $node->_call('fixSelf');
    }

    /**
     * @test
     */
    public function fixSelfDoesNotCallSetContentIfFileTargetContentIsNull(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['exists', 'createFile', 'setContent', 'getAbsolutePath', 'isFile', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $node->method('exists')->willReturn(false);
        $node->method('isFile')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $message = new FlashMessage('foo');
        $node->method('createFile')->willReturn($message);
        $node->_set('targetContent', null);
        $node->expects(self::never())->method('setContent');
        $node->_call('fixSelf');
    }

    /**
     * @test
     */
    public function fixSelfReturnsErrorStatusIfNodeExistsButIsNotAFileAndReturnsResult(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['exists', 'createFile', 'getAbsolutePath', 'isFile', 'isPermissionCorrect', 'fixPermission'],
            [],
            '',
            false
        );
        $node->method('exists')->willReturn(true);
        $node->method('isFile')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(false);
        $message = new FlashMessage('foo');
        $node->expects(self::once())->method('fixPermission')->willReturn($message);
        self::assertSame([$message], $node->_call('fixSelf'));
    }

    /**
     * @test
     */
    public function fixSelfCallsFixPermissionIfFileExistsButPermissionAreWrong(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['exists', 'createFile', 'getAbsolutePath', 'isFile', 'isPermissionCorrect', 'getRelativePathBelowSiteRoot'],
            [],
            '',
            false
        );
        $node->method('exists')->willReturn(true);
        $node->expects(self::once())->method('isFile')->willReturn(false);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->_call('fixSelf');
    }

    /**
     * @test
     */
    public function fixSelfReturnsArrayOfStatusMessages(): void
    {
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['exists', 'isFile', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $node->method('exists')->willReturn(true);
        $node->method('isFile')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        self::assertIsArray($node->_call('fixSelf'));
    }

    /**
     * @test
     */
    public function createFileThrowsExceptionIfNodeExists(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1366398198);
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['exists', 'getAbsolutePath'], [], '', false);
        $node->expects(self::once())->method('getAbsolutePath')->willReturn('');
        $node->expects(self::once())->method('exists')->willReturn(true);
        $node->_call('createFile');
    }

    /**
     * @test
     */
    public function createFileReturnsOkStatusIfFileWasCreated(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getVirtualTestFilePath('file_');
        $node->expects(self::once())->method('exists')->willReturn(false);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        self::assertSame(FlashMessage::OK, $node->_call('createFile')->getSeverity());
    }

    /**
     * @test
     */
    public function createFileCreatesFile(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getVirtualTestFilePath('file_');
        $node->expects(self::once())->method('exists')->willReturn(false);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->_call('createFile');
        self::assertTrue(is_file($path));
    }

    /**
     * @test
     */
    public function createFileReturnsErrorStatusIfFileWasNotCreated(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getVirtualTestDir();
        chmod($path, 02550);
        $subPath = $path . '/' . StringUtility::getUniqueId('file_');
        $node->expects(self::once())->method('exists')->willReturn(false);
        $node->method('getAbsolutePath')->willReturn($subPath);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($subPath);
        self::assertSame(FlashMessage::ERROR, $node->_call('createFile')->getSeverity());
    }

    /**
     * @test
     */
    public function isContentCorrectThrowsExceptionIfTargetIsNotAFile(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1367056363);
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestDir('dir_');
        $node->method('getAbsolutePath')->willReturn($path);
        $node->_call('isContentCorrect');
    }

    /**
     * @test
     */
    public function isContentCorrectReturnsTrueIfTargetContentPropertyIsNull(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestFilePath('file_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->_set('targetContent', null);
        self::assertTrue($node->_call('isContentCorrect'));
    }

    /**
     * @test
     */
    public function isContentCorrectReturnsTrueIfTargetContentEqualsCurrentContent(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestFilePath('file_');
        $content = StringUtility::getUniqueId('content_');
        file_put_contents($path, $content);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->_set('targetContent', $content);
        self::assertTrue($node->_call('isContentCorrect'));
    }

    /**
     * @test
     */
    public function isContentCorrectReturnsFalseIfTargetContentNotEqualsCurrentContent(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestFilePath('file_');
        $content = StringUtility::getUniqueId('content1_');
        $targetContent = StringUtility::getUniqueId('content2_');
        file_put_contents($path, $content);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->_set('targetContent', $targetContent);
        self::assertFalse($node->_call('isContentCorrect'));
    }

    /**
     * @test
     */
    public function isPermissionCorrectReturnsTrueIfTargetPermissionAndCurrentPermissionAreIdentical(): void
    {
        $parent = $this->createMock(NodeInterface::class);
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['getCurrentPermission', 'isWindowsOs'], [], '', false);
        $node->method('isWindowsOs')->willReturn(false);
        $node->method('getCurrentPermission')->willReturn('0664');
        $targetPermission = '0664';
        $structure = [
            'name' => 'foo',
            'targetPermission' => $targetPermission,
        ];
        $node->__construct($structure, $parent);
        self::assertTrue($node->_call('isPermissionCorrect'));
    }

    /**
     * @test
     */
    public function setContentThrowsExceptionIfTargetIsNotAFile(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1367060201);
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestDir('dir_');
        $node->method('getAbsolutePath')->willReturn($path);
        $node->_set('targetContent', 'foo');
        $node->_call('setContent');
    }

    /**
     * @test
     */
    public function setContentThrowsExceptionIfTargetContentIsNull(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1367060202);
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestFilePath('file_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->_set('targetContent', null);
        $node->_call('setContent');
    }

    /**
     * @test
     */
    public function setContentSetsContentToFile(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getVirtualTestFilePath('file_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $targetContent = StringUtility::getUniqueId('content_');
        $node->_set('targetContent', $targetContent);
        $node->_call('setContent');
        $resultContent = file_get_contents($path);
        self::assertSame($targetContent, $resultContent);
    }

    /**
     * @test
     */
    public function setContentReturnsOkStatusIfContentWasSuccessfullySet(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getVirtualTestFilePath('file_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $targetContent = StringUtility::getUniqueId('content_');
        $node->_set('targetContent', $targetContent);
        self::assertSame(FlashMessage::OK, $node->_call('setContent')->getSeverity());
    }

    /**
     * @test
     */
    public function setContentReturnsErrorStatusIfContentCanNotBeSetSet(): void
    {
        if (function_exists('posix_getegid') && posix_getegid() === 0) {
            self::markTestSkipped('Test skipped if run on linux as root');
        }
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $dir = $this->getVirtualTestDir('dir_');
        $file = $dir . '/' . StringUtility::getUniqueId('file_');
        touch($file);
        chmod($file, 0440);
        $node->method('getAbsolutePath')->willReturn($file);
        $targetContent = StringUtility::getUniqueId('content_');
        $node->_set('targetContent', $targetContent);
        self::assertSame(FlashMessage::ERROR, $node->_call('setContent')->getSeverity());
    }

    /**
     * @test
     */
    public function isFileReturnsTrueIfNameIsFile(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getVirtualTestFilePath('file_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        self::assertTrue($node->_call('isFile'));
    }

    /**
     * @test
     * @see https://github.com/mikey179/vfsStream/wiki/Known-Issues - symlink doesn't work with vfsStream
     */
    public function isFileReturnsFalseIfNameIsALinkFile(): void
    {
        /** @var $node FileNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath'], [], '', false);
        $path = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('root_');
        GeneralUtility::mkdir_deep($path);
        $this->testFilesToDelete[] = $path;
        $link = StringUtility::getUniqueId('link_');
        $file = StringUtility::getUniqueId('file_');
        touch($path . '/' . $file);
        symlink($path . '/' . $file, $path . '/' . $link);
        $node->method('getAbsolutePath')->willReturn($path . '/' . $link);
        self::assertFalse($node->_call('isFile'));
    }
}
