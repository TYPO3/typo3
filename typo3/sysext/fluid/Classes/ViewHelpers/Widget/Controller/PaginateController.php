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
class Tx_Fluid_ViewHelpers_Widget_Controller_PaginateController extends Tx_Fluid_Core_Widget_AbstractWidgetController {

	/**
	 * @var array
	 */
	protected $configuration = array('itemsPerPage' => 10, 'insertAbove' => FALSE, 'insertBelow' => TRUE);

	/**
	 * @var integer
	 */
	protected $currentPage = 1;

	/**
	 * @var integer
	 */
	protected $numberOfPages = 1;

	/**
	 * @param integer $currentPage
	 * @return void
	 */
	public function indexAction($currentPage = 1) {
		$objects = $this->widgetConfiguration['objects'];
		$as = $this->widgetConfiguration['as'];
		$this->configuration = t3lib_div::array_merge_recursive_overrule($this->configuration, $this->widgetConfiguration['configuration'], TRUE);
		$itemsPerPage = (integer)$this->configuration['itemsPerPage'];

			// calculate number of pages and set current page
		$this->numberOfPages = ceil(count($objects) / $itemsPerPage);
		if ($currentPage < 1) {
			$this->currentPage = 1;
		} elseif ($currentPage > $this->numberOfPages) {
			$this->currentPage = $this->numberOfPages;
		} else {
			$this->currentPage = $currentPage;
		}

			// modify query
		$query = $objects->getQuery();
		$query->setLimit($itemsPerPage);
		if ($currentPage > 1) {
			$query->setOffset($itemsPerPage * ($currentPage - 1));
		}
		$modifiedObjects = $query->execute();

		$this->view->assign('contentArguments', array(
			$as => $modifiedObjects
		));
		$this->view->assign('configuration', $this->configuration);

		$page = array(
			'list' => $this->buildPages(),
			'current' => $this->currentPage
		);

		if ($this->currentPage < $this->numberOfPages) {
			$page['next'] = $this->currentPage + 1;
		}
		if ($this->currentPage > 1) {
			$page['previous'] = $this->currentPage - 1;
		}

		$this->view->assign('page', $page);
	}

	/**
	 * @return array
	 */
	protected function buildPages() {
		$pages = array();
		for ($i = 1; $i <= $this->numberOfPages; $i++) {
			$pages[] = array('number' => $i, 'isCurrent' => ($i === $this->currentPage));
		}
		return $pages;
	}
}

?>