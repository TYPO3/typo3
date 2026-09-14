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

defined('TYPO3') or die();

// All three are fields the field map offers: two single value selects and a checkbox. A
// processor that throws is put on them so a reaction mapping any one of them runs code
// that cannot compose a list of items.
$GLOBALS['TCA']['pages']['columns']['layout']['config']['itemsProcFunc']
    = \TYPO3Tests\TestThrowingItems\ThrowingItemsProcFunc::class . '->throwException';
$GLOBALS['TCA']['pages']['columns']['shortcut_mode']['config']['itemsProcFunc']
    = \TYPO3Tests\TestThrowingItems\ThrowingItemsProcFunc::class . '->throwError';
$GLOBALS['TCA']['pages']['columns']['php_tree_stop']['config']['itemsProcFunc']
    = \TYPO3Tests\TestThrowingItems\ThrowingItemsProcFunc::class . '->throwException';
