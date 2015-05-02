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

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * View helper which returns a option tag.
 * This view helper only works in conjunction with \TYPO3\CMS\Fluid\ViewHelpers\Be\Menus\ActionMenuViewHelper
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
class ActionMenuItemViewHelper extends AbstractTagBasedViewHelper implements CompilableInterface {

	/**
	 * Renders an ActionMenu option tag
	 *
	 * @param string $label label of the option tag
	 * @param string $controller controller to be associated with this ActionMenuItem
	 * @param string $action the action to be associated with this ActionMenuItem
	 * @param array $arguments additional controller arguments to be passed to the action when this ActionMenuItem is
	 *     selected
	 * @return string the rendered option tag
	 * @see \TYPO3\CMS\Fluid\ViewHelpers\Be\Menus\ActionMenuViewHelper
	 */
	public function render($label, $controller, $action, array $arguments = array()) {
		return self::renderStatic(
			array(
				'label' => $label,
				'controller' => $controller,
				'action' => $action,
				'arguments' => $arguments
			),
			$this->buildRenderChildrenClosure(),
			$this->renderingContext
		);
	}

	/**
	 * Render static for more speed in fluid
	 *
	 * @param array $arguments
	 * @param callable $renderChildrenClosure
	 * @param RenderingContextInterface $renderingContext
	 * @return string
	 */
	static public function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext) {
		$label = $arguments['label'];
		$controller = $arguments['controller'];
		$action = $arguments['action'];
		$arguments = $arguments['arguments'];

		$tag = static::getTagBuilder();
		$tag->setTagName('option');
		$uriBuilder = $renderingContext->getControllerContext()->getUriBuilder();
		$uri = $uriBuilder->reset()->uriFor($action, $arguments, $controller);

		$tag->addAttribute('value', $uri);
		$currentRequest = $renderingContext->getControllerContext()->getRequest();
		$currentController = $currentRequest->getControllerName();
		$currentAction = $currentRequest->getControllerActionName();
		if ($action === $currentAction && $controller === $currentController) {
			$tag->addAttribute('selected', 'selected');
		}
		$tag->setContent($label);
		return $tag->render();
	}


}
