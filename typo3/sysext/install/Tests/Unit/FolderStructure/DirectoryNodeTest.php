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
use TYPO3\CMS\Install\FolderStructure\DirectoryNode;
use TYPO3\CMS\Install\FolderStructure\Exception;
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;
use TYPO3\CMS\Install\FolderStructure\NodeInterface;
use TYPO3\CMS\Install\FolderStructure\RootNodeInterface;

final class DirectoryNodeTest extends AbstractFolderStructureTestCase
{
    #[Test]
    public function constructorThrowsExceptionIfParentIsNull(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366222203);
        new DirectoryNode([], null);
    }

    #[Test]
    public function constructorThrowsExceptionIfNameContainsForwardSlash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366226639);
        $parent = $this->createMock(NodeInterface::class);
        $structure = [
            'name' => 'foo/bar',
        ];
        new DirectoryNode($structure, $parent);
    }

    #[Test]
    public function constructorCallsCreateChildrenIfChildrenAreSet(): void
    {
        $parent = $this->createMock(NodeInterface::class);
        $node = $this->getMockBuilder(DirectoryNode::class)
            ->onlyMethods(['createChildren'])
            ->disableOriginalConstructor()
            ->getMock();
        $childArray = [
            'foo',
        ];
        $structure = [
            'name' => 'foo',
            'children' => $childArray,
        ];
        $node->expects(self::once())->method('createChildren')->with($childArray);
        $node->__construct($structure, $parent);
    }

    #[Test]
    public function constructorSetsParent(): void
    {
        $parent = $this->createMock(NodeInterface::class);
        $node = $this->getAccessibleMock(DirectoryNode::class, null, [], '', false);
        $structure = [
            'name' => 'foo',
        ];
        $node->__construct($structure, $parent);
        self::assertSame($parent, $node->_call('getParent'));
    }

    #[Test]
    public function constructorSetsTargetPermission(): void
    {
        $parent = $this->createMock(NodeInterface::class);
        $node = $this->getAccessibleMock(DirectoryNode::class, null, [], '', false);
        $targetPermission = '2550';
        $structure = [
            'name' => 'foo',
            'targetPermission' => $targetPermission,
        ];
        $node->__construct($structure, $parent);
        self::assertSame($targetPermission, $node->_call('getTargetPermission'));
    }

    #[Test]
    public function constructorSetsName(): void
    {
        $parent = $this->createMock(RootNodeInterface::class);
        $name = StringUtility::getUniqueId('test_');
        $node = new DirectoryNode(['name' => $name], $parent);
        self::assertSame($name, $node->getName());
    }

    #[Test]
    public function getStatusReturnsArrayWithWarningStatusIfDirectoryNotExists(): void
    {
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $path = $this->getTestDirectory('dir_');
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(false);
        $node->method('isDirectory')->willReturn(false);
        $node->method('isPermissionCorrect')->willReturn(false);
        $node->method('isWritable')->willReturn(false);
        $statusArray = $node->getStatus();
        self::assertSame(ContextualFeedbackSeverity::WARNING, $statusArray[0]->getSeverity());
    }

    #[Test]
    public function getStatusReturnsArrayWithErrorStatusIfNodeIsNotADirectory(): void
    {
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $path = $this->getTestFilePath('dir_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isDirectory')->willReturn(false);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(ContextualFeedbackSeverity::ERROR, $statusArray[0]->getSeverity());
    }

    #[Test]
    public function getStatusReturnsArrayWithErrorStatusIfDirectoryExistsButIsNotWritable(): void
    {
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $path = $this->getTestFilePath('dir_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isDirectory')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(false);
        $statusArray = $node->getStatus();
        self::assertSame(ContextualFeedbackSeverity::ERROR, $statusArray[0]->getSeverity());
    }

    #[Test]
    public function getStatusReturnsArrayWithNoticeStatusIfDirectoryExistsButPermissionAreNotCorrect(): void
    {
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $path = $this->getTestFilePath('dir_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isDirectory')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(false);
        $node->method('isWritable')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(ContextualFeedbackSeverity::NOTICE, $statusArray[0]->getSeverity());
    }

    #[Test]
    public function getStatusReturnsArrayWithOkStatusIfDirectoryExistsAndPermissionAreCorrect(): void
    {
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['getAbsolutePath', 'getRelativePathBelowSiteRoot', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        $path = $this->getTestFilePath('dir_');
        touch($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isDirectory')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(ContextualFeedbackSeverity::OK, $statusArray[0]->getSeverity());
    }

    #[Test]
    public function getStatusCallsGetStatusOnChildren(): void
    {
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['exists', 'isDirectory', 'isPermissionCorrect', 'getRelativePathBelowSiteRoot', 'isWritable'],
            [],
            '',
            false
        );
        $node->method('exists')->willReturn(true);
        $node->method('isDirectory')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
        $childMock1 = $this->createMock(NodeInterface::class);
        $childMock1->expects(self::once())->method('getStatus')->willReturn([]);
        $childMock2 = $this->createMock(NodeInterface::class);
        $childMock2->expects(self::once())->method('getStatus')->willReturn([]);
        $node->_set('children', [$childMock1, $childMock2]);
        $node->getStatus();
    }

    #[Test]
    public function getStatusReturnsArrayWithOwnStatusAndStatusOfChild(): void
    {
        $node = $this->getAccessibleMock(
            DirectoryNode::class,
            ['exists', 'isDirectory', 'isPermissionCorrect', 'getRelativePathBelowSiteRoot', 'isWritable'],
            [],
            '',
            false
        );
        $node->method('exists')->willReturn(true);
        $node->method('isDirectory')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
        $childMock = $this->createMock(NodeInterface::class);
        $childMessage = new FlashMessage('foo');
        $childMock->expects(self::once())->method('getStatus')->willReturn([$childMessage]);
        $node->_set('children', [$childMock]);
        $status = $node->getStatus();
        $statusOfDirectory = $status[0];
        $statusOfChild = $status[1];
        self::assertSame(ContextualFeedbackSeverity::OK, $statusOfDirectory->getSeverity());
        self::assertSame($childMessage, $statusOfChild);
    }

    #[Test]
    public function fixCallsFixSelfAndReturnsItsResult(): void
    {
        $node = $this->getMockBuilder(DirectoryNode::class)
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
        $node = $this->getAccessibleMock(DirectoryNode::class, ['fixSelf'], [], '', false);
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
            DirectoryNode::class,
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
            DirectoryNode::class,
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
            DirectoryNode::class,
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
    public function createDirectoryThrowsExceptionIfNodeExists(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1366740091);
        $node = $this->getAccessibleMock(DirectoryNode::class, ['exists', 'getAbsolutePath'], [], '', false);
        $node->expects(self::once())->method('getAbsolutePath')->willReturn('');
        $node->expects(self::once())->method('exists')->willReturn(true);
        $node->_call('createDirectory');
    }

    #[Test]
    public function createDirectoryCreatesDirectory(): void
    {
        $node = $this->getAccessibleMock(DirectoryNode::class, ['exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getTestFilePath('dir_');
        $node->expects(self::once())->method('exists')->willReturn(false);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        $node->_call('createDirectory');
        self::assertDirectoryExists($path);
    }

    #[Test]
    public function createDirectoryReturnsOkStatusIfDirectoryWasCreated(): void
    {
        $node = $this->getAccessibleMock(DirectoryNode::class, ['exists', 'getAbsolutePath', 'getRelativePathBelowSiteRoot'], [], '', false);
        $path = $this->getTestFilePath('dir_');
        $node->expects(self::once())->method('exists')->willReturn(false);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('getRelativePathBelowSiteRoot')->willReturn($path);
        self::assertSame(ContextualFeedbackSeverity::OK, $node->_call('createDirectory')->getSeverity());
    }

    #[Test]
    public function createChildrenThrowsExceptionIfAChildTypeIsNotSet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366222204);
        $node = $this->getAccessibleMock(DirectoryNode::class, null, [], '', false);
        $brokenStructure = [
            [
                'name' => 'foo',
            ],
        ];
        $node->_call('createChildren', $brokenStructure);
    }

    #[Test]
    public function createChildrenThrowsExceptionIfAChildNameIsNotSet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366222205);
        $node = $this->getAccessibleMock(DirectoryNode::class, null, [], '', false);
        $brokenStructure = [
            [
                'type' => 'foo',
            ],
        ];
        $node->_call('createChildren', $brokenStructure);
    }

    #[Test]
    public function createChildrenThrowsExceptionForMultipleChildrenWithSameName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366222206);
        $node = $this->getAccessibleMock(DirectoryNode::class, null, [], '', false);
        $brokenStructure = [
            [
                'type' => DirectoryNode::class,
                'name' => 'foo',
            ],
            [
                'type' => DirectoryNode::class,
                'name' => 'foo',
            ],
        ];
        $node->_call('createChildren', $brokenStructure);
    }

    #[Test]
    public function getChildrenReturnsCreatedChild(): void
    {
        $node = $this->getAccessibleMock(DirectoryNode::class, null, [], '', false);
        $parent = $this->createMock(NodeInterface::class);
        $childName = StringUtility::getUniqueId('test_');
        $structure = [
            'name' => 'foo',
            'type' => DirectoryNode::class,
            'children' => [
                [
                    'type' => DirectoryNode::class,
                    'name' => $childName,
                ],
            ],
        ];
        $node->__construct($structure, $parent);
        $children = $node->_call('getChildren');
        /** @var NodeInterface $child */
        $child = $children[0];
        self::assertInstanceOf(DirectoryNode::class, $children[0]);
        self::assertSame($childName, $child->getName());
    }

    #[Test]
    public function isWritableReturnsFalseIfNodeDoesNotExist(): void
    {
        $node = $this->getAccessibleMock(DirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getTestFilePath('dir_');
        $node->method('getAbsolutePath')->willReturn($path);
        self::assertFalse($node->isWritable());
    }

    #[Test]
    public function isWritableReturnsTrueIfNodeExistsAndFileCanBeCreated(): void
    {
        $node = $this->getAccessibleMock(DirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getTestDirectory('root_');
        $node->method('getAbsolutePath')->willReturn($path);
        self::assertTrue($node->isWritable());
    }

    #[Test]
    public function isDirectoryReturnsTrueIfNameIsADirectory(): void
    {
        $node = $this->getAccessibleMock(DirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $path = $this->getTestDirectory('dir_');
        $node->method('getAbsolutePath')->willReturn($path);
        self::assertTrue($node->_call('isDirectory'));
    }

    #[Test]
    public function isDirectoryReturnsFalseIfNameIsALinkToADirectory(): void
    {
        $node = $this->getAccessibleMock(DirectoryNode::class, ['getAbsolutePath'], [], '', false);
        $testRoot = Environment::getVarPath() . '/tests/';
        $this->testFilesToDelete[] = $testRoot;
        $path = $testRoot . StringUtility::getUniqueId('root_');
        GeneralUtility::mkdir_deep($path);
        $link = StringUtility::getUniqueId('link_');
        $dir = StringUtility::getUniqueId('dir_');
        mkdir($path . '/' . $dir);
        symlink($path . '/' . $dir, $path . '/' . $link);
        $node->method('getAbsolutePath')->willReturn($path . '/' . $link);
        self::assertFalse($node->_call('isDirectory'));
    }
}
