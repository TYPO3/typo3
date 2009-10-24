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
 * View helper which returns a select box, that can be used to switch between
 * multiple actions and controllers and looks similar to TYPO3s funcMenu.
 * Note: This view helper is experimental!
 *
 * = Examples =
 *
 * <code title="Simple">
 * <f:be.menus.actionMenu>
 *   <f:be.menus.actionMenuItem label="Overview" controller="Blog" action="index" />
 *   <f:be.menus.actionMenuItem label="Create new Blog" controller="Blog" action="new" />
 *   <f:be.menus.actionMenuItem label="List Posts" controller="Post" action="index" arguments="{blog: blog}" />
 * </f:be.menus.actionMenu>
 * </code>
 *
 * Output:
 * Selectbox with the options "Overview", "Create new Blog" and "List Posts"
 *
 * <code title="Localized">
 * <f:be.menus.actionMenu>
 *   <f:be.menus.actionMenuItem label="{f:translate(key='overview')}" controller="Blog" action="index" />
 *   <f:be.menus.actionMenuItem label="{f:translate(key='create_blog')}" controller="Blog" action="new" />
 * </f:be.menus.actionMenu>
 * </code>
 *
 * @package     Fluid
 * @subpackage  ViewHelpers\Be\Menus
 * @author      Steffen Kamper <info@sk-typo3.de>
 * @author      Bastian Waidelich <bastian@typo3.org>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id:
 *
 */
class Tx_Fluid_ViewHelpers_Be_Menus_ActionMenuViewHelper extends Tx_Fluid_Core_ViewHelper_TagBasedViewHelper implements Tx_Fluid_Core_ViewHelper_Facets_ChildNodeAccessInterface {

	/**
	 * @var string
	 */
	protected $tagName = 'select';

	/**
	 * An array of Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode
	 * @var array
	 */
	protected $childNodes = array();

	/**
	 * @var Tx_Fluid_Core_Rendering_RenderingContext
	 */
	protected $renderingContext;

	/**
	 * Setter for ChildNodes - as defined in ChildNodeAccessInterface
	 *
	 * @param array $childNodes Child nodes of this syntax tree node
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function setChildNodes(array $childNodes) {
		$this->childNodes = $childNodes;
	}

	/**
	 * Sets the rendering context which needs to be passed on to child nodes
	 *
	 * @param Tx_Fluid_Core_Rendering_RenderingContext $renderingContext the renderingcontext to use
	 */
	public function setRenderingContext(Tx_Fluid_Core_Rendering_RenderingContext $renderingContext) {
		$this->renderingContext = $renderingContext;
	}

	/**
	 * Render FunctionMenu
	 *
	 * @param string $defaultController
	 * @return string
	 */
	public function render($defaultController = NULL) {
		$this->tag->addAttribute('onchange', 'jumpToUrl(this.options[this.selectedIndex].value, this);');
		$options = '';
		foreach ($this->childNodes as $childNode) {
			if ($childNode instanceof Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode) {
				$childNode->setRenderingContext($this->renderingContext);
				$options .= $childNode->evaluate();
			}
		}
		$this->tag->setContent($options);
		return '<div class="docheader-funcmenu">' . $this->tag->render() . '</div>';
	}
}
?>
