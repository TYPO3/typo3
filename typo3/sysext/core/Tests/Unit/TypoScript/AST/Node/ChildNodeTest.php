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

namespace TYPO3\CMS\Core\Tests\Unit\TypoScript\AST\Node;

use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\ReferenceChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ChildNodeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getIdentifierThrowsExceptionIfNotIdentifierHasBeenSet(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1674620169);
        (new ChildNode('someName'))->getIdentifier();
    }

    /**
     * @test
     */
    public function setIdentifierCreatesIdentifierString(): void
    {
        $node = new ChildNode('someName');
        $node->setIdentifier('testing');
        self::assertIsString($node->getIdentifier());
    }

    /**
     * @test
     */
    public function setIdentifierTriggersIdentifierCalculationForChild(): void
    {
        $node = new ChildNode('someName');
        $childNode = new ChildNode('child');
        $node->addChild($childNode);
        $referenceChildNode = new ReferenceChildNode('referenceChild');
        $node->addChild($referenceChildNode);
        $node->setIdentifier('testing1');
        self::assertSame('a938d0f2f9b8d3ae', $node->getIdentifier());
        self::assertSame('e79dcb87f1a23701', $childNode->getIdentifier());
        self::assertSame('a5690684acd44697', $referenceChildNode->getIdentifier());
        // Update rootNode identifier to verify child identifiers change
        $node->setIdentifier('testing2');
        self::assertSame('9432340ddb8d76f8', $node->getIdentifier());
        self::assertSame('8697b1591b1fc4a1', $childNode->getIdentifier());
        self::assertSame('38583b9e9ea973fc', $referenceChildNode->getIdentifier());
    }
}
