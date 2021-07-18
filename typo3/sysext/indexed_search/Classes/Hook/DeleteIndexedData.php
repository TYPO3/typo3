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

namespace TYPO3\CMS\IndexedSearch\Hook;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\IndexedSearch\Domain\Repository\AdministrationRepository;

/**
 * @internal This class is a hook implementation and is not part of the TYPO3 Core API.
 */
class DeleteIndexedData
{
    public function delete(array $params): void
    {
        if ($this->isEnabled()) {
            $administrationRepository = GeneralUtility::makeInstance(AdministrationRepository::class);

            $pageIds = $params['pageIdArray'] ?? [];
            foreach ($pageIds as $pageId) {
                $administrationRepository->removeIndexedPhashRow('ALL', $pageId, 0);
            }
        }
    }

    protected function isEnabled(): bool
    {
        try {
            return (bool)GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('indexed_search', 'deleteFromIndexAfterEditing');
        } catch (\Exception $e) {
            // do nothing
        }
        return false;
    }
}
