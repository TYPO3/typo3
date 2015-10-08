<?php
namespace TYPO3\CMS\Taskcenter;

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

/**
 * Interface for classes which provide a task.
 */
interface TaskInterface
{
    /**
     * Returns the content for a task
     *
     * @return string A task rendered HTML
     */
    public function getTask();

    /**
     * Returns the overview of a task
     *
     * @return string A task rendered HTML
     */
    public function getOverview();
}
