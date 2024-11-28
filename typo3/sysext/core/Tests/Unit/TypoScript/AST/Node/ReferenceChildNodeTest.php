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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\ReferenceChildNode;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ReferenceChildNodeTest extends UnitTestCase
{
    #[Test]
    public function getIdentifierThrowsExceptionIfNotIdentifierHasBeenSet(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1674620169);
        (new ReferenceChildNode('someName'))->getIdentifier();
    }

    #[Test]
    public function setIdentifierCreatesIdentifierString(): void
    {
        $rootNode = new ReferenceChildNode('someName');
        $rootNode->setIdentifier('testing');
        self::assertNotEmpty($rootNode->getIdentifier());
    }

    #[Test]
    public function setIdentifierTriggersIdentifierCalculationForChild(): void
    {
        $node = new ReferenceChildNode('someName');
        $childNode = new ChildNode('child');
        $node->addChild($childNode);
        $referenceChildNode = new ReferenceChildNode('referenceChild');
        $node->addChild($referenceChildNode);
        $node->setIdentifier('testing1');
        self::assertSame('341005f4ad49cdec', $node->getIdentifier());
        self::assertSame('8fd35cb34a348554', $childNode->getIdentifier());
        self::assertSame('96d0197da8ad1760', $referenceChildNode->getIdentifier());
        // Update rootNode identifier to verify child identifiers change
        $node->setIdentifier('testing2');
        self::assertSame('df6c9d843ccc5d0a', $node->getIdentifier());
        self::assertSame('aee30d139766c0c3', $childNode->getIdentifier());
        self::assertSame('3bba59e44d03ca84', $referenceChildNode->getIdentifier());
    }
}
