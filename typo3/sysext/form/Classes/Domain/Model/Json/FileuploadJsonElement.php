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
 * JSON Fileupload
 *
 * @author Peter Beernink <p.beernink@drecomm.nl>
 */
class FileuploadJsonElement extends \TYPO3\CMS\Form\Domain\Model\Json\AbstractJsonElement {

	/**
	 * The ExtJS xtype of the element
	 *
	 * @var string
	 */
	public $xtype = 'typo3-form-wizard-elements-basic-fileupload';

	/**
	 * The configuration array for the xtype
	 *
	 * @var array
	 */
	public $configuration = array(
		'attributes' => array(
			'type' => 'file'
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
		'name',
		'size',
		'style',
		'tabindex',
		'title',
		'type'
	);

}
