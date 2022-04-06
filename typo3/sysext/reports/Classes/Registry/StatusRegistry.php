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

namespace TYPO3\CMS\Reports\Registry;

use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Registry for status providers. The registry receives all services, tagged with "reports.status".
 * The tagging of status providers is automatically done based on the implemented StatusProviderInterface.
 *
 * @internal
 */
class StatusRegistry
{
    /**
     * @var StatusProviderInterface[]
     */
    private array $providers = [];

    /**
     * @param iterable<StatusProviderInterface> $providers
     */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $item) {
            $this->providers[] = $item;
        }
    }

    /**
     * Get all registered status providers
     *
     * @return StatusProviderInterface[]
     */
    public function getProviders(): array
    {
        return $this->providers;
    }
}
