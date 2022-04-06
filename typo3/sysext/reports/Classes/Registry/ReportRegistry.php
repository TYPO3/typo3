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

use TYPO3\CMS\Reports\ReportInterface;

/**
 * Registry for report providers. The registry receives all services, tagged with "reports.report".
 * The tagging of status providers is automatically done based on the implemented ReportInterface.
 *
 * @internal
 */
class ReportRegistry
{
    /**
     * @var ReportInterface[]
     */
    private array $providers = [];

    /**
     * @param iterable<ReportInterface> $providers
     */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $item) {
            $this->providers[$item->getIdentifier()] = $item;
        }
    }

    /**
     * Get all registered reports
     *
     * @return ReportInterface[]
     */
    public function getReports(): array
    {
        return $this->providers;
    }

    /**
     * Whether a registered report exists for the identifier
     */
    public function hasReport(string $identifier): bool
    {
        return isset($this->providers[$identifier]);
    }

    /**
     * Get registered report by identifier
     */
    public function getReport(string $identifier): ReportInterface
    {
        if (!$this->hasReport($identifier)) {
            throw new \UnexpectedValueException('Report with identifier ' . $identifier . ' is not registered.', 1647241087);
        }

        return $this->providers[$identifier];
    }
}
