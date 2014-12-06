<?php
namespace TYPO3\CMS\Install\Tests\Unit\FolderStructure;

/**
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

/**
 * Test case
 */
class RootNodeTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception\RootNodeException
	 */
	public function constructorThrowsExceptionIfParentIsNotNull() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\RootNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, array('isWindowsOs'), array(), '', FALSE);
		$falseParent = $this->getMock(
			\TYPO3\CMS\Install\FolderStructure\RootNodeInterface::class,
			array(),
			array(),
			'',
			FALSE
		);
		$node->__construct(array(), $falseParent);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
	 */
	public function constructorThrowsExceptionIfAbsolutePathIsNotSet() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\RootNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, array('isWindowsOs'), array(), '', FALSE);
		$structure = array(
			'type' => 'root',
		);
		$node->__construct($structure, NULL);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
	 */
	public function constructorThrowsExceptionIfAbsolutePathIsNotAbsoluteOnWindows() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\RootNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, array('isWindowsOs'), array(), '', FALSE);
		$node
			->expects($this->any())
			->method('isWindowsOs')
			->will($this->returnValue(TRUE));
		$structure = array(
			'name' => '/bar'
		);
		$node->__construct($structure, NULL);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
	 */
	public function constructorThrowsExceptionIfAbsolutePathIsNotAbsoluteOnUnix() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\RootNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, array('isWindowsOs'), array(), '', FALSE);
		$node
			->expects($this->any())
			->method('isWindowsOs')
			->will($this->returnValue(FALSE));
		$structure = array(
			'name' => 'C:/bar'
		);
		$node->__construct($structure, NULL);
	}

	/**
	 * @test
	 */
	public function constructorSetsParentToNull() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\RootNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, array('isWindowsOs'), array(), '', FALSE);
		$node
			->expects($this->any())
			->method('isWindowsOs')
			->will($this->returnValue(FALSE));
		$structure = array(
			'name' => '/bar'
		);
		$node->__construct($structure, NULL);
		$this->assertNull($node->_call('getParent'));
	}

	/**
	 * @test
	 */
	public function getChildrenReturnsChildCreatedByConstructor() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\RootNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, array('isWindowsOs'), array(), '', FALSE);
		$node
			->expects($this->any())
			->method('isWindowsOs')
			->will($this->returnValue(FALSE));
		$childName = uniqid('test_');
		$structure = array(
			'name' => '/foo',
			'children' => array(
				array(
					'type' => \TYPO3\CMS\Install\FolderStructure\DirectoryNode::class,
					'name' => $childName,
				),
			),
		);
		$node->__construct($structure, NULL);
		$children = $node->_call('getChildren');
		/** @var $child \TYPO3\CMS\install\FolderStructure\NodeInterface */
		$child = $children[0];
		$this->assertInstanceOf(\TYPO3\CMS\Install\FolderStructure\DirectoryNode::class, $child);
		$this->assertSame($childName, $child->getName());
	}

	/**
	 * @test
	 */
	public function constructorSetsTargetPermission() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\RootNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, array('isWindowsOs'), array(), '', FALSE);
		$node
			->expects($this->any())
			->method('isWindowsOs')
			->will($this->returnValue(FALSE));
		$targetPermission = '2550';
		$structure = array(
			'name' => '/foo',
			'targetPermission' => $targetPermission,
		);
		$node->__construct($structure, NULL);
		$this->assertSame($targetPermission, $node->_call('getTargetPermission'));
	}

	/**
	 * @test
	 */
	public function constructorSetsName() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\RootNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, array('isWindowsOs'), array(), '', FALSE);
		$node
			->expects($this->any())
			->method('isWindowsOs')
			->will($this->returnValue(FALSE));
		$name = '/' . uniqid('test_');
		$node->__construct(array('name' => $name), NULL);
		$this->assertSame($name, $node->getName());
	}

	/**
	 * @test
	 */
	public function getStatusReturnsArrayWithOkStatusAndCallsOwnStatusMethods() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\DirectoryNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			\TYPO3\CMS\Install\FolderStructure\RootNode::class,
			array('getAbsolutePath', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'),
			array(),
			'',
			FALSE
		);
		$path = PATH_site . 'typo3temp/' . uniqid('dir_');
		touch ($path);
		$this->testFilesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->expects($this->once())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->once())->method('isDirectory')->will($this->returnValue(TRUE));
		$node->expects($this->once())->method('isPermissionCorrect')->will($this->returnValue(TRUE));
		$node->expects($this->once())->method('isWritable')->will($this->returnValue(TRUE));
		$statusArray = $node->getStatus();
		/** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
		$status = $statusArray[0];
		$this->assertInstanceOf(\TYPO3\CMS\Install\Status\OkStatus::class, $status);
	}

	/**
	 * @test
	 */
	public function getStatusCallsGetChildrenStatusForStatus() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\DirectoryNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			\TYPO3\CMS\Install\FolderStructure\RootNode::class,
			array('getAbsolutePath', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect', 'getChildrenStatus'),
			array(),
			'',
			FALSE
		);
		$path = PATH_site . 'typo3temp/' . uniqid('dir_');
		touch ($path);
		$this->testFilesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isDirectory')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isWritable')->will($this->returnValue(TRUE));
		$childStatusMock = $this->getMock(\TYPO3\CMS\Install\Status\ErrorStatus::class, array(), array(), '', FALSE);
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
	public function getAbsolutePathReturnsGivenName() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\RootNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, array('isWindowsOs'), array(), '', FALSE);
		$node
			->expects($this->any())
			->method('isWindowsOs')
			->will($this->returnValue(FALSE));
		$path = '/foo/bar';
		$structure = array(
			'name' => $path,
		);
		$node->__construct($structure, NULL);
		$this->assertSame($path, $node->getAbsolutePath());
	}
}
