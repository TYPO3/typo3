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

namespace TYPO3\CMS\Core\Tests\Unit\TypoScript\AST\CurrentObjectPath;

use TYPO3\CMS\Core\TypoScript\AST\CurrentObjectPath\CurrentObjectPath;
use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CurrentObjectPathTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getAllReturnsPathArray()
    {
        $firstNode = new ChildNode('foo');
        $currentObjectPath = new CurrentObjectPath($firstNode);
        $secondNode = new ChildNode('bar');
        $currentObjectPath->append($secondNode);
        self::assertSame([$firstNode, $secondNode], $currentObjectPath->getAll());
    }

    /**
     * @test
     */
    public function getPathAsStringReturnsPath()
    {
        $currentObjectPath = new CurrentObjectPath(new RootNode());
        $currentObjectPath->append(new ChildNode('foo'));
        $currentObjectPath->append(new ChildNode('bar'));
        self::assertSame('foo.bar', $currentObjectPath->getPathAsString());
    }

    /**
     * @test
     */
    public function getPathAsStringReturnsQuotedPath()
    {
        $currentObjectPath = new CurrentObjectPath(new ChildNode('foo'));
        $currentObjectPath->append(new ChildNode('bar.baz'));
        self::assertSame('foo.bar\.baz', $currentObjectPath->getPathAsString());
    }

    /**
     * @test
     */
    public function getPathAsStringReturnsPathWithZero()
    {
        $currentObjectPath = new CurrentObjectPath(new ChildNode('foo'));
        $currentObjectPath->append(new ChildNode('0'));
        $currentObjectPath->append(new ChildNode('bar'));
        self::assertSame('foo.0.bar', $currentObjectPath->getPathAsString());
    }

    /**
     * @test
     */
    public function getPathAsStringThrowsWithNodeNameEmptyString()
    {
        $this->expectExceptionCode(\RuntimeException::class);
        $this->expectExceptionCode(1658578645);
        $currentObjectPath = new CurrentObjectPath(new ChildNode('foo'));
        $currentObjectPath->append(new ChildNode(''));
        $currentObjectPath->getPathAsString();
    }

    /**
     * @test
     */
    public function getFirstReturnsFirstNode()
    {
        $firstNode = new ChildNode('foo');
        $currentObjectPath = new CurrentObjectPath($firstNode);
        $secondNode = new ChildNode('bar');
        $currentObjectPath->append($secondNode);
        $thirdNode = new ChildNode('third');
        $currentObjectPath->append($thirdNode);
        self::assertSame($firstNode, $currentObjectPath->getFirst());
    }

    /**
     * @test
     */
    public function getLastReturnsLastNode()
    {
        $firstNode = new ChildNode('foo');
        $currentObjectPath = new CurrentObjectPath($firstNode);
        $secondNode = new ChildNode('bar');
        $currentObjectPath->append($secondNode);
        $thirdNode = new ChildNode('third');
        $currentObjectPath->append($thirdNode);
        self::assertSame($thirdNode, $currentObjectPath->getLast());
    }

    /**
     * @test
     */
    public function getSecondLastReturnsSecondLastNode()
    {
        $firstNode = new ChildNode('foo');
        $currentObjectPath = new CurrentObjectPath($firstNode);
        $secondNode = new ChildNode('bar');
        $currentObjectPath->append($secondNode);
        $thirdNode = new ChildNode('third');
        $currentObjectPath->append($thirdNode);
        self::assertSame($secondNode, $currentObjectPath->getSecondLast());
    }

    /**
     * @test
     */
    public function getSecondLastReturnsFirstIfThereIsOnlyOne()
    {
        $firstNode = new ChildNode('foo');
        $currentObjectPath = new CurrentObjectPath($firstNode);
        self::assertSame($firstNode, $currentObjectPath->getSecondLast());
    }

    /**
     * @test
     */
    public function removeLastRemovesLastNode()
    {
        $firstNode = new ChildNode('foo');
        $currentObjectPath = new CurrentObjectPath($firstNode);
        $secondNode = new ChildNode('bar');
        $currentObjectPath->append($secondNode);
        $thirdNode = new ChildNode('third');
        $currentObjectPath->append($thirdNode);
        $currentObjectPath->removeLast();
        self::assertSame([$firstNode, $secondNode], $currentObjectPath->getAll());
    }
}
