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
use TYPO3\CMS\Install\FolderStructure\Exception;
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;

/**
 * Test case
 */
class AbstractNodeTest extends \TYPO3\CMS\Install\Tests\Unit\FolderStructureTestCase
{
    /**
     * @test
     */
    public function getNameReturnsSetName()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\AbstractNode::class, array('dummy'), array(), '', false);
        $name = $this->getUniqueId('name_');
        $node->_set('name', $name);
        $this->assertSame($name, $node->getName());
    }

    /**
     * @test
     */
    public function getTargetPermissionReturnsSetTargetPermission()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\AbstractNode::class, array('dummy'), array(), '', false);
        $permission = '1234';
        $node->_set('targetPermission', $permission);
        $this->assertSame($permission, $node->_call('getTargetPermission'));
    }

    /**
     * @test
     */
    public function getChildrenReturnsSetChildren()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\AbstractNode::class, array('dummy'), array(), '', false);
        $children = array('1234');
        $node->_set('children', $children);
        $this->assertSame($children, $node->_call('getChildren'));
    }

    /**
     * @test
     */
    public function getParentReturnsSetParent()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\AbstractNode::class, array('dummy'), array(), '', false);
        $parent = $this->createMock(\TYPO3\CMS\Install\FolderStructure\RootNodeInterface::class);
        $node->_set('parent', $parent);
        $this->assertSame($parent, $node->_call('getParent'));
    }

    /**
     * @test
     */
    public function getAbsolutePathCallsParentForPathAndAppendsOwnName()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\AbstractNode::class, array('dummy'), array(), '', false);
        $parent = $this->createMock(\TYPO3\CMS\Install\FolderStructure\RootNodeInterface::class);
        $parentPath = '/foo/bar';
        $parent->expects($this->once())->method('getAbsolutePath')->will($this->returnValue($parentPath));
        $name = $this->getUniqueId('test_');
        $node->_set('parent', $parent);
        $node->_set('name', $name);
        $this->assertSame($parentPath . '/' . $name, $node->getAbsolutePath());
    }

    /**
     * @test
     */
    public function isWritableCallsParentIsWritable()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\AbstractNode::class, array('dummy'), array(), '', false);
        $parentMock = $this->createMock(\TYPO3\CMS\Install\FolderStructure\NodeInterface::class);
        $parentMock->expects($this->once())->method('isWritable');
        $node->_set('parent', $parentMock);
        $node->isWritable();
    }

    /**
     * @test
     */
    public function isWritableReturnsWritableStatusOfParent()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\AbstractNode::class, array('dummy'), array(), '', false);
        $parentMock = $this->createMock(\TYPO3\CMS\Install\FolderStructure\NodeInterface::class);
        $parentMock->expects($this->once())->method('isWritable')->will($this->returnValue(true));
        $node->_set('parent', $parentMock);
        $this->assertTrue($node->isWritable());
    }

    /**
     * @test
     */
    public function existsReturnsTrueIfNodeExists()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\AbstractNode::class, array('getAbsolutePath'), array(), '', false);
        $path = $this->getVirtualTestDir('dir_');
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $this->assertTrue($node->_call('exists'));
    }

    /**
     * @test
     * @see https://github.com/mikey179/vfsStream/wiki/Known-Issues - symlink doesn't work with vfsStream
     */
    public function existsReturnsTrueIfIsLinkAndTargetIsDead()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\AbstractNode::class, array('getAbsolutePath'), array(), '', false);
        $path = PATH_site . 'typo3temp/var/tests/' . $this->getUniqueId('link_');
        $target = PATH_site . 'typo3temp/var/tests/' . $this->getUniqueId('notExists_');
        touch($target);
        symlink($target, $path);
        unlink($target);
        $this->testFilesToDelete[] = $path;
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $this->assertTrue($node->_call('exists'));
    }

    /**
     * @test
     */
    public function existsReturnsFalseIfNodeNotExists()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\AbstractNode::class, array('getAbsolutePath'), array(), '', false);
        $path = $this->getVirtualTestFilePath('dir_');
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $this->assertFalse($node->_call('exists'));
    }

    /**
     * @test
     */
    public function fixPermissionThrowsExceptionIfPermissionAreAlreadyCorrect()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\AbstractNode::class,
            array('isPermissionCorrect', 'getAbsolutePath'),
            array(),
            '',
            false
        );
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1366744035);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue(''));
        $node->expects($this->once())->method('isPermissionCorrect')->will($this->returnValue(true));
        $node->_call('fixPermission');
    }

    /**
     * @test
     */
    public function fixPermissionReturnsNoticeStatusIfPermissionCanNotBeChanged()
    {
        if (function_exists('posix_getegid') && posix_getegid() === 0) {
            $this->markTestSkipped('Test skipped if run on linux as root');
        }
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\AbstractNode::class,
            array('isPermissionCorrect', 'getRelativePathBelowSiteRoot', 'getAbsolutePath'),
            array(),
            '',
            false
        );
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue(''));
        $node->expects($this->once())->method('isPermissionCorrect')->will($this->returnValue(false));
        $path = $this->getVirtualTestDir('root_');
        $subPath = $path . '/' . $this->getUniqueId('dir_');
        mkdir($subPath);
        chmod($path, 02000);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($subPath));
        $node->_set('targetPermission', '2770');
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\NoticeStatus::class, $node->_call('fixPermission'));
        chmod($path, 02770);
    }

    /**
     * @test
     */
    public function fixPermissionReturnsNoticeStatusIfPermissionsCanNotBeChanged()
    {
        if (function_exists('posix_getegid') && posix_getegid() === 0) {
            $this->markTestSkipped('Test skipped if run on linux as root');
        }
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\AbstractNode::class,
            array('isPermissionCorrect', 'getRelativePathBelowSiteRoot', 'getAbsolutePath'),
            array(),
            '',
            false
        );
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue(''));
        $node->expects($this->once())->method('isPermissionCorrect')->will($this->returnValue(false));
        $path = $this->getVirtualTestDir('root_');
        $subPath = $path . '/' . $this->getUniqueId('dir_');
        mkdir($subPath);
        chmod($path, 02000);
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($subPath));
        $node->_set('targetPermission', '2770');
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\NoticeStatus::class, $node->_call('fixPermission'));
        chmod($path, 02770);
    }

    /**
     * @test
     */
    public function fixPermissionReturnsOkStatusIfPermissionCanBeFixedAndSetsPermissionToCorrectValue()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\AbstractNode::class,
            array('isPermissionCorrect', 'getRelativePathBelowSiteRoot', 'getAbsolutePath'),
            array(),
            '',
            false
        );
        $node->expects($this->any())->method('getRelativePathBelowSiteRoot')->will($this->returnValue(''));
        $node->expects($this->once())->method('isPermissionCorrect')->will($this->returnValue(false));
        $path = $this->getVirtualTestDir('root_');
        $subPath = $path . '/' . $this->getUniqueId('dir_');
        mkdir($subPath);
        chmod($path, 02770);
        $node->_set('targetPermission', '2770');
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($subPath));
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\OkStatus::class, $node->_call('fixPermission'));
        $resultDirectoryPermissions = substr(decoct(fileperms($subPath)), 1);
        $this->assertSame('2770', $resultDirectoryPermissions);
    }

    /**
     * @test
     */
    public function isPermissionCorrectReturnsTrueOnWindowsOs()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\AbstractNode::class, array('isWindowsOs'), array(), '', false);
        $node->expects($this->once())->method('isWindowsOs')->will($this->returnValue(true));
        $this->assertTrue($node->_call('isPermissionCorrect'));
    }

    /**
     * @test
     */
    public function isPermissionCorrectReturnsFalseIfTargetPermissionAndCurrentPermissionAreNotIdentical()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\AbstractNode::class, array('isWindowsOs', 'getCurrentPermission'), array(), '', false);
        $node->expects($this->any())->method('isWindowsOs')->will($this->returnValue(false));
        $node->expects($this->any())->method('getCurrentPermission')->will($this->returnValue('foo'));
        $node->_set('targetPermission', 'bar');
        $this->assertFalse($node->_call('isPermissionCorrect'));
    }

    /**
     * @test
     */
    public function getCurrentPermissionReturnsCurrentDirectoryPermission()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\AbstractNode::class, array('getAbsolutePath'), array(), '', false);
        $path = $this->getVirtualTestDir('dir_');
        chmod($path, 02775);
        clearstatcache();
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $this->assertSame('2775', $node->_call('getCurrentPermission'));
    }

    /**
     * @test
     */
    public function getCurrentPermissionReturnsCurrentFilePermission()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\AbstractNode::class, array('getAbsolutePath'), array(), '', false);
        $file = $this->getVirtualTestFilePath('file_');
        touch($file);
        chmod($file, 0770);
        clearstatcache();
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($file));
        $this->assertSame('0770', $node->_call('getCurrentPermission'));
    }

    /**
     * @test
     */
    public function getRelativePathBelowSiteRootThrowsExceptionIfGivenPathIsNotBelowPathSiteConstant()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366398198);
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\AbstractNode::class, array('dummy'), array(), '', false);
        $node->_call('getRelativePathBelowSiteRoot', '/tmp');
    }

    /**
     * @test
     */
    public function getRelativePathCallsGetAbsolutePathIfPathIsNull()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\AbstractNode::class,
            array('getAbsolutePath'),
            array(),
            '',
            false
        );
        $node->expects($this->once())->method('getAbsolutePath')->will($this->returnValue(PATH_site));
        $node->_call('getRelativePathBelowSiteRoot', null);
    }

    /**
     * @test
     */
    public function getRelativePathBelowSiteRootReturnsSingleForwardSlashIfGivenPathEqualsPathSiteConstant()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\AbstractNode::class, array('dummy'), array(), '', false);
        $result = $node->_call('getRelativePathBelowSiteRoot', PATH_site);
        $this->assertSame('/', $result);
    }

    /**
     * @test
     */
    public function getRelativePathBelowSiteRootReturnsSubPath()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\AbstractNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\AbstractNode::class, array('dummy'), array(), '', false);
        $result = $node->_call('getRelativePathBelowSiteRoot', PATH_site . 'foo/bar');
        $this->assertSame('/foo/bar', $result);
    }
}
