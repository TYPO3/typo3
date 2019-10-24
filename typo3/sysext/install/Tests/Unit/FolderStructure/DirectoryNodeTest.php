<?php
namespace TYPO3\CMS\Install\Tests\Unit\FolderStructure;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Install\FolderStructure\DirectoryNode;
use TYPO3\CMS\Install\FolderStructure\Exception;
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;
use TYPO3\CMS\Install\FolderStructure\NodeInterface;
use TYPO3\CMS\Install\FolderStructure\RootNodeInterface;
use TYPO3\CMS\Install\Tests\Unit\FolderStructureTestCase;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;

/**
 * Test case
 */
class DirectoryNodeTest extends FolderStructureTestCase
{
    /**
     * @test
     */
    public function constructorThrowsExceptionIfParentIsNull()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366222203);
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['dummy'], [], '', false);
        $node->__construct([], null);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfNameContainsForwardSlash()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366226639);
        $parent = $this->createMock(NodeInterface::class);
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['dummy'], [], '', false);
        $structure = [
            'name' => 'foo/bar',
        ];
        $node->__construct($structure, $parent);
    }

    /**
     * @test
     */
    public function constructorCallsCreateChildrenIfChildrenAreSet()
    {
        $parent = $this->createMock(NodeInterface::class);
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['createChildren'],
            [],
            '',
            false
        );
        $childArray = [
            'foo',
        ];
        $structure = [
            'name' => 'foo',
            'children' => $childArray,
        ];
        $node->expects(self::once())->method('createChildren')->with($childArray);
        $node->__construct($structure, $parent);
    }

    /**
     * @test
     */
    public function constructorSetsParent()
    {
        $parent = $this->createMock(NodeInterface::class);
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['dummy'], [], '', false);
        $structure = [
            'name' => 'foo',
        ];
        $node->__construct($structure, $parent);
        self::assertSame($parent, $node->_call('getParent'));
    }

    /**
     * @test
     */
    public function constructorSetsTargetPermission()
    {
        $parent = $this->createMock(NodeInterface::class);
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['dummy'], [], '', false);
        $targetPermission = '2550';
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
    public function constructorSetsName()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $name = $this->getUniqueId('test_');
        $node->__construct(['name' => $name], $parent);
        self::assertSame($name, $node->getName());
    }

    /**
     * @test
     */
    public function getStatusReturnsArray()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $path = $this->getVirtualTestDir('dir_');
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        $node->expects(self::any())->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::any())->method('isDirectory')->willReturn(true);
        $node->expects(self::any())->method('isPermissionCorrect')->willReturn(true);
        $node->expects(self::any())->method('isWritable')->willReturn(true);
        self::assertIsArray($node->getStatus());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithWarningStatusIfDirectoryNotExists()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $path = $this->getVirtualTestDir('dir_');
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        $node->expects(self::any())->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->expects(self::any())->method('exists')->willReturn(false);
        $node->expects(self::any())->method('isDirectory')->willReturn(false);
        $node->expects(self::any())->method('isPermissionCorrect')->willReturn(false);
        $node->expects(self::any())->method('isWritable')->willReturn(false);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::WARNING, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithErrorStatusIfNodeIsNotADirectory()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $path = $this->getVirtualTestFilePath('dir_');
        touch($path);
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        $node->expects(self::any())->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::any())->method('isDirectory')->willReturn(false);
        $node->expects(self::any())->method('isPermissionCorrect')->willReturn(true);
        $node->expects(self::any())->method('isWritable')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::ERROR, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithErrorStatusIfDirectoryExistsButIsNotWritable()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $path = $this->getVirtualTestFilePath('dir_');
        touch($path);
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        $node->expects(self::any())->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::any())->method('isDirectory')->willReturn(true);
        $node->expects(self::any())->method('isPermissionCorrect')->willReturn(true);
        $node->expects(self::any())->method('isWritable')->willReturn(false);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::ERROR, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithNoticeStatusIfDirectoryExistsButPermissionAreNotCorrect()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $path = $this->getVirtualTestFilePath('dir_');
        touch($path);
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        $node->expects(self::any())->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::any())->method('isDirectory')->willReturn(true);
        $node->expects(self::any())->method('isPermissionCorrect')->willReturn(false);
        $node->expects(self::any())->method('isWritable')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::NOTICE, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithOkStatusIfDirectoryExistsAndPermissionAreCorrect()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $path = $this->getVirtualTestFilePath('dir_');
        touch($path);
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        $node->expects(self::any())->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::any())->method('isDirectory')->willReturn(true);
        $node->expects(self::any())->method('isPermissionCorrect')->willReturn(true);
        $node->expects(self::any())->method('isWritable')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::OK, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusCallsGetStatusOnChildren()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['exists', 'isDirectory', 'isPermissionCorrect', 'getRelativePathBelowSiteRoot', 'isWritable'],
            [],
            '',
            false
        );
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::any())->method('isDirectory')->willReturn(true);
        $node->expects(self::any())->method('isPermissionCorrect')->willReturn(true);
        $node->expects(self::any())->method('isWritable')->willReturn(true);
        $childMock1 = $this->createMock(NodeInterface::class);
        $childMock1->expects(self::once())->method('getStatus')->willReturn([]);
        $childMock2 = $this->createMock(NodeInterface::class);
        $childMock2->expects(self::once())->method('getStatus')->willReturn([]);
        $node->_set('children', [$childMock1, $childMock2]);
        $node->getStatus();
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithOwnStatusAndStatusOfChild()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['exists', 'isDirectory', 'isPermissionCorrect', 'getRelativePathBelowSiteRoot', 'isWritable'],
            [],
            '',
            false
        );
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::any())->method('isDirectory')->willReturn(true);
        $node->expects(self::any())->method('isPermissionCorrect')->willReturn(true);
        $node->expects(self::any())->method('isWritable')->willReturn(true);
        $childMock = $this->createMock(NodeInterface::class);
        $childMessage = new FlashMessage('foo');
        $childMock->expects(self::once())->method('getStatus')->willReturn([$childMessage]);
        $node->_set('children', [$childMock]);
        $status = $node->getStatus();
        $statusOfDirectory = $status[0];
        $statusOfChild = $status[1];
        self::assertSame(FlashMessage::OK, $statusOfDirectory->getSeverity());
        self::assertSame($childMessage, $statusOfChild);
    }

    /**
     * @test
     */
    public function fixCallsFixSelfAndReturnsItsResult()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['fixSelf'],
            [],
            '',
            false
        );
        $uniqueReturn = [$this->getUniqueId('foo_')];
        $node->expects(self::once())->method('fixSelf')->willReturn($uniqueReturn);
        self::assertSame($uniqueReturn, $node->fix());
    }

    /**
     * @test
     */
    public function fixCallsFixOnChildrenAndReturnsMergedResult()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['fixSelf'], [], '', false);
        $uniqueReturnSelf = $this->getUniqueId('foo_');
        $node->expects(self::once())->method('fixSelf')->willReturn([$uniqueReturnSelf]);

        $childMock1 = $this->createMock(NodeInterface::class);
        $uniqueReturnChild1 = $this->getUniqueId('foo_');
        $childMock1->expects(self::once())->method('fix')->willReturn([$uniqueReturnChild1]);

        $childMock2 = $this->createMock(NodeInterface::class);
        $uniqueReturnChild2 = $this->getUniqueId('foo_');
        $childMock2->expects(self::once())->method('fix')->willReturn([$uniqueReturnChild2]);

        $node->_set('children', [$childMock1, $childMock2]);

        self::assertSame([$uniqueReturnSelf, $uniqueReturnChild1, $uniqueReturnChild2], $node->fix());
    }

    /**
     * @test
     */
    public function fixSelfCallsCreateDirectoryIfDirectoryDoesNotExistAndReturnsResult()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['exists', 'createDirectory', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $node->expects(self::once())->method('exists')->willReturn(false);
        $node->expects(self::any())->method('isPermissionCorrect')->willReturn(true);
        $uniqueReturn = new FlashMessage('foo');
        $node->expects(self::once())->method('createDirectory')->willReturn($uniqueReturn);
        self::assertSame([$uniqueReturn], $node->_call('fixSelf'));
    }

    /**
     * @test
     */
    public function fixSelfReturnsErrorStatusIfNodeExistsButIsNotADirectoryAndReturnsResult()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['exists', 'isWritable', 'getRelativePathBelowSiteRoot', 'isDirectory', 'getAbsolutePath'],
            [],
            '',
            false
        );
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::any())->method('isWritable')->willReturn(true);
        $node->expects(self::any())->method('isDirectory')->willReturn(false);
        $node->expects(self::any())->method('getRelativePathBelowSiteRoot')->willReturn('');
        $node->expects(self::any())->method('getAbsolutePath')->willReturn('');
        $result = $node->_call('fixSelf');
        self::assertSame(FlashMessage::ERROR, $result[0]->getSeverity());
    }

    /**
     * @test
     */
    public function fixSelfCallsFixPermissionIfDirectoryExistsButIsNotWritable()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['exists', 'isWritable', 'fixPermission'],
            [],
            '',
            false
        );
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::any())->method('isWritable')->willReturn(false);
        $message = new FlashMessage('foo');
        $node->expects(self::once())->method('fixPermission')->willReturn($message);
        self::assertSame([$message], $node->_call('fixSelf'));
    }

    /**
     * @test
     */
    public function createDirectoryThrowsExceptionIfNodeExists()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1366740091);
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['exists', 'getAbsolutePath'], [], '', false);
        $node->expects(self::once())->method('getAbsolutePath')->willReturn('');
        $node->expects(self::once())->method('exists')->willReturn(true);
        $node->_call('createDirectory');
    }

    /**
     * @test
     */
    public function createDirectoryCreatesDirectory()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getVirtualTestFilePath('dir_');
        $node->expects(self::once())->method('exists')->willReturn(false);
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        $node->expects(self::any())->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->_call('createDirectory');
        self::assertTrue(is_dir($path));
    }

    /**
     * @test
     */
    public function createDirectoryReturnsOkStatusIfDirectoryWasCreated()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getVirtualTestFilePath('dir_');
        $node->expects(self::once())->method('exists')->willReturn(false);
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        $node->expects(self::any())->method('getRelativePathBelowSiteRoot')->willReturn($path);
        self::assertSame(FlashMessage::OK, $node->_call('createDirectory')->getSeverity());
    }

    /**
     * @test
     */
    public function createDirectoryReturnsErrorStatusIfDirectoryWasNotCreated()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getVirtualTestDir('root_');
        chmod($path, 02550);
        $subPath = $path . '/' . $this->getUniqueId('dir_');
        $node->expects(self::once())->method('exists')->willReturn(false);
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($subPath);
        $node->expects(self::any())->method('getRelativePathBelowSiteRoot')->willReturn($subPath);
        self::assertSame(FlashMessage::ERROR, $node->_call('createDirectory')->getSeverity());
    }

    /**
     * @test
     */
    public function createChildrenThrowsExceptionIfAChildTypeIsNotSet()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366222204);
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['dummy'], [], '', false);
        $brokenStructure = [
            [
                'name' => 'foo',
            ],
        ];
        $node->_call('createChildren', $brokenStructure);
    }

    /**
     * @test
     */
    public function createChildrenThrowsExceptionIfAChildNameIsNotSet()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366222205);
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['dummy'], [], '', false);
        $brokenStructure = [
            [
                'type' => 'foo',
            ],
        ];
        $node->_call('createChildren', $brokenStructure);
    }

    /**
     * @test
     */
    public function createChildrenThrowsExceptionForMultipleChildrenWithSameName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366222206);
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['dummy'], [], '', false);
        $brokenStructure = [
            [
                'type' => DirectoryNode::class,
                'name' => 'foo',
            ],
            [
                'type' => DirectoryNode::class,
                'name' => 'foo',
            ],
        ];
        $node->_call('createChildren', $brokenStructure);
    }

    /**
     * @test
     */
    public function getChildrenReturnsCreatedChild()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(NodeInterface::class);
        $childName = $this->getUniqueId('test_');
        $structure = [
            'name' => 'foo',
            'type' => DirectoryNode::class,
            'children' => [
                [
                    'type' => DirectoryNode::class,
                    'name' => $childName,
                ],
            ],
        ];
        $node->__construct($structure, $parent);
        $children = $node->_call('getChildren');
        /** @var $child NodeInterface */
        $child = $children[0];
        self::assertInstanceOf(DirectoryNode::class, $children[0]);
        self::assertSame($childName, $child->getName());
    }

    /**
     * @test
     */
    public function isWritableReturnsFalseIfNodeDoesNotExist()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestFilePath('dir_');
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        self::assertFalse($node->isWritable());
    }

    /**
     * @test
     */
    public function isWritableReturnsTrueIfNodeExistsAndFileCanBeCreated()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestDir('root_');
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        self::assertTrue($node->isWritable());
    }

    /**
     * @test
     */
    public function isWritableReturnsFalseIfNodeExistsButFileCanNotBeCreated()
    {
        if (function_exists('posix_getegid') && posix_getegid() === 0) {
            self::markTestSkipped('Test skipped if run on linux as root');
        }
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestDir('root_');
        chmod($path, 02550);
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        self::assertFalse($node->isWritable());
    }

    /**
     * @test
     */
    public function isDirectoryReturnsTrueIfNameIsADirectory()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestDir('dir_');
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        self::assertTrue($node->_call('isDirectory'));
    }

    /**
     * @test
     * @see https://github.com/mikey179/vfsStream/wiki/Known-Issues - symlink doesn't work with vfsStream
     */
    public function isDirectoryReturnsFalseIfNameIsALinkToADirectory()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $path = Environment::getVarPath() . '/tests/' . $this->getUniqueId('root_');
        \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($path);
        $this->testFilesToDelete[] = $path;
        $link = $this->getUniqueId('link_');
        $dir = $this->getUniqueId('dir_');
        mkdir($path . '/' . $dir);
        symlink($path . '/' . $dir, $path . '/' . $link);
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path . '/' . $link);
        self::assertFalse($node->_call('isDirectory'));
    }
}
