<?php
namespace TYPO3\CMS\Form\View\Mail\Plain\Element;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
		$this->spaces = (int)$spaces;
	}

}
