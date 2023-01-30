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

namespace TYPO3\CMS\Core\Tests\Unit\TypoScript\IncludeTree\IncludeNode;

use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\RootInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\StringInclude;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RootNodeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getIdentifierThrowsExceptionIfNotIdentifierHasBeenSet(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1673634853);
        (new RootInclude())->getIdentifier();
    }

    /**
     * @test
     */
    public function setIdentifierCreatesIdentifierString(): void
    {
        $rootNode = new RootInclude();
        $rootNode->setIdentifier('testing');
        self::assertSame('5c638577a9858bb2', $rootNode->getIdentifier());
    }

    /**
     * @test
     */
    public function setIdentifierTriggersIdentifierCalculationForChild(): void
    {
        $rootNode = new RootInclude();
        $childNode = new StringInclude();
        $rootNode->addChild($childNode);
        $childNode2 = new StringInclude();
        $rootNode->addChild($childNode2);
        $rootNode->setIdentifier('testing');
        self::assertSame('5c638577a9858bb2', $rootNode->getIdentifier());
        self::assertSame('32461a3cf2fd1b37', $childNode->getIdentifier());
        self::assertSame('915fb7c57d95d83c', $childNode2->getIdentifier());
        // Update rootNode identifier to verify child identifiers change
        $rootNode->setIdentifier('testing1');
        self::assertSame('341005f4ad49cdec', $rootNode->getIdentifier());
        self::assertSame('8fd35cb34a348554', $childNode->getIdentifier());
        self::assertSame('96d0197da8ad1760', $childNode2->getIdentifier());
    }
}
