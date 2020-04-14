<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Install\Tests\Unit\FolderStructure;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\StringUtility;
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
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
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
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
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
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['dummy'], [], '', false);
        $structure = [
            'name' => 'foo',
        ];
        $node->__construct($structure, $parent);
        self::assertSame($parent, $node->_call('getParent'));
    }

    /**
     * @test
     */
    public function constructorSetsName()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $name = StringUtility::getUniqueId('test_');
        $node->__construct(['name' => $name], $parent);
        self::assertSame($name, $node->getName());
    }

    /**
     * @test
     */
    public function constructorSetsNameAndTarget()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $name = StringUtility::getUniqueId('test_');
        $target = '../' . StringUtility::getUniqueId('test_');
        $node->__construct(['name' => $name, 'target' => $target], $parent);
        self::assertSame($target, $node->_call('getTarget'));
    }

    /**
     * @test
     */
    public function getStatusReturnsArray()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists'],
            [],
            '',
            false
        );
        // do not use var path here, as link nodes get checked for public path as first part
        $path = Environment::getPublicPath() . '/typo3temp/tests/' . StringUtility::getUniqueId('dir_');
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        self::assertIsArray($node->getStatus());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithInformationStatusIfRunningOnWindows()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists'],
            [],
            '',
            false
        );
        // do not use var path here, as link nodes get checked for public path as first part
        $path = Environment::getPublicPath() . '/tests/' . StringUtility::getUniqueId('dir_');
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        $node->expects(self::once())->method('isWindowsOs')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::INFO, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithErrorStatusIfLinkNotExists()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists'],
            [],
            '',
            false
        );
        // do not use var path here, as link nodes get checked for public path as first part
        $path = Environment::getPublicPath() . '/tests/' . StringUtility::getUniqueId('dir_');
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        $node->expects(self::any())->method('isWindowsOs')->willReturn(false);
        $node->expects(self::once())->method('exists')->willReturn(false);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::ERROR, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithWarningStatusIfNodeIsNotALink()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists', 'isLink', 'getRelativePathBelowSiteRoot'],
            [],
            '',
            false
        );
        $node->expects(self::any())->method('getAbsolutePath')->willReturn('');
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::once())->method('isLink')->willReturn(false);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::WARNING, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsErrorStatusIfLinkTargetIsNotCorrect()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists', 'isLink', 'isTargetCorrect', 'getCurrentTarget', 'getRelativePathBelowSiteRoot'],
            [],
            '',
            false
        );
        $node->expects(self::any())->method('getAbsolutePath')->willReturn('');
        $node->expects(self::any())->method('getCurrentTarget')->willReturn('');
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::any())->method('isLink')->willReturn(true);
        $node->expects(self::once())->method('isLink')->willReturn(false);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::ERROR, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsOkStatusIfLinkExistsAndTargetIsCorrect()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists', 'isLink', 'isTargetCorrect', 'getRelativePathBelowSiteRoot'],
            [],
            '',
            false
        );
        $node->expects(self::any())->method('getAbsolutePath')->willReturn('');
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::once())->method('isLink')->willReturn(true);
        $node->expects(self::once())->method('isTargetCorrect')->willReturn(true);
        $node->expects(self::once())->method('getRelativePathBelowSiteRoot')->willReturn('');
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::OK, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function fixReturnsEmptyArray()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['getRelativePathBelowSiteRoot'],
            [],
            '',
            false
        );
        $statusArray = $node->fix();
        self::assertEmpty($statusArray);
    }

    /**
     * @test
     */
    public function isLinkThrowsExceptionIfLinkNotExists()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380556246);
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists'], [], '', false);
        $node->expects(self::once())->method('exists')->willReturn(false);
        self::assertFalse($node->_call('isLink'));
    }

    /**
     * @test
     */
    public function isLinkReturnsTrueIfNameIsLink()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'getAbsolutePath'], [], '', false);
        $path = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('link_');
        $target = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('linkTarget_');
        touch($target);
        symlink($target, $path);
        $this->testFilesToDelete[] = $path;
        $this->testFilesToDelete[] = $target;
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        self::assertTrue($node->_call('isLink'));
    }

    /**
     * @test
     */
    public function isFileReturnsFalseIfNameIsAFile()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'getAbsolutePath'], [], '', false);
        $path = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('file_');
        touch($path);
        $this->testFilesToDelete[] = $path;
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        self::assertFalse($node->_call('isLink'));
    }

    /**
     * @test
     */
    public function isTargetCorrectThrowsExceptionIfLinkNotExists()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380556245);
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists'], [], '', false);
        $node->expects(self::once())->method('exists')->willReturn(false);
        self::assertFalse($node->_call('isTargetCorrect'));
    }

    /**
     * @test
     */
    public function isTargetCorrectThrowsExceptionIfNodeIsNotALink()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380556247);
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'isLink', 'getTarget'], [], '', false);
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::once())->method('isLink')->willReturn(false);
        self::assertTrue($node->_call('isTargetCorrect'));
    }

    /**
     * @test
     */
    public function isTargetCorrectReturnsTrueIfNoExpectedLinkTargetIsSpecified()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'isLink', 'getTarget'], [], '', false);
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::any())->method('isLink')->willReturn(true);
        $node->expects(self::once())->method('getTarget')->willReturn('');
        self::assertTrue($node->_call('isTargetCorrect'));
    }

    /**
     * @test
     */
    public function isTargetCorrectAcceptsATargetWithATrailingSlash()
    {
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'isLink', 'getCurrentTarget', 'getTarget'], [], '', false);
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::any())->method('isLink')->willReturn(true);
        $node->expects(self::once())->method('getCurrentTarget')->willReturn('someLinkTarget/');
        $node->expects(self::once())->method('getTarget')->willReturn('someLinkTarget');
        self::assertTrue($node->_call('isTargetCorrect'));
    }

    /**
     * @test
     * @see https://github.com/mikey179/vfsStream/wiki/Known-Issues - symlink doesn't work with vfsStream
     */
    public function isTargetCorrectReturnsTrueIfActualTargetIsIdenticalToSpecifiedTarget()
    {
        $path = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('link_');
        $target = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('linkTarget_');
        touch($target);
        symlink($target, $path);
        $this->testFilesToDelete[] = $path;
        $this->testFilesToDelete[] = $target;
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['exists', 'isLink', 'getTarget', 'getAbsolutePath'],
            [],
            '',
            false
        );
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::any())->method('isLink')->willReturn(true);
        $node->expects(self::once())->method('getTarget')->willReturn(str_replace('/', DIRECTORY_SEPARATOR, $target));
        $node->expects(self::once())->method('getAbsolutePath')->willReturn($path);
        self::assertTrue($node->_call('isTargetCorrect'));
    }

    /**
     * @test
     * @see https://github.com/mikey179/vfsStream/wiki/Known-Issues - symlink doesn't work with vfsStream
     */
    public function isTargetCorrectReturnsFalseIfActualTargetIsNotIdenticalToSpecifiedTarget()
    {
        $path = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('link_');
        $target = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('linkTarget_');
        touch($target);
        symlink($target, $path);
        $this->testFilesToDelete[] = $path;
        $this->testFilesToDelete[] = $target;
        /** @var $node LinkNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['exists', 'isLink', 'getTarget', 'getAbsolutePath'],
            [],
            '',
            false
        );
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::any())->method('isLink')->willReturn(true);
        $node->expects(self::once())->method('getTarget')->willReturn('foo');
        $node->expects(self::once())->method('getAbsolutePath')->willReturn($path);
        self::assertFalse($node->_call('isTargetCorrect'));
    }
}
