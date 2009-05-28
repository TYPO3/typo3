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
 * @package Fluid
 * @subpackage Core
 * @version $Id: ParsedTemplateInterface.php 2340 2009-05-22 14:12:18Z sebastian $
 */

/**
 * This interface is returned by Tx_Fluid_Core_Parser_TemplateParser->parse() method and is a parsed template
 *
 * @package Fluid
 * @subpackage Core
 * @version $Id: ParsedTemplateInterface.php 2340 2009-05-22 14:12:18Z sebastian $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @internal
 */
interface Tx_Fluid_Core_Parser_ParsedTemplateInterface {

	/**
	 * Render the parsed template with rendering context
	 *
	 * @param Tx_Fluid_Core_Rendering_RenderingContext $renderingContext The rendering context to use
	 * @return Rendered string
	 * @internal
	 */
	public function render(Tx_Fluid_Core_Rendering_RenderingContext $renderingContext);

	/**
	 * Returns a variable container used in the PostParse Facet.
	 *
	 * @return Tx_Fluid_Core_ViewHelper_TemplateVariableContainer
	 * @internal
	 */
	// TODO
	public function getVariableContainer(); // rename to getPostParseVariableContainer -- @internal definitely
}

?>