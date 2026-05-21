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

namespace TYPO3\CMS\Frontend\Preview;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Converts the frontend user groups simulated in a preview between the
 * comma-separated "ADMCMD_simUser" query parameter and a list of group IDs.
 *
 * @internal not part of TYPO3 Core API
 */
final readonly class PreviewSimulatorCalculator
{
    /**
     * @return list<int>
     */
    public function getUserGroupIds(string $simulatedUserGroups): array
    {
        return array_values(array_unique(array_filter(
            GeneralUtility::intExplode(',', $simulatedUserGroups, true),
            static fn(int $groupId): bool => $groupId !== 0
        )));
    }

    /**
     * Merges several "ADMCMD_simUser" values, so the simulated user is
     * member of every group any of them requires.
     */
    public function mergeUserGroups(string ...$simulatedUserGroups): string
    {
        return implode(',', $this->getUserGroupIds(implode(',', $simulatedUserGroups)));
    }
}
