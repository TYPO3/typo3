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

namespace TYPO3\CMS\Core\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Registry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RegistryTest extends UnitTestCase
{
    #[Test]
    public function getThrowsExceptionForInvalidNamespacesUsingNoNamespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1249755131);
        (new Registry())->get('', 'someKey');
    }

    #[Test]
    public function getThrowsExceptionForInvalidNamespacesUsingTooShortNamespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1249755131);
        (new Registry())->get('t', 'someKey');
    }

    #[Test]
    public function setThrowsAnExceptionOnEmptyNamespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1249755131);
        (new Registry())->set('', 'someKey', 'someValue');
    }

    #[Test]
    public function setThrowsAnExceptionOnWrongNamespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1249755131);
        (new Registry())->set('t', 'someKey', 'someValue');
    }

    #[Test]
    public function removeThrowsAnExceptionOnWrongNamespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1249755131);
        (new Registry())->remove('t', 'someKey');
    }

    #[Test]
    public function removeAllByNamespaceThrowsAnExceptionOnWrongNamespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1249755131);
        (new Registry())->removeAllByNamespace('');
    }
}
