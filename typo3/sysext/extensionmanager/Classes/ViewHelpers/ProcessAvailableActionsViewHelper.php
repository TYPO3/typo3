<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Xavier Perseguers <xavier@typo3.org>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * View helper to let 3rd-party extensions process the list of available
 * actions for a given extension.
 *
 * @author Xavier Perseguers <xavier@typo3.org>
 */
class ProcessAvailableActionsViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Link\ActionViewHelper {

	const SIGNAL_ProcessActions = 'processActions';

	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 * @inject
	 */
	protected $signalSlotDispatcher;

	/**
	 * Processes the list of actions.
	 *
	 * @param string $extension
	 * @return string the rendered list of actions
	 */
	public function render($extension) {
		$html = $this->renderChildren();
		$actions = preg_split('#\\n\\s*#s', trim($html));

		$this->signalSlotDispatcher->dispatch(
			__CLASS__,
			static::SIGNAL_ProcessActions,
			array(
				'extension' => $extension,
				'actions' => &$actions,
			)
		);

		return implode(' ', $actions);
	}

}
