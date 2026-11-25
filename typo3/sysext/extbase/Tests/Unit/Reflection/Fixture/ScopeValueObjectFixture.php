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

namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture;

/**
 * Fixture with properties of different visibility and initialization state
 * as well as a hooked and a virtual property
 */
final class ScopeValueObjectFixture extends ParentScopeValueObjectFixture
{
    public string $ownPublic = 'ownPublic';
    protected string $ownProtected = 'ownProtected';
    private string $ownPrivate = 'ownPrivate';
    public string $uninitialized;
    public string $hooked = 'raw' { get => strtoupper($this->hooked); }
    public string $virtual { get => 'virtual'; }

    public function getOwnPrivate(): string
    {
        return $this->ownPrivate;
    }
}
