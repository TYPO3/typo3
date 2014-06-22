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
 * Fieldset model object
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class FieldsetElement extends \TYPO3\CMS\Form\Domain\Model\Element\ContainerElement {

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'class' => '',
		'dir' => '',
		'id' => '',
		'lang' => '',
		'style' => ''
	);

}
