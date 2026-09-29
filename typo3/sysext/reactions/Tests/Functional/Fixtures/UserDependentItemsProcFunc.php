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

namespace TYPO3\CMS\Reactions\Tests\Functional\Fixtures;

/**
 * Offers an item to one backend user only, read from $GLOBALS['BE_USER'] the way item
 * processors commonly do.
 */
final class UserDependentItemsProcFunc
{
    public function addItemForGrantedEditor(array &$params): void
    {
        if (($GLOBALS['BE_USER']->user['username'] ?? '') === 'granted_editor') {
            $params['items'][] = ['label' => 'Granted editors only', 'value' => '42'];
        }
    }
}
