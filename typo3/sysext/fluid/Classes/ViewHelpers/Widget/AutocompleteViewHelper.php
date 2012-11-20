<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Widget;

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
 * Simple paginate widget
 * Note: Make sure to include jQuery and jQuery UI in the HTML, like that:
 * <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
 * <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.4/jquery-ui.min.js"></script>
 * <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.3/themes/base/jquery-ui.css" type="text/css" media="all" />
 * <link rel="stylesheet" href="http://static.jquery.com/ui/css/demo-docs-theme/ui.theme.css" type="text/css" media="all" />
 * You can include the provided TS template that includes the above snippet to the pages headerData.
 *
 * = Examples =
 *
 * <code title="Render lib object">
 * <input type="text" id="name" />
 * <f:widget.autocomplete for="name" objects="{posts}" searchProperty="author">
 * </code>
 * <output>
 * <input type="text" id="name" />
 * the input field and the required JavaScript for the Ajax communication (see Resources/Private/Templates/ViewHelpers/Widget/Autocomplete/Index.html
 * </output>
 *
 * @api
 */
class AutocompleteViewHelper extends \TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper {

	/**
	 * @var bool
	 */
	protected $ajaxWidget = TRUE;

	/**
	 * @var \TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\AutocompleteController
	 */
	protected $controller;

	/**
	 * @param \TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\AutocompleteController $controller
	 * @return void
	 */
	public function injectController(\TYPO3\CMS\Fluid\ViewHelpers\Widget\Controller\AutocompleteController $controller) {
		$this->controller = $controller;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult $objects
	 * @param string $for
	 * @param string $searchProperty
	 * @return string
	 */
	public function render(\TYPO3\CMS\Extbase\Persistence\Generic\QueryResult $objects, $for, $searchProperty) {
		return $this->initiateSubRequest();
	}
}

?>