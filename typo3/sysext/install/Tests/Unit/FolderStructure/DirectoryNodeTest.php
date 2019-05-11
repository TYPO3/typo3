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
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
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
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
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
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
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
        $node->expects($this->once())->method('createChildren')->with($childArray);
        $node->__construct($structure, $parent);
    }

    /**
     * @test
     */
    public function constructorSetsParent()
    {
        $parent = $this->createMock(NodeInterface::class);
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['dummy'], [], '', false);
        $structure = [
            'name' => 'foo',
        ];
        $node->__construct($structure, $parent);
        $this->assertSame($parent, $node->_call('getParent'));
    }

    /**
     * @test
     */
    public function constructorSetsTargetPermission()
    {
        $parent = $this->createMock(NodeInterface::class);
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['dummy'], [], '', false);
        $targetPermission = '2550';
        $structure = [
            'name' => 'foo',
            'targetPermission' => $targetPermission,
        ];
        $node->__construct($structure, $parent);
        $this->assertSame($targetPermission, $node->_call('getTargetPermission'));
    }

    /**
     * @test
     */
    public function constructorSetsName()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $name = $this->getUniqueId('test_');
        $node->__construct(['name' => $name], $parent);
        $this->assertSame($name, $node->getName());
    }

    /**
     * @test
     */
    public function getStatusReturnsArray()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $path = $this->getVirtualTestDir('dir_');
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue($path));
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isDirectory')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $node->expects($this->any())->method('isWritable')->will($this->returnValue(true));
        $this->assertIsArray($node->getStatus());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithWarningStatusIfDirectoryNotExists()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $path = $this->getVirtualTestDir('dir_');
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue($path));
        $node->expects($this->any())->method('exists')->will($this->returnValue(false));
        $node->expects($this->any())->method('isDirectory')->will($this->returnValue(false));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(false));
        $node->expects($this->any())->method('isWritable')->will($this->returnValue(false));
        $statusArray = $node->getStatus();
        $this->assertSame(FlashMessage::WARNING, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithErrorStatusIfNodeIsNotADirectory()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $path = $this->getVirtualTestFilePath('dir_');
        touch($path);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue($path));
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isDirectory')->will($this->returnValue(false));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $node->expects($this->any())->method('isWritable')->will($this->returnValue(true));
        $statusArray = $node->getStatus();
        $this->assertSame(FlashMessage::ERROR, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithErrorStatusIfDirectoryExistsButIsNotWritable()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $path = $this->getVirtualTestFilePath('dir_');
        touch($path);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue($path));
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isDirectory')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $node->expects($this->any())->method('isWritable')->will($this->returnValue(false));
        $statusArray = $node->getStatus();
        $this->assertSame(FlashMessage::ERROR, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithNoticeStatusIfDirectoryExistsButPermissionAreNotCorrect()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $path = $this->getVirtualTestFilePath('dir_');
        touch($path);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue($path));
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isDirectory')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(false));
        $node->expects($this->any())->method('isWritable')->will($this->returnValue(true));
        $statusArray = $node->getStatus();
        $this->assertSame(FlashMessage::NOTICE, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithOkStatusIfDirectoryExistsAndPermissionAreCorrect()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $path = $this->getVirtualTestFilePath('dir_');
        touch($path);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue($path));
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isDirectory')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $node->expects($this->any())->method('isWritable')->will($this->returnValue(true));
        $statusArray = $node->getStatus();
        $this->assertSame(FlashMessage::OK, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusCallsGetStatusOnChildren()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['exists', 'isDirectory', 'isPermissionCorrect', 'getRelativePathBelowSiteRoot', 'isWritable'],
            [],
            '',
            false
        );
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isDirectory')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $node->expects($this->any())->method('isWritable')->will($this->returnValue(true));
        $childMock1 = $this->createMock(NodeInterface::class);
        $childMock1->expects($this->once())->method('getStatus')->will($this->returnValue([]));
        $childMock2 = $this->createMock(NodeInterface::class);
        $childMock2->expects($this->once())->method('getStatus')->will($this->returnValue([]));
        $node->_set('children', [$childMock1, $childMock2]);
        $node->getStatus();
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithOwnStatusAndStatusOfChild()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['exists', 'isDirectory', 'isPermissionCorrect', 'getRelativePathBelowSiteRoot', 'isWritable'],
            [],
            '',
            false
        );
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isDirectory')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $node->expects($this->any())->method('isWritable')->will($this->returnValue(true));
        $childMock = $this->createMock(NodeInterface::class);
        $childMessage = new FlashMessage('foo');
        $childMock->expects($this->once())->method('getStatus')->will($this->returnValue([$childMessage]));
        $node->_set('children', [$childMock]);
        $status = $node->getStatus();
        $statusOfDirectory = $status[0];
        $statusOfChild = $status[1];
        $this->assertSame(FlashMessage::OK, $statusOfDirectory->getSeverity());
        $this->assertSame($childMessage, $statusOfChild);
    }

    /**
     * @test
     */
    public function fixCallsFixSelfAndReturnsItsResult()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['fixSelf'],
            [],
            '',
            false
        );
        $uniqueReturn = [$this->getUniqueId('foo_')];
        $node->expects($this->once())->method('fixSelf')->will($this->returnValue($uniqueReturn));
        $this->assertSame($uniqueReturn, $node->fix());
    }

    /**
     * @test
     */
    public function fixCallsFixOnChildrenAndReturnsMergedResult()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['fixSelf'], [], '', false);
        $uniqueReturnSelf = $this->getUniqueId('foo_');
        $node->expects($this->once())->method('fixSelf')->will($this->returnValue([$uniqueReturnSelf]));

        $childMock1 = $this->createMock(NodeInterface::class);
        $uniqueReturnChild1 = $this->getUniqueId('foo_');
        $childMock1->expects($this->once())->method('fix')->will($this->returnValue([$uniqueReturnChild1]));

        $childMock2 = $this->createMock(NodeInterface::class);
        $uniqueReturnChild2 = $this->getUniqueId('foo_');
        $childMock2->expects($this->once())->method('fix')->will($this->returnValue([$uniqueReturnChild2]));

        $node->_set('children', [$childMock1, $childMock2]);

        $this->assertSame([$uniqueReturnSelf, $uniqueReturnChild1, $uniqueReturnChild2], $node->fix());
    }

    /**
     * @test
     */
    public function fixSelfCallsCreateDirectoryIfDirectoryDoesNotExistAndReturnsResult()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['exists', 'createDirectory', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $node->expects($this->once())->method('exists')->will($this->returnValue(false));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $uniqueReturn = new FlashMessage('foo');
        $node->expects($this->once())->method('createDirectory')->will($this->returnValue($uniqueReturn));
        $this->assertSame([$uniqueReturn], $node->_call('fixSelf'));
    }

    /**
     * @test
     */
    public function fixSelfReturnsErrorStatusIfNodeExistsButIsNotADirectoryAndReturnsResult()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['exists', 'isWritable', 'getRelativePathBelowSiteRoot', 'isDirectory', 'getAbsolutePath'],
            [],
            '',
            false
        );
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isWritable')->will($this->returnValue(true));
        $node->expects($this->any())->method('isDirectory')->will($this->returnValue(false));
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue(''));
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue(''));
        $result = $node->_call('fixSelf');
        $this->assertSame(FlashMessage::ERROR, $result[0]->getSeverity());
    }

    /**
     * @test
     */
    public function fixSelfCallsFixPermissionIfDirectoryExistsButIsNotWritable()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['exists', 'isWritable', 'fixPermission'],
            [],
            '',
            false
        );
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isWritable')->will($this->returnValue(false));
        $message = new FlashMessage('foo');
        $node->expects($this->once())->method('fixPermission')->will($this->returnValue($message));
        $this->assertSame([$message], $node->_call('fixSelf'));
    }

    /**
     * @test
     */
    public function createDirectoryThrowsExceptionIfNodeExists()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1366740091);
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['exists', 'getAbsolutePath'], [], '', false);
        $node->expects($this->once())->method('getAbsolutePath')->will($this->returnValue(''));
        $node->expects($this->once())->method('exists')->will($this->returnValue(true));
        $node->_call('createDirectory');
    }

    /**
     * @test
     */
    public function createDirectoryCreatesDirectory()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getVirtualTestFilePath('dir_');
        $node->expects($this->once())->method('exists')->will($this->returnValue(false));
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue($path));
        $node->_call('createDirectory');
        $this->assertTrue(is_dir($path));
    }

    /**
     * @test
     */
    public function createDirectoryReturnsOkStatusIfDirectoryWasCreated()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getVirtualTestFilePath('dir_');
        $node->expects($this->once())->method('exists')->will($this->returnValue(false));
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue($path));
        $this->assertSame(FlashMessage::OK, $node->_call('createDirectory')->getSeverity());
    }

    /**
     * @test
     */
    public function createDirectoryReturnsErrorStatusIfDirectoryWasNotCreated()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getVirtualTestDir('root_');
        chmod($path, 02550);
        $subPath = $path . '/' . $this->getUniqueId('dir_');
        $node->expects($this->once())->method('exists')->will($this->returnValue(false));
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($subPath));
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue($subPath));
        $this->assertSame(FlashMessage::ERROR, $node->_call('createDirectory')->getSeverity());
    }

    /**
     * @test
     */
    public function createChildrenThrowsExceptionIfAChildTypeIsNotSet()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366222204);
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
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
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
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
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
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
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
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
        $this->assertInstanceOf(DirectoryNode::class, $children[0]);
        $this->assertSame($childName, $child->getName());
    }

    /**
     * @test
     */
    public function isWritableReturnsFalseIfNodeDoesNotExist()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestFilePath('dir_');
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $this->assertFalse($node->isWritable());
    }

    /**
     * @test
     */
    public function isWritableReturnsTrueIfNodeExistsAndFileCanBeCreated()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestDir('root_');
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $this->assertTrue($node->isWritable());
    }

    /**
     * @test
     */
    public function isWritableReturnsFalseIfNodeExistsButFileCanNotBeCreated()
    {
        if (function_exists('posix_getegid') && posix_getegid() === 0) {
            $this->markTestSkipped('Test skipped if run on linux as root');
        }
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestDir('root_');
        chmod($path, 02550);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $this->assertFalse($node->isWritable());
    }

    /**
     * @test
     */
    public function isDirectoryReturnsTrueIfNameIsADirectory()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getVirtualTestDir('dir_');
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $this->assertTrue($node->_call('isDirectory'));
    }

    /**
     * @test
     * @see https://github.com/mikey179/vfsStream/wiki/Known-Issues - symlink doesn't work with vfsStream
     */
    public function isDirectoryReturnsFalseIfNameIsALinkToADirectory()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(DirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $path = Environment::getVarPath() . '/tests/' . $this->getUniqueId('root_');
        \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($path);
        $this->testFilesToDelete[] = $path;
        $link = $this->getUniqueId('link_');
        $dir = $this->getUniqueId('dir_');
        mkdir($path . '/' . $dir);
        symlink($path . '/' . $dir, $path . '/' . $link);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path . '/' . $link));
        $this->assertFalse($node->_call('isDirectory'));
    }
}
