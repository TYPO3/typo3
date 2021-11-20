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

namespace TYPO3\CMS\Backend\Toolbar;

/**
 * Registry class for toolbar items
 * @internal
 */
class ToolbarItemsRegistry
{
    protected array $toolbarItems = [];

    public function __construct(iterable $toolbarItems)
    {
        foreach ($toolbarItems as $toolbarItem) {
            if ($toolbarItem instanceof ToolbarItemInterface) {
                $index = (int)$toolbarItem->getIndex();
                if ($index < 0 || $index > 100) {
                    throw new \RuntimeException(
                        'getIndex() must return an integer between 0 and 100',
                        1415968498
                    );
                }
                // Find next free position in array
                while (isset($this->toolbarItems[$index])) {
                    $index++;
                }
                $this->toolbarItems[$index] = $toolbarItem;
            }
        }
        ksort($this->toolbarItems);
    }

    /**
     * Get all registered toolbarItems
     *
     * @return ToolbarItemInterface[]
     */
    public function getToolbarItems(): array
    {
        return $this->toolbarItems;
    }
}
