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

/**
 * Test case
 */
class LinkNodeTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function constructorThrowsExceptionIfParentIsNull()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380485700);
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\LinkNode::class, array('dummy'), array(), '', false);
        $node->__construct(array(), null);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfNameContainsForwardSlash()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380546061);
        $parent = $this->createMock(\TYPO3\CMS\Install\FolderStructure\NodeInterface::class);
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\LinkNode::class, array('dummy'), array(), '', false);
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
        $parent = $this->createMock(\TYPO3\CMS\Install\FolderStructure\NodeInterface::class);
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\LinkNode::class, array('dummy'), array(), '', false);
        $structure = array(
            'name' => 'foo',
        );
        $node->__construct($structure, $parent);
        $this->assertSame($parent, $node->_call('getParent'));
    }

    /**
     * @test
     */
    public function constructorSetsName()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\LinkNode::class, array('dummy'), array(), '', false);
        $parent = $this->createMock(\TYPO3\CMS\Install\FolderStructure\RootNodeInterface::class);
        $name = $this->getUniqueId('test_');
        $node->__construct(array('name' => $name), $parent);
        $this->assertSame($name, $node->getName());
    }

    /**
     * @test
     */
    public function constructorSetsTarget()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\LinkNode::class, array('dummy'), array(), '', false);
        $parent = $this->createMock(\TYPO3\CMS\Install\FolderStructure\RootNodeInterface::class);
        $target = '../' . $this->getUniqueId('test_');
        $node->__construct(array('target' => $target), $parent);
        $this->assertSame($target, $node->_call('getTarget'));
    }

    /**
     * @test
     */
    public function getStatusReturnsArray()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\LinkNode::class,
            array('isWindowsOs', 'getAbsolutePath', 'exists'),
            array(),
            '',
            false
        );
        $path = PATH_site . 'typo3temp/var/tests/' . $this->getUniqueId('dir_');
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $this->assertInternalType('array', $node->getStatus());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithInformationStatusIfRunningOnWindows()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\LinkNode::class,
            array('isWindowsOs', 'getAbsolutePath', 'exists'),
            array(),
            '',
            false
        );
        $path = PATH_site . 'typo3temp/var/tests/' . $this->getUniqueId('dir_');
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->once())->method('isWindowsOs')->will($this->returnValue(true));
        $statusArray = $node->getStatus();
        /** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
        $status = $statusArray[0];
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\InfoStatus::class, $status);
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithErrorStatusIfLinkNotExists()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\LinkNode::class,
            array('isWindowsOs', 'getAbsolutePath', 'exists'),
            array(),
            '',
            false
        );
        $path = PATH_site . 'typo3temp/var/tests/' . $this->getUniqueId('dir_');
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('isWindowsOs')->will($this->returnValue(false));
        $node->expects($this->once())->method('exists')->will($this->returnValue(false));
        $statusArray = $node->getStatus();
        /** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
        $status = $statusArray[0];
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\ErrorStatus::class, $status);
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithWarningStatusIfNodeIsNotALink()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\LinkNode::class,
            array('isWindowsOs', 'getAbsolutePath', 'exists', 'isLink', 'getRelativePathBelowSiteRoot'),
            array(),
            '',
            false
        );
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->once())->method('isLink')->will($this->returnValue(false));
        $statusArray = $node->getStatus();
        /** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
        $status = $statusArray[0];
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\WarningStatus::class, $status);
    }

    /**
     * @test
     */
    public function getStatusReturnsErrorStatusIfLinkTargetIsNotCorrect()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\LinkNode::class,
            array('isWindowsOs', 'getAbsolutePath', 'exists', 'isLink', 'isTargetCorrect', 'getCurrentTarget', 'getRelativePathBelowSiteRoot'),
            array(),
            '',
            false
        );
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('getCurrentTarget')->will($this->returnValue(''));
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isLink')->will($this->returnValue(true));
        $node->expects($this->once())->method('isLink')->will($this->returnValue(false));
        $statusArray = $node->getStatus();
        /** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
        $status = $statusArray[0];
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\ErrorStatus::class, $status);
    }

    /**
     * @test
     */
    public function getStatusReturnsOkStatusIfLinkExistsAndTargetIsCorrect()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\LinkNode::class,
            array('isWindowsOs', 'getAbsolutePath', 'exists', 'isLink', 'isTargetCorrect', 'getRelativePathBelowSiteRoot'),
            array(),
            '',
            false
        );
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->once())->method('isLink')->will($this->returnValue(true));
        $node->expects($this->once())->method('isTargetCorrect')->will($this->returnValue(true));
        $statusArray = $node->getStatus();
        /** @var $status \TYPO3\CMS\Install\Status\StatusInterface */
        $status = $statusArray[0];
        $this->assertInstanceOf(\TYPO3\CMS\Install\Status\OkStatus::class, $status);
    }

    /**
     * @test
     */
    public function fixReturnsEmptyArray()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            \TYPO3\CMS\Install\FolderStructure\LinkNode::class,
            array('getRelativePathBelowSiteRoot'),
            array(),
            '',
            false
        );
        $statusArray = $node->fix();
        $this->assertEmpty($statusArray);
    }

    /**
     * @test
     */
    public function isLinkThrowsExceptionIfLinkNotExists()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380556246);
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\LinkNode::class, array('exists'), array(), '', false);
        $node->expects($this->once())->method('exists')->will($this->returnValue(false));
        $this->assertFalse($node->_call('isLink'));
    }

    /**
     * @test
     */
    public function isLinkReturnsTrueIfNameIsLink()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\LinkNode::class, array('exists', 'getAbsolutePath'), array(), '', false);
        $path = PATH_site . 'typo3temp/var/tests/' . $this->getUniqueId('link_');
        $target = PATH_site . 'typo3temp/var/tests/' . $this->getUniqueId('linkTarget_');
        touch($target);
        symlink($target, $path);
        $this->testFilesToDelete[] = $path;
        $this->testFilesToDelete[] = $target;
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $this->assertTrue($node->_call('isLink'));
    }

    /**
     * @test
     */
    public function isFileReturnsFalseIfNameIsAFile()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\LinkNode::class, array('exists', 'getAbsolutePath'), array(), '', false);
        $path = PATH_site . 'typo3temp/var/tests/' . $this->getUniqueId('file_');
        touch($path);
        $this->testFilesToDelete[] = $path;
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $this->assertFalse($node->_call('isLink'));
    }

    /**
     * @test
     */
    public function isTargetCorrectThrowsExceptionIfLinkNotExists()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380556245);
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\LinkNode::class, array('exists'), array(), '', false);
        $node->expects($this->once())->method('exists')->will($this->returnValue(false));
        $this->assertFalse($node->_call('isTargetCorrect'));
    }

    /**
     * @test
     */
    public function isTargetCorrectThrowsExceptionIfNodeIsNotALink()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380556247);
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\LinkNode::class, array('exists', 'isLink', 'getTarget'), array(), '', false);
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->once())->method('isLink')->will($this->returnValue(false));
        $this->assertTrue($node->_call('isTargetCorrect'));
    }

    /**
     * @test
     */
    public function isTargetCorrectReturnsTrueIfNoExpectedLinkTargetIsSpecified()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\LinkNode::class, array('exists', 'isLink', 'getTarget'), array(), '', false);
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isLink')->will($this->returnValue(true));
        $node->expects($this->once())->method('getTarget')->will($this->returnValue(''));
        $this->assertTrue($node->_call('isTargetCorrect'));
    }

    /**
     * @test
     */
    public function isTargetCorrectAcceptsATargetWithATrailingSlash()
    {
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\LinkNode::class, array('exists', 'isLink', 'getCurrentTarget', 'getTarget'), array(), '', false);
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isLink')->will($this->returnValue(true));
        $node->expects($this->once())->method('getCurrentTarget')->will($this->returnValue('someLinkTarget/'));
        $node->expects($this->once())->method('getTarget')->will($this->returnValue('someLinkTarget'));
        $this->assertTrue($node->_call('isTargetCorrect'));
    }

    /**
     * @test
     * @see https://github.com/mikey179/vfsStream/wiki/Known-Issues - symlink doesn't work with vfsStream
     */
    public function isTargetCorrectReturnsTrueIfActualTargetIsIdenticalToSpecifiedTarget()
    {
        $path = PATH_site . 'typo3temp/var/tests/' . $this->getUniqueId('link_');
        $target = PATH_site . 'typo3temp/var/tests/' . $this->getUniqueId('linkTarget_');
        touch($target);
        symlink($target, $path);
        $this->testFilesToDelete[] = $path;
        $this->testFilesToDelete[] = $target;
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\LinkNode::class,
            array('exists', 'isLink', 'getTarget', 'getAbsolutePath'),
            array(),
            '',
            false
        );
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isLink')->will($this->returnValue(true));
        $node->expects($this->once())->method('getTarget')->will($this->returnValue(str_replace('/', DIRECTORY_SEPARATOR, $target)));
        $node->expects($this->once())->method('getAbsolutePath')->will($this->returnValue($path));
        $this->assertTrue($node->_call('isTargetCorrect'));
    }

    /**
     * @test
     * @see https://github.com/mikey179/vfsStream/wiki/Known-Issues - symlink doesn't work with vfsStream
     */
    public function isTargetCorrectReturnsFalseIfActualTargetIsNotIdenticalToSpecifiedTarget()
    {
        $path = PATH_site . 'typo3temp/var/tests/' . $this->getUniqueId('link_');
        $target = PATH_site . 'typo3temp/var/tests/' . $this->getUniqueId('linkTarget_');
        touch($target);
        symlink($target, $path);
        $this->testFilesToDelete[] = $path;
        $this->testFilesToDelete[] = $target;
        /** @var $node \TYPO3\CMS\Install\FolderStructure\LinkNode|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\LinkNode::class,
            array('exists', 'isLink', 'getTarget', 'getAbsolutePath'),
            array(),
            '',
            false
        );
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isLink')->will($this->returnValue(true));
        $node->expects($this->once())->method('getTarget')->will($this->returnValue('foo'));
        $node->expects($this->once())->method('getAbsolutePath')->will($this->returnValue($path));
        $this->assertFalse($node->_call('isTargetCorrect'));
    }
}
