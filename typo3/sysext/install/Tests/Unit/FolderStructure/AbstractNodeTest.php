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
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
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
class AbstractNodeTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array Directories or files in typo3temp/ created during tests to delete afterwards
	 */
	protected $testNodesToDelete = array();

	/**
	 * Tear down
	 */
	public function tearDown() {
		foreach($this->testNodesToDelete as $node) {
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($node, PATH_site . 'typo3temp/')) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::rmdir($node, TRUE);
			}
		}
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function getNameReturnsSetName() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode', array('dummy'), array(), '', FALSE);
		$name = uniqid('name_');
		$node->_set('name', $name);
		$this->assertSame($name, $node->getName());
	}

	/**
	 * @test
	 */
	public function getTargetPermissionReturnsSetTargetPermission() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode', array('dummy'), array(), '', FALSE);
		$permission = '1234';
		$node->_set('targetPermission', $permission);
		$this->assertSame($permission, $node->_call('getTargetPermission'));
	}

	/**
	 * @test
	 */
	public function getChildrenReturnsSetChildren() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode', array('dummy'), array(), '', FALSE);
		$children = array('1234');
		$node->_set('children', $children);
		$this->assertSame($children, $node->_call('getChildren'));
	}

	/**
	 * @test
	 */
	public function getParentReturnsSetParent() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode', array('dummy'), array(), '', FALSE);
		$parent = $this->getMock('TYPO3\CMS\Install\FolderStructure\RootNodeInterface', array(), array(), '', FALSE);
		$node->_set('parent', $parent);
		$this->assertSame($parent, $node->_call('getParent'));
	}

	/**
	 * @test
	 */
	public function getAbsolutePathCallsParentForPathAndAppendsOwnName() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode', array('dummy'), array(), '', FALSE);
		$parent = $this->getMock('TYPO3\CMS\Install\FolderStructure\RootNodeInterface', array(), array(), '', FALSE);
		$parentPath = '/foo/bar';
		$parent->expects($this->once())->method('getAbsolutePath')->will($this->returnValue($parentPath));
		$name = uniqid('test_');
		$node->_set('parent', $parent);
		$node->_set('name', $name);
		$this->assertSame($parentPath . '/' . $name, $node->getAbsolutePath());
	}

	/**
	 * @test
	 */
	public function isWritableCallsParentIsWritable() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode', array('dummy'), array(), '', FALSE);
		$parentMock = $this->getMock('TYPO3\\CMS\\Install\\FolderStructure\\NodeInterface', array(), array(), '', FALSE);
		$parentMock->expects($this->once())->method('isWritable');
		$node->_set('parent', $parentMock);
		$node->isWritable();
	}

	/**
	 * @test
	 */
	public function isWritableReturnsWritableStatusOfParent() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode', array('dummy'), array(), '', FALSE);
		$parentMock = $this->getMock('TYPO3\\CMS\\Install\\FolderStructure\\NodeInterface', array(), array(), '', FALSE);
		$parentMock->expects($this->once())->method('isWritable')->will($this->returnValue(TRUE));
		$node->_set('parent', $parentMock);
		$this->assertTrue($node->isWritable());
	}

	/**
	 * @test
	 */
	public function existsReturnsTrueIfNodeExists() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode', array('getAbsolutePath'), array(), '', FALSE);
		$path = PATH_site . 'typo3temp/' . uniqid('dir_');
		\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($path);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$this->assertTrue($node->_call('exists'));
	}

	/**
	 * @test
	 */
	public function existsReturnsTrueIfIsLinkAndTargetIsDead() {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test not available on Windows OS.');
		}
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode', array('getAbsolutePath'), array(), '', FALSE);
		$path = PATH_site . 'typo3temp/' . uniqid('link_');
		$target = PATH_site . 'typo3temp/' . uniqid('notExists_');
		symlink($target, $path);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$this->assertTrue($node->_call('exists'));
	}

	/**
	 * @test
	 */
	public function existsReturnsFalseIfNodeNotExists() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode', array('getAbsolutePath'), array(), '', FALSE);
		$path = PATH_site . 'typo3temp/' . uniqid('dir_');
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$this->assertFalse($node->_call('exists'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception
	 */
	public function fixPermissionThrowsExceptionIfPermissionAreAlreadyCorrect() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode',
			array('isPermissionCorrect', 'getAbsolutePath'),
			array(),
			'',
			FALSE
		);
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue(''));
		$node->expects($this->once())->method('isPermissionCorrect')->will($this->returnValue(TRUE));
		$node->_call('fixPermission');
	}

	/**
	 * @test
	 */
	public function fixPermissionReturnsNoticeStatusIfPermissionCanNotBeChanged() {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test not available on Windows OS.');
		}
		if (function_exists('posix_getegid') && posix_getegid() === 0) {
			$this->markTestSkipped('Test skipped if run on linux as root');
		}
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode',
			array('isPermissionCorrect', 'getRelativePathBelowSiteRoot', 'getAbsolutePath'),
			array(),
			'',
			FALSE
		);
		$node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue(''));
		$node->expects($this->once())->method('isPermissionCorrect')->will($this->returnValue(FALSE));
		$path = PATH_site . 'typo3temp/' . uniqid('root_');
		mkdir($path);
		$subPath = $path . '/' . uniqid('dir_');
		mkdir($subPath);
		chmod($path, 02000);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($subPath));
		$node->_set('targetPermission', '2770');
		$this->assertInstanceOf('TYPO3\\CMS\\Install\\Status\\NoticeStatus', $node->_call('fixPermission'));
		chmod($path, 02770);
	}

	/**
	 * @test
	 */
	public function fixPermissionReturnsNoticeStatusIfPermissionsCanNotBeChanged() {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test not available on Windows OS.');
		}
		if (function_exists('posix_getegid') && posix_getegid() === 0) {
			$this->markTestSkipped('Test skipped if run on linux as root');
		}
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode',
			array('isPermissionCorrect', 'getRelativePathBelowSiteRoot', 'getAbsolutePath'),
			array(),
			'',
			FALSE
		);
		$node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue(''));
		$node->expects($this->once())->method('isPermissionCorrect')->will($this->returnValue(FALSE));
		$path = PATH_site . 'typo3temp/' . uniqid('root_');
		mkdir($path);
		$subPath = $path . '/' . uniqid('dir_');
		mkdir($subPath);
		chmod($path, 02000);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($subPath));
		$node->_set('targetPermission', '2770');
		$this->assertInstanceOf('TYPO3\\CMS\\Install\\Status\\NoticeStatus', $node->_call('fixPermission'));
		chmod($path, 02770);
	}

	/**
	 * @test
	 */
	public function fixPermissionReturnsOkStatusIfPermissionCanBeFixedAndSetsPermissionToCorrectValue() {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test not available on Windows OS.');
		}
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode',
			array('isPermissionCorrect', 'getRelativePathBelowSiteRoot', 'getAbsolutePath'),
			array(),
			'',
			FALSE
		);
		$node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue(''));
		$node->expects($this->once())->method('isPermissionCorrect')->will($this->returnValue(FALSE));
		$path = PATH_site . 'typo3temp/' . uniqid('root_');
		mkdir($path);
		$subPath = $path . '/' . uniqid('dir_');
		mkdir($subPath);
		chmod($path, 02770);
		$this->testNodesToDelete[] = $path;
		$node->_set('targetPermission', '2770');
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($subPath));
		$this->assertInstanceOf('TYPO3\\CMS\\Install\\Status\\OkStatus', $node->_call('fixPermission'));
		$resultDirectoryPermissions = substr(decoct(fileperms($subPath)), 1);
		$this->assertSame('2770', $resultDirectoryPermissions);
	}

	/**
	 * @test
	 */
	public function isPermissionCorrectReturnsTrueOnWindowsOs() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode', array('isWindowsOs'), array(), '', FALSE);
		$node->expects($this->once())->method('isWindowsOs')->will($this->returnValue(TRUE));
		$this->assertTrue($node->_call('isPermissionCorrect'));
	}

	/**
	 * @test
	 */
	public function isPermissionCorrectReturnsFalseIfTargetPermissionAndCurrentPermissionAreNotIdentical() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode', array('isWindowsOs', 'getCurrentPermission'), array(), '', FALSE);
		$node->expects($this->any())->method('isWindowsOs')->will($this->returnValue(FALSE));
		$node->expects($this->any())->method('getCurrentPermission')->will($this->returnValue('foo'));
		$node->_set('targetPermission', 'bar');
		$this->assertFalse($node->_call('isPermissionCorrect'));
	}

	/**
	 * @test
	 */
	public function getCurrentPermissionReturnsCurrentDirectoryPermission() {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test not available on Windows OS.');
		}
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode', array('getAbsolutePath'), array(), '', FALSE);
		$path = PATH_site . 'typo3temp/' . uniqid('dir_');
		\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($path);
		$this->testNodesToDelete[] = $path;
		chmod($path, 02775);
		clearstatcache();
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$this->assertSame('2775', $node->_call('getCurrentPermission'));
	}

	/**
	 * @test
	 */
	public function getCurrentPermissionReturnsCurrentFilePermission() {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test not available on Windows OS.');
		}
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode', array('getAbsolutePath'), array(), '', FALSE);
		$file = PATH_site . 'typo3temp/' . uniqid('file_');
		touch($file);
		$this->testNodesToDelete[] = $file;
		chmod($file, 0770);
		clearstatcache();
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($file));
		$this->assertSame('0770', $node->_call('getCurrentPermission'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
	 */
	public function getRelativePathBelowSiteRootThrowsExceptionIfGivenPathIsNotBelowPathSiteConstant() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode', array('dummy'), array(), '', FALSE);
		$node->_call('getRelativePathBelowSiteRoot', '/tmp');
	}

	/**
	 * @test
	 */
	public function getRelativePathCallsGetAbsolutePathIfPathIsNull() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode',
			array('getAbsolutePath'),
			array(),
			'',
			FALSE
		);
		$node->expects($this->once())->method('getAbsolutePath')->will($this->returnValue(PATH_site));
		$node->_call('getRelativePathBelowSiteRoot', NULL);
	}

	/**
	 * @test
	 */
	public function getRelativePathBelowSiteRootReturnsSingleForwardSlashIfGivenPathEqualsPathSiteConstant() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode', array('dummy'), array(), '', FALSE);
		$result = $node->_call('getRelativePathBelowSiteRoot', PATH_site);
		$this->assertSame('/', $result);
	}

	/**
	 * @test
	 */
	public function getRelativePathBelowSiteRootReturnsSubPath() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\AbstractNode', array('dummy'), array(), '', FALSE);
		$result = $node->_call('getRelativePathBelowSiteRoot', PATH_site . 'foo/bar');
		$this->assertSame('/foo/bar', $result);
	}

}
