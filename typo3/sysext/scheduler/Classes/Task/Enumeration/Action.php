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

namespace TYPO3\CMS\Scheduler\Task\Enumeration;

use TYPO3\CMS\Core\Type\Enumeration;

/**
 * List of possible scheduler actions. Additional field providers use this.
 * Set by SchedulerModuleController.
 */
final class Action extends Enumeration
{
    public const __default = self::LIST;
    public const ADD = 'add';
    public const EDIT = 'edit';
    public const LIST = 'list';
}
