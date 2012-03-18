<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Franz Geiger <mail@fx-g.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Main view layer for plain form data.
 *
 * @author Franz Geiger <mail@fx-g.de>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_View_Database_Csv extends tx_form_View_Database_Csv_Element_Container {

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct(tx_form_Domain_Model_Form $model, $delimiter) {
		parent::__construct($model, $delimiter);
	}

	/**
	 * Render submitted values and field names as a csv like text
	 *
	 * @return string Plain content containing the submitted values
	 */
	public function render() {
		$content = $this->renderChildren($this->model->getElements(), $this->delimiter);

		return $content;
	}
}
?>
