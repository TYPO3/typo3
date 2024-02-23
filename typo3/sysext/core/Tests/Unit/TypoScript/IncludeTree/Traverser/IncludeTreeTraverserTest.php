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

namespace TYPO3\CMS\Core\Tests\Unit\TypoScript\IncludeTree\Traverser;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\RootInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\IncludeTreeTraverser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class IncludeTreeTraverserTest extends UnitTestCase
{
    #[Test]
    public function traverseThrowsExceptionWithVisitorNotImplementingInterface(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1689244841);
        // @phpstan-ignore-next-line
        (new IncludeTreeTraverser())->traverse(new RootInclude(), [new \stdClass()]);
    }
}
