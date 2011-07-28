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
 * This interface is returned by Tx_Fluid_Core_Parser_TemplateParser->parse()
 * method and is a parsed template
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface Tx_Fluid_Core_Parser_ParsedTemplateInterface {

	/**
	 * Render the parsed template with rendering context
	 *
	 * @param Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext The rendering context to use
	 * @return Rendered string
	 */
	public function render(Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext);

	/**
	 * Returns a variable container used in the PostParse Facet.
	 *
	 * @return Tx_Fluid_Core_ViewHelper_TemplateVariableContainer
	 */
	// TODO remove
	public function getVariableContainer();

	/**
	 * Returns the name of the layout that is defined within the current template via <f:layout name="..." />
	 * If no layout is defined, this returns NULL
	 * This requires the current rendering context in order to be able to evaluate the layout name
	 *
	 * @param Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext
	 * @return string
	 */
	public function getLayoutName(Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext);

	/**
	 * Returns TRUE if the current template has a template defined via <f:layout name="..." />
	 * @see getLayoutName()
	 *
	 * @return boolean
	 */
	public function hasLayout();

	/**
	 * If the template contains constructs which prevent the compiler from compiling the template
	 * correctly, isCompilable() will return FALSE.
	 *
	 * @return boolean TRUE if the template can be compiled
	 * @internal
	 */
	public function isCompilable();

	/**
	 * @return boolean TRUE if the template is already compiled, FALSE otherwise
	 * @internal
	 */
	public function isCompiled();
}

?>