<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Scheduler\Task\Enumeration;

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

use TYPO3\CMS\Core\Type\Enumeration;

/**
 * List of possible scheduler actions
 */
final class Action extends Enumeration
{
    const __default = self::LIST;
    const ADD = 'add';
    const DELETE = 'delete';
    const EDIT = 'edit';
    const LIST = 'list';
    const SAVE = 'save';
    const SAVE_CLOSE = 'saveclose';
    const SAVE_NEW = 'savenew';
    const SET_NEXT_EXECUTION_TIME = 'setNextExecutionTime';
    const STOP = 'stop';
    const TOGGLE_HIDDEN = 'toggleHidden';
}
