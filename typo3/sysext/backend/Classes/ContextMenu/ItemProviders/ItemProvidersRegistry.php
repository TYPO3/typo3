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

namespace TYPO3\CMS\Backend\ContextMenu\ItemProviders;

/**
 * Registry class for context menu item provider
 *
 * @internal
 */
class ItemProvidersRegistry
{
    protected array $itemProviders = [];

    public function __construct(iterable $itemProviders)
    {
        foreach ($itemProviders as $itemProvider) {
            if ($itemProvider instanceof ProviderInterface) {
                $this->itemProviders[] = $itemProvider;
            }
        }
    }

    /**
     * Get all registered item providers
     *
     * @return ProviderInterface[]
     */
    public function getItemProviders(): array
    {
        return $this->itemProviders;
    }
}
