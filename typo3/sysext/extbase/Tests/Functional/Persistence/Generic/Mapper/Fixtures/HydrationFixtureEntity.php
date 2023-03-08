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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence\Generic\Mapper\Fixtures;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class HydrationFixtureEntity extends AbstractEntity
{
    public function __construct()
    {
        // Used to verify __construct() *is not* called by DataMapper when hydrating.
        throw new \RuntimeException('constructor called', 1680071490);
    }

    public function initializeObject(): void
    {
        // Used to verify initializeObject() *is* called by DataMapper when hydrating.
        throw new \RuntimeException('initializeObject called', 1680071491);
    }
}
