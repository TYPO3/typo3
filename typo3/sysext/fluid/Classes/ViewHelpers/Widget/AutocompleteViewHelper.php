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
 * Usage:
 * <f:input id="name" ... />
 * <f:widget.autocomplete for="name" objects="{posts}" searchProperty="author">
 *
 * Make sure to include jQuery and jQuery UI in the HTML, like that:
 *    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
 *    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.4/jquery-ui.min.js"></script>
 *    <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.3/themes/base/jquery-ui.css" type="text/css" media="all" />
 *    <link rel="stylesheet" href="http://static.jquery.com/ui/css/demo-docs-theme/ui.theme.css" type="text/css" media="all" />
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Widget_AutocompleteViewHelper extends Tx_Fluid_Core_Widget_AbstractWidgetViewHelper {

	/**
	 * @var bool
	 */
	protected $ajaxWidget = TRUE;

	/**
	 * @var Tx_Fluid_ViewHelpers_Widget_Controller_AutocompleteController
	 */
	protected $controller;

	/**
	 * @param Tx_Fluid_ViewHelpers_Widget_Controller_AutocompleteController $controller
	 * @return void
	 */
	public function injectController(Tx_Fluid_ViewHelpers_Widget_Controller_AutocompleteController $controller) {
		$this->controller = $controller;
	}

	/**
	 *
	 * @param Tx_Extbase_Persistence_QueryResult $objects
	 * @param string $for
	 * @param string $searchProperty
	 * @return string
	 */
	public function render(Tx_Extbase_Persistence_QueryResult $objects, $for, $searchProperty) {
		return $this->initiateSubRequest();
	}
}
?>