<?php
/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * View helper which renders the FlashMessageQueu
 *
 * = Examples =
 *
 * <code title="Minimal">
 * <f:be.flashMessageQueue />
 * </code>
 *
 * Output:
 * All FlashMessages which were registered using t3lib_FlashMessageQueue::addMessage($message);
 *
 * @author      Tolleiv Nietsch <info@tolleiv.de>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id:
 *
 */

/**
 * ViewHelper to integrate the backend FlashMessageQueue into our module
 *
 * @todo Remove this viewHelper once http://forge.typo3.org/issues/10821 is available
 */
class Tx_Workspaces_ViewHelpers_TempFlashMessageQueueViewHelper extends Tx_Fluid_ViewHelpers_Be_AbstractBackendViewHelper {


	/**
	 * Renders a shortcut button as known from the TYPO3 backend
	 *
	 * @return string the rendered flashMessage
	 * @see template::makeShortcutIcon()
	 */
	public function render() {

		$renderedMessages = '';
		$flashMessages = t3lib_FlashMessageQueue::renderFlashMessages();
		if (!empty($flashMessages)) {
			$renderedMessages = '<div id="typo3-messages">' . $flashMessages . '</div>';
		}

		return $renderedMessages;
	}
}
?>