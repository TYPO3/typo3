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

namespace TYPO3\CMS\Install\Tests\Unit\FolderStructure;

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
    public function getNameReturnsSetName()
    {
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(AbstractNode::class, ['dummy'], [], '', false);
        $name = StringUtility::getUniqueId('name_');
        $node->_set('name', $name);
        self::assertSame($name, $node->getName());
    }

    /**
     * @test
     */
    public function getTargetPermissionReturnsSetTargetPermission()
    {
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(AbstractNode::class, ['dummy'], [], '', false);
        $permission = '1234';
        $node->_set('targetPermission', $permission);
        self::assertSame($permission, $node->_call('getTargetPermission'));
    }

    /**
     * @test
     */
    public function getChildrenReturnsSetChildren()
    {
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(AbstractNode::class, ['dummy'], [], '', false);
        $children = ['1234'];
        $node->_set('children', $children);
        self::assertSame($children, $node->_call('getChildren'));
    }

    /**
     * @test
     */
    public function getParentReturnsSetParent()
    {
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(AbstractNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $node->_set('parent', $parent);
        self::assertSame($parent, $node->_call('getParent'));
    }

    /**
     * @test
     */
    public function getAbsolutePathCallsParentForPathAndAppendsOwnName()
    {
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
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
    public function isWritableCallsParentIsWritable()
    {
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(AbstractNode::class, ['dummy'], [], '', false);
        $parentMock = $this->createMock(NodeInterface::class);
        $parentMock->expects(self::once())->method('isWritable');
        $node->_set('parent', $parentMock);
        $node->isWritable();
    }

    /**
     * @test
     */
    public function isWritableReturnsWritableStatusOfParent()
    {
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(AbstractNode::class, ['dummy'], [], '', false);
        $parentMock = $this->createMock(NodeInterface::class);
        $parentMock->expects(self::once())->method('isWritable')->willReturn(true);
        $node->_set('parent', $parentMock);
        self::assertTrue($node->isWritable());
    }

    /**
     * @test
     */
    public function existsReturnsTrueIfNodeExists()
    {
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(AbstractNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestDir('dir_');
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        self::assertTrue($node->_call('exists'));
    }

    /**
     * @test
     * @see https://github.com/mikey179/vfsStream/wiki/Known-Issues - symlink doesn't work with vfsStream
     */
    public function existsReturnsTrueIfIsLinkAndTargetIsDead()
    {
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(AbstractNode::class, ['getAbsolutePath'], [], '', false);
        $path = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('link_');
        $target = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('notExists_');
        touch($target);
        symlink($target, $path);
        unlink($target);
        $this->testFilesToDelete[] = $path;
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        self::assertTrue($node->_call('exists'));
    }

    /**
     * @test
     */
    public function existsReturnsFalseIfNodeNotExists()
    {
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(AbstractNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestFilePath('dir_');
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        self::assertFalse($node->_call('exists'));
    }

    /**
     * @test
     */
    public function fixPermissionThrowsExceptionIfPermissionAreAlreadyCorrect()
    {
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            AbstractNode::class,
            ['isPermissionCorrect', 'getAbsolutePath'],
            [],
            '',
            false
        );
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1366744035);
        $node->expects(self::any())->method('getAbsolutePath')->willReturn('');
        $node->expects(self::once())->method('isPermissionCorrect')->willReturn(true);
        $node->_call('fixPermission');
    }

    /**
     * @test
     */
    public function fixPermissionReturnsNoticeStatusIfPermissionCanNotBeChanged()
    {
        if (function_exists('posix_getegid') && posix_getegid() === 0) {
            self::markTestSkipped('Test skipped if run on linux as root');
        }
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            AbstractNode::class,
            ['isPermissionCorrect', 'getRelativePathBelowSiteRoot', 'getAbsolutePath'],
            [],
            '',
            false
        );
        $node->expects(self::any())->method('getRelativePathBelowSiteRoot')->willReturn('');
        $node->expects(self::once())->method('isPermissionCorrect')->willReturn(false);
        $path = $this->getVirtualTestDir('root_');
        $subPath = $path . '/' . StringUtility::getUniqueId('dir_');
        mkdir($subPath);
        chmod($path, 02000);
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($subPath);
        $node->_set('targetPermission', '2770');
        self::assertEquals(FlashMessage::NOTICE, $node->_call('fixPermission')->getSeverity());
        chmod($path, 02770);
    }

    /**
     * @test
     */
    public function fixPermissionReturnsNoticeStatusIfPermissionsCanNotBeChanged()
    {
        if (function_exists('posix_getegid') && posix_getegid() === 0) {
            self::markTestSkipped('Test skipped if run on linux as root');
        }
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            AbstractNode::class,
            ['isPermissionCorrect', 'getRelativePathBelowSiteRoot', 'getAbsolutePath'],
            [],
            '',
            false
        );
        $node->expects(self::any())->method('getRelativePathBelowSiteRoot')->willReturn('');
        $node->expects(self::once())->method('isPermissionCorrect')->willReturn(false);
        $path = $this->getVirtualTestDir('root_');
        $subPath = $path . '/' . StringUtility::getUniqueId('dir_');
        mkdir($subPath);
        chmod($path, 02000);
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($subPath);
        $node->_set('targetPermission', '2770');
        self::assertEquals(FlashMessage::NOTICE, $node->_call('fixPermission')->getSeverity());
        chmod($path, 02770);
    }

    /**
     * @test
     */
    public function fixPermissionReturnsOkStatusIfPermissionCanBeFixedAndSetsPermissionToCorrectValue()
    {
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            AbstractNode::class,
            ['isPermissionCorrect', 'getRelativePathBelowSiteRoot', 'getAbsolutePath'],
            [],
            '',
            false
        );
        $node->expects(self::any())->method('getRelativePathBelowSiteRoot')->willReturn('');
        $node->expects(self::once())->method('isPermissionCorrect')->willReturn(false);
        $path = $this->getVirtualTestDir('root_');
        $subPath = $path . '/' . StringUtility::getUniqueId('dir_');
        mkdir($subPath);
        chmod($path, 02770);
        $node->_set('targetPermission', '2770');
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($subPath);
        self::assertEquals(FlashMessage::OK, $node->_call('fixPermission')->getSeverity());
        $resultDirectoryPermissions = substr(decoct(fileperms($subPath)), 1);
        self::assertSame('2770', $resultDirectoryPermissions);
    }

    /**
     * @test
     */
    public function isPermissionCorrectReturnsTrueOnWindowsOs()
    {
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(AbstractNode::class, ['isWindowsOs'], [], '', false);
        $node->expects(self::once())->method('isWindowsOs')->willReturn(true);
        self::assertTrue($node->_call('isPermissionCorrect'));
    }

    /**
     * @test
     */
    public function isPermissionCorrectReturnsFalseIfTargetPermissionAndCurrentPermissionAreNotIdentical()
    {
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(AbstractNode::class, ['isWindowsOs', 'getCurrentPermission'], [], '', false);
        $node->expects(self::any())->method('isWindowsOs')->willReturn(false);
        $node->expects(self::any())->method('getCurrentPermission')->willReturn('foo');
        $node->_set('targetPermission', 'bar');
        self::assertFalse($node->_call('isPermissionCorrect'));
    }

    /**
     * @test
     */
    public function getCurrentPermissionReturnsCurrentDirectoryPermission()
    {
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(AbstractNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestDir('dir_');
        chmod($path, 02775);
        clearstatcache();
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        self::assertSame('2775', $node->_call('getCurrentPermission'));
    }

    /**
     * @test
     */
    public function getCurrentPermissionReturnsCurrentFilePermission()
    {
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(AbstractNode::class, ['getAbsolutePath'], [], '', false);
        $file = $this->getVirtualTestFilePath('file_');
        touch($file);
        chmod($file, 0770);
        clearstatcache();
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($file);
        self::assertSame('0770', $node->_call('getCurrentPermission'));
    }

    /**
     * @test
     */
    public function getRelativePathBelowSiteRootThrowsExceptionIfGivenPathIsNotBelowPathSiteConstant()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366398198);
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(AbstractNode::class, ['dummy'], [], '', false);
        $node->_call('getRelativePathBelowSiteRoot', '/tmp');
    }

    /**
     * @test
     */
    public function getRelativePathCallsGetAbsolutePathIfPathIsNull()
    {
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
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
    public function getRelativePathBelowSiteRootReturnsSingleForwardSlashIfGivenPathEqualsPathSiteConstant()
    {
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(AbstractNode::class, ['dummy'], [], '', false);
        $result = $node->_call('getRelativePathBelowSiteRoot', Environment::getPublicPath() . '/');
        self::assertSame('/', $result);
    }

    /**
     * @test
     */
    public function getRelativePathBelowSiteRootReturnsSubPath()
    {
        /** @var $node AbstractNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(AbstractNode::class, ['dummy'], [], '', false);
        $result = $node->_call('getRelativePathBelowSiteRoot', Environment::getPublicPath() . '/foo/bar');
        self::assertSame('/foo/bar', $result);
    }
}
