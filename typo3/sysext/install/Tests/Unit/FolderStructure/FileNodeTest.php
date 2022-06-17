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

use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Install\FolderStructure\Exception;
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;
use TYPO3\CMS\Install\FolderStructure\FileNode;
use TYPO3\CMS\Install\FolderStructure\NodeInterface;
use TYPO3\CMS\Install\FolderStructure\RootNodeInterface;
use TYPO3\CMS\Install\Tests\Unit\FolderStructureTestCase;

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
        $node = $this->getAccessibleMock(FileNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $targetFile = $this->getTestFilePath('test_');
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
        $node = $this->getAccessibleMock(FileNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $targetFile = $this->getTestFilePath('test_');
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
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['getAbsolutePath', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'],
            [],
            '',
            false
        );
        // do not use var path here, as file nodes explicitly check for public path
        $testRoot = Environment::getPublicPath() . '/typo3temp/tests/';
        $this->testFilesToDelete[] = $testRoot;
        GeneralUtility::mkdir_deep($testRoot);
        $path = $testRoot . StringUtility::getUniqueId('dir_');
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
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'],
            [],
            '',
            false
        );
        $path = $this->getTestDirectory('dir_');
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(false);
        $node->method('isFile')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
        $node->method('isContentCorrect')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(ContextualFeedbackSeverity::WARNING, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithErrorStatusIfNodeIsNotAFile(): void
    {
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'],
            [],
            '',
            false
        );
        $path = $this->getTestFilePath('dir_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isFile')->willReturn(false);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
        $node->method('isContentCorrect')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(ContextualFeedbackSeverity::ERROR, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayNoticeStatusIfFileExistsButIsNotWritable(): void
    {
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'],
            [],
            '',
            false
        );
        $path = $this->getTestFilePath('dir_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isFile')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(false);
        $node->method('isContentCorrect')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(ContextualFeedbackSeverity::NOTICE, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithNoticeStatusIfFileExistsButPermissionAreNotCorrect(): void
    {
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'],
            [],
            '',
            false
        );
        $path = $this->getTestFilePath('dir_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isFile')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(false);
        $node->method('isWritable')->willReturn(true);
        $node->method('isContentCorrect')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(ContextualFeedbackSeverity::NOTICE, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithNoticeStatusIfFileExistsButContentIsNotCorrect(): void
    {
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'],
            [],
            '',
            false
        );
        $path = $this->getTestFilePath('dir_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isFile')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
        $node->method('isContentCorrect')->willReturn(false);
        $statusArray = $node->getStatus();
        self::assertSame(ContextualFeedbackSeverity::NOTICE, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithOkStatusIfFileExistsAndPermissionAreCorrect(): void
    {
        $node = $this->getAccessibleMock(
            FileNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'],
            [],
            '',
            false
        );
        $path = $this->getTestFilePath('dir_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isFile')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
        $node->method('isContentCorrect')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(ContextualFeedbackSeverity::OK, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function fixCallsFixSelfAndReturnsItsResult(): void
    {
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
        $message = new FlashMessage('foo', '', ContextualFeedbackSeverity::ERROR);
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
        $node = $this->getAccessibleMock(FileNode::class, ['exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getTestFilePath('file_');
        $node->expects(self::once())->method('exists')->willReturn(false);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        self::assertSame(ContextualFeedbackSeverity::OK, $node->_call('createFile')->getSeverity());
    }

    /**
     * @test
     */
    public function createFileCreatesFile(): void
    {
        $node = $this->getAccessibleMock(FileNode::class, ['exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getTestFilePath('file_');
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
        $node = $this->getAccessibleMock(FileNode::class, ['exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        // using vfs here to avoid inconsistent behaviour of file systems concerning permissions
        $root = vfsStream::setup();
        $path = $root->url() . '/typo3temp/var/tests/' . StringUtility::getUniqueId();
        chmod($path, 00440);
        $subPath = $path . '/' . StringUtility::getUniqueId('file_');
        $node->expects(self::once())->method('exists')->willReturn(false);
        $node->method('getAbsolutePath')->willReturn($subPath);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($subPath);
        self::assertSame(ContextualFeedbackSeverity::ERROR, $node->_call('createFile')->getSeverity());
    }

    /**
     * @test
     */
    public function isContentCorrectThrowsExceptionIfTargetIsNotAFile(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1367056363);
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getTestDirectory('dir_');
        $node->method('getAbsolutePath')->willReturn($path);
        $node->_call('isContentCorrect');
    }

    /**
     * @test
     */
    public function isContentCorrectReturnsTrueIfTargetContentPropertyIsNull(): void
    {
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getTestFilePath('file_');
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
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getTestFilePath('file_');
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
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getTestFilePath('file_');
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
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getTestDirectory('dir_');
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
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getTestFilePath('file_');
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
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getTestFilePath('file_');
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
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getTestFilePath('file_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $targetContent = StringUtility::getUniqueId('content_');
        $node->_set('targetContent', $targetContent);
        self::assertSame(ContextualFeedbackSeverity::OK, $node->_call('setContent')->getSeverity());
    }

    /**
     * @test
     */
    public function isFileReturnsTrueIfNameIsFile(): void
    {
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getTestFilePath('file_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        self::assertTrue($node->_call('isFile'));
    }

    /**
     * @test
     */
    public function isFileReturnsFalseIfNameIsALinkFile(): void
    {
        $node = $this->getAccessibleMock(FileNode::class, ['getAbsolutePath'], [], '', false);
        // do not use var path here, as file nodes explicitly check for public path
        $testRoot = Environment::getPublicPath() . '/typo3temp/tests/';
        $path = $testRoot . StringUtility::getUniqueId('root_');
        $this->testFilesToDelete[] = $testRoot;
        GeneralUtility::mkdir_deep($path);
        $link = StringUtility::getUniqueId('link_');
        $file = StringUtility::getUniqueId('file_');
        touch($path . '/' . $file);
        symlink($path . '/' . $file, $path . '/' . $link);
        $node->method('getAbsolutePath')->willReturn($path . '/' . $link);
        self::assertFalse($node->_call('isFile'));
    }
}
