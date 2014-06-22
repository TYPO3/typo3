<?php
namespace TYPO3\CMS\Form\Domain\Model\Element;

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
 * Image button model object
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class ImagebuttonElement extends \TYPO3\CMS\Form\Domain\Model\Element\AbstractElement {

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'accesskey' => '',
		'alt' => '',
		'class' => '',
		'dir' => '',
		'disabled' => '',
		'id' => '',
		'lang' => '',
		'name' => '',
		'src' => '',
		'style' => '',
		'tabindex' => '',
		'title' => '',
		'type' => 'image',
		'value' => ''
	);

	/**
	 * Mandatory attributes for this object
	 *
	 * @var array
	 */
	protected $mandatoryAttributes = array(
		'name',
		'id'
	);

}
