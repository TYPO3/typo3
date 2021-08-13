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

use PHPUnit\Framework\MockObject\MockObject;
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
    public function constructorThrowsExceptionIfParentIsNull(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380485700);
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['dummy'], [], '', false);
        $node->__construct([], null);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfNameContainsForwardSlash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380546061);
        $parent = $this->createMock(NodeInterface::class);
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['dummy'], [], '', false);
        $structure = [
            'name' => 'foo/bar',
        ];
        $node->__construct($structure, $parent);
    }

    /**
     * @test
     */
    public function constructorSetsParent(): void
    {
        $parent = $this->createMock(NodeInterface::class);
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
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
    public function constructorSetsName(): void
    {
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['dummy'], [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $name = StringUtility::getUniqueId('test_');
        $node->__construct(['name' => $name], $parent);
        self::assertSame($name, $node->getName());
    }

    /**
     * @test
     */
    public function constructorSetsNameAndTarget(): void
    {
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
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
    public function getStatusReturnsArray(): void
    {
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists'],
            [],
            '',
            false
        );
        // do not use var path here, as link nodes get checked for public path as first part
        $path = Environment::getPublicPath() . '/typo3temp/tests/' . StringUtility::getUniqueId('dir_');
        $node->method('getAbsolutePath')->willReturn($path);
        self::assertIsArray($node->getStatus());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithInformationStatusIfRunningOnWindows(): void
    {
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists'],
            [],
            '',
            false
        );
        // do not use var path here, as link nodes get checked for public path as first part
        $path = Environment::getPublicPath() . '/tests/' . StringUtility::getUniqueId('dir_');
        $node->method('getAbsolutePath')->willReturn($path);
        $node->expects(self::once())->method('isWindowsOs')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::INFO, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithErrorStatusIfLinkNotExists(): void
    {
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists'],
            [],
            '',
            false
        );
        // do not use var path here, as link nodes get checked for public path as first part
        $path = Environment::getPublicPath() . '/tests/' . StringUtility::getUniqueId('dir_');
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('isWindowsOs')->willReturn(false);
        $node->expects(self::once())->method('exists')->willReturn(false);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::ERROR, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithWarningStatusIfNodeIsNotALink(): void
    {
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists', 'isLink', 'getRelativePathBelowSiteRoot'],
            [],
            '',
            false
        );
        $node->method('getAbsolutePath')->willReturn('');
        $node->method('exists')->willReturn(true);
        $node->expects(self::once())->method('isLink')->willReturn(false);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::WARNING, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsErrorStatusIfLinkTargetIsNotCorrect(): void
    {
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists', 'isLink', 'isTargetCorrect', 'getCurrentTarget', 'getRelativePathBelowSiteRoot'],
            [],
            '',
            false
        );
        $node->method('getAbsolutePath')->willReturn('');
        $node->method('getCurrentTarget')->willReturn('');
        $node->method('exists')->willReturn(true);
        $node->method('isLink')->willReturn(true);
        $node->expects(self::once())->method('isLink')->willReturn(false);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::ERROR, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsOkStatusIfLinkExistsAndTargetIsCorrect(): void
    {
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists', 'isLink', 'isTargetCorrect', 'getRelativePathBelowSiteRoot'],
            [],
            '',
            false
        );
        $node->method('getAbsolutePath')->willReturn('');
        $node->method('exists')->willReturn(true);
        $node->expects(self::once())->method('isLink')->willReturn(true);
        $node->expects(self::once())->method('isTargetCorrect')->willReturn(true);
        $node->expects(self::once())->method('getRelativePathBelowSiteRoot')->willReturn('');
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::OK, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function fixReturnsEmptyArray(): void
    {
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
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
    public function isLinkThrowsExceptionIfLinkNotExists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380556246);
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists'], [], '', false);
        $node->expects(self::once())->method('exists')->willReturn(false);
        self::assertFalse($node->_call('isLink'));
    }

    /**
     * @test
     */
    public function isLinkReturnsTrueIfNameIsLink(): void
    {
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'getAbsolutePath'], [], '', false);
        $path = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('link_');
        $target = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('linkTarget_');
        touch($target);
        symlink($target, $path);
        $this->testFilesToDelete[] = $path;
        $this->testFilesToDelete[] = $target;
        $node->method('exists')->willReturn(true);
        $node->method('getAbsolutePath')->willReturn($path);
        self::assertTrue($node->_call('isLink'));
    }

    /**
     * @test
     */
    public function isFileReturnsFalseIfNameIsAFile(): void
    {
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'getAbsolutePath'], [], '', false);
        $path = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('file_');
        touch($path);
        $this->testFilesToDelete[] = $path;
        $node->method('exists')->willReturn(true);
        $node->method('getAbsolutePath')->willReturn($path);
        self::assertFalse($node->_call('isLink'));
    }

    /**
     * @test
     */
    public function isTargetCorrectThrowsExceptionIfLinkNotExists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380556245);
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists'], [], '', false);
        $node->expects(self::once())->method('exists')->willReturn(false);
        self::assertFalse($node->_call('isTargetCorrect'));
    }

    /**
     * @test
     */
    public function isTargetCorrectThrowsExceptionIfNodeIsNotALink(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380556247);
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'isLink', 'getTarget'], [], '', false);
        $node->method('exists')->willReturn(true);
        $node->expects(self::once())->method('isLink')->willReturn(false);
        self::assertTrue($node->_call('isTargetCorrect'));
    }

    /**
     * @test
     */
    public function isTargetCorrectReturnsTrueIfNoExpectedLinkTargetIsSpecified(): void
    {
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'isLink', 'getTarget'], [], '', false);
        $node->method('exists')->willReturn(true);
        $node->method('isLink')->willReturn(true);
        $node->expects(self::once())->method('getTarget')->willReturn('');
        self::assertTrue($node->_call('isTargetCorrect'));
    }

    /**
     * @test
     */
    public function isTargetCorrectAcceptsATargetWithATrailingSlash(): void
    {
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'isLink', 'getCurrentTarget', 'getTarget'], [], '', false);
        $node->method('exists')->willReturn(true);
        $node->method('isLink')->willReturn(true);
        $node->expects(self::once())->method('getCurrentTarget')->willReturn('someLinkTarget/');
        $node->expects(self::once())->method('getTarget')->willReturn('someLinkTarget');
        self::assertTrue($node->_call('isTargetCorrect'));
    }

    /**
     * @test
     * @see https://github.com/mikey179/vfsStream/wiki/Known-Issues - symlink doesn't work with vfsStream
     */
    public function isTargetCorrectReturnsTrueIfActualTargetIsIdenticalToSpecifiedTarget(): void
    {
        $path = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('link_');
        $target = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('linkTarget_');
        touch($target);
        symlink($target, $path);
        $this->testFilesToDelete[] = $path;
        $this->testFilesToDelete[] = $target;
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['exists', 'isLink', 'getTarget', 'getAbsolutePath'],
            [],
            '',
            false
        );
        $node->method('exists')->willReturn(true);
        $node->method('isLink')->willReturn(true);
        $node->expects(self::once())->method('getTarget')->willReturn(str_replace('/', DIRECTORY_SEPARATOR, $target));
        $node->expects(self::once())->method('getAbsolutePath')->willReturn($path);
        self::assertTrue($node->_call('isTargetCorrect'));
    }

    /**
     * @test
     * @see https://github.com/mikey179/vfsStream/wiki/Known-Issues - symlink doesn't work with vfsStream
     */
    public function isTargetCorrectReturnsFalseIfActualTargetIsNotIdenticalToSpecifiedTarget(): void
    {
        $path = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('link_');
        $target = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('linkTarget_');
        touch($target);
        symlink($target, $path);
        $this->testFilesToDelete[] = $path;
        $this->testFilesToDelete[] = $target;
        /** @var $node LinkNode|AccessibleObjectInterface|MockObject */
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['exists', 'isLink', 'getTarget', 'getAbsolutePath'],
            [],
            '',
            false
        );
        $node->method('exists')->willReturn(true);
        $node->method('isLink')->willReturn(true);
        $node->expects(self::once())->method('getTarget')->willReturn('foo');
        $node->expects(self::once())->method('getAbsolutePath')->willReturn($path);
        self::assertFalse($node->_call('isTargetCorrect'));
    }
}
