<?php
namespace TYPO3\CMS\Install\Controller\Action\Step;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Markus Klein <klein.t3@mfc-linz.at>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * General purpose Step controller action
 */
abstract class AbstractStepAction extends \TYPO3\CMS\Install\Controller\Action\AbstractAction implements StepInterface {

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
	public function setStepsCounter($current, $total) {
		$this->currentStep = $current;
		$this->totalSteps = $total;
	}

	/**
	 * Gets current position
	 *
	 * @return int
	 */
	public function getCurrentStep() {
		return $this->currentStep;
	}

	/**
	 * Gets total steps
	 *
	 * @return int
	 */
	public function getTotalSteps() {
		return $this->totalSteps;
	}

	/**
	 * @return void
	 */
	protected function assignSteps() {
		$steps = array();
		$currentStep = $this->getCurrentStep();
		$totalSteps = $this->getTotalSteps();
		for ($i = 1; $i <= $totalSteps; $i++) {
			$class = '';
			if ($i == $currentStep) {
				$class = 'cur';
			} elseif ($i < $currentStep) {
				$class = 'prev';
			}
			$steps[] = array(
				'number' => $i,
				'class' => $class,
				'total' => $totalSteps,
			);
		}
		$this->view->assign('steps', $steps);
	}
}
