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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_ViewHelpers_Widget_Controller_AutocompleteController extends Tx_Fluid_Core_Widget_AbstractWidgetController {

	/**
	 * @return void
	 */
	public function indexAction() {
		$this->view->assign('id', $this->widgetConfiguration['for']);
	}

	/**
	 * @param string $term
	 * @return string
	 */
	public function autocompleteAction($term) {
		$searchProperty = $this->widgetConfiguration['searchProperty'];
		$query = $this->widgetConfiguration['objects']->getQuery();
		$constraint = $query->getConstraint();

		if ($constraint !== NULL) {
			$query->matching($query->logicalAnd(
				$constraint,
				$query->like($searchProperty, '%' . $term . '%', FALSE)
			));
		} else {
			$query->matching(
				$query->like($searchProperty, '%' . $term . '%', FALSE)
			);
		}

		$results = $query->execute();

		$output = array();
		foreach ($results as $singleResult) {
			$val = Tx_Extbase_Reflection_ObjectAccess::getProperty($singleResult, $searchProperty);
			$output[] = array(
				'id' => $val,
				'label' => $val,
				'value' => $val
			);
		}
		return json_encode($output);
	}
}

?>