<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be\Menus;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
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
 * = Examples =
 *
 * <code title="Simple">
 * <f:be.menus.actionMenu>
 * <f:be.menus.actionMenuItem label="Overview" controller="Blog" action="index" />
 * <f:be.menus.actionMenuItem label="Create new Blog" controller="Blog" action="new" />
 * <f:be.menus.actionMenuItem label="List Posts" controller="Post" action="index" arguments="{blog: blog}" />
 * </f:be.menus.actionMenu>
 * </code>
 * <output>
 * Selectbox with the options "Overview", "Create new Blog" and "List Posts"
 * </output>
 *
 * <code title="Localized">
 * <f:be.menus.actionMenu>
 * <f:be.menus.actionMenuItem label="{f:translate(key='overview')}" controller="Blog" action="index" />
 * <f:be.menus.actionMenuItem label="{f:translate(key='create_blog')}" controller="Blog" action="new" />
 * </f:be.menus.actionMenu>
 * </code>
 * <output>
 * localized selectbox
 * <output>
 */
class ActionMenuItemViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper {

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
		$uri = $uriBuilder->reset()->uriFor($action, $arguments, $controller);
		$this->tag->addAttribute('value', $uri);
		$currentRequest = $this->controllerContext->getRequest();
		$currentController = $currentRequest->getControllerName();
		$currentAction = $currentRequest->getControllerActionName();
		if ($action === $currentAction && $controller === $currentController) {
			$this->tag->addAttribute('selected', 'selected');
		}
		$this->tag->setContent($label);
		return $this->tag->render();
	}
}

?>