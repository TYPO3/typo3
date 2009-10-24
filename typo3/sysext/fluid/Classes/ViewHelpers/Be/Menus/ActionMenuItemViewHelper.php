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
 * View helper which returns a option tag.
 * This view helper only works in conjunction with Tx_Fluid_ViewHelpers_Be_Menus_ActionMenuViewHelper
 * Note: This view helper is experimental!
 *
 *
 * @package     Fluid
 * @subpackage  ViewHelpers\Be\Menus
 * @author      Steffen Kamper <info@sk-typo3.de>
 * @author      Bastian Waidelich <bastian@typo3.org>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id:
 */
class Tx_Fluid_ViewHelpers_Be_Menus_ActionMenuItemViewHelper extends Tx_Fluid_Core_ViewHelper_TagBasedViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'option';

	/**
	 * Renders an ActionMenu option tag
	 *
	 * @param string $label label of the option tag
	 * @param string $controller controller to be associated with this ActionMenuItem
	 * @param string $action the action to be associated with this ActionMenuItem
	 * @param array $arguments additional controller arguments to be passed to the action when this ActionMenuItem is selected
	 * @return string the rendered option tag
	 * @see Tx_Fluid_ViewHelpers_Be_Menus_ActionMenuViewHelper
	 */
	public function render($label, $controller, $action, array $arguments = array()) {
		$uriBuilder = $this->controllerContext->getUriBuilder();
		$uri = $uriBuilder
			->reset()
			->uriFor($action, $arguments, $controller);
		$this->tag->addAttribute('value', $uri);

		$currentRequest = $this->controllerContext->getRequest();
		$currentController = $currentRequest->getControllerName();
		$currentAction = $currentRequest->getControllerActionName();
		if ($action === $currentAction && $controller === $currentController) {
			$this->tag->addAttribute('selected', TRUE);
		}

		$this->tag->setContent($label);

		return $this->tag->render();
	}
}
?>
