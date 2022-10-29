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
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Install\FolderStructure\DirectoryNode;
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;
use TYPO3\CMS\Install\FolderStructure\Exception\RootNodeException;
use TYPO3\CMS\Install\FolderStructure\NodeInterface;
use TYPO3\CMS\Install\FolderStructure\RootNode;
use TYPO3\CMS\Install\FolderStructure\RootNodeInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RootNodeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function constructorThrowsExceptionIfParentIsNotNull(): void
    {
        $this->expectException(RootNodeException::class);
        $this->expectExceptionCode(1366140117);
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $falseParent = $this->createMock(RootNodeInterface::class);
        $node->__construct([], $falseParent);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfAbsolutePathIsNotSet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366141329);
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $structure = [
            'type' => 'root',
        ];
        $node->__construct($structure, null);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfAbsolutePathIsNotAbsoluteOnWindows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366141329);
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->method('isWindowsOs')
            ->willReturn(true);
        $structure = [
            'name' => '/bar',
        ];
        $node->__construct($structure, null);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfAbsolutePathIsNotAbsoluteOnUnix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366141329);
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->method('isWindowsOs')
            ->willReturn(false);
        $structure = [
            'name' => 'C:/bar',
        ];
        $node->__construct($structure, null);
    }

    /**
     * @test
     */
    public function constructorSetsParentToNull(): void
    {
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->method('isWindowsOs')
            ->willReturn(false);
        $structure = [
            'name' => '/bar',
        ];
        $node->__construct($structure, null);
        self::assertNull($node->_call('getParent'));
    }

    /**
     * @test
     */
    public function getChildrenReturnsChildCreatedByConstructor(): void
    {
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->method('isWindowsOs')
            ->willReturn(false);
        $childName = StringUtility::getUniqueId('test_');
        $structure = [
            'name' => '/foo',
            'children' => [
                [
                    'type' => DirectoryNode::class,
                    'name' => $childName,
                ],
            ],
        ];
        $node->__construct($structure, null);
        $children = $node->_call('getChildren');
        /** @var NodeInterface $node */
        $child = $children[0];
        self::assertInstanceOf(DirectoryNode::class, $child);
        self::assertSame($childName, $child->getName());
    }

    /**
     * @test
     */
    public function constructorSetsTargetPermission(): void
    {
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->method('isWindowsOs')
            ->willReturn(false);
        $targetPermission = '2550';
        $structure = [
            'name' => '/foo',
            'targetPermission' => $targetPermission,
        ];
        $node->__construct($structure, null);
        self::assertSame($targetPermission, $node->_call('getTargetPermission'));
    }

    /**
     * @test
     */
    public function constructorSetsName(): void
    {
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->method('isWindowsOs')
            ->willReturn(false);
        $name = '/' . StringUtility::getUniqueId('test_');
        $node->__construct(['name' => $name], null);
        self::assertSame($name, $node->getName());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithOkStatusAndCallsOwnStatusMethods(): void
    {
        $node = $this->getAccessibleMock(
            RootNode::class,
            ['getAbsolutePath', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        // do not use var path here, as root nodes get checked for public path as first part
        $testRoot = Environment::getPublicPath() . '/typo3temp/tests/';
        $this->testFilesToDelete[] = $testRoot;
        $path = $testRoot . StringUtility::getUniqueId('dir_');
        GeneralUtility::mkdir_deep($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->expects(self::once())->method('exists')->willReturn(true);
        $node->expects(self::once())->method('isDirectory')->willReturn(true);
        $node->expects(self::once())->method('isPermissionCorrect')->willReturn(true);
        $node->expects(self::once())->method('isWritable')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(ContextualFeedbackSeverity::OK, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusCallsGetChildrenStatusForStatus(): void
    {
        $node = $this->getAccessibleMock(
            RootNode::class,
            ['getAbsolutePath', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect', 'getChildrenStatus'],
            [],
            '',
            false
        );
        // do not use var path here, as root nodes get checked for public path as first part
        $testRoot = Environment::getPublicPath() . '/typo3temp/tests/';
        $this->testFilesToDelete[] = $testRoot;
        $path = $testRoot . StringUtility::getUniqueId('dir_');
        GeneralUtility::mkdir_deep($path);
        $node->method('getAbsolutePath')->willReturn($path);
        $node->method('exists')->willReturn(true);
        $node->method('isDirectory')->willReturn(true);
        $node->method('isPermissionCorrect')->willReturn(true);
        $node->method('isWritable')->willReturn(true);
        $childStatus = new FlashMessage('foo', '', ContextualFeedbackSeverity::ERROR);
        $node->expects(self::once())->method('getChildrenStatus')->willReturn([$childStatus]);
        $statusArray = $node->getStatus();
        $statusSelf = $statusArray[0];
        $statusOfChild = $statusArray[1];
        self::assertSame(ContextualFeedbackSeverity::OK, $statusSelf->getSeverity());
        self::assertSame($childStatus, $statusOfChild);
    }

    /**
     * @test
     */
    public function getAbsolutePathReturnsGivenName(): void
    {
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->method('isWindowsOs')
            ->willReturn(false);
        $path = '/foo/bar';
        $structure = [
            'name' => $path,
        ];
        $node->__construct($structure, null);
        self::assertSame($path, $node->getAbsolutePath());
    }
}
