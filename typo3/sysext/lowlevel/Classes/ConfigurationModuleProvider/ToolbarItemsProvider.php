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

namespace TYPO3\CMS\Lowlevel\ConfigurationModuleProvider;

use TYPO3\CMS\Backend\Toolbar\ToolbarItemsRegistry;

class ToolbarItemsProvider extends AbstractProvider
{
    public function __construct(protected readonly ToolbarItemsRegistry $toolbarItemsRegistry) {}

    public function getConfiguration(): array
    {
        $configuration = [];
        foreach ($this->toolbarItemsRegistry->getToolbarItems() as $item) {
            $configuration[get_class($item)] = [
                'index' => $item->getIndex(),
                'hasDropdown' => $item->hasDropDown(),
            ];
        }
        return $configuration;
    }
}
