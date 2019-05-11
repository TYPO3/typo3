<?php
declare(strict_types = 1);
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
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;
use TYPO3\CMS\Install\FolderStructure\LinkNode;
use TYPO3\CMS\Install\FolderStructure\NodeInterface;
use TYPO3\CMS\Install\FolderStructure\RootNodeInterface;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class LinkNodeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function constructorThrowsExceptionIfParentIsNull()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380485700);
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['dummy'], [], '', false);
        $node->__construct([], null);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfNameContainsForwardSlash()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380546061);
        $parent = $this->createMock(NodeInterface::class);
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['dummy'], [], '', false);
        $structure = [
            'name' => 'foo/bar',
        ];
        $node->__construct($structure, $parent);
    }

    /**
     * @test
     */
    public function constructorSetsParent()
    {
        $parent = $this->createMock(NodeInterface::class);
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['dummy'], [], '', false);
        $structure = [
            'name' => 'foo',
        ];
        $node->__construct($structure, $parent);
        $this->assertSame($parent, $node->_call('getParent'));
    }

    /**
     * @test
     */
    public function constructorSetsName()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $name = $this->getUniqueId('test_');
        $node->__construct(['name' => $name], $parent);
        $this->assertSame($name, $node->getName());
    }

    /**
     * @test
     */
    public function constructorSetsNameAndTarget()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $name = $this->getUniqueId('test_');
        $target = '../' . $this->getUniqueId('test_');
        $node->__construct(['name' => $name, 'target' => $target], $parent);
        $this->assertSame($target, $node->_call('getTarget'));
    }

    /**
     * @test
     */
    public function getStatusReturnsArray()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists'],
            [],
            '',
            false
        );
        $path = Environment::getVarPath() . '/tests/' . $this->getUniqueId('dir_');
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $this->assertIsArray($node->getStatus());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithInformationStatusIfRunningOnWindows()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists'],
            [],
            '',
            false
        );
        $path = Environment::getVarPath() . '/tests/' . $this->getUniqueId('dir_');
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->once())->method('isWindowsOs')->will($this->returnValue(true));
        $statusArray = $node->getStatus();
        $this->assertSame(FlashMessage::INFO, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithErrorStatusIfLinkNotExists()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists'],
            [],
            '',
            false
        );
        $path = Environment::getVarPath() . '/tests/' . $this->getUniqueId('dir_');
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue($path));
        $node->expects($this->any())->method('isWindowsOs')->will($this->returnValue(false));
        $node->expects($this->once())->method('exists')->will($this->returnValue(false));
        $statusArray = $node->getStatus();
        $this->assertSame(FlashMessage::ERROR, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithWarningStatusIfNodeIsNotALink()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists', 'isLink', 'getRelativePathBelowSiteRoot'],
            [],
            '',
            false
        );
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue(''));
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->once())->method('isLink')->will($this->returnValue(false));
        $statusArray = $node->getStatus();
        $this->assertSame(FlashMessage::WARNING, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsErrorStatusIfLinkTargetIsNotCorrect()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists', 'isLink', 'isTargetCorrect', 'getCurrentTarget', 'getRelativePathBelowSiteRoot'],
            [],
            '',
            false
        );
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue(''));
        $node->expects($this->any())->method('getCurrentTarget')->will($this->returnValue(''));
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->any())->method('isLink')->will($this->returnValue(true));
        $node->expects($this->once())->method('isLink')->will($this->returnValue(false));
        $statusArray = $node->getStatus();
        $this->assertSame(FlashMessage::ERROR, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsOkStatusIfLinkExistsAndTargetIsCorrect()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists', 'isLink', 'isTargetCorrect', 'getRelativePathBelowSiteRoot'],
            [],
            '',
            false
        );
        $node->expects($this->any())->method('getAbsolutePath')->will($this->returnValue(''));
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->once())->method('isLink')->will($this->returnValue(true));
        $node->expects($this->once())->method('isTargetCorrect')->will($this->returnValue(true));
        $node->expects($this->once())->method('getRelativePathBelowSiteRoot')->will($this->returnValue(''));
        $statusArray = $node->getStatus();
        $this->assertSame(FlashMessage::OK, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function fixReturnsEmptyArray()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['getRelativePathBelowSiteRoot'],
            [],
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
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists'], [], '', false);
        $node->expects($this->once())->method('exists')->will($this->returnValue(false));
        $this->assertFalse($node->_call('isLink'));
    }

    /**
     * @test
     */
    public function isLinkReturnsTrueIfNameIsLink()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'getAbsolutePath'], [], '', false);
        $path = Environment::getVarPath() . '/tests/' . $this->getUniqueId('link_');
        $target = Environment::getVarPath() . '/tests/' . $this->getUniqueId('linkTarget_');
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
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'getAbsolutePath'], [], '', false);
        $path = Environment::getVarPath() . '/tests/' . $this->getUniqueId('file_');
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
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists'], [], '', false);
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
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'isLink', 'getTarget'], [], '', false);
        $node->expects($this->any())->method('exists')->will($this->returnValue(true));
        $node->expects($this->once())->method('isLink')->will($this->returnValue(false));
        $this->assertTrue($node->_call('isTargetCorrect'));
    }

    /**
     * @test
     */
    public function isTargetCorrectReturnsTrueIfNoExpectedLinkTargetIsSpecified()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'isLink', 'getTarget'], [], '', false);
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
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'isLink', 'getCurrentTarget', 'getTarget'], [], '', false);
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
        $path = Environment::getVarPath() . '/tests/' . $this->getUniqueId('link_');
        $target = Environment::getVarPath() . '/tests/' . $this->getUniqueId('linkTarget_');
        touch($target);
        symlink($target, $path);
        $this->testFilesToDelete[] = $path;
        $this->testFilesToDelete[] = $target;
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['exists', 'isLink', 'getTarget', 'getAbsolutePath'],
            [],
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
        $path = Environment::getVarPath() . '/tests/' . $this->getUniqueId('link_');
        $target = Environment::getVarPath() . '/tests/' . $this->getUniqueId('linkTarget_');
        touch($target);
        symlink($target, $path);
        $this->testFilesToDelete[] = $path;
        $this->testFilesToDelete[] = $target;
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['exists', 'isLink', 'getTarget', 'getAbsolutePath'],
            [],
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
