<?php
namespace TYPO3\CMS\Form\View\Mail\Plain\Element;

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
 * The element abstract
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
abstract class AbstractElementView {

	/**
	 * @var \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement
	 */
	protected $model;

	/**
	 * @var integer
	 */
	protected $spaces;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $model
	 * @param integer $spaces
	 */
	public function __construct(\TYPO3\CMS\Form\Domain\Model\Element\AbstractElement $model, $spaces) {
		$this->model = $model;
		$this->spaces = (int) $spaces;
	}

}

?>