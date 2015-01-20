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
class FileNodeTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

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
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('dummy'), array(), '', FALSE);
		$node->__construct(array(), NULL);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
	 */
	public function constructorThrowsExceptionIfNameContainsForwardSlash() {
		$parent = $this->getMock('TYPO3\CMS\Install\FolderStructure\NodeInterface', array(), array(), '', FALSE);
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('dummy'), array(), '', FALSE);
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
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('dummy'), array(), '', FALSE);
		$structure = array(
			'name' => 'foo',
		);
		$node->__construct($structure, $parent);
		$this->assertSame($parent, $node->_call('getParent'));
	}

	/**
	 * @test
	 */
	public function constructorSetsTargetPermission() {
		$parent = $this->getMock('TYPO3\CMS\Install\FolderStructure\NodeInterface', array(), array(), '', FALSE);
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('dummy'), array(), '', FALSE);
		$targetPermission = '0660';
		$structure = array(
			'name' => 'foo',
			'targetPermission' => $targetPermission,
		);
		$node->__construct($structure, $parent);
		$this->assertSame($targetPermission, $node->_call('getTargetPermission'));
	}

	/**
	 * @test
	 */
	public function constructorSetsName() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('dummy'), array(), '', FALSE);
		$parent = $this->getMock('TYPO3\CMS\Install\FolderStructure\RootNodeInterface', array(), array(), '', FALSE);
		$name = $this->getUniqueId('test_');
		$node->__construct(array('name' => $name), $parent);
		$this->assertSame($name, $node->getName());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
	 */
	public function constructorThrowsExceptionIfBothTargetContentAndTargetContentFileAreSet() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('dummy'), array(), '', FALSE);
		$parent = $this->getMock('TYPO3\CMS\Install\FolderStructure\RootNodeInterface', array(), array(), '', FALSE);
		$structure = array(
			'name' => 'foo',
			'targetContent' => 'foo',
			'targetContentFile' => 'aPath',
		);
		$node->__construct($structure, $parent);
	}

	/**
	 * @test
	 */
	public function constructorSetsTargetContent() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('dummy'), array(), '', FALSE);
		$parent = $this->getMock('TYPO3\CMS\Install\FolderStructure\RootNodeInterface', array(), array(), '', FALSE);
		$targetContent = $this->getUniqueId('content_');
		$structure = array(
			'name' => 'foo',
			'targetContent' => $targetContent,
		);
		$node->__construct($structure, $parent);
		$this->assertSame($targetContent, $node->_get('targetContent'));
	}

	/**
	 * @test
	 */
	public function constructorSetsTargetContentToContentOfTargetContentFile() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('dummy'), array(), '', FALSE);
		$parent = $this->getMock('TYPO3\CMS\Install\FolderStructure\RootNodeInterface', array(), array(), '', FALSE);
		$targetFile = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
		$targetContent = $this->getUniqueId('content_');
		file_put_contents($targetFile, $targetContent);
		$this->testNodesToDelete[] = $targetFile;
		$structure = array(
			'name' => 'foo',
			'targetContentFile' => $targetFile,
		);
		$node->__construct($structure, $parent);
		$this->assertSame($targetContent, $node->_get('targetContent'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
	 */
	public function constructorThrowsExceptionIfTargetContentFileDoesNotExist() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('dummy'), array(), '', FALSE);
		$parent = $this->getMock('TYPO3\CMS\Install\FolderStructure\RootNodeInterface', array(), array(), '', FALSE);
		$targetFile = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
		$structure = array(
			'name' => 'foo',
			'targetContentFile' => $targetFile,
		);
		$node->__construct($structure, $parent);
	}

	/**
	 * @test
	 */
	public function targetContentIsNullIfNotGiven() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('dummy'), array(), '', FALSE);
		$parent = $this->getMock('TYPO3\CMS\Install\FolderStructure\RootNodeInterface', array(), array(), '', FALSE);
		$structure = array(
			'name' => 'foo',
		);
		$node->__construct($structure, $parent);
		$this->assertNull($node->_get('targetContent'));
	}

	/**
	 * @test
	 */
	public function getStatusReturnsArray() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\FileNode',
			array('getAbsolutePath', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'),
			array(),
			'',
			FALSE
		);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('dir_');
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isFile')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isWritable')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isContentCorrect')->will($this->returnValue(TRUE));
		$this->assertInternalType('array', $node->getStatus());
	}

	/**
	 * @test
	 */
	public function getStatusReturnsArrayWithWarningStatusIFileNotExists() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\FileNode',
			array('getAbsolutePath', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'),
			array(),
			'',
			FALSE
		);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('dir_');
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->expects($this->any())->method('exists')->will($this->returnValue(FALSE));
		$node->expects($this->any())->method('isFile')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isWritable')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isContentCorrect')->will($this->returnValue(TRUE));
		$statusArray = $node->getStatus();
		/** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
		$status = $statusArray[0];
		$this->assertInstanceOf('\TYPO3\CMS\Install\Status\WarningStatus', $status);
	}

	/**
	 * @test
	 */
	public function getStatusReturnsArrayWithErrorStatusIfNodeIsNotAFile() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\FileNode',
			array('getAbsolutePath', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'),
			array(),
			'',
			FALSE
		);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('dir_');
		touch($path);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isFile')->will($this->returnValue(FALSE));
		$node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isWritable')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isContentCorrect')->will($this->returnValue(TRUE));
		$statusArray = $node->getStatus();
		/** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
		$status = $statusArray[0];
		$this->assertInstanceOf('\TYPO3\CMS\Install\Status\ErrorStatus', $status);
	}

	/**
	 * @test
	 */
	public function getStatusReturnsArrayNoticeStatusIfFileExistsButIsNotWritable() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\FileNode',
			array('getAbsolutePath', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'),
			array(),
			'',
			FALSE
		);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('dir_');
		touch($path);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isFile')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isWritable')->will($this->returnValue(FALSE));
		$node->expects($this->any())->method('isContentCorrect')->will($this->returnValue(TRUE));
		$statusArray = $node->getStatus();
		/** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
		$status = $statusArray[0];
		$this->assertInstanceOf('\TYPO3\CMS\Install\Status\NoticeStatus', $status);
	}

	/**
	 * @test
	 */
	public function getStatusReturnsArrayWithNoticeStatusIfFileExistsButPermissionAreNotCorrect() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\FileNode',
			array('getAbsolutePath', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'),
			array(),
			'',
			FALSE
		);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('dir_');
		touch ($path);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isFile')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(FALSE));
		$node->expects($this->any())->method('isWritable')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isContentCorrect')->will($this->returnValue(TRUE));
		$statusArray = $node->getStatus();
		/** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
		$status = $statusArray[0];
		$this->assertInstanceOf('\TYPO3\CMS\Install\Status\NoticeStatus', $status);
	}

	/**
	 * @test
	 */
	public function getStatusReturnsArrayWithNoticeStatusIfFileExistsButContentIsNotCorrect() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\FileNode',
			array('getAbsolutePath', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'),
			array(),
			'',
			FALSE
		);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('dir_');
		touch($path);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isFile')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isWritable')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isContentCorrect')->will($this->returnValue(FALSE));
		$statusArray = $node->getStatus();
		/** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
		$status = $statusArray[0];
		$this->assertInstanceOf('\TYPO3\CMS\Install\Status\NoticeStatus', $status);
	}

	/**
	 * @test
	 */
	public function getStatusReturnsArrayWithOkStatusIfFileExistsAndPermissionAreCorrect() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\FileNode',
			array('getAbsolutePath', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'),
			array(),
			'',
			FALSE
		);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('dir_');
		touch($path);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isFile')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isWritable')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isContentCorrect')->will($this->returnValue(TRUE));
		$statusArray = $node->getStatus();
		/** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
		$status = $statusArray[0];
		$this->assertInstanceOf('\TYPO3\CMS\Install\Status\OkStatus', $status);
	}

	/**
	 * @test
	 */
	public function fixCallsFixSelfAndReturnsItsResult() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\FileNode',
			array('fixSelf'),
			array(),
			'',
			FALSE
		);
		$uniqueReturn = array($this->getUniqueId('foo_'));
		$node->expects($this->once())->method('fixSelf')->will($this->returnValue($uniqueReturn));
		$this->assertSame($uniqueReturn, $node->fix());
	}

	/**
	 * @test
	 */
	public function fixSelfCallsCreateFileIfFileDoesNotExistAndReturnsResult() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\FileNode',
			array('exists', 'createFile', 'setContent', 'getAbsolutePath', 'isFile', 'isPermissionCorrect'),
			array(),
			'',
			FALSE
		);
		$node->expects($this->any())->method('exists')->will($this->returnValue(FALSE));
		$node->expects($this->any())->method('isFile')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(TRUE));
		$uniqueReturn = $this->getUniqueId();
		$node->expects($this->once())->method('createFile')->will($this->returnValue($uniqueReturn));
		$actualReturn = $node->_call('fixSelf');
		$actualReturn = $actualReturn[0];
		$this->assertSame($uniqueReturn, $actualReturn);
	}

	/**
	 * @test
	 */
	public function fixSelfCallsSetsContentIfFileCreationWasSuccessfulAndTargetContentIsNotNullAndReturnsResult() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\FileNode',
			array('exists', 'createFile', 'setContent', 'getAbsolutePath', 'isFile', 'isPermissionCorrect'),
			array(),
			'',
			FALSE
		);
		$node->expects($this->any())->method('exists')->will($this->returnValue(FALSE));
		$node->expects($this->any())->method('isFile')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(TRUE));
		$uniqueReturn = $this->getUniqueId();
		$createFileStatus = $this->getMock('TYPO3\\CMS\\Install\\Status\\OkStatus', array(), array(), '', FALSE);
		$node->expects($this->any())->method('createFile')->will($this->returnValue($createFileStatus));
		$node->_set('targetContent', 'foo');
		$node->expects($this->once())->method('setContent')->will($this->returnValue($uniqueReturn));
		$actualReturn = $node->_call('fixSelf');
		$actualReturn = $actualReturn[1];
		$this->assertSame($uniqueReturn, $actualReturn);
	}

	/**
	 * @test
	 */
	public function fixSelfDoesNotCallSetContentIfFileCreationFailed() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\FileNode',
			array('exists', 'createFile', 'setContent', 'getAbsolutePath', 'isFile', 'isPermissionCorrect'),
			array(),
			'',
			FALSE
		);
		$node->expects($this->any())->method('exists')->will($this->returnValue(FALSE));
		$node->expects($this->any())->method('isFile')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(TRUE));
		$createFileStatus = $this->getMock('TYPO3\\CMS\\Install\\Status\\ErrorStatus', array(), array(), '', FALSE);
		$node->expects($this->any())->method('createFile')->will($this->returnValue($createFileStatus));
		$node->_set('targetContent', 'foo');
		$node->expects($this->never())->method('setContent');
		$node->_call('fixSelf');
	}

	/**
	 * @test
	 */
	public function fixSelfDoesNotCallSetContentIfFileTargetContentIsNull() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\FileNode',
			array('exists', 'createFile', 'setContent', 'getAbsolutePath', 'isFile', 'isPermissionCorrect'),
			array(),
			'',
			FALSE
		);
		$node->expects($this->any())->method('exists')->will($this->returnValue(FALSE));
		$node->expects($this->any())->method('isFile')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(TRUE));
		$createFileStatus = $this->getMock('TYPO3\\CMS\\Install\\Status\\OkStatus', array(), array(), '', FALSE);
		$node->expects($this->any())->method('createFile')->will($this->returnValue($createFileStatus));
		$node->_set('targetContent', NULL);
		$node->expects($this->never())->method('setContent');
		$node->_call('fixSelf');
	}

	/**
	 * @test
	 */
	public function fixSelfReturnsErrorStatusIfNodeExistsButIsNotAFileAndReturnsResult() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\FileNode',
			array('exists', 'createFile', 'getAbsolutePath', 'isFile', 'isPermissionCorrect', 'fixPermission'),
			array(),
			'',
			FALSE
		);
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isFile')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(FALSE));
		$uniqueReturn = $this->getUniqueId();
		$node->expects($this->once())->method('fixPermission')->will($this->returnValue($uniqueReturn));
		$this->assertSame(array($uniqueReturn), $node->_call('fixSelf'));
	}

	/**
	 * @test
	 */
	public function fixSelfCallsFixPermissionIfFileExistsButPermissionAreWrong() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\FileNode',
			array('exists', 'createFile', 'getAbsolutePath', 'isFile', 'isPermissionCorrect', 'getRelativePathBelowSiteRoot'),
			array(),
			'',
			FALSE
		);
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->once())->method('isFile')->will($this->returnValue(FALSE));
		$node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(TRUE));
		$resultArray = $node->_call('fixSelf');
		$this->assertInstanceOf('TYPO3\\CMS\Install\\Status\\StatusInterface', $resultArray[0]);
	}

	/**
	 * @test
	 */
	public function fixSelfReturnsArrayOfStatusMessages() {
		$node = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\FolderStructure\\FileNode',
			array('exists', 'isFile', 'isPermissionCorrect'),
			array(),
			'',
			FALSE
		);
		$node->expects($this->any())->method('exists')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isFile')->will($this->returnValue(TRUE));
		$node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(TRUE));
		$this->assertInternalType('array', $node->_call('fixSelf'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception
	 */
	public function createFileThrowsExceptionIfNodeExists() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('exists', 'getAbsolutePath'), array(), '', FALSE);
		$node->expects($this->once())->method('getAbsolutePath')->will($this->returnValue(''));
		$node->expects($this->once())->method('exists')->will($this->returnValue(TRUE));
		$node->_call('createFile');
	}

	/**
	 * @test
	 */
	public function createFileReturnsOkStatusIfFileWasCreated() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('exists', 'getAbsolutePath'), array(), '', FALSE);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('file_');
		$this->testNodesToDelete[] = $path;
		$node->expects($this->once())->method('exists')->will($this->returnValue(FALSE));
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$this->assertInstanceOf('TYPO3\\CMS\Install\\Status\\StatusInterface', $node->_call('createFile'));
	}

	/**
	 * @test
	 */
	public function createFileCreatesFile() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('exists', 'getAbsolutePath'), array(), '', FALSE);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('file_');
		$this->testNodesToDelete[] = $path;
		$node->expects($this->once())->method('exists')->will($this->returnValue(FALSE));
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->_call('createFile');
		$this->assertTrue(is_file($path));
	}

	/**
	 * @test
	 */
	public function createFileReturnsErrorStatusIfFileWasNotCreated() {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test not available on Windows OS.');
		}
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('exists', 'getAbsolutePath'), array(), '', FALSE);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('root_');
		mkdir($path);
		chmod($path, 02550);
		$subPath = $path . '/' . $this->getUniqueId('file_');
		$this->testNodesToDelete[] = $path;
		$node->expects($this->once())->method('exists')->will($this->returnValue(FALSE));
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($subPath));
		$this->assertInstanceOf('TYPO3\\CMS\Install\\Status\\StatusInterface', $node->_call('createFile'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception
	 */
	public function isContentCorrectThrowsExceptionIfTargetIsNotAFile() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('getAbsolutePath'), array(), '', FALSE);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('dir_');
		mkdir($path);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->_call('isContentCorrect');
	}

	/**
	 * @test
	 */
	public function isContentCorrectReturnsTrueIfTargetContentPropertyIsNull() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('getAbsolutePath'), array(), '', FALSE);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('file_');
		touch($path);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->_set('targetContent', NULL);
		$this->assertTrue($node->_call('isContentCorrect'));
	}

	/**
	 * @test
	 */
	public function isContentCorrectReturnsTrueIfTargetContentEqualsCurrentContent() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('getAbsolutePath'), array(), '', FALSE);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('file_');
		$content = $this->getUniqueId('content_');
		file_put_contents($path, $content);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->_set('targetContent', $content);
		$this->assertTrue($node->_call('isContentCorrect'));
	}

	/**
	 * @test
	 */
	public function isContentCorrectReturnsFalseIfTargetContentNotEqualsCurrentContent() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('getAbsolutePath'), array(), '', FALSE);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('file_');
		$content = $this->getUniqueId('content1_');
		$targetContent = $this->getUniqueId('content2_');
		file_put_contents($path, $content);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->_set('targetContent', $targetContent);
		$this->assertFalse($node->_call('isContentCorrect'));
	}

	/**
	 * @test
	 */
	public function isPermissionCorrectReturnsTrueIfTargetPermissionAndCurrentPermissionAreIdentical() {
		$parent = $this->getMock('TYPO3\CMS\Install\FolderStructure\NodeInterface', array(), array(), '', FALSE);
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('getCurrentPermission', 'isWindowsOs'), array(), '', FALSE);
		$node->expects($this->any())->method('isWindowsOs')->will($this->returnValue(FALSE));
		$node->expects($this->any())->method('getCurrentPermission')->will($this->returnValue('0664'));
		$targetPermission = '0664';
		$structure = array(
			'name' => 'foo',
			'targetPermission' => $targetPermission,
		);
		$node->__construct($structure, $parent);
		$this->assertTrue($node->_call('isPermissionCorrect'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception
	 */
	public function setContentThrowsExceptionIfTargetIsNotAFile() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('getAbsolutePath'), array(), '', FALSE);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('dir_');
		mkdir($path);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->_set('targetContent', 'foo');
		$node->_call('setContent');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception
	 */
	public function setContentThrowsExceptionIfTargetContentIsNull() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('getAbsolutePath'), array(), '', FALSE);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('file_');
		touch($path);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$node->_set('targetContent', NULL);
		$node->_call('setContent');
	}

	/**
	 * @test
	 */
	public function setContentSetsContentToFile() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('getAbsolutePath', 'getRelativePathBelowSiteRoot'), array(), '', FALSE);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('file_');
		touch($path);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$targetContent = $this->getUniqueId('content_');
		$node->_set('targetContent', $targetContent);
		$node->_call('setContent');
		$resultContent = file_get_contents($path);
		$this->assertSame($targetContent, $resultContent);
	}

	/**
	 * @test
	 */
	public function setContentReturnsOkStatusIfContentWasSuccessfullySet() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('getAbsolutePath', 'getRelativePathBelowSiteRoot'), array(), '', FALSE);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('file_');
		touch($path);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$targetContent = $this->getUniqueId('content_');
		$node->_set('targetContent', $targetContent);
		$this->assertInstanceOf('TYPO3\\CMS\\Install\\Status\\OkStatus', $node->_call('setContent'));
	}

	/**
	 * @test
	 */
	public function setContentReturnsErrorStatusIfContentCanNotBeSetSet() {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test not available on Windows OS.');
		}
		if (function_exists('posix_getegid') && posix_getegid() === 0) {
			$this->markTestSkipped('Test skipped if run on linux as root');
		}
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('getAbsolutePath', 'getRelativePathBelowSiteRoot'), array(), '', FALSE);
		$dir = PATH_site . 'typo3temp/' . $this->getUniqueId('dir_');
		mkdir($dir);
		$file = $dir . '/' . $this->getUniqueId('file_');
		touch($file);
		chmod($file, 0440);
		$this->testNodesToDelete[] = $dir;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($file));
		$targetContent = $this->getUniqueId('content_');
		$node->_set('targetContent', $targetContent);
		$this->assertInstanceOf('TYPO3\\CMS\\Install\\Status\\ErrorStatus', $node->_call('setContent'));
	}

	/**
	 * @test
	 */
	public function isFileReturnsTrueIfNameIsFile() {
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('getAbsolutePath'), array(), '', FALSE);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('file_');
		touch($path);
		$this->testNodesToDelete[] = $path;
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
		$this->assertTrue($node->_call('isFile'));
	}

	/**
	 * @test
	 */
	public function isFileReturnsFalseIfNameIsALinkFile() {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test not available on Windows OS.');
		}
		/** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$node = $this->getAccessibleMock('TYPO3\\CMS\\Install\\FolderStructure\\FileNode', array('getAbsolutePath'), array(), '', FALSE);
		$path = PATH_site . 'typo3temp/' . $this->getUniqueId('root_');
		\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($path);
		$this->testNodesToDelete[] = $path;
		$link = $this->getUniqueId('link_');
		$file = $this->getUniqueId('file_');
		touch($path . '/' . $file);
		symlink($path . '/' . $file, $path . '/' . $link);
		$node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path . '/' . $link));
		$this->assertFalse($node->_call('isFile'));
	}
}
