<?php
namespace TYPO3\CMS\Install\Controller\Action\Step;

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

use TYPO3\CMS\Install\Controller\Action;

/**
 * Interface implemented by single steps
 */
interface StepInterface
{
    /**
     * Execute a step
     *
     * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
     */
    public function execute();

    /**
     * Whether this step must be executed
     *
     * @return bool TRUE if this step needs to be executed
     */
    public function needsExecution();

    /**
     * Tell the action which position it has in the list of actions
     *
     * @param int $current The current position
     * @param int $total The total number of steps
     */
    public function setStepsCounter($current, $total);

    /**
     * Gets current position
     *
     * @return int
     */
    public function getCurrentStep();

    /**
     * Gets total steps
     *
     * @return int
     */
    public function getTotalSteps();
}
