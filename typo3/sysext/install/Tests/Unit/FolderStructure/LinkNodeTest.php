<?php
namespace TYPO3\CMS\Install\Tests\Unit\FolderStructure;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case
 */
class LinkNodeTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array Directories or files in typo3temp/ created during tests to delete afterwards
	 */
	protected $testNodesToDelete = array();

	/**
	 * Tear down
	 */
	public function tearDown() {
		foreach ($this->testNodesToDelete as $node) {
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($node, PATH_site . 'typo3temp/')) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::rmdir($node, TRUE);
			}
		}
		parent::tearDown();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
	 */
	public function constructorThrowsExceptionIfParentIsNull() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\LinkNode', array('dummy'), array(), '', FALSE);
		$node->__construct(array(), NULL);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
	 */
	public function constructorThrowsExceptionIfNameContainsForwardSlash() {
		$parent = $this->getMock('TYPO3\CMS\Install\FolderStructure\NodeInterface', array(), array(), '', FALSE);
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\LinkNode', array('dummy'), array(), '', FALSE);
		$structure = array(
			'name' => 'foo/bar',
		);
		$node->__construct($structure, $parent);
	}

	/**
	 * @test
	 */
	public function constructorSetsParent() {
		$parent = $this->getMock('TYPO3\CMS\Install\FolderStructure\NodeInterface', array(), array(), '', FALSE);
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\LinkNode', array('dummy'), array(), '', FALSE);
		$structure = array(
			'name' => 'foo',
		);
		$node->__construct($structure, $parent);
		$this->assertSame($parent, $node->_call('getParent'));
	}

	/**
	 * @test
	 */
	public function constructorSetsName() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\LinkNode', array('dummy'), array(), '', FALSE);
		$parent = $this->getMock('TYPO3\CMS\Install\FolderStructure\RootNodeInterface', array(), array(), '', FALSE);
		$name = uniqid('test_');
		$node->__construct(array('name' => $name), $parent);
		$this->assertSame($name, $node->getName());
	}

	/**
	 * @test
	 */
	public function constructorSetsTarget() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\LinkNode', array('dummy'), array(), '', FALSE);
		$parent = $this->getMock('TYPO3\CMS\Install\FolderStructure\RootNodeInterface', array(), array(), '', FALSE);
		$target = '../' . uniqid('test_');
		$node->__construct(array('target' => $target), $parent);
		$this->assertSame($target, $node->_call('getTarget'));
	}

	/**
	 * @test
	 */
	public function getStatusReturnsArray() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\LinkNode',
			array('isWindowsOs', 'getAbsolutePath', 'exists'),
			array(),
			'',
			FALSE
		);
		$path = PATH_site . 'typo3temp/' . uniqid('dir_');
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$this->assertInternalType('array', $node->getStatus());
	}

	/**
	 * @test
	 */
	public function getStatusReturnsArrayWithInformationStatusIfRunningOnWindows() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\LinkNode',
			array('isWindowsOs', 'getAbsolutePath', 'exists'),
			array(),
			'',
			FALSE
		);
		$path = PATH_site . 'typo3temp/' . uniqid('dir_');
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->expects($this->once())->method('isWindowsOs')->will($this->returnValue(TRUE));
		$statusArray = $node->getStatus();
		/** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
		$status = $statusArray[0];
		$this->assertInstanceOf('TYPO3\\CMS\\Install\\Status\\InfoStatus', $status);
	}

	/**
	 * @test
	 */
	public function getStatusReturnsArrayWithErrorStatusIfLinkNotExists() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\LinkNode',
			array('isWindowsOs', 'getAbsolutePath', 'exists'),
			array(),
			'',
			FALSE
		);
		$path = PATH_site . 'typo3temp/' . uniqid('dir_');
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->expects($this->any())->method('isWindowsOs')->will($this->returnValue(FALSE));
		$node->expects($this->once())->method('exists')->will($this->returnValue(FALSE));
		$statusArray = $node->getStatus();
		/** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
		$status = $statusArray[0];
		$this->assertInstanceOf('TYPO3\\CMS\\Install\\Status\\ErrorStatus', $status);
	}

	/**
	 * @test
	 */
	public function getStatusReturnsArrayWithWarningStatusIfNodeIsNotALink() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\LinkNode',
			array('isWindowsOs', 'getAbsolutePath', 'exists', 'isLink', 'getRelativePathBelowSiteRoot'),
			array(),
			'',
			FALSE
		);
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->once())->method('isLink')->will($this->returnValue(FALSE));
		$statusArray = $node->getStatus();
		/** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
		$status = $statusArray[0];
		$this->assertInstanceOf('\TYPO3\CMS\Install\Status\WarningStatus', $status);
	}

	/**
	 * @test
	 */
	public function getStatusReturnsErrorStatusIfLinkTargetIsNotCorrect() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\LinkNode',
			array('isWindowsOs', 'getAbsolutePath', 'exists', 'isLink', 'isTargetCorrect', 'getCurrentTarget', 'getRelativePathBelowSiteRoot'),
			array(),
			'',
			FALSE
		);
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->expects($this->any())->method('getCurrentTarget')->will($this->returnValue(''));
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isLink')->will($this->returnValue(TRUE));
		$node->expects($this->once())->method('isLink')->will($this->returnValue(FALSE));
		$statusArray = $node->getStatus();
		/** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
		$status = $statusArray[0];
		$this->assertInstanceOf('\TYPO3\CMS\Install\Status\ErrorStatus', $status);
	}

	/**
	 * @test
	 */
	public function getStatusReturnsOkStatusIfLinkExistsAndTargetIsCorrect() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\LinkNode',
			array('isWindowsOs', 'getAbsolutePath', 'exists', 'isLink', 'isTargetCorrect', 'getRelativePathBelowSiteRoot'),
			array(),
			'',
			FALSE
		);
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->once())->method('isLink')->will($this->returnValue(TRUE));
		$node->expects($this->once())->method('isTargetCorrect')->will($this->returnValue(TRUE));
		$statusArray = $node->getStatus();
		/** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
		$status = $statusArray[0];
		$this->assertInstanceOf('\TYPO3\CMS\Install\Status\OkStatus', $status);
	}

	/**
	 * @test
	 */
	public function fixReturnsEmptyArray() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\LinkNode',
			array('getRelativePathBelowSiteRoot'),
			array(),
			'',
			FALSE
		);
		$statusArray = $node->fix();
		$this->assertEmpty($statusArray);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
	 */
	public function isLinkThrowsExceptionIfLinkNotExists() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\LinkNode', array('exists'), array(), '', FALSE);
		$node->expects($this->once())->method('exists')->will($this->returnValue(FALSE));
		$this->assertFalse($node->_call('isLink'));
	}

	/**
	 * @test
	 */
	public function isLinkReturnsTrueIfNameIsLink() {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test not available on Windows OS.');
		}
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\LinkNode', array('exists', 'getAbsolutePath'), array(), '', FALSE);
		$path = PATH_site . 'typo3temp/' . uniqid('link_');
		$target = PATH_site . uniqid('linkTarget_');
		symlink($target, $path);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$this->assertTrue($node->_call('isLink'));
	}

	/**
	 * @test
	 */
	public function isFileReturnsFalseIfNameIsAFile() {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test not available on Windows OS.');
		}
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\LinkNode', array('exists', 'getAbsolutePath'), array(), '', FALSE);
		$path = PATH_site . 'typo3temp/' . uniqid('file_');
		touch($path);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$this->assertFalse($node->_call('isLink'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
	 */
	public function isTargetCorrectThrowsExceptionIfLinkNotExists() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\LinkNode', array('exists'), array(), '', FALSE);
		$node->expects($this->once())->method('exists')->will($this->returnValue(FALSE));
		$this->assertFalse($node->_call('isTargetCorrect'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
	 */
	public function isTargetCorrectThrowsExceptionIfNodeIsNotALink() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\LinkNode', array('exists', 'isLink', 'getTarget'), array(), '', FALSE);
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->once())->method('isLink')->will($this->returnValue(FALSE));
		$this->assertTrue($node->_call('isTargetCorrect'));
	}

	/**
	 * @test
	 */
	public function isTargetCorrectReturnsTrueIfNoExpectedLinkTargetIsSpecified() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\LinkNode', array('exists', 'isLink', 'getTarget'), array(), '', FALSE);
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isLink')->will($this->returnValue(TRUE));
		$node->expects($this->once())->method('getTarget')->will($this->returnValue(''));
		$this->assertTrue($node->_call('isTargetCorrect'));
	}

	/**
	 * @test
	 */
	public function isTargetCorrectAcceptsATargetWithATrailingSlash() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\LinkNode', array('exists', 'isLink', 'getCurrentTarget', 'getTarget'), array(), '', FALSE);
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isLink')->will($this->returnValue(TRUE));
		$node->expects($this->once())->method('getCurrentTarget')->will($this->returnValue('someLinkTarget'));
		$node->expects($this->once())->method('getTarget')->will($this->returnValue('someLinkTarget/'));
		$this->assertTrue($node->_call('isTargetCorrect'));
	}

	/**
	 * @test
	 */
	public function isTargetCorrectReturnsTrueIfActualTargetIsIdenticalToSpecifiedTarget() {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test not available on Windows OS.');
		}
		$path = PATH_site . 'typo3temp/' . uniqid('link_');
		$target = uniqid('linkTarget_');
		symlink($target, $path);
		$this->testNodesToDelete[] = $path;
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\LinkNode',
			array('exists', 'isLink', 'getTarget', 'getAbsolutePath'),
			array(),
			'',
			FALSE
		);
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isLink')->will($this->returnValue(TRUE));
		$node->expects($this->once())->method('getTarget')->will($this->returnValue($target));
		$node->expects($this->once())->method('getAbsolutePath')->will($this->returnValue($path));
		$this->assertTrue($node->_call('isTargetCorrect'));
	}

	/**
	 * @test
	 */
	public function isTargetCorrectReturnsFalseIfActualTargetIsNotIdenticalToSpecifiedTarget() {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test not available on Windows OS.');
		}
		$path = PATH_site . 'typo3temp/' . uniqid('link_');
		$target = uniqid('linkTarget_');
		symlink($target, $path);
		$this->testNodesToDelete[] = $path;
		/** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\LinkNode',
			array('exists', 'isLink', 'getTarget', 'getAbsolutePath'),
			array(),
			'',
			FALSE
		);
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isLink')->will($this->returnValue(TRUE));
		$node->expects($this->once())->method('getTarget')->will($this->returnValue('foo'));
		$node->expects($this->once())->method('getAbsolutePath')->will($this->returnValue($path));
		$this->assertFalse($node->_call('isTargetCorrect'));
	}

}
