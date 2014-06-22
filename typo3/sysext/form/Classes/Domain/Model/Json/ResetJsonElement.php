<?php
namespace TYPO3\CMS\Form\Domain\Model\Json;

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
 * JSON reset
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class ResetJsonElement extends \TYPO3\CMS\Form\Domain\Model\Json\AbstractJsonElement {

	/**
	 * The ExtJS xtype of the element
	 *
	 * @var string
	 */
	public $xtype = 'typo3-form-wizard-elements-basic-reset';

	/**
	 * The configuration array for the xtype
	 *
	 * @var array
	 */
	public $configuration = array(
		'attributes' => array(
			'type' => 'reset'
		),
		'filters' => array(),
		'label' => array(
			'value' => ''
		),
		'layout' => 'front',
		'validation' => array()
	);

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'accesskey',
		'alt',
		'class',
		'dir',
		'disabled',
		'id',
		'lang',
		'name:',
		'style',
		'tabindex',
		'title',
		'type',
		'value'
	);

}
