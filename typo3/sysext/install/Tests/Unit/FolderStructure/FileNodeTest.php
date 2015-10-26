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

/**
 * Test case
 */
class FileNodeTest extends \TYPO3\CMS\Install\Tests\Unit\FolderStructureTestCase
{
    /**
     * @test
     * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
     */
    public function constructorThrowsExceptionIfParentIsNull()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('dummy'), array(), '', false);
        $node->__construct(array(), null);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
     */
    public function constructorThrowsExceptionIfNameContainsForwardSlash()
    {
        $parent = $this->getMock(\TYPO3\CMS\Install\FolderStructure\NodeInterface::class, array(), array(), '', false);
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('dummy'), array(), '', false);
        $structure = array(
            'name' => 'foo/bar',
        );
        $node->__construct($structure, $parent);
    }

    /**
     * @test
     */
    public function constructorSetsParent()
    {
        $parent = $this->getMock(\TYPO3\CMS\Install\FolderStructure\NodeInterface::class, array(), array(), '', false);
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('dummy'), array(), '', false);
        $structure = array(
            'name' => 'foo',
        );
        $node->__construct($structure, $parent);
        $this->assertSame($parent, $node->_call('getParent'));
    }

    /**
     * @test
     */
    public function constructorSetsTargetPermission()
    {
        $parent = $this->getMock(\TYPO3\CMS\Install\FolderStructure\NodeInterface::class, array(), array(), '', false);
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('dummy'), array(), '', false);
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
    public function constructorSetsName()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('dummy'), array(), '', false);
        $parent = $this->getMock(\TYPO3\CMS\Install\FolderStructure\RootNodeInterface::class, array(), array(), '', false);
        $name = $this->getUniqueId('test_');
        $node->__construct(array('name' => $name), $parent);
        $this->assertSame($name, $node->getName());
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException
     */
    public function constructorThrowsExceptionIfBothTargetContentAndTargetContentFileAreSet()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('dummy'), array(), '', false);
        $parent = $this->getMock(\TYPO3\CMS\Install\FolderStructure\RootNodeInterface::class, array(), array(), '', false);
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
    public function constructorSetsTargetContent()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('dummy'), array(), '', false);
        $parent = $this->getMock(\TYPO3\CMS\Install\FolderStructure\RootNodeInterface::class, array(), array(), '', false);
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
    public function constructorSetsTargetContentToContentOfTargetContentFile()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('dummy'), array(), '', false);
        $parent = $this->getMock(\TYPO3\CMS\Install\FolderStructure\RootNodeInterface::class, array(), array(), '', false);
        $targetFile = $this->getVirtualTestFilePath('test_');
        $targetContent = $this->getUniqueId('content_');
        file_put_contents($targetFile, $targetContent);
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
    public function constructorThrowsExceptionIfTargetContentFileDoesNotExist()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('dummy'), array(), '', false);
        $parent = $this->getMock(\TYPO3\CMS\Install\FolderStructure\RootNodeInterface::class, array(), array(), '', false);
        $targetFile = $this->getVirtualTestFilePath('test_');
        $structure = array(
            'name' => 'foo',
            'targetContentFile' => $targetFile,
        );
        $node->__construct($structure, $parent);
    }

    /**
     * @test
     */
    public function targetContentIsNullIfNotGiven()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('dummy'), array(), '', false);
        $parent = $this->getMock(\TYPO3\CMS\Install\FolderStructure\RootNodeInterface::class, array(), array(), '', false);
        $structure = array(
            'name' => 'foo',
        );
        $node->__construct($structure, $parent);
        $this->assertNull($node->_get('targetContent'));
    }

    /**
     * @test
     */
    public function getStatusReturnsArray()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\FileNode::class,
            array('getAbsolutePath', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'),
            array(),
            '',
            false
        );
        $path = PATH_site . 'typo3temp/' . $this->getUniqueId('dir_');
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isFile')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $node->expects($this->any())->method('isWritable')->will($this->returnValue(true));
        $node->expects($this->any())->method('isContentCorrect')->will($this->returnValue(true));
        $this->assertInternalType('array', $node->getStatus());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithWarningStatusIFileNotExists()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\FileNode::class,
            array('getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'),
            array(),
            '',
            false
        );
        $path = $this->getVirtualTestDir('dir_');
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue($path));
        $node->expects($this->any())->method('exists')->will($this->returnValue(false));
        $node->expects($this->any())->method('isFile')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $node->expects($this->any())->method('isWritable')->will($this->returnValue(true));
        $node->expects($this->any())->method('isContentCorrect')->will($this->returnValue(true));
        $statusArray = $node->getStatus();
        /** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
        $status = $statusArray[0];
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\WarningStatus::class, $status);
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithErrorStatusIfNodeIsNotAFile()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\FileNode::class,
            array('getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'),
            array(),
            '',
            false
        );
        $path = $this->getVirtualTestFilePath('dir_');
        touch($path);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue($path));
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isFile')->will($this->returnValue(false));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $node->expects($this->any())->method('isWritable')->will($this->returnValue(true));
        $node->expects($this->any())->method('isContentCorrect')->will($this->returnValue(true));
        $statusArray = $node->getStatus();
        /** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
        $status = $statusArray[0];
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\ErrorStatus::class, $status);
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayNoticeStatusIfFileExistsButIsNotWritable()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\FileNode::class,
            array('getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'),
            array(),
            '',
            false
        );
        $path = $this->getVirtualTestFilePath('dir_');
        touch($path);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue($path));
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isFile')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $node->expects($this->any())->method('isWritable')->will($this->returnValue(false));
        $node->expects($this->any())->method('isContentCorrect')->will($this->returnValue(true));
        $statusArray = $node->getStatus();
        /** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
        $status = $statusArray[0];
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\NoticeStatus::class, $status);
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithNoticeStatusIfFileExistsButPermissionAreNotCorrect()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\FileNode::class,
            array('getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'),
            array(),
            '',
            false
        );
        $path = $this->getVirtualTestFilePath('dir_');
        touch($path);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue($path));
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isFile')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(false));
        $node->expects($this->any())->method('isWritable')->will($this->returnValue(true));
        $node->expects($this->any())->method('isContentCorrect')->will($this->returnValue(true));
        $statusArray = $node->getStatus();
        /** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
        $status = $statusArray[0];
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\NoticeStatus::class, $status);
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithNoticeStatusIfFileExistsButContentIsNotCorrect()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\FileNode::class,
            array('getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'),
            array(),
            '',
            false
        );
        $path = $this->getVirtualTestFilePath('dir_');
        touch($path);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue($path));
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isFile')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $node->expects($this->any())->method('isWritable')->will($this->returnValue(true));
        $node->expects($this->any())->method('isContentCorrect')->will($this->returnValue(false));
        $statusArray = $node->getStatus();
        /** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
        $status = $statusArray[0];
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\NoticeStatus::class, $status);
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithOkStatusIfFileExistsAndPermissionAreCorrect()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\FileNode::class,
            array('getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isFile', 'isWritable', 'isPermissionCorrect', 'isContentCorrect'),
            array(),
            '',
            false
        );
        $path = $this->getVirtualTestFilePath('dir_');
        touch($path);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue($path));
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isFile')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $node->expects($this->any())->method('isWritable')->will($this->returnValue(true));
        $node->expects($this->any())->method('isContentCorrect')->will($this->returnValue(true));
        $statusArray = $node->getStatus();
        /** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
        $status = $statusArray[0];
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\OkStatus::class, $status);
    }

    /**
     * @test
     */
    public function fixCallsFixSelfAndReturnsItsResult()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\FileNode::class,
            array('fixSelf'),
            array(),
            '',
            false
        );
        $uniqueReturn = array($this->getUniqueId('foo_'));
        $node->expects($this->once())->method('fixSelf')->will($this->returnValue($uniqueReturn));
        $this->assertSame($uniqueReturn, $node->fix());
    }

    /**
     * @test
     */
    public function fixSelfCallsCreateFileIfFileDoesNotExistAndReturnsResult()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\FileNode::class,
            array('exists', 'createFile', 'setContent', 'getAbsolutePath', 'isFile', 'isPermissionCorrect'),
            array(),
            '',
            false
        );
        $node->expects($this->any())->method('exists')->will($this->returnValue(false));
        $node->expects($this->any())->method('isFile')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $uniqueReturn = $this->getUniqueId();
        $node->expects($this->once())->method('createFile')->will($this->returnValue($uniqueReturn));
        $actualReturn = $node->_call('fixSelf');
        $actualReturn = $actualReturn[0];
        $this->assertSame($uniqueReturn, $actualReturn);
    }

    /**
     * @test
     */
    public function fixSelfCallsSetsContentIfFileCreationWasSuccessfulAndTargetContentIsNotNullAndReturnsResult()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\FileNode::class,
            array('exists', 'createFile', 'setContent', 'getAbsolutePath', 'isFile', 'isPermissionCorrect'),
            array(),
            '',
            false
        );
        $node->expects($this->any())->method('exists')->will($this->returnValue(false));
        $node->expects($this->any())->method('isFile')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $uniqueReturn = $this->getUniqueId();
        $createFileStatus = $this->getMock(\TYPO3\CMS\Install\Status\OkStatus::class, array(), array(), '', false);
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
    public function fixSelfDoesNotCallSetContentIfFileCreationFailed()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\FileNode::class,
            array('exists', 'createFile', 'setContent', 'getAbsolutePath', 'isFile', 'isPermissionCorrect'),
            array(),
            '',
            false
        );
        $node->expects($this->any())->method('exists')->will($this->returnValue(false));
        $node->expects($this->any())->method('isFile')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $createFileStatus = $this->getMock(\TYPO3\CMS\Install\Status\ErrorStatus::class, array(), array(), '', false);
        $node->expects($this->any())->method('createFile')->will($this->returnValue($createFileStatus));
        $node->_set('targetContent', 'foo');
        $node->expects($this->never())->method('setContent');
        $node->_call('fixSelf');
    }

    /**
     * @test
     */
    public function fixSelfDoesNotCallSetContentIfFileTargetContentIsNull()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\FileNode::class,
            array('exists', 'createFile', 'setContent', 'getAbsolutePath', 'isFile', 'isPermissionCorrect'),
            array(),
            '',
            false
        );
        $node->expects($this->any())->method('exists')->will($this->returnValue(false));
        $node->expects($this->any())->method('isFile')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $createFileStatus = $this->getMock(\TYPO3\CMS\Install\Status\OkStatus::class, array(), array(), '', false);
        $node->expects($this->any())->method('createFile')->will($this->returnValue($createFileStatus));
        $node->_set('targetContent', null);
        $node->expects($this->never())->method('setContent');
        $node->_call('fixSelf');
    }

    /**
     * @test
     */
    public function fixSelfReturnsErrorStatusIfNodeExistsButIsNotAFileAndReturnsResult()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\FileNode::class,
            array('exists', 'createFile', 'getAbsolutePath', 'isFile', 'isPermissionCorrect', 'fixPermission'),
            array(),
            '',
            false
        );
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isFile')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(false));
        $uniqueReturn = $this->getUniqueId();
        $node->expects($this->once())->method('fixPermission')->will($this->returnValue($uniqueReturn));
        $this->assertSame(array($uniqueReturn), $node->_call('fixSelf'));
    }

    /**
     * @test
     */
    public function fixSelfCallsFixPermissionIfFileExistsButPermissionAreWrong()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\FileNode::class,
            array('exists', 'createFile', 'getAbsolutePath', 'isFile', 'isPermissionCorrect', 'getRelativePathBelowSiteRoot'),
            array(),
            '',
            false
        );
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->once())->method('isFile')->will($this->returnValue(false));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $resultArray = $node->_call('fixSelf');
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\StatusInterface::class, $resultArray[0]);
    }

    /**
     * @test
     */
    public function fixSelfReturnsArrayOfStatusMessages()
    {
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\FileNode::class,
            array('exists', 'isFile', 'isPermissionCorrect'),
            array(),
            '',
            false
        );
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isFile')->will($this->returnValue(true));
        $node->expects($this->any())->method('isPermissionCorrect')->will($this->returnValue(true));
        $this->assertInternalType('array', $node->_call('fixSelf'));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception
     */
    public function createFileThrowsExceptionIfNodeExists()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('exists', 'getAbsolutePath'), array(), '', false);
        $node->expects($this->once())->method('getAbsolutePath')->will($this->returnValue(''));
        $node->expects($this->once())->method('exists')->will($this->returnValue(true));
        $node->_call('createFile');
    }

    /**
     * @test
     */
    public function createFileReturnsOkStatusIfFileWasCreated()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'), array(), '', false);
        $path = $this->getVirtualTestFilePath('file_');
        $node->expects($this->once())->method('exists')->will($this->returnValue(false));
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue($path));
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\StatusInterface::class, $node->_call('createFile'));
    }

    /**
     * @test
     */
    public function createFileCreatesFile()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'), array(), '', false);
        $path = $this->getVirtualTestFilePath('file_');
        $node->expects($this->once())->method('exists')->will($this->returnValue(false));
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue($path));
        $node->_call('createFile');
        $this->assertTrue(is_file($path));
    }

    /**
     * @test
     */
    public function createFileReturnsErrorStatusIfFileWasNotCreated()
    {
        if (TYPO3_OS === 'WIN') {
            $this->markTestSkipped('Test not available on Windows OS.');
        }
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'), array(), '', false);
        $path = $this->getVirtualTestDir();
        chmod($path, 02550);
        $subPath = $path . '/' . $this->getUniqueId('file_');
        $node->expects($this->once())->method('exists')->will($this->returnValue(false));
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($subPath));
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue($subPath));
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\StatusInterface::class, $node->_call('createFile'));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception
     */
    public function isContentCorrectThrowsExceptionIfTargetIsNotAFile()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('getAbsolutePath'), array(), '', false);
        $path = $this->getVirtualTestDir('dir_');
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->_call('isContentCorrect');
    }

    /**
     * @test
     */
    public function isContentCorrectReturnsTrueIfTargetContentPropertyIsNull()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('getAbsolutePath'), array(), '', false);
        $path = $this->getVirtualTestFilePath('file_');
        touch($path);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->_set('targetContent', null);
        $this->assertTrue($node->_call('isContentCorrect'));
    }

    /**
     * @test
     */
    public function isContentCorrectReturnsTrueIfTargetContentEqualsCurrentContent()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('getAbsolutePath'), array(), '', false);
        $path = $this->getVirtualTestFilePath('file_');
        $content = $this->getUniqueId('content_');
        file_put_contents($path, $content);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->_set('targetContent', $content);
        $this->assertTrue($node->_call('isContentCorrect'));
    }

    /**
     * @test
     */
    public function isContentCorrectReturnsFalseIfTargetContentNotEqualsCurrentContent()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('getAbsolutePath'), array(), '', false);
        $path = $this->getVirtualTestFilePath('file_');
        $content = $this->getUniqueId('content1_');
        $targetContent = $this->getUniqueId('content2_');
        file_put_contents($path, $content);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->_set('targetContent', $targetContent);
        $this->assertFalse($node->_call('isContentCorrect'));
    }

    /**
     * @test
     */
    public function isPermissionCorrectReturnsTrueIfTargetPermissionAndCurrentPermissionAreIdentical()
    {
        $parent = $this->getMock(\TYPO3\CMS\Install\FolderStructure\NodeInterface::class, array(), array(), '', false);
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('getCurrentPermission', 'isWindowsOs'), array(), '', false);
        $node->expects($this->any())->method('isWindowsOs')->will($this->returnValue(false));
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
    public function setContentThrowsExceptionIfTargetIsNotAFile()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('getAbsolutePath'), array(), '', false);
        $path = $this->getVirtualTestDir('dir_');
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->_set('targetContent', 'foo');
        $node->_call('setContent');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Install\FolderStructure\Exception
     */
    public function setContentThrowsExceptionIfTargetContentIsNull()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('getAbsolutePath'), array(), '', false);
        $path = $this->getVirtualTestFilePath('file_');
        touch($path);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->_set('targetContent', null);
        $node->_call('setContent');
    }

    /**
     * @test
     */
    public function setContentSetsContentToFile()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('getAbsolutePath', 'getRelativePathBelowSiteRoot'), array(), '', false);
        $path = $this->getVirtualTestFilePath('file_');
        touch($path);
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
    public function setContentReturnsOkStatusIfContentWasSuccessfullySet()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('getAbsolutePath', 'getRelativePathBelowSiteRoot'), array(), '', false);
        $path = $this->getVirtualTestFilePath('file_');
        touch($path);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $targetContent = $this->getUniqueId('content_');
        $node->_set('targetContent', $targetContent);
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\OkStatus::class, $node->_call('setContent'));
    }

    /**
     * @test
     */
    public function setContentReturnsErrorStatusIfContentCanNotBeSetSet()
    {
        if (TYPO3_OS === 'WIN') {
            $this->markTestSkipped('Test not available on Windows OS.');
        }
        if (function_exists('posix_getegid') && posix_getegid() === 0) {
            $this->markTestSkipped('Test skipped if run on linux as root');
        }
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('getAbsolutePath', 'getRelativePathBelowSiteRoot'), array(), '', false);
        $dir = $this->getVirtualTestDir('dir_');
        $file = $dir . '/' . $this->getUniqueId('file_');
        touch($file);
        chmod($file, 0440);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($file));
        $targetContent = $this->getUniqueId('content_');
        $node->_set('targetContent', $targetContent);
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\ErrorStatus::class, $node->_call('setContent'));
    }

    /**
     * @test
     */
    public function isFileReturnsTrueIfNameIsFile()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('getAbsolutePath', 'getRelativePathBelowSiteRoot'), array(), '', false);
        $path = $this->getVirtualTestFilePath('file_');
        touch($path);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $this->assertTrue($node->_call('isFile'));
    }

    /**
     * @test
     * @see https://github.com/mikey179/vfsStream/wiki/Known-Issues - symlink doesn't work with vfsStream
     */
    public function isFileReturnsFalseIfNameIsALinkFile()
    {
        if (TYPO3_OS === 'WIN') {
            $this->markTestSkipped('Test not available on Windows OS.');
        }
        /** @var $node \TYPO3\CMS\Install\FolderStructure\FileNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\FileNode::class, array('getAbsolutePath'), array(), '', false);
        $path = PATH_site . 'typo3temp/' . $this->getUniqueId('root_');
        \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($path);
        $this->testFilesToDelete[] = $path;
        $link = $this->getUniqueId('link_');
        $file = $this->getUniqueId('file_');
        touch($path . '/' . $file);
        symlink($path . '/' . $file, $path . '/' . $link);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path . '/' . $link));
        $this->assertFalse($node->_call('isFile'));
    }
}
