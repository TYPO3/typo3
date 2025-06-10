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

use TYPO3\CMS\Core\Upgrades\AbstractListTypeToCTypeUpdate as CoreImplementation;

/**
 * This class can be extended by 3rd party extensions to easily add a custom
 * `list_type` to `CType` update for deprecated "plugin" content element usages.
 *
 * @since 13.4
 * @deprecated since v14.0, will be removed in TYPO34 v15.0. Use \TYPO3\CMS\Core\Upgrades\AbstractListTypeToCTypeUpdate instead.
 */
abstract class AbstractListTypeToCTypeUpdate extends CoreImplementation {}
