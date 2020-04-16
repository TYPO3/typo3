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

namespace TYPO3\CMS\Dashboard\Widgets;

/**
 * The dataprovider of a ListWidget, should implement this interface
 */
interface ListDataProviderInterface
{
    /**
     * Return the items to be shown. This should be an array like ['item 1', 'item 2', 'item 3']. This is a
     * real simple list of items.
     *
     * @return array
     */
    public function getItems(): array;
}
