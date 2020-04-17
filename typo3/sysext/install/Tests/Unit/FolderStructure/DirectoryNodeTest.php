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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
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
    public function constructorThrowsExceptionIfParentIsNull(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366222203);
        new DirectoryNode([], null);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfNameContainsForwardSlash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366226639);
        $parent = $this->createMock(NodeInterface::class);
        $structure = [
            'name' => 'foo/bar',
        ];
        new DirectoryNode($structure, $parent);
    }

    /**
     * @test
     */
    public function constructorCallsCreateChildrenIfChildrenAreSet(): void
    {
        $parent = $this->createMock(NodeInterface::class);
        /** @var $node DirectoryNode|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getMockBuilder(DirectoryNode::class)
            ->setMethods(['createChildren'])
            ->disableOriginalConstructor()
            ->getMock();
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
    public function constructorSetsParent(): void
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
    public function constructorSetsTargetPermission(): void
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
    public function constructorSetsName(): void
    {
        $parent = $this->createMock(RootNodeInterface::class);
        $name = StringUtility::getUniqueId('test_');
        $node = new DirectoryNode(['name' => $name], $parent);
        self::assertSame($name, $node->getName());
    }

    /**
     * @test
     */
    public function getStatusReturnsArray(): void
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
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isDirectory')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
        self::assertIsArray($node->getStatus());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithWarningStatusIfDirectoryNotExists(): void
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
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(false);
        $node->method('isDirectory')->willReturn(false);
        $node->method('isPermissionCorrect')->willReturn(false);
        $node->method('isWritable')->willReturn(false);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::WARNING, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithErrorStatusIfNodeIsNotADirectory(): void
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
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isDirectory')->willReturn(false);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::ERROR, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithErrorStatusIfDirectoryExistsButIsNotWritable(): void
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
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isDirectory')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(false);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::ERROR, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithNoticeStatusIfDirectoryExistsButPermissionAreNotCorrect(): void
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
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isDirectory')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(false);
        $node->method('isWritable')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::NOTICE, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithOkStatusIfDirectoryExistsAndPermissionAreCorrect(): void
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
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isDirectory')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::OK, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusCallsGetStatusOnChildren(): void
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['exists', 'isDirectory', 'isPermissionCorrect', 'getRelativePathBelowSiteRoot', 'isWritable'],
            [],
            '',
            false
        );
        $node->method('exists')->willReturn(true);
        $node->method('isDirectory')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
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
    public function getStatusReturnsArrayWithOwnStatusAndStatusOfChild(): void
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['exists', 'isDirectory', 'isPermissionCorrect', 'getRelativePathBelowSiteRoot', 'isWritable'],
            [],
            '',
            false
        );
        $node->method('exists')->willReturn(true);
        $node->method('isDirectory')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
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
    public function fixCallsFixSelfAndReturnsItsResult(): void
    {
        /** @var $node DirectoryNode|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getMockBuilder(DirectoryNode::class)
            ->disableOriginalConstructor()
            ->setMethods(['fixSelf'])
            ->getMock();
        $uniqueReturn = [StringUtility::getUniqueId('foo_')];
        $node->expects(self::once())->method('fixSelf')->willReturn($uniqueReturn);
        self::assertSame($uniqueReturn, $node->fix());
    }

    /**
     * @test
     */
    public function fixCallsFixOnChildrenAndReturnsMergedResult(): void
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['fixSelf'], [], '', false);
        $uniqueReturnSelf = StringUtility::getUniqueId('foo_');
        $node->expects(self::once())->method('fixSelf')->willReturn([$uniqueReturnSelf]);

        $childMock1 = $this->createMock(NodeInterface::class);
        $uniqueReturnChild1 = StringUtility::getUniqueId('foo_');
        $childMock1->expects(self::once())->method('fix')->willReturn([$uniqueReturnChild1]);

        $childMock2 = $this->createMock(NodeInterface::class);
        $uniqueReturnChild2 = StringUtility::getUniqueId('foo_');
        $childMock2->expects(self::once())->method('fix')->willReturn([$uniqueReturnChild2]);

        $node->_set('children', [$childMock1, $childMock2]);

        self::assertSame([$uniqueReturnSelf, $uniqueReturnChild1, $uniqueReturnChild2], $node->fix());
    }

    /**
     * @test
     */
    public function fixSelfCallsCreateDirectoryIfDirectoryDoesNotExistAndReturnsResult(): void
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
        $node->method('isPermissionCorrect')->willReturn(true);
        $uniqueReturn = new FlashMessage('foo');
        $node->expects(self::once())->method('createDirectory')->willReturn($uniqueReturn);
        self::assertSame([$uniqueReturn], $node->_call('fixSelf'));
    }

    /**
     * @test
     */
    public function fixSelfReturnsErrorStatusIfNodeExistsButIsNotADirectoryAndReturnsResult(): void
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['exists', 'isWritable', 'getRelativePathBelowSiteRoot', 'isDirectory', 'getAbsolutePath'],
            [],
            '',
            false
        );
        $node->method('exists')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
        $node->method('isDirectory')->willReturn(false);
        $node->method('getRelativePathBelowSiteRoot')->willReturn('');
        $node->method('getAbsolutePath')->willReturn('');
        $result = $node->_call('fixSelf');
        self::assertSame(FlashMessage::ERROR, $result[0]->getSeverity());
    }

    /**
     * @test
     */
    public function fixSelfCallsFixPermissionIfDirectoryExistsButIsNotWritable(): void
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['exists', 'isWritable', 'fixPermission'],
            [],
            '',
            false
        );
        $node->method('exists')->willReturn(true);
        $node->method('isWritable')->willReturn(false);
        $message = new FlashMessage('foo');
        $node->expects(self::once())->method('fixPermission')->willReturn($message);
        self::assertSame([$message], $node->_call('fixSelf'));
    }

    /**
     * @test
     */
    public function createDirectoryThrowsExceptionIfNodeExists(): void
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
    public function createDirectoryCreatesDirectory(): void
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getVirtualTestFilePath('dir_');
        $node->expects(self::once())->method('exists')->willReturn(false);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->_call('createDirectory');
        self::assertDirectoryExists($path);
    }

    /**
     * @test
     */
    public function createDirectoryReturnsOkStatusIfDirectoryWasCreated(): void
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getVirtualTestFilePath('dir_');
        $node->expects(self::once())->method('exists')->willReturn(false);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        self::assertSame(FlashMessage::OK, $node->_call('createDirectory')->getSeverity());
    }

    /**
     * @test
     */
    public function createDirectoryReturnsErrorStatusIfDirectoryWasNotCreated(): void
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getVirtualTestDir('root_');
        chmod($path, 02550);
        $subPath = $path . '/' . StringUtility::getUniqueId('dir_');
        $node->expects(self::once())->method('exists')->willReturn(false);
        $node->method('getAbsolutePath')->willReturn($subPath);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($subPath);
        self::assertSame(FlashMessage::ERROR, $node->_call('createDirectory')->getSeverity());
    }

    /**
     * @test
     */
    public function createChildrenThrowsExceptionIfAChildTypeIsNotSet(): void
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
    public function createChildrenThrowsExceptionIfAChildNameIsNotSet(): void
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
    public function createChildrenThrowsExceptionForMultipleChildrenWithSameName(): void
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
    public function getChildrenReturnsCreatedChild(): void
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(NodeInterface::class);
        $childName = StringUtility::getUniqueId('test_');
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
    public function isWritableReturnsFalseIfNodeDoesNotExist(): void
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestFilePath('dir_');
        $node->method('getAbsolutePath')->willReturn($path);
        self::assertFalse($node->isWritable());
    }

    /**
     * @test
     */
    public function isWritableReturnsTrueIfNodeExistsAndFileCanBeCreated(): void
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestDir('root_');
        $node->method('getAbsolutePath')->willReturn($path);
        self::assertTrue($node->isWritable());
    }

    /**
     * @test
     */
    public function isWritableReturnsFalseIfNodeExistsButFileCanNotBeCreated(): void
    {
        if (function_exists('posix_getegid') && posix_getegid() === 0) {
            self::markTestSkipped('Test skipped if run on linux as root');
        }
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestDir('root_');
        chmod($path, 02550);
        $node->method('getAbsolutePath')->willReturn($path);
        self::assertFalse($node->isWritable());
    }

    /**
     * @test
     */
    public function isDirectoryReturnsTrueIfNameIsADirectory(): void
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestDir('dir_');
        $node->method('getAbsolutePath')->willReturn($path);
        self::assertTrue($node->_call('isDirectory'));
    }

    /**
     * @test
     * @see https://github.com/mikey179/vfsStream/wiki/Known-Issues - symlink doesn't work with vfsStream
     */
    public function isDirectoryReturnsFalseIfNameIsALinkToADirectory(): void
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $path = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('root_');
        GeneralUtility::mkdir_deep($path);
        $this->testFilesToDelete[] = $path;
        $link = StringUtility::getUniqueId('link_');
        $dir = StringUtility::getUniqueId('dir_');
        mkdir($path . '/' . $dir);
        symlink($path . '/' . $dir, $path . '/' . $link);
        $node->method('getAbsolutePath')->willReturn($path . '/' . $link);
        self::assertFalse($node->_call('isDirectory'));
    }
}
