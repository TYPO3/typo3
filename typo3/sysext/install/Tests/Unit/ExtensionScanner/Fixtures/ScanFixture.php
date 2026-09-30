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

namespace TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Fixtures;

class ScanFixture
{
    public function aMethod(): void
    {
        // weak: the matcher sees a bare method name and never resolves the object type
        $this->aMethodToCheck();
        // strong: a fully qualified class name
        $this->consume(new RemovedFixtureClass());
    }

    public function aMethodToCheck(): void {}

    // "object" on purpose: a class type here would be a second FullyQualified node and thus
    // a second strong match for ClassNameMatcher.
    private function consume(object $anything): void {}
}
