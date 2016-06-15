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
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;
use TYPO3\CMS\Install\FolderStructure\Exception\RootNodeException;

/**
 * Test case
 */
class RootNodeTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function constructorThrowsExceptionIfParentIsNotNull()
    {
        $this->expectException(RootNodeException::class);
        $this->expectExceptionCode(1366140117);
        /** @var $node \TYPO3\CMS\Install\FolderStructure\RootNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, array('isWindowsOs'), array(), '', false);
        $falseParent = $this->createMock(\TYPO3\CMS\Install\FolderStructure\RootNodeInterface::class);
        $node->__construct(array(), $falseParent);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfAbsolutePathIsNotSet()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366141329);
        /** @var $node \TYPO3\CMS\Install\FolderStructure\RootNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, array('isWindowsOs'), array(), '', false);
        $structure = array(
            'type' => 'root',
        );
        $node->__construct($structure, null);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfAbsolutePathIsNotAbsoluteOnWindows()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366141329);
        /** @var $node \TYPO3\CMS\Install\FolderStructure\RootNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, array('isWindowsOs'), array(), '', false);
        $node
            ->expects($this->any())
            ->method('isWindowsOs')
            ->will($this->returnValue(true));
        $structure = array(
            'name' => '/bar'
        );
        $node->__construct($structure, null);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfAbsolutePathIsNotAbsoluteOnUnix()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366141329);
        /** @var $node \TYPO3\CMS\Install\FolderStructure\RootNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, array('isWindowsOs'), array(), '', false);
        $node
            ->expects($this->any())
            ->method('isWindowsOs')
            ->will($this->returnValue(false));
        $structure = array(
            'name' => 'C:/bar'
        );
        $node->__construct($structure, null);
    }

    /**
     * @test
     */
    public function constructorSetsParentToNull()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\RootNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, array('isWindowsOs'), array(), '', false);
        $node
            ->expects($this->any())
            ->method('isWindowsOs')
            ->will($this->returnValue(false));
        $structure = array(
            'name' => '/bar'
        );
        $node->__construct($structure, null);
        $this->assertNull($node->_call('getParent'));
    }

    /**
     * @test
     */
    public function getChildrenReturnsChildCreatedByConstructor()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\RootNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, array('isWindowsOs'), array(), '', false);
        $node
            ->expects($this->any())
            ->method('isWindowsOs')
            ->will($this->returnValue(false));
        $childName = $this->getUniqueId('test_');
        $structure = array(
            'name' => '/foo',
            'children' => array(
                array(
                    'type' => \TYPO3\CMS\Install\FolderStructure\DirectoryNode::class,
                    'name' => $childName,
                ),
            ),
        );
        $node->__construct($structure, null);
        $children = $node->_call('getChildren');
        /** @var $child \TYPO3\CMS\install\FolderStructure\NodeInterface */
        $child = $children[0];
        $this->assertInstanceOf(\TYPO3\CMS\Install\FolderStructure\DirectoryNode::class, $child);
        $this->assertSame($childName, $child->getName());
    }

    /**
     * @test
     */
    public function constructorSetsTargetPermission()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\RootNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, array('isWindowsOs'), array(), '', false);
        $node
            ->expects($this->any())
            ->method('isWindowsOs')
            ->will($this->returnValue(false));
        $targetPermission = '2550';
        $structure = array(
            'name' => '/foo',
            'targetPermission' => $targetPermission,
        );
        $node->__construct($structure, null);
        $this->assertSame($targetPermission, $node->_call('getTargetPermission'));
    }

    /**
     * @test
     */
    public function constructorSetsName()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\RootNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, array('isWindowsOs'), array(), '', false);
        $node
            ->expects($this->any())
            ->method('isWindowsOs')
            ->will($this->returnValue(false));
        $name = '/' . $this->getUniqueId('test_');
        $node->__construct(array('name' => $name), null);
        $this->assertSame($name, $node->getName());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithOkStatusAndCallsOwnStatusMethods()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\DirectoryNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\RootNode::class,
            array('getAbsolutePath', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'),
            array(),
            '',
            false
        );
        $path = PATH_site . 'typo3temp/var/tests/' . $this->getUniqueId('dir_');
        touch($path);
        $this->testFilesToDelete[] = $path;
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->once())->method('exists')->will($this->returnValue(true));
        $node->expects($this->once())->method('isDirectory')->will($this->returnValue(true));
        $node->expects($this->once())->method('isPermissionCorrect')->will($this->returnValue(true));
        $node->expects($this->once())->method('isWritable')->will($this->returnValue(true));
        $statusArray = $node->getStatus();
        /** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
        $status = $statusArray[0];
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\OkStatus::class, $status);
    }

    /**
     * @test
     */
    public function getStatusCallsGetChildrenStatusForStatus()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\DirectoryNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\RootNode::class,
            array('getAbsolutePath', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect', 'getChildrenStatus'),
            array(),
            '',
            false
        );
        $path = PATH_site . 'typo3temp/var/tests/' . $this->getUniqueId('dir_');
        touch($path);
        $this->testFilesToDelete[] = $path;
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isDirectory')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $node->expects($this->any())->method('isWritable')->will($this->returnValue(true));
        $childStatusMock = $this->createMock(\TYPO3\CMS\Install\Status\ErrorStatus::class);
        $node->expects($this->once())->method('getChildrenStatus')->will($this->returnValue(array($childStatusMock)));
        $statusArray = $node->getStatus();
        /** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
        $statusSelf = $statusArray[0];
        $statusOfChild = $statusArray[1];
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\OkStatus::class, $statusSelf);
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\ErrorStatus::class, $statusOfChild);
    }

    /**
     * @test
     */
    public function getAbsolutePathReturnsGivenName()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\RootNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, array('isWindowsOs'), array(), '', false);
        $node
            ->expects($this->any())
            ->method('isWindowsOs')
            ->will($this->returnValue(false));
        $path = '/foo/bar';
        $structure = array(
            'name' => $path,
        );
        $node->__construct($structure, null);
        $this->assertSame($path, $node->getAbsolutePath());
    }
}
