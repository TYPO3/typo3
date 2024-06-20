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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Install\FolderStructure\LinkOrDirectoryNode;
use TYPO3\CMS\Install\FolderStructure\NodeInterface;

final class LinkOrDirectoryNodeTest extends AbstractFolderStructureTestCase
{
    #[Test]
    public function fixCallsFixSelfAndReturnsItsResult(): void
    {
        $node = $this->getMockBuilder(LinkOrDirectoryNode::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fixSelf'])
            ->getMock();
        $uniqueReturn = [new FlashMessage(StringUtility::getUniqueId('foo_'))];
        $node->expects(self::once())->method('fixSelf')->willReturn($uniqueReturn);
        self::assertSame($uniqueReturn, $node->fix());
    }

    #[Test]
    public function fixCallsFixOnChildrenAndReturnsMergedResult(): void
    {
        $node = $this->getAccessibleMock(LinkOrDirectoryNode::class, ['fixSelf'], [], '', false);
        $uniqueReturnSelf = new FlashMessage(StringUtility::getUniqueId('foo_'));
        $node->expects(self::once())->method('fixSelf')->willReturn([$uniqueReturnSelf]);

        $childMock1 = $this->createMock(NodeInterface::class);
        $uniqueReturnChild1 = new FlashMessage(StringUtility::getUniqueId('foo_'));
        $childMock1->expects(self::once())->method('fix')->willReturn([$uniqueReturnChild1]);

        $childMock2 = $this->createMock(NodeInterface::class);
        $uniqueReturnChild2 = new FlashMessage(StringUtility::getUniqueId('foo_'));
        $childMock2->expects(self::once())->method('fix')->willReturn([$uniqueReturnChild2]);

        $node->_set('children', [$childMock1, $childMock2]);

        self::assertSame([$uniqueReturnSelf, $uniqueReturnChild1, $uniqueReturnChild2], $node->fix());
    }

    #[Test]
    public function fixSelfCallsCreateDirectoryIfDirectoryDoesNotExistAndReturnsResult(): void
    {
        $node = $this->getAccessibleMock(
            LinkOrDirectoryNode::class,
            ['exists', 'createDirectory', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $node->expects(self::once())->method('exists')->willReturn(false);
        $node->method('isPermissionCorrect')->willReturn(true);
        $uniqueReturn = new FlashMessage('foo');
        $node->expects(self::once())->method('createDirectory')->willReturn($uniqueReturn);
        self::assertSame([$uniqueReturn], $node->_call('fixSelf'));
    }

    #[Test]
    public function fixSelfReturnsErrorStatusIfNodeExistsButIsNotADirectoryAndReturnsResult(): void
    {
        $node = $this->getAccessibleMock(
            LinkOrDirectoryNode::class,
            ['exists', 'isWritable', 'getRelativePathBelowSiteRoot', 'isDirectory', 'getAbsolutePath'],
            [],
            '',
            false
        );
        $node->method('exists')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
        $node->method('isDirectory')->willReturn(false);
        $node->method('getRelativePathBelowSiteRoot')->willReturn('');
        $node->method('getAbsolutePath')->willReturn('');
        $result = $node->_call('fixSelf');
        self::assertSame(ContextualFeedbackSeverity::ERROR, $result[0]->getSeverity());
    }

    #[Test]
    public function fixSelfCallsFixPermissionIfDirectoryExistsButIsNotWritable(): void
    {
        $node = $this->getAccessibleMock(
            LinkOrDirectoryNode::class,
            ['exists', 'isWritable', 'fixPermission'],
            [],
            '',
            false
        );
        $node->method('exists')->willReturn(true);
        $node->method('isWritable')->willReturn(false);
        $message = new FlashMessage('foo');
        $node->expects(self::once())->method('fixPermission')->willReturn($message);
        self::assertSame([$message], $node->_call('fixSelf'));
    }

    #[Test]
    public function isDirectoryReturnsTrueIfNameIsADirectory(): void
    {
        $node = $this->getAccessibleMock(LinkOrDirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getTestDirectory('dir_');
        $node->method('getAbsolutePath')->willReturn($path);
        self::assertTrue($node->_call('isDirectory'));
    }

    #[Test]
    public function isDirectoryReturnsTrueIfNameIsALinkToADirectory(): void
    {
        $node = $this->getAccessibleMock(LinkOrDirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $testRoot = Environment::getVarPath() . '/tests/';
        $this->testFilesToDelete[] = $testRoot;
        $path = $testRoot . StringUtility::getUniqueId('root_');
        GeneralUtility::mkdir_deep($path);
        $link = StringUtility::getUniqueId('link_');
        $dir = StringUtility::getUniqueId('dir_');
        mkdir($path . '/' . $dir);
        symlink($path . '/' . $dir, $path . '/' . $link);
        $node->method('getAbsolutePath')->willReturn($path . '/' . $link);
        self::assertTrue($node->_call('isDirectory'));
    }
}
