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
use TYPO3\CMS\Core\Upgrades\ReferenceIndexUpdatedPrerequisite as CoreReferenceIndexUpdatedPrerequisite;

/**
 * @internal
 * @deprecated since v14.0, will be removed in TYPO34 v15.0. Use \TYPO3\CMS\Core\Upgrades\ReferenceIndexUpdatedPrerequisite instead.
 * @todo Make \TYPO3\CMS\Core\Upgrades\ReferenceIndexUpdatedPrerequisite with TYPO3 v15.
 * @phpstan-ignore class.extendsFinalByPhpDoc
 */
#[Autoconfigure(public: true)]
class ReferenceIndexUpdatedPrerequisite extends CoreReferenceIndexUpdatedPrerequisite {}
