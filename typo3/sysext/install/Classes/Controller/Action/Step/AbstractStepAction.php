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

/**
 * General purpose Step controller action
 */
abstract class AbstractStepAction extends \TYPO3\CMS\Install\Controller\Action\AbstractAction implements StepInterface
{
    /**
     * @var int Current step position
     */
    protected $currentStep = 0;

    /**
     * @var int Total number of available steps
     */
    protected $totalSteps = 0;

    /**
     * Tell the action which position it has in the list of actions
     *
     * @param int $current The current position
     * @param int $total The total number of steps
     * @return void
     */
    public function setStepsCounter($current, $total)
    {
        $this->currentStep = $current;
        $this->totalSteps = $total;
    }

    /**
     * Gets current position
     *
     * @return int
     */
    public function getCurrentStep()
    {
        return $this->currentStep;
    }

    /**
     * Gets total steps
     *
     * @return int
     */
    public function getTotalSteps()
    {
        return $this->totalSteps;
    }

    /**
     * @return void
     */
    protected function assignSteps()
    {
        $steps = [];
        $currentStep = $this->getCurrentStep();
        $totalSteps = $this->getTotalSteps();
        for ($i = 1; $i <= $totalSteps; $i++) {
            $class = '';
            if ($i == $currentStep) {
                $class = 'cur';
            } elseif ($i < $currentStep) {
                $class = 'prev';
            }
            $steps[] = [
                'number' => $i,
                'class' => $class,
                'total' => $totalSteps,
                'percent' => floor((100 * $i) / $totalSteps)
            ];
        }
        $this->view->assign('steps', $steps);
        $this->view->assign('currentStep', $steps[$currentStep-1]);
    }
}
