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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\FolderStructure\DirectoryNode;
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;
use TYPO3\CMS\Install\FolderStructure\Exception\RootNodeException;
use TYPO3\CMS\Install\FolderStructure\RootNode;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RootNodeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function constructorThrowsExceptionIfParentIsNotNull()
    {
        $this->expectException(RootNodeException::class);
        $this->expectExceptionCode(1366140117);
        /** @var $node RootNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $falseParent = $this->createMock(\TYPO3\CMS\Install\FolderStructure\RootNodeInterface::class);
        $node->__construct([], $falseParent);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfAbsolutePathIsNotSet()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366141329);
        /** @var $node RootNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $structure = [
            'type' => 'root',
        ];
        $node->__construct($structure, null);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfAbsolutePathIsNotAbsoluteOnWindows()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366141329);
        /** @var $node RootNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->expects($this->any())
            ->method('isWindowsOs')
            ->will($this->returnValue(true));
        $structure = [
            'name' => '/bar'
        ];
        $node->__construct($structure, null);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfAbsolutePathIsNotAbsoluteOnUnix()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366141329);
        /** @var $node RootNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->expects($this->any())
            ->method('isWindowsOs')
            ->will($this->returnValue(false));
        $structure = [
            'name' => 'C:/bar'
        ];
        $node->__construct($structure, null);
    }

    /**
     * @test
     */
    public function constructorSetsParentToNull()
    {
        /** @var $node RootNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->expects($this->any())
            ->method('isWindowsOs')
            ->will($this->returnValue(false));
        $structure = [
            'name' => '/bar'
        ];
        $node->__construct($structure, null);
        $this->assertNull($node->_call('getParent'));
    }

    /**
     * @test
     */
    public function getChildrenReturnsChildCreatedByConstructor()
    {
        /** @var $node RootNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->expects($this->any())
            ->method('isWindowsOs')
            ->will($this->returnValue(false));
        $childName = $this->getUniqueId('test_');
        $structure = [
            'name' => '/foo',
            'children' => [
                [
                    'type' => DirectoryNode::class,
                    'name' => $childName,
                ],
            ],
        ];
        $node->__construct($structure, null);
        $children = $node->_call('getChildren');
        /** @var $child \TYPO3\CMS\install\FolderStructure\NodeInterface */
        $child = $children[0];
        $this->assertInstanceOf(DirectoryNode::class, $child);
        $this->assertSame($childName, $child->getName());
    }

    /**
     * @test
     */
    public function constructorSetsTargetPermission()
    {
        /** @var $node RootNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->expects($this->any())
            ->method('isWindowsOs')
            ->will($this->returnValue(false));
        $targetPermission = '2550';
        $structure = [
            'name' => '/foo',
            'targetPermission' => $targetPermission,
        ];
        $node->__construct($structure, null);
        $this->assertSame($targetPermission, $node->_call('getTargetPermission'));
    }

    /**
     * @test
     */
    public function constructorSetsName()
    {
        /** @var $node RootNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->expects($this->any())
            ->method('isWindowsOs')
            ->will($this->returnValue(false));
        $name = '/' . $this->getUniqueId('test_');
        $node->__construct(['name' => $name], null);
        $this->assertSame($name, $node->getName());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithOkStatusAndCallsOwnStatusMethods()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            RootNode::class,
            ['getAbsolutePath', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        // do not use var path here, as root nodes get checked for public path as first part
        $path = Environment::getPublicPath() . '/typo3temp/tests/' . $this->getUniqueId('dir_');
        GeneralUtility::mkdir_deep($path);
        $this->testFilesToDelete[] = $path;
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->once())->method('exists')->will($this->returnValue(true));
        $node->expects($this->once())->method('isDirectory')->will($this->returnValue(true));
        $node->expects($this->once())->method('isPermissionCorrect')->will($this->returnValue(true));
        $node->expects($this->once())->method('isWritable')->will($this->returnValue(true));
        $statusArray = $node->getStatus();
        $this->assertSame(FlashMessage::OK, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusCallsGetChildrenStatusForStatus()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            RootNode::class,
            ['getAbsolutePath', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect', 'getChildrenStatus'],
            [],
            '',
            false
        );
        // do not use var path here, as root nodes get checked for public path as first part
        $path = Environment::getPublicPath() . '/typo3temp/tests/' . $this->getUniqueId('dir_');
        GeneralUtility::mkdir_deep($path);
        $this->testFilesToDelete[] = $path;
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isDirectory')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $node->expects($this->any())->method('isWritable')->will($this->returnValue(true));
        $childStatus = new FlashMessage('foo', '', FlashMessage::ERROR);
        $node->expects($this->once())->method('getChildrenStatus')->will($this->returnValue([$childStatus]));
        $statusArray = $node->getStatus();
        $statusSelf = $statusArray[0];
        $statusOfChild = $statusArray[1];
        $this->assertSame(FlashMessage::OK, $statusSelf->getSeverity());
        $this->assertSame($childStatus, $statusOfChild);
    }

    /**
     * @test
     */
    public function getAbsolutePathReturnsGivenName()
    {
        /** @var $node RootNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->expects($this->any())
            ->method('isWindowsOs')
            ->will($this->returnValue(false));
        $path = '/foo/bar';
        $structure = [
            'name' => $path,
        ];
        $node->__construct($structure, null);
        $this->assertSame($path, $node->getAbsolutePath());
    }
}
