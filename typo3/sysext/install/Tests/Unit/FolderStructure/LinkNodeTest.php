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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;
use TYPO3\CMS\Install\FolderStructure\LinkNode;
use TYPO3\CMS\Install\FolderStructure\NodeInterface;
use TYPO3\CMS\Install\FolderStructure\RootNodeInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class LinkNodeTest extends UnitTestCase
{
    private string $testRoot;

    public function setUp(): void
    {
        parent::setUp();
        // do not use var path here, as link nodes get checked for public path as first part
        $this->testRoot = Environment::getPublicPath() . '/typo3temp/tests/';
        $this->testFilesToDelete[] = $this->testRoot;
        GeneralUtility::mkdir_deep($this->testRoot);
    }

    #[Test]
    public function constructorThrowsExceptionIfParentIsNull(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380485700);
        $node = $this->getAccessibleMock(LinkNode::class, null, [], '', false);
        $node->__construct([], null);
    }

    #[Test]
    public function constructorThrowsExceptionIfNameContainsForwardSlash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380546061);
        $parent = $this->createMock(NodeInterface::class);
        $node = $this->getAccessibleMock(LinkNode::class, null, [], '', false);
        $structure = [
            'name' => 'foo/bar',
        ];
        $node->__construct($structure, $parent);
    }

    #[Test]
    public function constructorSetsParent(): void
    {
        $parent = $this->createMock(NodeInterface::class);
        $node = $this->getAccessibleMock(LinkNode::class, null, [], '', false);
        $structure = [
            'name' => 'foo',
        ];
        $node->__construct($structure, $parent);
        self::assertSame($parent, $node->_call('getParent'));
    }

    #[Test]
    public function constructorSetsName(): void
    {
        $node = $this->getAccessibleMock(LinkNode::class, null, [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $name = StringUtility::getUniqueId('test_');
        $node->__construct(['name' => $name], $parent);
        self::assertSame($name, $node->getName());
    }

    #[Test]
    public function constructorSetsNameAndTarget(): void
    {
        $node = $this->getAccessibleMock(LinkNode::class, null, [], '', false);
        $parent = $this->createMock(RootNodeInterface::class);
        $name = StringUtility::getUniqueId('test_');
        $target = '../' . StringUtility::getUniqueId('test_');
        $node->__construct(['name' => $name, 'target' => $target], $parent);
        self::assertSame($target, $node->_call('getTarget'));
    }

    #[Test]
    public function getStatusReturnsArrayWithInformationStatusIfRunningOnWindows(): void
    {
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists'],
            [],
            '',
            false
        );
        $path = $this->testRoot . StringUtility::getUniqueId('dir_');
        $node->method('getAbsolutePath')->willReturn($path);
        $node->expects(self::once())->method('isWindowsOs')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(ContextualFeedbackSeverity::INFO, $statusArray[0]->getSeverity());
    }

    #[Test]
    public function getStatusReturnsArrayWithErrorStatusIfLinkNotExists(): void
    {
        $node = $this->getAccessibleMock(
            LinkNode::class,
            ['isWindowsOs', 'getAbsolutePath', 'exists'],
            [],
            '',
            false
        );
        $path = $this->testRoot . StringUtility::getUniqueId('dir_');
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('isWindowsOs')->willReturn(false);
        $node->expects(self::once())->method('exists')->willReturn(false);
        $statusArray = $node->getStatus();
        self::assertSame(ContextualFeedbackSeverity::ERROR, $statusArray[0]->getSeverity());
    }

    #[Test]
    public function getStatusReturnsArrayWithWarningStatusIfNodeIsNotALink(): void
    {
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
        self::assertSame(ContextualFeedbackSeverity::WARNING, $statusArray[0]->getSeverity());
    }

    #[Test]
    public function getStatusReturnsErrorStatusIfLinkTargetIsNotCorrect(): void
    {
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
        self::assertSame(ContextualFeedbackSeverity::ERROR, $statusArray[0]->getSeverity());
    }

    #[Test]
    public function getStatusReturnsOkStatusIfLinkExistsAndTargetIsCorrect(): void
    {
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
        self::assertSame(ContextualFeedbackSeverity::OK, $statusArray[0]->getSeverity());
    }

    #[Test]
    public function fixReturnsEmptyArray(): void
    {
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

    #[Test]
    public function isLinkThrowsExceptionIfLinkNotExists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380556246);
        $node = $this->getAccessibleMock(LinkNode::class, ['exists'], [], '', false);
        $node->expects(self::once())->method('exists')->willReturn(false);
        self::assertFalse($node->_call('isLink'));
    }

    #[Test]
    public function isLinkReturnsTrueIfNameIsLink(): void
    {
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'getAbsolutePath'], [], '', false);
        $path = $this->testRoot . StringUtility::getUniqueId('link_');
        $target = $this->testRoot . StringUtility::getUniqueId('linkTarget_');
        touch($target);
        symlink($target, $path);
        $node->method('exists')->willReturn(true);
        $node->method('getAbsolutePath')->willReturn($path);
        self::assertTrue($node->_call('isLink'));
    }

    #[Test]
    public function isFileReturnsFalseIfNameIsAFile(): void
    {
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'getAbsolutePath'], [], '', false);
        $path = $this->testRoot . StringUtility::getUniqueId('file_');
        touch($path);
        $node->method('exists')->willReturn(true);
        $node->method('getAbsolutePath')->willReturn($path);
        self::assertFalse($node->_call('isLink'));
    }

    #[Test]
    public function isTargetCorrectThrowsExceptionIfLinkNotExists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380556245);
        $node = $this->getAccessibleMock(LinkNode::class, ['exists'], [], '', false);
        $node->expects(self::once())->method('exists')->willReturn(false);
        self::assertFalse($node->_call('isTargetCorrect'));
    }

    #[Test]
    public function isTargetCorrectThrowsExceptionIfNodeIsNotALink(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1380556247);
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'isLink', 'getTarget'], [], '', false);
        $node->method('exists')->willReturn(true);
        $node->expects(self::once())->method('isLink')->willReturn(false);
        self::assertTrue($node->_call('isTargetCorrect'));
    }

    #[Test]
    public function isTargetCorrectReturnsTrueIfNoExpectedLinkTargetIsSpecified(): void
    {
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'isLink', 'getTarget'], [], '', false);
        $node->method('exists')->willReturn(true);
        $node->method('isLink')->willReturn(true);
        $node->expects(self::once())->method('getTarget')->willReturn('');
        self::assertTrue($node->_call('isTargetCorrect'));
    }

    #[Test]
    public function isTargetCorrectAcceptsATargetWithATrailingSlash(): void
    {
        $node = $this->getAccessibleMock(LinkNode::class, ['exists', 'isLink', 'getCurrentTarget', 'getTarget'], [], '', false);
        $node->method('exists')->willReturn(true);
        $node->method('isLink')->willReturn(true);
        $node->expects(self::once())->method('getCurrentTarget')->willReturn('someLinkTarget/');
        $node->expects(self::once())->method('getTarget')->willReturn('someLinkTarget');
        self::assertTrue($node->_call('isTargetCorrect'));
    }

    #[Test]
    public function isTargetCorrectReturnsTrueIfActualTargetIsIdenticalToSpecifiedTarget(): void
    {
        $path = $this->testRoot . StringUtility::getUniqueId('link_');
        $target = $this->testRoot . StringUtility::getUniqueId('linkTarget_');
        touch($target);
        symlink($target, $path);
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

    #[Test]
    public function isTargetCorrectReturnsFalseIfActualTargetIsNotIdenticalToSpecifiedTarget(): void
    {
        $path = $this->testRoot . StringUtility::getUniqueId('link_');
        $target = $this->testRoot . StringUtility::getUniqueId('linkTarget_');
        touch($target);
        symlink($target, $path);
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
