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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite as CoreDatabaseUpdatedPrerequisite;

/**
 * Prerequisite for upgrade wizards to ensure the database is up-to-date
 *
 * @internal
 * @deprecated since v14.0, will be removed in TYPO34 v15.0. Use \TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite instead.
 * @todo Make {@see CoreDatabaseUpdatedPrerequisite} final in TYPO3 v15.
 * @phpstan-ignore class.extendsFinalByPhpDoc
 */
#[Autoconfigure(public: true)]
class DatabaseUpdatedPrerequisite extends CoreDatabaseUpdatedPrerequisite {}
