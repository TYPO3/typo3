<?php
namespace TYPO3\CMS\Form\Domain\Model\Json;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Patrick Broens (patrick@patrickbroens.nl)
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
 * JSON container abstract
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class ContainerJsonElement extends \TYPO3\CMS\Form\Domain\Model\Json\AbstractJsonElement {

	/**
	 * The items within this container
	 *
	 * @var array
	 */
	public $elementContainer = array(
		'hasDragAndDrop' => TRUE,
		'items' => array()
	);

	/**
	 * Add an element to this container
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Json\AbstractJsonElement $element The element to add
	 * @return void
	 */
	public function addElement(\TYPO3\CMS\Form\Domain\Model\Json\AbstractJsonElement $element) {
		$this->elementContainer['items'][] = $element;
	}

}

?>