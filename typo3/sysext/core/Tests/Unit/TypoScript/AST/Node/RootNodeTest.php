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

class RootNodeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getIdentifierThrowsExceptionIfNotIdentifierHasBeenSet(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1674620169);
        (new RootNode())->getIdentifier();
    }

    /**
     * @test
     */
    public function setIdentifierCreatesIdentifierString(): void
    {
        $rootNode = new RootNode();
        $rootNode->setIdentifier('testing');
        self::assertSame('5c638577a9858bb2', $rootNode->getIdentifier());
    }

    /**
     * @test
     */
    public function setIdentifierTriggersIdentifierCalculationForChild(): void
    {
        $rootNode = new RootNode();
        $childNode = new ChildNode('child');
        $rootNode->addChild($childNode);
        $referenceChildNode = new ReferenceChildNode('referenceChild');
        $rootNode->addChild($referenceChildNode);
        $rootNode->setIdentifier('testing1');
        self::assertSame('341005f4ad49cdec', $rootNode->getIdentifier());
        self::assertSame('36b65985c908c6ca', $childNode->getIdentifier());
        self::assertSame('8f99e21e850e6fac', $referenceChildNode->getIdentifier());
        // Update rootNode identifier to verify child identifiers change
        $rootNode->setIdentifier('testing2');
        self::assertSame('df6c9d843ccc5d0a', $rootNode->getIdentifier());
        self::assertSame('2233b835e0a2f7a7', $childNode->getIdentifier());
        self::assertSame('b1444e428a66c062', $referenceChildNode->getIdentifier());
    }
}
