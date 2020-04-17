<?php

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Install\FolderStructure\DirectoryNode;
use TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException;
use TYPO3\CMS\Install\FolderStructure\Exception\RootNodeException;
use TYPO3\CMS\Install\FolderStructure\RootNode;
use TYPO3\CMS\Install\FolderStructure\RootNodeInterface;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RootNodeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function constructorThrowsExceptionIfParentIsNotNull()
    {
        $this->expectException(RootNodeException::class);
        $this->expectExceptionCode(1366140117);
        /** @var $node RootNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $falseParent = $this->createMock(RootNodeInterface::class);
        $node->__construct([], $falseParent);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfAbsolutePathIsNotSet()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366141329);
        /** @var $node RootNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $structure = [
            'type' => 'root',
        ];
        $node->__construct($structure, null);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfAbsolutePathIsNotAbsoluteOnWindows()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366141329);
        /** @var $node RootNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->expects(self::any())
            ->method('isWindowsOs')
            ->willReturn(true);
        $structure = [
            'name' => '/bar'
        ];
        $node->__construct($structure, null);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfAbsolutePathIsNotAbsoluteOnUnix()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1366141329);
        /** @var $node RootNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->expects(self::any())
            ->method('isWindowsOs')
            ->willReturn(false);
        $structure = [
            'name' => 'C:/bar'
        ];
        $node->__construct($structure, null);
    }

    /**
     * @test
     */
    public function constructorSetsParentToNull()
    {
        /** @var $node RootNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->expects(self::any())
            ->method('isWindowsOs')
            ->willReturn(false);
        $structure = [
            'name' => '/bar'
        ];
        $node->__construct($structure, null);
        self::assertNull($node->_call('getParent'));
    }

    /**
     * @test
     */
    public function getChildrenReturnsChildCreatedByConstructor()
    {
        /** @var $node RootNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->expects(self::any())
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
        /** @var $child \TYPO3\CMS\install\FolderStructure\NodeInterface */
        $child = $children[0];
        self::assertInstanceOf(DirectoryNode::class, $child);
        self::assertSame($childName, $child->getName());
    }

    /**
     * @test
     */
    public function constructorSetsTargetPermission()
    {
        /** @var $node RootNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->expects(self::any())
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
    public function constructorSetsName()
    {
        /** @var $node RootNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->expects(self::any())
            ->method('isWindowsOs')
            ->willReturn(false);
        $name = '/' . StringUtility::getUniqueId('test_');
        $node->__construct(['name' => $name], null);
        self::assertSame($name, $node->getName());
    }

    /**
     * @test
     */
    public function getStatusReturnsArrayWithOkStatusAndCallsOwnStatusMethods()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            RootNode::class,
            ['getAbsolutePath', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect'],
            [],
            '',
            false
        );
        // do not use var path here, as root nodes get checked for public path as first part
        $path = Environment::getPublicPath() . '/typo3temp/tests/' . StringUtility::getUniqueId('dir_');
        GeneralUtility::mkdir_deep($path);
        $this->testFilesToDelete[] = $path;
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        $node->expects(self::once())->method('exists')->willReturn(true);
        $node->expects(self::once())->method('isDirectory')->willReturn(true);
        $node->expects(self::once())->method('isPermissionCorrect')->willReturn(true);
        $node->expects(self::once())->method('isWritable')->willReturn(true);
        $statusArray = $node->getStatus();
        self::assertSame(FlashMessage::OK, $statusArray[0]->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusCallsGetChildrenStatusForStatus()
    {
        /** @var $node DirectoryNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(
            RootNode::class,
            ['getAbsolutePath', 'exists', 'isDirectory', 'isWritable', 'isPermissionCorrect', 'getChildrenStatus'],
            [],
            '',
            false
        );
        // do not use var path here, as root nodes get checked for public path as first part
        $path = Environment::getPublicPath() . '/typo3temp/tests/' . StringUtility::getUniqueId('dir_');
        GeneralUtility::mkdir_deep($path);
        $this->testFilesToDelete[] = $path;
        $node->expects(self::any())->method('getAbsolutePath')->willReturn($path);
        $node->expects(self::any())->method('exists')->willReturn(true);
        $node->expects(self::any())->method('isDirectory')->willReturn(true);
        $node->expects(self::any())->method('isPermissionCorrect')->willReturn(true);
        $node->expects(self::any())->method('isWritable')->willReturn(true);
        $childStatus = new FlashMessage('foo', '', FlashMessage::ERROR);
        $node->expects(self::once())->method('getChildrenStatus')->willReturn([$childStatus]);
        $statusArray = $node->getStatus();
        $statusSelf = $statusArray[0];
        $statusOfChild = $statusArray[1];
        self::assertSame(FlashMessage::OK, $statusSelf->getSeverity());
        self::assertSame($childStatus, $statusOfChild);
    }

    /**
     * @test
     */
    public function getAbsolutePathReturnsGivenName()
    {
        /** @var $node RootNode|AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $node = $this->getAccessibleMock(RootNode::class, ['isWindowsOs'], [], '', false);
        $node
            ->expects(self::any())
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
