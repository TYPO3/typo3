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
 * JSON header
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class HeaderJsonElement extends \TYPO3\CMS\Form\Domain\Model\Json\AbstractJsonElement {

	/**
	 * The ExtJS xtype of the element
	 *
	 * @var string
	 */
	public $xtype = 'typo3-form-wizard-elements-content-header';

	/**
	 * The configuration array for the xtype
	 *
	 * @var array
	 */
	public $configuration = array(
		'attributes' => array(),
		'various' => array(
			'headingSize' => 'h1',
			'content' => ''
		)
	);

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'class',
		'dir',
		'id',
		'lang',
		'style',
		'title'
	);

	/**
	 * Set all the parameters for this object
	 *
	 * @param array $parameters Configuration array
	 * @return void
	 * @see \TYPO3\CMS\Form\Domain\Model\Json\AbstractJsonElement::setParameters()
	 */
	public function setParameters(array $parameters) {
		parent::setParameters($parameters);
		$this->setVarious($parameters);
	}

	/**
	 * Set the various properties for the element
	 *
	 * For this element this is the headingsize and the value
	 *
	 * @param array $parameters Configuration array
	 * @return void
	 */
	protected function setVarious(array $parameters) {
		if (isset($parameters['headingSize'])) {
			if (preg_match('#^h[1-5]$#', $parameters['headingSize'])) {
				$this->configuration['various']['headingSize'] = $parameters['headingSize'];
			}
		}
		if (isset($parameters['content'])) {
			$this->configuration['various']['content'] = $parameters['content'];
		}
	}

}

?>