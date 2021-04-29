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

namespace TYPO3\CMS\Belog\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository for workspaces
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class WorkspaceRepository extends Repository
{
    public ?QuerySettingsInterface $querySettings = null;

    /**
     * @param QuerySettingsInterface $querySettings
     */
    public function injectQuerySettings(QuerySettingsInterface $querySettings): void
    {
        $this->querySettings = $querySettings;
    }

    /**
     * Initializes the repository.
     */
    public function initializeObject(): void
    {
        $this->setDefaultQuerySettings($this->querySettings->setRespectStoragePage(false));
    }
}
