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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\IrreForeignField\InlineLocalizeSynchronize\Fixtures;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\DataHandlerCheckModifyAccessListHookInterface;

/**
 * Denies modifying the table set in $deniedTable, like for a user without
 * permission to modify it.
 */
final class DenyModifyAccessToTable implements DataHandlerCheckModifyAccessListHookInterface
{
    public static string $deniedTable = '';

    public function checkModifyAccessList(&$accessAllowed, $table, DataHandler $parent): void
    {
        if ($table === self::$deniedTable) {
            $accessAllowed = false;
        }
    }
}
