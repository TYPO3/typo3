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

namespace TYPO3\CMS\Core\Tests\Unit\DataHandling\Fixtures;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\DataHandlerCheckModifyAccessListHookInterface;

/**
 * Fixture hook allow access
 */
class AllowAccessHookFixture implements DataHandlerCheckModifyAccessListHookInterface
{
    /**
     *  Check modify access list
     *
     * @param bool $accessAllowed
     * @param string $table
     * @param DataHandler $parent
     */
    public function checkModifyAccessList(&$accessAllowed, $table, DataHandler $parent): void
    {
        $accessAllowed = true;
    }
}
