<?php
namespace TYPO3\CMS\Install\Controller\Action\Tool;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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

use TYPO3\CMS\Install\Controller\Action;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Show configuration sets
 */
class Configuration extends Action\AbstractAction implements Action\ActionInterface {

	/**
	 * @var \TYPO3\CMS\Install\Configuration\FeatureManager
	 * @inject
	 */
	protected $featureManager;

	/**
	 * Handle this action
	 *
	 * @return string content
	 */
	public function handle() {
		$this->initialize();

		/*
		$actionMessages = array();
		if (isset($this->postValues['set']['activateFeature'])) {
			$actionMessages[] = $this->activateFeature();
		}
		$this->view->assign('actionMessages', $actionMessages);
		*/

		$this->view->assign('features', $this->featureManager->getFeatures());

		return $this->view->render();
	}

	/**
	protected function activateFeature() {
		$this->slotFeatureManager->activateSlotFeature($this->postValues['values']['slot'], $this->postValues['values']['feature']);
	}
	 */
}
?>
