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

namespace TYPO3\CMS\Install\Updates;

use TYPO3\CMS\Install\Attribute\UpgradeWizard;

/**
 * @since 13.0
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('indexedSearchCTypeMigration')]
final class IndexedSearchCTypeMigration extends AbstractListTypeToCTypeUpdate
{
    protected function getListTypeToCTypeMapping(): array
    {
        return ['indexedsearch_pi2' => 'indexedsearch_pi2'];
    }

    public function getTitle(): string
    {
        return 'Migrate "Indexed Search" plugins to content elements.';
    }

    public function getDescription(): string
    {
        return 'The "Indexed Search" plugin is now registered as content element. Update migrates existing records and backend user permissions.';
    }
}
