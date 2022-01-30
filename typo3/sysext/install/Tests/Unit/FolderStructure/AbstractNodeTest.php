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
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Install\FolderStructure\AbstractNode;
use TYPO3\CMS\Install\FolderStructure\Exception;
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;
use TYPO3\CMS\Install\FolderStructure\NodeInterface;
use TYPO3\CMS\Install\FolderStructure\RootNodeInterface;
use TYPO3\CMS\Install\Tests\Unit\FolderStructureTestCase;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;

/**
 * Test case
 */
class AbstractNodeTest extends FolderStructureTestCase
{
    /**
     * @test
     */
    public function getNameReturnsSetName(): void
    {
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(AbstractNode::class, ['dummy'], [], '', false);
        $name = StringUtility::getUniqueId('name_');
        $node->_set('name', $name);
        self::assertSame($name, $node->getName());
    }

    /**
     * @test
     */
    public function getTargetPermissionReturnsSetTargetPermission(): void
    {
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(AbstractNode::class, ['dummy'], [], '', false);
        $permission = '1234';
        $node->_set('targetPermission', $permission);
        self::assertSame($permission, $node->_call('getTargetPermission'));
    }

    /**
     * @test
     */
    public function getChildrenReturnsSetChildren(): void
    {
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(AbstractNode::class, ['dummy'], [], '', false);
        $children = ['1234'];
        $node->_set('children', $children);
        self::assertSame($children, $node->_call('getChildren'));
    }

    /**
     * @test
     */
    public function getParentReturnsSetParent(): void
    {
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(AbstractNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $node->_set('parent', $parent);
        self::assertSame($parent, $node->_call('getParent'));
    }

    /**
     * @test
     */
    public function getAbsolutePathCallsParentForPathAndAppendsOwnName(): void
    {
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(AbstractNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $parentPath = '/foo/bar';
        $parent->expects(self::once())->method('getAbsolutePath')->willReturn($parentPath);
        $name = StringUtility::getUniqueId('test_');
        $node->_set('parent', $parent);
        $node->_set('name', $name);
        self::assertSame($parentPath . '/' . $name, $node->getAbsolutePath());
    }

    /**
     * @test
     */
    public function isWritableCallsParentIsWritable(): void
    {
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(AbstractNode::class, ['dummy'], [], '', false);
        $parentMock = $this->createMock(NodeInterface::class);
        $parentMock->expects(self::once())->method('isWritable');
        $node->_set('parent', $parentMock);
        $node->isWritable();
    }

    /**
     * @test
     */
    public function isWritableReturnsWritableStatusOfParent(): void
    {
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(AbstractNode::class, ['dummy'], [], '', false);
        $parentMock = $this->createMock(NodeInterface::class);
        $parentMock->expects(self::once())->method('isWritable')->willReturn(true);
        $node->_set('parent', $parentMock);
        self::assertTrue($node->isWritable());
    }

    /**
     * @test
     */
    public function existsReturnsTrueIfNodeExists(): void
    {
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(AbstractNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestDir('dir_');
        $node->method('getAbsolutePath')->willReturn($path);
        self::assertTrue($node->_call('exists'));
    }

    /**
     * @test
     * @see https://github.com/mikey179/vfsStream/wiki/Known-Issues - symlink doesn't work with vfsStream
     */
    public function existsReturnsTrueIfIsLinkAndTargetIsDead(): void
    {
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(AbstractNode::class, ['getAbsolutePath'], [], '', false);
        $path = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('link_');
        $target = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('notExists_');
        touch($target);
        symlink($target, $path);
        unlink($target);
        $this->testFilesToDelete[] = $path;
        $node->method('getAbsolutePath')->willReturn($path);
        self::assertTrue($node->_call('exists'));
    }

    /**
     * @test
     */
    public function existsReturnsFalseIfNodeNotExists(): void
    {
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(AbstractNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestFilePath('dir_');
        $node->method('getAbsolutePath')->willReturn($path);
        self::assertFalse($node->_call('exists'));
    }

    /**
     * @test
     */
    public function fixPermissionThrowsExceptionIfPermissionAreAlreadyCorrect(): void
    {
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(
            AbstractNode::class,
            ['isPermissionCorrect', 'getAbsolutePath'],
            [],
            '',
            false
        );
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1366744035);
        $node->method('getAbsolutePath')->willReturn('');
        $node->expects(self::once())->method('isPermissionCorrect')->willReturn(true);
        $node->_call('fixPermission');
    }

    /**
     * @test
     */
    public function fixPermissionReturnsOkStatusIfPermissionCanBeFixedAndSetsPermissionToCorrectValue(): void
    {
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(
            AbstractNode::class,
            ['isPermissionCorrect', 'getRelativePathBelowSiteRoot', 'getAbsolutePath'],
            [],
            '',
            false
        );
        $node->method('getRelativePathBelowSiteRoot')->willReturn('');
        $node->expects(self::once())->method('isPermissionCorrect')->willReturn(false);
        $path = $this->getVirtualTestDir();
        $subPath = $path . '/' . StringUtility::getUniqueId('dir_');
        mkdir($subPath);
        chmod($path, 02770);
        $node->_set('targetPermission', '2770');
        $node->method('getAbsolutePath')->willReturn($subPath);
        self::assertEquals(FlashMessage::OK, $node->_call('fixPermission')->getSeverity());
        $resultDirectoryPermissions = substr(decoct(fileperms($subPath)), 1);
        self::assertSame('2770', $resultDirectoryPermissions);
    }

    /**
     * @test
     */
    public function isPermissionCorrectReturnsTrueOnWindowsOs(): void
    {
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(AbstractNode::class, ['isWindowsOs'], [], '', false);
        $node->expects(self::once())->method('isWindowsOs')->willReturn(true);
        self::assertTrue($node->_call('isPermissionCorrect'));
    }

    /**
     * @test
     */
    public function isPermissionCorrectReturnsFalseIfTargetPermissionAndCurrentPermissionAreNotIdentical(): void
    {
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(AbstractNode::class, ['isWindowsOs', 'getCurrentPermission'], [], '', false);
        $node->method('isWindowsOs')->willReturn(false);
        $node->method('getCurrentPermission')->willReturn('foo');
        $node->_set('targetPermission', 'bar');
        self::assertFalse($node->_call('isPermissionCorrect'));
    }

    /**
     * @test
     */
    public function getCurrentPermissionReturnsCurrentDirectoryPermission(): void
    {
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(AbstractNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestDir('dir_');
        chmod($path, 02775);
        clearstatcache();
        $node->method('getAbsolutePath')->willReturn($path);
        self::assertSame('2775', $node->_call('getCurrentPermission'));
    }

    /**
     * @test
     */
    public function getCurrentPermissionReturnsCurrentFilePermission(): void
    {
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(AbstractNode::class, ['getAbsolutePath'], [], '', false);
        $file = $this->getVirtualTestFilePath('file_');
        touch($file);
        chmod($file, 0770);
        clearstatcache();
        $node->method('getAbsolutePath')->willReturn($file);
        self::assertSame('0770', $node->_call('getCurrentPermission'));
    }

    /**
     * @test
     */
    public function getRelativePathBelowSiteRootThrowsExceptionIfGivenPathIsNotBelowPathSiteConstant(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366398198);
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(AbstractNode::class, ['dummy'], [], '', false);
        $node->_call('getRelativePathBelowSiteRoot', '/tmp');
    }

    /**
     * @test
     */
    public function getRelativePathCallsGetAbsolutePathIfPathIsNull(): void
    {
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(
            AbstractNode::class,
            ['getAbsolutePath'],
            [],
            '',
            false
        );
        $node->expects(self::once())->method('getAbsolutePath')->willReturn(Environment::getPublicPath());
        $node->_call('getRelativePathBelowSiteRoot', null);
    }

    /**
     * @test
     */
    public function getRelativePathBelowSiteRootReturnsSingleForwardSlashIfGivenPathEqualsPathSiteConstant(): void
    {
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(AbstractNode::class, ['dummy'], [], '', false);
        $result = $node->_call('getRelativePathBelowSiteRoot', Environment::getPublicPath() . '/');
        self::assertSame('/', $result);
    }

    /**
     * @test
     */
    public function getRelativePathBelowSiteRootReturnsSubPath(): void
    {
        /** @var AbstractNode|AccessibleObjectInterface|MockObject $node */
        $node = $this->getAccessibleMock(AbstractNode::class, ['dummy'], [], '', false);
        $result = $node->_call('getRelativePathBelowSiteRoot', Environment::getPublicPath() . '/foo/bar');
        self::assertSame('/foo/bar', $result);
    }
}
