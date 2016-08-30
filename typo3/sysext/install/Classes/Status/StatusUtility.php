<?php
namespace TYPO3\CMS\Install\Status;

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

/**
 * Utility methods to handle status objects. Provides some helper
 * methods to filter, sort and render status objects.
 */
class StatusUtility
{
    /**
     * Order status objects by severity
     *
     * @param array<\TYPO3\CMS\Install\Status\StatusInterface> $statusObjects Status objects in random order
     * @return array With sub arrays by severity
     * @throws Exception
     */
    public function sortBySeverity(array $statusObjects = [])
    {
        $orderedStatus = [
            'alert' => $this->filterBySeverity($statusObjects, 'alert'),
            'error' => $this->filterBySeverity($statusObjects, 'error'),
            'warning' => $this->filterBySeverity($statusObjects, 'warning'),
            'ok' => $this->filterBySeverity($statusObjects, 'ok'),
            'information' => $this->filterBySeverity($statusObjects, 'information'),
            'notice' => $this->filterBySeverity($statusObjects, 'notice'),
        ];
        return $orderedStatus;
    }

    /**
     * Filter a list of status objects by severity
     *
     * @param array $statusObjects Given list of status objects
     * @param string $severity Severity identifier
     * @throws Exception
     * @return array List of status objects with given severity
     */
    public function filterBySeverity(array $statusObjects = [], $severity = 'ok')
    {
        $filteredObjects = [];
        /** @var $status StatusInterface */
        foreach ($statusObjects as $status) {
            if (!$status instanceof StatusInterface) {
                throw new Exception(
                    'Object must implement StatusInterface',
                    1366919442
                );
            }
            if ($status->getSeverity() === $severity) {
                $filteredObjects[] = $status;
            }
        }
        return $filteredObjects;
    }
}
