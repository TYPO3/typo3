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

namespace TYPO3\CMS\Install\Tests\Functional\Updates\Fixtures;

use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\AbstractListTypeToCTypeUpdate;

/**
 * @since 13.0
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('CustomCTypeMigration')]
final class CustomCTypeMigration extends AbstractListTypeToCTypeUpdate
{
    protected function getListTypeToCTypeMapping(): array
    {
        return [
            'someoldplugin_pi1' => 'someoldplugin_pi1',
            'someoldplugin_pi2' => 'somenewplugin_pi2',
            '42' => 'somenewplugin_pi3',
            13 => 'somenewplugin_pi4',
        ];
    }

    public function getTitle(): string
    {
        return 'Migrate "Custom CType" plugins to content elements.';
    }

    public function getDescription(): string
    {
        return 'The "Custom CType" plugin is now registered as content element. Update migrates existing records and backend user permissions.';
    }
}
